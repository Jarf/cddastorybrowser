<?php
define('DIR_ROOT', dirname(__DIR__) . '/');
define('DIR_DATA', DIR_ROOT . 'data/');
define('DIR_INCLUDE', DIR_ROOT . 'include/');
define('DIR_CLASSES', DIR_ROOT . 'classes/');
define('DIR_CLASSES_ABSTRACT', DIR_CLASSES . 'abstract/');
define('DIR_VENDOR', DIR_ROOT . 'vendor/');
define('DIR_TPL', DIR_ROOT . 'tpl/');
define('DIR_CACHE', false);
define('DIR_CSS', DIR_ROOT . 'css/');
define('DIR_JS', DIR_ROOT . 'js/');
define('DIR_IMG', DIR_ROOT . 'img/');

define('SITE_CSS', '/css/');
define('SITE_JS', '/js/');
define('SITE_VENDOR', '/vendor/');
define('SITE_AJAX', '/ajax/');

define('DB_USER', 'cddastorybrowser');
define('DB_PASS', 'ywqe)EBJa#-Gp~@N6<[R?d');
define('DB_NAME', 'cddastorybrowser');
define('DB_HOST', 'localhost');
define('DB_TYPE', 'mysql');

$dev = 0;

if(isset($_SERVER['SERVER_NAME'])){
	$servername = $_SERVER['SERVER_NAME'];
}else{
	switch (gethostname()) {
		case 'localhost':
			$servername = 'cddastory.jarfjam.co.uk';
			break;
		
		default:
			$servername = 'cddastory.local';
			$dev = 1;
			break;
	}
}
$https = false;
if(isset($_SERVER['HTTPS'])){
	$https = true;
}elseif(isset($_SERVER['SERVER_PROTOCOL']) && stripos($_SERVER['SERVER_PROTOCOL'], 'https') === 0){
	$https = true;
}
define('SITE_ROOT', ($https ? 'https' : 'http') . '://' . $servername . '/');

define('ISDEV', $dev);
?>