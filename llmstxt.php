<?php
include(dirname(__FILE__) . '/include/config.php');
include(dirname(__FILE__) . '/include/autoload.php');
$categories = new categories();
$categories->indexListings();
header("Content-type: text/plain; charset=utf-8");
?>
# cddastory.jarfjam.co.uk

> CDDA Story Browser provides fans of the lore and story telling within Cataclysm: Dark Days Ahead a way of browsing them

## Home
- [CDDA Story Browser](<?=SITE_ROOT?>): A way to browse through the lore snippets found throughout CDDA

## Categories Index
- [CDDA Story Browser - Story Category Index](<?=SITE_ROOT?>story): An index of categories for the stories found in CDDA

## Stories Indexes
<?php foreach($categories->categories as $category): ?>
- [CDDA Story Browser - <?=$categories->humanReadable($category->name)?>](<?=SITE_ROOT?>story/<?=$category->name?>): An index of stories found in the "<?=$categories->humanReadable($category->name)?>" category
<?php endforeach; ?>