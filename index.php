<?php
include(dirname(__FILE__) . '/include/config.php');
include(dirname(__FILE__) . '/include/autoload.php');

$story = new story();
$storyid = false;
if(isset($_GET) && isset($_GET['id']) && is_numeric($_GET['id'])){
	$storyid = intval($_GET['id']);
}
$story->loadStory($storyid);
?>