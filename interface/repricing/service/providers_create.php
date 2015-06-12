<?php
require_once('../api/RepricingAPI.php');

$fname = $_POST['fname'];
$mname = $_POST['mname'];
$lname = $_POST['lname'];
$npi = $_POST['npi'];

$repricingAPI = new RepricingAPI();
$result = $repricingAPI->createProvider($fname, $mname, $lname, $npi);

$json_row = array();
$json_row['id'] = $result->id;
$json_row['value'] = $result->name;
$json_row['label'] = $result->name;

header('Content-type: application/json');
echo json_encode( $json_row );

?>