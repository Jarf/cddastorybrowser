<?php
print 'Init...';
include(dirname(__DIR__) . '/include/config.php');
include(dirname(__DIR__) . '/include/autoload.php');
print 'Done' . PHP_EOL . 'Download master.zip...';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://github.com/CleverRaven/Cataclysm-DDA/archive/refs/heads/master.zip');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$response = curl_exec($ch);
file_put_contents(DIR_DATA . 'master.zip', $response);
print 'Done' . PHP_EOL . 'Extract JSON snippets';
// Extract story snippets
$zip = new ZipArchive;
if($zip->open(DIR_DATA . 'master.zip') === true){
	for($i = 0; $i < $zip->numFiles; $i++){
		print '.';
		$filename = $zip->getNameIndex($i);
		if(preg_match('/^.*\/data\/json\/snippets\/.*\.json$/', $filename) === 1){
			file_put_contents(DIR_DATA . basename($filename), $zip->getFromIndex($i));
		}
	}
	$zip->close();
}
print 'Done' . PHP_EOL . 'Cleanup master.zip...';
// Clean up zip
unlink(DIR_DATA . 'master.zip');
print 'Done' . PHP_EOL . 'Cleanup stories...';
// Init DB class
$db = new db();

// Pre-import clean up
$db->query('DELETE FROM stories');
$db->execute();
$db->query('ALTER TABLE stories AUTO_INCREMENT = 1');
$db->execute();
print 'Done' . PHP_EOL . 'Retrieve existing categories...';
// Get already set categories
$categorymap = array();
$items = new categories();
$items = $items->selectAll();
foreach($items as $item){
	$categorymap[$item->id] = $item->name;
}
print 'Done' . PHP_EOL . 'Parse JSON files';
// Parse JSON files
$dir = new DirectoryIterator(DIR_DATA);
foreach($dir as $fileinfo){
	print '.';
	if(!$fileinfo->isDot() && $fileinfo->getExtension() === 'json'){
		$json = file_get_contents(DIR_DATA . $fileinfo->getFilename());
		$json = @json_decode($json);
		if($json !== null){
			foreach($json as &$row){
				$categoryid = null;
				if(isset($row->category)){
					// Identify if descriptor or story
					$descriptor = preg_match('/^\<.*\>$/', $row->category);
					if(!in_array($row->category, $categorymap)){
						$category = new category();
						$category->name = $row->category;
						$category->descriptor = $descriptor;
						$category->saveChanges();
						$categoryid = $category->id;
					}else{
						$categoryid = array_search($row->category, $categorymap, true);
					}

					if(!isset($categorymap[$categoryid])){
						$categorymap[$categoryid] = $row->category;
					}

					// Parse stories
					$stories = parseStories($row);
					foreach($stories as $entry){
						$story = new story();
						$story->category = $categoryid;
						$story->story = $entry;
						if(!empty(trim($entry))){
							$story->saveChanges();
						}
					}
				}
			}
		}
		// Clean up JSON file
		unlink(DIR_DATA . $fileinfo->getFilename());
	}
}
print 'Done' . PHP_EOL . 'Assign styles...';
// Assign styles
$db->query('DELETE FROM categoriesStyles');
$db->execute();
$styles = array();
$db->query('SELECT id, name FROM styles');
$db->execute();
if($db->rowCount() > 0){
	$rs = $db->fetchAll();
	foreach($rs as $row){
		$styles[$row->id] = $row->name;
	}
}
$bind = $vals = array();
$i = 0;
foreach($categorymap as $categoryid => $categoryname){
	foreach($styles as $styleid => $stylename){
		if(strpos($categoryname, $stylename) !== false){
			$bind['category' . $i] = $categoryid;
			$bind['style' . $i] = $styleid;
			$vals[] = '(:category' . $i . ', :style' . $i . ')';
			$i++;
		}
	}
}

if(!empty($vals)){
	$sql = 'INSERT INTO categoriesStyles (categoriesid, stylesid) VALUES ' . implode(',', $vals);
	$db->query($sql);
	foreach($bind as $bkey => $bval){
		$db->bind($bkey, $bval);
	}
	$db->execute();
}
print 'Done' . PHP_EOL;

function parseStories(&$row){
	$stories = array();
	if(isset($row->text)){
		if(is_array($row->text)){
			foreach($row->text as $story){
				if(is_string($story)){
					$stories[] = $story;
				}elseif(is_object($story) && isset($story->text) && is_string($story->text)){
					$stories[] = $story->text;
				}elseif(is_object($story) && isset($story->str) && is_string($story->str)){
					$stories[] = $story->str;
				}elseif(is_object($story) && isset($story->text) && is_object($story->text) && isset($story->text->str) && is_string($story->text->str)){
					$stories[] = $story->text->str;
				}
			}
		}elseif(is_string($row->text)){
			$stories[] = $row->text;
		}
	}
	return $stories;
}
?>