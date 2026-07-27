<?php
// header('Content-Type: text/plain');
include(dirname(__FILE__) . '/include/config.php');
include(dirname(__FILE__) . '/include/autoload.php');

$urlpath = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$urlpath = explode('/', $urlpath);
$page = current($urlpath);

$output = $pagevars = array();
$pagevars['shareurl'] = urlencode(SITE_ROOT . substr($_SERVER['REQUEST_URI'],1));
$pagevars['stylesheets'] = array(SITE_CSS . 'main.css');
$pagevars['javascripts'] = array();
$story = new story();
$pagevars['randomstory'] = $story->getRandomStoryId();
$db = new db();
$pagevars['lastimport'] = null;
$pagevars['breadcrumb'] = array();
if(!empty($urlpath)){
	$url = substr(SITE_ROOT, 0, -1);
	foreach($urlpath as $pkey => $pval){
		if(!empty($pval)){
			$url .= '/' . $pval;
			$pagevars['breadcrumb'][$pkey] = array(
				'position' => $pkey + 1,
				'name' => $story->humanReadable($pval)
			);
			if($pkey !== (count($urlpath) - 1)){
				$pagevars['breadcrumb'][$pkey]['item'] = $url;
			}
		}
	}
}

$pagevars['header'] = array(
	'title' => 'CDDA Story Browser',
	'description' => 'A way to browse through the lore snippets found throughout CDDA'
);
$pagevars['dependencies'] = array(
	array(
		'type' => 'font',
		'path' => '/fonts/terminus.woff2'
	),
	array(
		'type' => 'image',
		'path' => SITE_ICO . 'favicon-16x16.png'
	)
);
unset($story);

