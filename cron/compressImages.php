<?php
print 'Init...';
include(dirname(__DIR__) . '/include/config.php');
include(dirname(__DIR__) . '/include/autoload.php');
use WebPConvert\WebPConvert;
print 'Done' . PHP_EOL . 'Fetching images without webp...';
$images = getDirectoryFiles(DIR_IMG);
$convert = $webps = array();
foreach($images as $ikey => $image){
	$extension = pathinfo($image, PATHINFO_EXTENSION);
	if($extension === 'jpg'){
		$webppath = getWebpPath($image);
		if(!file_exists($webppath)){
			$convert[$ikey] = $image;
			$webps[$ikey] = $webppath;
		}
	}
}
print 'Done' . PHP_EOL . 'Converting ' . count($convert) . ' images...';
$webpoptions = array(
	'jpeg' => array(
		'quality' => 'auto',
		'max-quality' => 75
	)
);
foreach($convert as $ikey => $image){
	WebPConvert::convert($image, $webps[$ikey], $webpoptions);
	print '.';
}
print 'Done' . PHP_EOL;


function getWebpPath(string $filepath){
	$webppath = pathinfo($filepath);
	$webppath = $webppath['dirname'] . '/' . $webppath['filename'] . '.webp';
	return $webppath;
}
?>