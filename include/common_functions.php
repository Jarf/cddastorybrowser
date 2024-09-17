<?php
function getDirectoryFiles(string $path){
	$files = scandir($path);
	$return = array();
	if($files !== false){
		foreach($files as $file){
			if(is_file($path . $file)){
				$return[] = $path . $file;
			}
		}
	}
	return $return;
}
?>