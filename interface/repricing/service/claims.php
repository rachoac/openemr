<?php
require_once('../api/RepricingAPI.php');

$claimData = json_decode($HTTP_RAW_POST_DATA, true);
$repricingAPI = new RepricingAPI();

$toReturn = $repricingAPI->saveClaim($claimData);

header('Content-type: application/json');
echo json_encode( $toReturn );

?>