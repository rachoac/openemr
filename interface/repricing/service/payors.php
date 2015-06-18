<?php
require_once('../api/RepricingAPI.php');

$patientID = $_GET['patientID'];
$dateOfService = $_GET['dateOfService'];

$repricingAPI = new RepricingAPI();
$payors = $repricingAPI->getPayors($patientID, $dateOfService);

header('Content-type: application/json');
echo json_encode( $payors );

?>