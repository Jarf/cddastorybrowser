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

function display404(){
	// header("HTTP/1.0 404 Not Found");
	$layout = true;
	$loader = new \Twig\Loader\FilesystemLoader(array(DIR_TPL, DIR_TPL . 'include/'));
	$twig = new \Twig\Environment($loader, array(
		'cache' => DIR_CACHE,
		'debug' => ISDEV
	));
	$output = $pagevars = array();
	$pagevars['stylesheets'] = array(SITE_CSS . 'main.css', SITE_CSS . '404.css');
	$pagevars['javascripts'] = array();
	$pagevars['header'] = array(
		'title' => 'CDDA Story Browser - 404 Page Not Found',
		'description' => '404 Page Not Found'
	);
	$output[] = $twig->render('header.twig', $pagevars);
	$output[] = $twig->render('404.twig', $pagevars);
	$output[] = $twig->render('footer.twig', $pagevars);
	$output = implode('', $output);
	print $output;
	exit();
}
?>