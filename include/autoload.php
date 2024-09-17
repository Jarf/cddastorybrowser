<?php
// Vendor Autoload
// require_once(DIR_VENDOR . 'autoload.php');

// Generic Functions
require_once(DIR_INCLUDE . 'common_functions.php');

// Abstract Classes Autoload
$classes = getDirectoryFiles(DIR_CLASSES_ABSTRACT);
foreach($classes as $class){
	if(str_ends_with($class, '.php')){
		include_once($class);
	}
}

// Classes Autoload
$classes = getDirectoryFiles(DIR_CLASSES);
foreach($classes as $class){
	if(str_ends_with($class, '.php')){
		include_once($class);
	}
}
?>