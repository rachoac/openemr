<?php
require_once('../api/RepricingAPI.php');

if ( $HTTP_RAW_POST_DATA ) {
    // POST
    $claimData = json_decode($HTTP_RAW_POST_DATA, true);
    $repricingAPI = new RepricingAPI();

    $toReturn = $repricingAPI->saveClaim($claimData);

    header('Content-type: application/json');
    echo json_encode( $toReturn );
} else {
    // GET
    $repricingAPI = new RepricingAPI();
    $claimData = $repricingAPI->loadClaim($_GET['encounterID']);

    header('Content-type: application/json');
    echo json_encode( $claimData );
}

?>