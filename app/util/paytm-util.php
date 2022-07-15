<?php 

require_once("../util/PaytmChecksum.php");

function get_paytm_tnx_token($order_id, $amount, $customer_id){
    // payment step 2: send data to Paytm including amount, generated order id etc.

    $paytmParams = array();

    $paytmParams["body"] = array(
        "requestType"   => "Payment",
        "mid"           => "OsXyfL78631649755177",
        "orderId"       => $order_id,
        "callbackUrl"   => "https://login.rssi.in/payment-confirmation.php",
        "txnAmount"     => array(
            "value"     => $amount, # Amount
            "currency"  => "INR",
        ),
        "userInfo"      => array(
            "custId"    => $customer_id,
        ),
    );

    /*
    * Generate checksum by parameters we have in body
    * Find your Merchant Key in your Paytm Dashboard at https://dashboard.paytm.com/next/apikeys 
    */
    $checksum = PaytmChecksum::generateSignature(json_encode($paytmParams["body"], JSON_UNESCAPED_SLASHES), "0jsr1z9J3L_&1B3w");

    $paytmParams["head"] = array(
    "signature" => $checksum
    );

    $post_data = json_encode($paytmParams, JSON_UNESCAPED_SLASHES);

    /* for Staging */
    // $url = "https://securegw-stage.paytm.in/theia/api/v1/initiateTransaction?mid=uWfstQ00017645274861&orderId=$order_id";

    /* for Production */
    $url = "https://securegw.paytm.in/theia/api/v1/initiateTransaction?mid=OsXyfL78631649755177&orderId=$order_id";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json")); 
    $response = curl_exec($ch);
    
    $res = json_decode($response, true);
    return $res["body"]["txnToken"];
    // return json_decode($response).body.txnToken;
}

?>