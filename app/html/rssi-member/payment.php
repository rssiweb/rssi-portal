<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");
include("../../util/paytm-util.php");

// if (!isLoggedIn("aid")) {
//     $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
//     header("Location: index.php");
//     exit;
// }

// if (@$password_updated_by == null || @$password_updated_on < @$default_pass_updated_on) {
//     header("Location: defaultpasswordreset.php");
// }



/*
* import checksum generation utility
* You can get this utility from https://developer.paytm.com/docs/checksum/
*/
$order_id = "";
?>

<!DOCTYPE html>
<html>

<head>
    <meta name="description" content="">
    <meta name="author" content="">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Online Payment</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <!-- Main css -->
<link rel="stylesheet" href="/css/style.css" />
    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
    <script src="https://kit.fontawesome.com/58c4cdb942.js" crossorigin="anonymous"></script>
    <!------ Include the above in your HEAD tag ---------->

    <script src="https://cdn.jsdelivr.net/gh/manucaralmo/GlowCookies@3.0.1/src/glowCookies.min.js"></script>
    <!-- Glow Cookies v3.0.1 -->
    
    <!-- payment step 3: call JS checkout paytm -->
    <script type="application/javascript" crossorigin="anonymous" src="https://securegw.paytm.in/merchantpgpui/checkoutjs/merchants/OsXyfL78631649755177.js"></script>
    <script>

        function start_paytm_js(orderid, amount, token){
            console.log("start paytm");
            var config = {
            "root": "",
            "flow": "DEFAULT",
            "data": {
                "orderId": orderid /* update order id */,
                "token": token /* update token value */,
                "tokenType": "TXN_TOKEN",
                "amount": amount /* update amount */
            },
            "handler": {
                "notifyMerchant": function(eventName,data){
                console.log("notifyMerchant handler function called");
                console.log("eventName => ",eventName);
                console.log("data => ",data);
                } 
            }
            };

            if(window.Paytm && window.Paytm.CheckoutJS){
                console.log("after paytm if check");
                window.Paytm.CheckoutJS.init(config).then(function onSuccess() {
                    // after successfully update configuration invoke checkoutjs
                    window.Paytm.CheckoutJS.invoke();
                }).catch(function onError(error){
                    console.log("error => ",error);
                });
            } 
        }
    
    </script>
    <script>
        glowCookies.start('en', {
            analytics: 'G-S25QWTFJ2S',
            //facebookPixel: '',
            policyLink: 'https://www.rssi.in/disclaimer'
        });
    </script>
</head>

<body>
    <div class="container">
        <div class="row">
            <div class="col-md-4 col-md-offset-4">
                <div class="login-panel panel panel-info" style="padding: 5%;">
                    <div class="panel-heading">
                        <h3 class="panel-title">Fee deposit</h3>
                    </div>

                    <form method="post" name="google-sheet" >
                        <div id="loading" class="overlay"></div>
                        <br>
                        <input type="hidden" name="form-type" value="test" required>
                        <label for="sname">Student Name:</label><br>
                        <input type="text" name="sname" required><br><br>
                        <label for="sid">Student ID:</label><br>
                        <input type="text" name="sid" required><br><br>
                        <label for="amount">Amount:</label><br>
                        <input type="number" name="amount" required min="0" step="0.01" title="Currency" pattern="^\d+(?:\.\d{1,2})?$" onblur="
this.parentNode.parentNode.style.backgroundColor=/^\d+(?:\.\d{1,2})?$/.test(this.value)"><br><br>

                        <button type="submit">Pay now</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        const scriptURL = 'payment-api.php'
        const form = document.forms['google-sheet']

        form.addEventListener('submit', e => {
            e.preventDefault()
            fetch(scriptURL, {
                    method: 'POST',
                    body: new FormData(form)
                })
                .then(response => response.json())
                .then(data => {
                    // $('#loading').hide();
                    console.log(data)
                    start_paytm_js(data.orderid, data.amount, data.txnToken)
                    // alert("Your response has been recorded. Your order id")
                })
                .catch(error => console.error('Error!', error.message))
        })
    </script>
</body>

</html>
