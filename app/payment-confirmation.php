<?php
/**
* import checksum generation utility
*/
include("./rssi-member/database.php");
require_once("./util/PaytmChecksum.php");

$paytmChecksum = "";

/* Create a Dictionary from the parameters received in POST */
$paytmParams = array();
foreach($_POST as $key => $value){
	if($key == "CHECKSUMHASH"){
		$paytmChecksum = $value;
        $paytmParams[$key] = $value;
	} else {
		$paytmParams[$key] = $value;
	}
}

/**
* Verify checksum
* Find your Merchant Key in your Paytm Dashboard at https://dashboard.paytm.com/next/apikeys 
*/

$isValidChecksum = PaytmChecksum::verifySignature($paytmParams, "C6_2T26Ep@bTugrM", $paytmChecksum);
// $isValidChecksum = PaytmChecksum::verifySignature($paytmParams, "C6_2T26Ep@bTugrM", $paytmChecksum);
echo json_encode($paytmParams);
echo json_encode($_POST);
echo json_encode($isValidChecksum);

if($isValidChecksum) {
    $orderid=$paytmParams['ORDERID'];
    // update database 
    if(strpos($orderid, "ORDER_") !== false) {
        $test = "UPDATE test SET  orderstatus = 'completed' WHERE orderid = $orderid";
    }
    // else if (strpos($paytmParams['ORDERID'], "FEES_") !== false) {
    //     $test = "UPDATE test2 VALUES SET STATUS = 'completed' where ORDER_ID = $paytmParams['ORDERID'];
    // }
    echo "Checksum Matched! payment received!";
    // success output
} else {
	echo "Checksum Mismatched";
    // error output
}
