<?php
require_once('../api/RepricingAPI.php');

$searchTerm = $_GET['term'];
$codeID = $_GET['codeID'];

if ( $codeID ) {
    $code = $repricingAPI->getServiceCodeByID($codeID);
    header('Content-type: application/json');
    echo json_encode( $code );
} else if ( $searchTerm ) {
    $repricingAPI = new RepricingAPI();
    $codes = $repricingAPI->searchServiceCodes($searchTerm);
    $results = [];

    foreach( $codes as $result ) {
        $json_row = array();
        $json_row['id'] = $result->id;
        $json_row['value'] = $result->code;
        $json_row['label'] = $result->codeType . ' ' . $result->code . ': ' . $result->text;
        $json_row['allowedCharge'] = $result->allowedCharge;
        array_push( $results, $json_row );
    }

    header('Content-type: application/json');
    echo json_encode( $results );
} else {
    header('Content-type: application/json');
    echo json_encode( [] );
}

?>