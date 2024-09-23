<?php
include(dirname(__FILE__) . '/include/config.php');
include(dirname(__FILE__) . '/include/autoload.php');
$lmod = date('Y-m-d\TH:i:sP',filemtime(DIR_DATA));
$idxlmod = date('Y-m-d\TH:i:sP',filemtime(DIR_TPL . 'home.twig'));
$pages = array(
	array(
		'loc' => SITE_ROOT,
		'lastmod' => $idxlmod
	),
	array(
		'loc' => SITE_ROOT . 'index',
		'lastmod' => $lmod
	)
);
$categories = new categories();
$stories = new stories();
$categories->indexListings();
foreach($categories->categories as $category){
	$pages[] = array(
		'loc' => SITE_ROOT . 'index/' . $category->id,
		'lastmod' => $lmod
	);
	$stories->loadStories($category->id);
	foreach($stories->stories as $story){
		$pages[] = array(
			'loc' => SITE_ROOT . 'story/' . $story->id,
			'lastmod' => $lmod
		);
	}
}
header("Content-type: application/xml; charset=utf-8");
?>
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
	<?php foreach($pages as $page): ?>
		<url>
			<loc><?=$page['loc']?></loc>
			<lastmod><?=$page['lastmod']?></lastmod>
		</url>
	<?php endforeach; ?>
</urlset>