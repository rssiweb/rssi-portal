<?php

/**
 * import checksum generation utility
 */
include("./rssi-member/database.php");
require_once("./util/PaytmChecksum.php");

$paytmChecksum = "";

/* Create a Dictionary from the parameters received in POST */
$paytmParams = array();
foreach ($_POST as $key => $value) {
    if ($key == "CHECKSUMHASH") {
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

$isValidChecksum = PaytmChecksum::verifySignature($paytmParams, "0jsr1z9J3L_&1B3w", $paytmChecksum); ?>

<html>

<head>
    <meta name="description" content="">
    <meta name="author" content="">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Payment Confirmation</title>
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <link rel="stylesheet" type="text/css" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" />
    <link rel="stylesheet" type="text/css" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" />
    <style type="text/css">
        body {
            background: #f2f2f2;
        }

        .payment {
            border: 1px solid #f2f2f2;
            height: 280px;
            border-radius: 20px;
            background: #fff;
        }

        .payment_header {
            background: rgb(45, 190, 123);
            padding: 20px;
            border-radius: 20px 20px 0px 0px;

        }

        .payment_header_failed {
            background: rgb(194, 0, 0);
            padding: 20px;
            border-radius: 20px 20px 0px 0px;

        }

        .check {
            margin: 0px auto;
            width: 50px;
            height: 50px;
            border-radius: 100%;
            background: #fff;
            text-align: center;
        }

        .check i {
            vertical-align: middle;
            line-height: 50px;
            font-size: 30px;
        }

        .content {
            text-align: center;
        }

        .content h1 {
            font-size: 25px;
            padding-top: 25px;
        }

        .content a {
            width: 200px;
            height: 35px;
            color: #fff;
            border-radius: 30px;
            padding: 5px 10px;
            background: rgb(45, 190, 123);
            transition: all ease-in-out 0.3s;
        }

        .failed a {
            background: rgb(194, 0, 0);
        }

        .content a:hover {
            text-decoration: none;
            background: #000;
        }
    </style>
</head>

<body>

    <?php
    @$orderid = $paytmParams['ORDERID'];
    if ($isValidChecksum) {
        // update database 
        if (strpos($orderid, "ORDER_") !== false) {
            $test = "UPDATE test SET  orderstatus = 'completed' WHERE orderid = $orderid";
        }
        // else if (strpos($paytmParams['ORDERID'], "FEES_") !== false) {
        //     $test = "UPDATE test2 VALUES SET STATUS = 'completed' where ORDER_ID = $paytmParams['ORDERID'];
        // }
        // echo "Checksum Matched! payment received!";
    ?>
        <div class="container">
            <div class="row">
                <div class="col-md-6 mx-auto mt-5">
                    <div class="payment">
                        <div class="payment_header">
                            <div class="check"><i class="fa fa-check" aria-hidden="true" style="color:rgb(45,190,123)"></i></div>
                        </div>
                        <div class="content">
                            <h1>Payment Success !</h1>
                            <p>Your orderid: <?php echo @$orderid ?><br>Your payment was successful! For invoicing please check your email or contact the RSSI administrator.</p>
                            <a href="javascript:history.back()">Go to Home</a>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        // success output
    <?php } else {
        // echo "Checksum Mismatched";
    ?>
        <div class="container">
            <div class="row">
                <div class="col-md-6 mx-auto mt-5">
                    <div class="payment">
                        <div class="payment_header_failed">
                            <div class="check"><i class="fa fa-check" aria-hidden="true" style="color:rgb(194,0,0);"></i></div>
                        </div>
                        <div class="content failed">
                            <h1>Payment Failed !</h1>
                            <p>Your orderid: <?php echo @$orderid ?><br>Transaction failed. If your money was debited, you should get a refund within the next 5 to 7 business days.</p>
                            <a href="javascript:history.back()">Go to Home</a>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    <?php
        // error output
    } ?>
</body>

</html>