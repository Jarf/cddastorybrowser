<?php
// header('Content-Type: text/plain');
include(dirname(__FILE__) . '/include/config.php');
include(dirname(__FILE__) . '/include/autoload.php');

$urlpath = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$urlpath = explode('/', $urlpath);
$page = current($urlpath);

$output = $pagevars = array();
$pagevars['stylesheets'] = array(SITE_CSS . 'main.css');
$pagevars['javascripts'] = array();
$story = new story();
$pagevars['randomstory'] = $story->getRandomStoryId();
unset($story);

switch ($page) {
	default:
		$template = 'home.twig';
		$pagevars['stylesheets'][] = SITE_CSS . 'home.css';
		break;

	case 'story':
		$story = new story();
		$storyid = null;
		if(!empty($urlpath) && isset($urlpath[1]) && is_numeric($urlpath[1])){
			$storyid = $urlpath[1];
		}
		$story->loadStory($storyid);
		$story->getNextPrevIds();
		$pagevars['story'] = &$story;
		$template = 'story.twig';
		$pagevars['stylesheets'][] = SITE_CSS . 'story.css';
		$pagevars['stylesheets'][] = SITE_CSS . 'stories/' . $story->style . '.css';
		break;
	
	case 'index':
		$categoryid = null;
		if(!empty($urlpath) && isset($urlpath[1]) && is_numeric($urlpath[1])){
			$categoryid = $urlpath[1];
		}
		$pagevars['categoryid'] = $categoryid;
		$template = 'index.twig';
		$pagevars['stylesheets'][] = SITE_CSS . 'index.css';
		$pagevars['javascripts'][] = SITE_VENDOR . 'components/jquery/jquery.min.js';
		$pagevars['stylesheets'][] = SITE_VENDOR . 'datatables/datatables/media/css/jquery.dataTables.min.css';
		$pagevars['javascripts'][] = SITE_VENDOR . 'datatables/datatables/media/js/jquery.dataTables.min.js';
		$pagevars['javascripts'][] = SITE_JS . 'index.js';
		$categories = new categories();
		$categories->indexListings($categoryid);
		$pagevars['categories'] = &$categories;
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