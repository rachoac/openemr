<?php
require_once('../api/RepricingAPI.php');

$searchTerm = $_GET['term'];

$repricingAPI = new RepricingAPI();
$codes = $repricingAPI->searchServiceCodes($searchTerm);
$results = [];

foreach( $codes as $result ) {
    $json_row = array();
    $json_row['id'] = $result->id;
    $json_row['value'] = $result->code;
    $json_row['label'] = $result->codeType . ' ' . $result->code . ': ' . $result->text;
    array_push( $results, $json_row );
}

header('Content-type: application/json');
echo json_encode( $results );

?>