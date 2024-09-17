<?php
// header('Content-Type: text/plain');
include(dirname(__FILE__) . '/include/config.php');
include(dirname(__FILE__) . '/include/autoload.php');

$urlpath = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$urlpath = explode('/', $urlpath);
$page = current($urlpath);

$output = $pagevars = array();
$pagevars['stylesheets'] = array('main');

switch ($page) {
	case '':
	default:
		$story = new story();
		$storyid = false;
		if(!empty($urlpath) && isset($urlpath[0]) && is_numeric($urlpath[0])){
			$storyid = $urlpath[0];
		}
		$story->loadStory($storyid);
		$pagevars['story'] = &$story;
		$template = 'story.twig';
		$pagevars['stylesheets'][] = 'story';
		break;
	
	case 'listing':
		$template = 'listing.twig';
		$pagevars['stylesheets'][] = 'story';
		break;
}



$loader = new \Twig\Loader\FilesystemLoader(array(DIR_TPL, DIR_TPL . 'include/'));
$twig = new \Twig\Environment($loader, array(
	'cache' => DIR_CACHE,
	'debug' => (bool) ISDEV
));
if(ISDEV){
	$twig->addExtension(new \Twig\Extension\DebugExtension());
}

$output[] = $twig->render('header.twig', $pagevars);
$output[] = $twig->render($template, $pagevars);
$output[] = $twig->render('footer.twig', $pagevars);

$output = implode('', $output);
print $output;

?>