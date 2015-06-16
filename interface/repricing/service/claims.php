<?php
require_once('../api/RepricingAPI.php');

$claimData = json_decode($HTTP_RAW_POST_DATA, true);
$repricingAPI = new RepricingAPI();

$repricingAPI->saveClaim($claimData);


?>