switch ($page) {
	// Home page
	case '':
	default:
		$template = 'home.twig';
		$pagevars['stylesheets'][] = SITE_CSS . 'home.css';
		$pagevars['dependencies'][] = array(
			'type' => 'image',
			'path' => SITE_ICO . 'favicon-196x196.png'
		);
		$db->query('SELECT import.lastimport FROM import LIMIT 1');
		$db->execute();
		if($db->rowCount() === 1){
			$pagevars['lastimport'] = $db->fetch()->lastimport;
			$pagevars['lastimport'] = DateTime::createFromFormat('Y-m-d H:i:s', $pagevars['lastimport']);
			$pagevars['lastimport'] = $pagevars['lastimport']->format('F jS, Y');
		}
		break;

	// Story
	case (!empty($urlpath) && isset($urlpath[0]) && $urlpath[0] === 'story' && isset($urlpath[2])):
		$story = new story();
		$storyid = $categoryname = $categorystoryid = null;
		if(!empty($urlpath) && isset($urlpath[1]) && !is_numeric($urlpath[1]) && isset($urlpath[2]) && is_numeric($urlpath[2])){
			$categoryname = $urlpath[1];
			$categorystoryid = $urlpath[2];
			$storyid = $story->getStoryId($categoryname, $categorystoryid);
		}elseif(!empty($urlpath) && isset($urlpath[1]) && is_numeric($urlpath[1])){
			$storyurl = $story->getStoryUrl($urlpath[1]);
			if($storyurl === false){
				display404();
				exit();
			}else{
				header('Location: /story/' . $storyurl);
				exit();
			}
		}
		if(empty($storyid)){
			display404();
			exit();
		}
		$story->loadStory($storyid);
		if(!isset($story->story) || empty($story->story)){
			display404();
			exit();
		}
		$story->getNextPrevIds();
		$pagevars['story'] = &$story;
		$pagevars['categoryname'] = $story->humanReadable($story->categoryName);
		$categoryid = new category();
		$categoryid = $categoryid->getIdFromName($story->categoryName);
		$pagevars['storycount'] = new stories();
		$pagevars['storycount'] = $pagevars['storycount']->countStories($categoryid, null, true);
		$pagevars['storycount'] = '(' . $categorystoryid . '/' . $pagevars['storycount'] . ')';
		$pagevars['header']['title'] .= ' - ' . $story->humanReadable($story->categoryName) . ' ' . $pagevars['storycount'];
		$pagevars['header']['description'] = $story->getMetaDescription();
		$template = 'story.twig';
		$pagevars['stylesheets'][] = SITE_CSS . 'story.css';
		$pagevars['stylesheets'][] = SITE_CSS . 'stories/' . $story->style . '.css';
		$pagevars['dependencies'] = array_merge($pagevars['dependencies'], $pagevars['story']->getDependencies());
		break;
	
	// Story Indexes
	case 'index':
	case (!empty($urlpath) && isset($urlpath[0]) && $urlpath[0] === 'story' && !isset($urlpath[2])):
	case (!empty($urlpath) && isset($urlpath[0]) && $urlpath[0] === 'dialogue' && !isset($urlpath[3])):
		$pagevars['indextype'] = $urlpath[0];
		$pagevars['indexheading'] = 'Entry Category Index';
		$pagevars['entries'] = array();
		if($urlpath[0] === 'index'){
			$pagevars['header']['title'] .= ' - Category Index';
			$pagevars['header']['description'] = 'An index of the various categories the CDDA lore snippets/dialogue are sorted into';
			$categories = new categories();
			$categories->indexListings(null);
			$factions = new factions();
			$factions->indexListings(null);
			$pagevars['entries'] = array_merge($categories->categories, $factions->factions);
		}elseif($urlpath[0] === 'story'){
			$categoryid = null;
			if(!empty($urlpath) && isset($urlpath[1]) && !empty($urlpath[1]) && is_string($urlpath[1])){
				$category = new category();
				if(is_numeric($urlpath[1])){
					$category->loadCategory($urlpath[1]);
					if(isset($category->name)){
						header('Location: /story/' . $category->name);
						exit();
					}else{
						display404();
						exit();
					}
				}
				$categoryid = $urlpath[1];
				$categoryid = $category->getIdFromName($categoryid);
				if($categoryid === false){
					display404();
					exit();
				}
			}
			$pagevars['categoryid'] = $categoryid;
			$categories = new categories();
			$categories->indexListings(isset($category) && isset($category->id) ? $category->id : null);
			$pagevars['entries'] = $categories->categories;
			$pagevars['categoryname'] = !empty($pagevars['categoryid']) && isset($categories->categories) && isset($categories->categories[0]) && isset($categories->categories[0]->name) ? $categories->categories[0]->name : null;
			$pagevars['categorynamereadable'] = null;
			if(empty($categoryid)){
				$pagevars['header']['title'] .= ' - Story Category Index';
				$pagevars['header']['description'] = 'An index of the various categories the CDDA lore snippets are sorted into';
			}else{
				if(isset($categories->categories) && !empty($categories->categories) && isset($categories->categories[0]) && isset($categories->categories[0]->nameReadable)){
					$pagevars['header']['title'] .= ' - ' . $categories->categories[0]->nameReadable . ' Story Index';
					$pagevars['header']['description'] = 'An index of the stories found in the ' . $categories->categories[0]->nameReadable . ' category of CDDA lore snippets';
					$pagevars['categorynamereadable'] = $categories->categories[0]->nameReadable;
				}
				$stories = new stories();
				$pagevars['entries'] = $stories->loadStories($categoryid);
				$pagevars['entries'] = $pagevars['entries']['data'];
			}
		}elseif ($urlpath[0] === 'dialogue') {
			if(!isset($urlpath[2])){
				$pagevars['indextype'] = 'index';
			}
			if(isset($urlpath[2])){
				
			}elseif(isset($urlpath[1])){
				$faction = new faction();
				$factionid = $faction->getIdFromCode($urlpath[1], true);
				if(empty($factionid)){
					$factionid = null;
				}
				$npcs = new npcs();
				$npcs->indexListings($factionid);
				$pagevars['entries'] = $npcs->npcs;
				$pagevars['header']['title'] .= ' - ' . $faction->name . ' NPC Index';
				$pagevars['header']['description'] = 'An index of the NPCs with dialogue found in the ' . $faction->name . ' faction of CDDA';
				$pagevars['indexheading'] = $faction->name . ' NPC Index';
			}
		}

		$template = 'index.twig';
		$pagevars['stylesheets'][] = SITE_CSS . 'index.css';
		$pagevars['javascripts'][] = SITE_VENDOR . 'components/jquery/jquery.min.js';
		$pagevars['stylesheets'][] = SITE_VENDOR . 'datatables/datatables/media/css/jquery.dataTables.min.css';
		$pagevars['javascripts'][] = SITE_VENDOR . 'datatables/datatables/media/js/jquery.dataTables.min.js';
		$pagevars['javascripts'][] = SITE_JS . 'index.js';	
		$pagevars['categories'] = &$categories;
		$pagevars['dependencies'][] = array(
			'type' => 'image',
			'path' => SITE_VENDOR . 'datatables/datatables/media/images/sort_both.png'
		);
		$pagevars['dependencies'][] = array(
			'type' => 'image',
			'path' => SITE_VENDOR . 'datatables/datatables/media/images/sort_desc.png'
		);
		break;

	// Dialogue
	case (!empty($urlpath) && isset($urlpath[0]) && $urlpath[0] === 'dialogue' && isset($urlpath[3])):
		$dialogue = new dialogue();
		$dialogueid = $dialogue->getDialogueId($urlpath[1], $urlpath[2], $urlpath[3]);
		$dialogue->loadDialogue($dialogueid);
		var_dump($dialogue);
		exit();
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