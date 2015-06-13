<?php
require_once('../api/RepricingAPI.php');

$patientID = $_GET['patientID'];

$repricingAPI = new RepricingAPI();
$result = $repricingAPI->getPatient($patientID);

header('Content-type: application/json');
echo json_encode( $result );

?>