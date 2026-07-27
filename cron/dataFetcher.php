<?php
print 'Init...';
include(dirname(__DIR__) . '/include/config.php');
include(dirname(__DIR__) . '/include/autoload.php');
$zip = new ZipArchive;
// Init DB class
$db = new db();

$masterpath = DIR_DATA . 'master.zip';
print 'Done' . PHP_EOL . 'Download master.zip...';
$fp = fopen($masterpath, 'w+');
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://github.com/CleverRaven/Cataclysm-DDA/archive/refs/heads/master.zip');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_FILE, $fp);
curl_exec($ch);
curl_close($ch);
print 'Done' . PHP_EOL . 'Extract snippets JSON';

// === STORIES ===
// Extract story snippets
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
print 'Done' . PHP_EOL . 'Retrieve existing categories...';
// Get already set categories
$categorymap = array();
$items = new categories();
$items = $items->selectAll();
foreach($items as $item){
	$categorymap[$item->id] = $item->name;
}
print 'Done' . PHP_EOL . 'Parse snippets JSON files';
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
print 'Done' . PHP_EOL . 'Populate stories';
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
print 'Done' . PHP_EOL . 'Populate Styles';
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
		if(strcasecmp($categoryname, $stylename) === 0){
			matchStoryStyle($stylemap, $bind, $vals, $i, $categoryid, $categoryname, $styleid, $stylename);
		}
	}
}
foreach($categorymap as $categoryid => $categoryname){
	foreach($styles as $styleid => $stylename){
		if(preg_match('/^' . $stylename . '($|_)/i', $categoryname) === 1 && !isset($stylemap[$categoryname])){
			matchStoryStyle($stylemap, $bind, $vals, $i, $categoryid, $categoryname, $styleid, $stylename);
		}
	}
}
foreach($categorymap as $categoryid => $categoryname){
	foreach($styles as $styleid => $stylename){
		if(preg_match('/(^|_)' . $stylename . '$/i', $categoryname) === 1 && !isset($stylemap[$categoryname])){
			matchStoryStyle($stylemap, $bind, $vals, $i, $categoryid, $categoryname, $styleid, $stylename);
		}
	}
}
foreach($categorymap as $categoryid => $categoryname){
	foreach($styles as $styleid => $stylename){
		if(preg_match('/(^|_)' . $stylename . '($|_)/i', $categoryname) === 1 && !isset($stylemap[$categoryname])){
			matchStoryStyle($stylemap, $bind, $vals, $i, $categoryid, $categoryname, $styleid, $stylename);
		}
	}
}
foreach($categorymap as $categoryid => $categoryname){
	foreach($styles as $styleid => $stylename){
		if(stripos($categoryname, $stylename) !== false){
			matchStoryStyle($stylemap, $bind, $vals, $i, $categoryid, $categoryname, $styleid, $stylename);
		}
	}
}
// Additonal style checks
foreach($categorymap as $categoryid => $categoryname){
	if(!isset($stylemap[$categoryname]) && substr($categoryname, 0, 1) !== '<' && substr($categoryname, -1, 1) !== '>'){
		foreach($styles as $styleid => $stylename){
			if(
				($stylename === 'scrf' && preg_match('/^sr\d+_mess$/', $categoryname) === 1) ||
				($stylename === 'organs' && (str_starts_with($categoryname, 'harvest') || str_contains($categoryname, 'dissection') || str_contains($categoryname, 'butchery') || str_contains($categoryname, 'tainted'))) ||
				($stylename === 'starving' && (str_contains($categoryname, 'emaciated') || str_contains($categoryname, 'malnutrition') || str_contains($categoryname, 'low_cal'))) ||
				($stylename === 'lab_notes' && str_contains($categoryname, 't-substrate')) ||
				($stylename === 'addiction' && str_starts_with($categoryname, 'addict'))
			){
				matchStoryStyle($stylemap, $bind, $vals, $i, $categoryid, $categoryname, $styleid, $stylename);
			}
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

// === NPCs/Dialogue ===
// Extract npc data
print 'Done' . PHP_EOL . 'Extract NPC JSON';
if($zip->open(DIR_DATA . 'master.zip') === true){
	for($i = 0; $i < $zip->numFiles; $i++){
		print '.';
		$filename = $zip->getNameIndex($i);
		if(preg_match('/^.*\/data\/json\/npcs\/(.*\.json)$/', $filename, $match) === 1){
			$dir = dirname($match[1]) . '/';
			if($dir !== '.' && !is_dir(DIR_DATA . $dir)){
				mkdir(DIR_DATA . $dir, 0775, true);
			}
			file_put_contents(DIR_DATA . $match[1], $zip->getFromIndex($i));
		}
	}
	$zip->close();
}

print 'Done' . PHP_EOL . 'Retrieve existing factions...';
// Get already set factions
$factionmap = array();
$factions = new factions();
$factions = $factions->selectAll();
foreach($factions as $faction){
	$factionmap[$faction->id] = $faction->code;
}

print 'Done' . PHP_EOL . 'Parse Factions JSON file';
$factions = array();
if(file_exists(DIR_DATA . 'factions.json')){
	$json = file_get_contents(DIR_DATA . 'factions.json');
	$json = @json_decode($json);
	if($json !== null){
		foreach($json as &$row){
			print '.';
			if(isset($row->type) && $row->type === 'faction' && isset($row->id) && isset($row->name)){
				$factioncode = $row->id;
				if(!in_array($row->id, $factionmap)){
					$faction = new faction();
					$faction->name = $row->name;
					$faction->code = $factioncode;
					$faction->description = isset($row->description) ? $row->description : null;
					$faction->saveChanges();
					$factionid = $faction->id;
				}else{
					$factionid = array_search($factioncode, $factionmap, true);
				}

				if(!isset($factionmap[$factionid])){
					$factionmap[$factionid] = $factioncode;
				}
			}
		}
	}
}

print 'Done' . PHP_EOL . 'Parse NPC JSON files';
$npcinsert = array();
$dir = new RecursiveDirectoryIterator(DIR_DATA);
$iterator = new RecursiveIteratorIterator($dir);
foreach($iterator as $fileinfo){
	if($fileinfo->isFile() && $fileinfo->getExtension() === 'json'){
		$filename = $fileinfo->getPathname();
		$json = file_get_contents($filename);
		$json = @json_decode($json);
		if($json !== null){
			foreach($json as &$row){
				if(isset($row->type) && $row->type === 'npc' && isset($row->id)){
					print '.';
					$faction = isset($row->faction) ? $row->faction : 'no_faction';
					$factionid = array_search($faction, $factionmap, true);
					$code = $row->id;
					$name = null;
					if(isset($row->name_unique) && !empty($row->name_unique)){
						$name = $row->name_unique;
					}elseif(isset($row->name_suffix) && !empty($row->name_suffix)){
						$name = $row->name_suffix;
					}
					$desckey = '//';
					$description = isset($row->$desckey) ? $row->$desckey : null;
					$npcinsert[] = array($code, $name, $description, $factionid);
				}
			}
		}
	}
}
$npcinserts = array_chunk($npcinsert, 1000);
print 'Done' . PHP_EOL . 'Populate NPCs...';
// Pre-import clean up
$db->query('DELETE FROM npcs');
$db->execute();
$db->query('ALTER TABLE npcs AUTO_INCREMENT = 1');
$db->execute();
// NPC inserts
foreach($npcinserts as $npcinsert){
	$vals = $bind = array();
	$sql = 'INSERT INTO npcs (code, name, description, faction) VALUES ';
	foreach($npcinsert as $nkey => $npc){
		$vals[] = '(:code' . $nkey . ', :name' . $nkey . ', :desc' . $nkey . ', :faction' . $nkey . ')';
		$bind['code' . $nkey] = $npc[0];
		$bind['name' . $nkey] = $npc[1];
		$bind['desc' . $nkey] = $npc[2];
		$bind['faction' . $nkey] = $npc[3];
	}
	$sql .= implode(',', $vals);
	$db->query($sql);
	foreach($bind as $bkey => $bval){
		$db->bind($bkey, $bval);
	}
	$db->execute();
	print '.';
}
print 'Done' . PHP_EOL . 'Parse NPC Dialogue';
// Fetch NPCs
$npcs = new npcs();
$npcs->indexListings();
// Parse dialogue
$dialogueinsert = array();
$dir = new RecursiveDirectoryIterator(DIR_DATA);
$iterator = new RecursiveIteratorIterator($dir);
foreach($iterator as $fileinfo){
	$npccode = $npcid = null;
	if($fileinfo->isFile()){
		$filename = $fileinfo->getPathname();
		if($fileinfo->getExtension() === 'json'){
			$json = file_get_contents($filename);
			$json = @json_decode($json);
			if($json !== null){
				foreach($json as &$row){
					if(isset($row->type) && $row->type === 'npc' && isset($row->id)){
						print '.';
						$npccode = $row->id;
						foreach($npcs->npcs as $npc){
							if($npc->code === $npccode){
								$npcid = $npc->id;
								break;
							}
						}
					}
					if(!empty($npcid) && isset($row->type) && $row->type === 'talk_topic' && isset($row->id)){
						print '.';
						$dialoguecode = $row->id;
						if(is_array($dialoguecode)){
							$dialoguecode = current($dialoguecode);
						}
						$dialogue = parseDialogues($row);
						foreach($dialogue as $entry){
							if(!empty(trim($entry))){
								$dialogueinsert[] = array($dialoguecode, $entry, $npcid);
							}
						}
					}
				}
			}
			unlink($filename);
		}
	}
}
$dialogueinserts = array_chunk($dialogueinsert, 1000);
// Clean up directories
$directories = array();
foreach($iterator as $fileinfo){
	if(!$fileinfo->isFile()){
		$dir = $fileinfo->getPathname();
		if($dir !== DIR_DATA && str_ends_with($dir, '..')){
			$directories[] = substr($dir, 0, -2);
		}
	}
}
usort($directories, function($a, $b){
	return substr_count($a, DIRECTORY_SEPARATOR) < substr_count($b, DIRECTORY_SEPARATOR);
});
foreach($directories as $dir){
	if($dir !== DIR_DATA){
		rmdir($dir);
	}
}
print 'Done' . PHP_EOL . 'Populate dialogue';
// Pre-import clean up
$db->query('DELETE FROM dialogue');
$db->execute();
$db->query('ALTER TABLE dialogue AUTO_INCREMENT = 1');
$db->execute();
// Dialogue inserts
foreach($dialogueinserts as $dialogueinsert){
	$vals = $bind = array();
	$sql = 'INSERT INTO dialogue (code, dialogue, npc) VALUES ';
	foreach($dialogueinsert as $dkey => $dialogue){
		$vals[] = '(:code' . $dkey . ', :dialogue' . $dkey . ', :npc' . $dkey . ')';
		$bind['code'. $dkey] = $dialogue[0];
		$bind['dialogue'. $dkey] = $dialogue[1];
		$bind['npc'. $dkey] = $dialogue[2];
	}
	$sql .= implode(',', $vals);
	$db->query($sql);
	foreach($bind as $bkey => $bval){
		$db->bind($bkey, $bval);
	}
	$db->execute();
	print '.';
}

print 'Done' . PHP_EOL . 'Cleanup master.zip...';
// Clean up zip
unlink($masterpath);

print 'Done' . PHP_EOL . 'Setting last import date...';
$db->query('UPDATE import SET lastimport = NOW()');
$db->execute();
print 'Done' . PHP_EOL;

print 'Unmapped story categories:' . PHP_EOL;
foreach($categorymap as $categoryid => $categoryname){
	if(!isset($stylemap[$categoryname]) && substr($categoryname, 0, 1) !== '<' && substr($categoryname, -1, 1) !== '>'){
		print $categoryname . PHP_EOL;
	}
}

function parseStories(object &$row){
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

function parseDialogues(object &$row){
	$dialogues = array();
	$dialoguecode = $row->id;
	if(isset($row->dynamic_line)){
		if(is_string($row->dynamic_line)){
			$dialogues[] = $row->dynamic_line;
		}else{
			$iterator = new RecursiveArrayIterator($row->dynamic_line);
			iterator_apply($iterator, 'traverseDialogue', array($iterator, &$dialogues));
		}
	}
	return $dialogues;
}

function traverseDialogue(Iterator $iterator, array &$dialogues){
	$return = null;
	$keyignore = array('math', 'compare_string', 'relevant_genders', 'u_has_trait', 'u_has_mission', 'npc_has_trait', 'u_has_any_trait', 'npc_has_effect');
	while($iterator->valid()){
		if(!in_array($iterator->key(), $keyignore)){
			if($iterator->hasChildren()){
				traverseDialogue($iterator->getChildren(), $dialogues);
			}elseif(is_string($iterator->current()) && $iterator->current() !== '-' && $iterator->current() !== '...'){
				$dialogues[] = $iterator->current();
			}
		}
		$iterator->next();
	}
}

function matchStoryStyle(array &$stylemap, array &$bind, array &$vals, int &$i, int $categoryid, string $categoryname, int $styleid, string $stylename){
	if(!isset($stylemap[$categoryname])){
		$bind['category' . $i] = $categoryid;
		$bind['style' . $i] = $styleid;
		$vals[] = '(:category' . $i . ', :style' . $i . ')';
		$i++;
		$stylemap[$categoryname] = $stylename;
	}
}
?>