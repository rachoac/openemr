<?php
require_once('../api/RepricingAPI.php');

$repricingAPI = new RepricingAPI();
$claimTypes = $repricingAPI->getClaimTypes();
$results = [];

foreach( $claimTypes as $result ) {
    $json_row = array();
    $json_row['id'] = $result->id;
    $json_row['label'] = $result->label;
    array_push( $results, $json_row );
}

header('Content-type: application/json');
echo json_encode( $results );

?>