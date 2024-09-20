<?php
print 'Init...';
include(dirname(__DIR__) . '/include/config.php');
include(dirname(__DIR__) . '/include/autoload.php');
// Init DB class
$db = new db();
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
		if(preg_match('/^.*\/data\/json\/snippets\/.*\.json$/', $filename) === 1 || preg_match('/^.*\/data\/names\/en\.json$/', $filename) === 1){
			file_put_contents(DIR_DATA . basename($filename), $zip->getFromIndex($i));
		}
	}
	$zip->close();
}
print 'Done' . PHP_EOL . 'Cleanup master.zip...';
// Clean up zip
unlink(DIR_DATA . 'master.zip');
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
$storyinsert = array();
$dir = new DirectoryIterator(DIR_DATA);
foreach($dir as $fileinfo){
	print '.';
	if(!$fileinfo->isDot() && $fileinfo->getExtension() === 'json'){
		$filename = $fileinfo->getFilename();
		$json = file_get_contents(DIR_DATA . $filename);
		$json = @json_decode($json);
		if($json !== null){
			if($filename === 'en.json'){
				foreach($json as &$row){
					$descriptor = 1;
					if(isset($row->usage)){
						$categoryname = $row->usage;
						if(isset($row->gender)){
							$categoryname = $row->gender . '_' . $row->usage . '_name';
						}elseif($row->usage !== 'city'){
							$categoryname .= '_name';
						}
						$categoryname = '<' . $categoryname . '>';
						if(!in_array($categoryname, $categorymap)){
							$category = new category();
							$category->name = $categoryname;
							$category->descriptor = $descriptor;
							$category->saveChanges();
							$categoryid = $category->id;
						}else{
							$categoryid = array_search($categoryname, $categorymap, true);
						}

						if(!isset($categorymap[$categoryid])){
							$categorymap[$categoryid] = $categoryname;
						}

						if(!empty($row->name)){
							foreach($row->name as $name){
								if(!empty(trim($name))){
									$storyinsert[] = array($categoryid, $name);
								}
							}
						}
					}
				}
			}else{
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
							if(!empty(trim($entry))){
								$storyinsert[] = array($categoryid, $entry);
							}
						}
					}
				}
			}
		}
		// Clean up JSON file
		unlink(DIR_DATA . $fileinfo->getFilename());
	}
}
$storyinserts = array_chunk($storyinsert, 1000);
print 'Done' . PHP_EOL . 'Update database...';
// Pre-import clean up
$db->query('DELETE FROM stories');
$db->execute();
$db->query('ALTER TABLE stories AUTO_INCREMENT = 1');
$db->execute();
// Story inserts
foreach($storyinserts as $storyinsert){
	$vals = $bind = array();
	$sql = 'INSERT INTO stories (category, story) VALUES ';
	foreach($storyinsert as $skey => $story){
		$vals[] = '(:cat' . $skey . ', :story' . $skey . ')';
		$bind['cat' . $skey] = $story[0];
		$bind['story' . $skey] = $story[1];
	}
	$sql .= implode(',', $vals);
	$db->query($sql);
	foreach($bind as $bkey => $bval){
		$db->bind($bkey, $bval);
	}
	$db->execute();
	print '.';
}
print 'Done' . PHP_EOL . 'Populate Styles...';
$dir = new DirectoryIterator(DIR_CSS . 'stories/');
$bind = $vals = array();
$i = 0;
foreach($dir as $fileinfo){
	print '.';
	if(!$fileinfo->isDot() && $fileinfo->getExtension() === 'css' && $fileinfo->getFilename() !== 'default.css'){
		$bind['style' . $i] = $fileinfo->getBasename('.css');
		$vals[] = '(:style' . $i . ')';
		$i++;
	}
}
$db->query('DELETE FROM styles');
$db->execute();
$db->query('ALTER TABLE styles AUTO_INCREMENT = 1');
$db->execute();
if(!empty($vals)){
	$sql = 'INSERT INTO styles (name) VALUES ' . implode(',', $vals);
	$db->query($sql);
	foreach($bind as $bkey => $bval){
		$db->bind($bkey, $bval);
	}
	$db->execute();
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
$stylemap = array();
foreach($categorymap as $categoryid => $categoryname){
	foreach($styles as $styleid => $stylename){
		if($categoryname === $stylename){
			$bind['category' . $i] = $categoryid;
			$bind['style' . $i] = $styleid;
			$vals[] = '(:category' . $i . ', :style' . $i . ')';
			$i++;
			$stylemap[$categoryname] = $stylename;
		}
	}
}
foreach($categorymap as $categoryid => $categoryname){
	foreach($styles as $styleid => $stylename){
		if(strpos($categoryname, $stylename) !== false && !isset($stylemap[$categoryname])){
			$bind['category' . $i] = $categoryid;
			$bind['style' . $i] = $styleid;
			$vals[] = '(:category' . $i . ', :style' . $i . ')';
			$i++;
			$stylemap[$categoryname] = $stylename;
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