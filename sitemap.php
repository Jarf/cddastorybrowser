<?php
include(dirname(__FILE__) . '/include/config.php');
include(dirname(__FILE__) . '/include/autoload.php');
$lmod = date('Y-m-d\TH:i:sP',filemtime(DIR_DATA));
$sitemap = substr(trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'), 0, -4);
$print = '';
$index = false;
$pages = array();
switch ($sitemap) {
	case 'sitemap':
	case 'sitemap_index':
		$index = true;
		$pages[] = array('loc' => SITE_ROOT . 'sitemap_categoryindex.xml', 'lastmod' => $lmod);
		$categories = new categories();
		$categories->indexListings();
		foreach($categories->categories as $category){
			if($category->storiesCount > 0){
				$pages[] = array('loc' => SITE_ROOT . 'sitemap_storyindex_' . $category->name . '.xml', 'lastmod' => $lmod);
			}
		}
		break;

	case 'sitemap_categoryindex':
		$pages[] = array('loc' => SITE_ROOT . 'story', 'lastmod' => $lmod);
		$categories = new categories();
		$categories->indexListings();
		foreach($categories->categories as $category){
			$pages[] = array('loc' => SITE_ROOT . 'story/' . $category->name, 'lastmod' => $lmod);
		}
		break;

	case preg_match('/^sitemap_storyindex_(.*)$/', $sitemap, $category) === 1:
		$categoryname = end($category);
		$category = new category();
		$category->getIdFromName($categoryname);
		if(isset($category->id) && !empty($category->id)){
			$stories = new stories();
			$storycount = $stories->countStories($category->id, null, true);
			$i = 1;
			while($i <= $storycount){
				$pages[] = array('loc' => SITE_ROOT . 'story/' . $category->name . '/' . $i, 'lastmod' => $lmod);
				$i++;
			}
		}
		break;
}
if(empty($pages)){
	display404();
	exit();
}
ob_clean();
header("Content-type: application/xml; charset=utf-8");
if($index === true): ?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach($pages as $page): ?>
	<sitemap>
		<?php foreach($page as $tag => $val): ?>
		<<?=$tag?>><?=$val?></<?=$tag?>>
		<?php endforeach; ?>
	</sitemap>
<?php endforeach; ?>
</sitemapindex>
<?php else: ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach($pages as $page): ?>
	<url>
		<?php foreach($page as $tag => $val): ?>
		<<?=$tag?>><?=$val?></<?=$tag?>>
		<?php endforeach; ?>
	</url>
<?php endforeach; ?>
</urlset>
<?php endif; ?>