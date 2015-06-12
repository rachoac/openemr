<?php
require_once('../api/RepricingAPI.php');

$searchTerm = $_GET['term'];

$repricingAPI = new RepricingAPI();
$codes = $repricingAPI->searchProviders($searchTerm);
$results = [];

foreach( $codes as $result ) {
    $json_row = array();
    $json_row['id'] = $result->id;
    $json_row['value'] = $result->name;
    $json_row['label'] = $result->name;
    array_push( $results, $json_row );
}

header('Content-type: application/json');
echo json_encode( $results );

?>