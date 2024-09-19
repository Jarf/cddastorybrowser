<?php
require_once(dirname(__DIR__) . '/include/config.php');
require_once(dirname(__DIR__) . '/include/autoload.php');
$categoryid = isset($_GET['category']) ? intval($_GET['category']) : null;
$limit = isset($_GET['length']) ? intval($_GET['length']) : 10;
$pageindex = isset($_GET['start']) ? intval($_GET['start']) : 0;
if(!empty($pageindex)){
	$pageindex = $pageindex / $limit;
}
$search = null;
if(isset($_GET['search']) && isset($_GET['search']['value'])){
	$search = $_GET['search']['value'];
}
if(!empty($_GET['order'])){
	$order = $_GET['order'];
}

$stories = new stories();
$response = $stories->loadStories($categoryid, $limit, $pageindex, $search);
$response['data'] = array_map(function($data){
	return array_values((array) $data);
}, $response['data']);
$response['draw'] = intval($_GET['draw']);
header('Content-type: application/json; charset=utf-8');
$json = json_encode($response);
print $json;
?>