<?php
session_start();
// Storing Session
include("../util/login_util.php");

if (!isLoggedIn("sid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

if ($password_updated_by == null || $password_updated_on < $default_pass_updated_on) {

    echo '<script type="text/javascript">';
    echo 'window.location.href = "defaultpasswordreset.php";';
    echo '</script>';
}
$url = ""
?>
<!DOCTYPE html>
<html>

<head>
    <title>Payment</title>
    <meta name="description" content="">
    <meta name="author" content="">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>My Allocation</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <!-- Main css -->
    <style>
        <?php include '../css/style.css'; ?>
    </style>
    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
    <script src="https://kit.fontawesome.com/58c4cdb942.js" crossorigin="anonymous"></script>
    <!------ Include the above in your HEAD tag ---------->

    <script src="https://cdn.jsdelivr.net/gh/manucaralmo/GlowCookies@3.0.1/src/glowCookies.min.js"></script>
    <!-- Glow Cookies v3.0.1 -->
    <script>
        glowCookies.start('en', {
            analytics: 'G-S25QWTFJ2S',
            //facebookPixel: '',
            policyLink: 'https://drive.google.com/file/d/1o-ULIIYDLv5ipSRfUa6ROzxJZyoEZhDF/view'
        });
    </script>
    <style>
        /*
 CSS for the main interaction
*/
        .tabset>input[type="radio"] {
            position: absolute;
            left: -200vw;
        }

        .tabset .tab-panel {
            display: none;
        }

        .tabset>input:first-child:checked~.tab-panels>.tab-panel:first-child,
        .tabset>input:nth-child(3):checked~.tab-panels>.tab-panel:nth-child(2),
        .tabset>input:nth-child(5):checked~.tab-panels>.tab-panel:nth-child(3),
        .tabset>input:nth-child(7):checked~.tab-panels>.tab-panel:nth-child(4),
        .tabset>input:nth-child(9):checked~.tab-panels>.tab-panel:nth-child(5),
        .tabset>input:nth-child(11):checked~.tab-panels>.tab-panel:nth-child(6) {
            display: block;
        }

        .tabset>label {
            position: relative;
            display: inline-block;
            padding: 15px 15px 25px;
            border: 1px solid transparent;
            border-bottom: 0;
            cursor: pointer;
            font-weight: 600;
        }

        .tabset>label::after {
            content: "";
            position: absolute;
            left: 15px;
            bottom: 10px;
            width: 22px;
            height: 4px;
            background: #8d8d8d;
        }

        .tabset>label:hover,
        .tabset>input:focus+label {
            color: #06c;
        }

        .tabset>label:hover::after,
        .tabset>input:focus+label::after,
        .tabset>input:checked+label::after {
            background: #06c;
        }

        .tabset>input:checked+label {
            border-color: #ccc;
            border-bottom: 1px solid #fff;
            margin-bottom: -1px;
        }

        .tab-panel {
            padding: 30px 0;
            border-top: 1px solid #ccc;
        }
    </style>
</head>

<body>

    <?php include 'header.php'; ?>

    <section id="main-content">
        <section class="wrapper main-wrapper row">
            <div class="col-md-12">
            <div class="col" style="display: inline-block; width:100%; text-align:right">
                            <span class="noticea" style="line-height: 2;"><a href="#" onClick="javascript:history.go(-1)">Back to previous page</a></span>
                        </div>
                <section class="box" style="padding: 2%;">
                    <div class="tabset">
                        <!-- Tab 1 -->
                        <input type="radio" name="tabset" id="tab1" aria-controls="marzen" checked>
                        <label for="tab1">Paytm</label>
                        <!-- Tab 2 -->
                        <input type="radio" name="tabset" id="tab2" aria-controls="rauchbier">
                        <label for="tab2">Razorpay</label>
                        <!-- Tab 3 -->
                        <input type="radio" name="tabset" id="tab3" aria-controls="dunkles">
                        <label for="tab3">Bank transaction</label>

                        <div class="tab-panels">
                            <section id="marzen" class="tab-panel">
                                <a href="https://securegw.paytm.in/link/paymentForm/58509/LL_516177374" target='_blank' rel='im-checkout' data-behaviour='remote' data-style='light' data-text="Pay with Paytm" style="border-radius: 2px;display: inline-block;border: 1px solid #e6ebf3;padding: 0 23px;color: #182233;font-size: 12px;text-decoration: none;font-family: 'Nunito Sans', sans-serif;height: 32px;line-height: 28px;background: #00b9f5;color: #ffffff;border: 1px solid #00b9f5;">
                                    <span>Pay with</span>
                                    <img style="margin-left: 6px;vertical-align:sub;width: 50px;" src="https://staticgw.paytm.in/1.4/plogo/paytmlogo-white.png" /></a>
                            </section>
                            <section id="rauchbier" class="tab-panel">
                                <form>
                                    <script src="https://checkout.razorpay.com/v1/payment-button.js" data-payment_button_id="pl_Jkwj2APScBh1eb" async> </script>
                                </form>
                            </section>
                            <section id="dunkles" class="tab-panel">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p>Account Number: <b>201008915512</b></p>
                                        <p>A/C Name: 52617897 - Rina Shiksha Sahayak Foundation</p>
                                        <p>Bank Name: Indusind Bank Ltd, 0865 - Chhinhat Lucknow</p>
                                        <p>IFSC Code: INDB0000865</p>

                                    </div>
                                    <div class="col-md-6">
                                        <img src="http://web.local/images/donation/payment.jpg" alt="Google pay QR" width="100px">
                                    </div>
                                </div>
                                <br>
                                <p>
                                    After making the payment you are requested to generate the receipt with the transaction ID generated by your bank after a successful transaction.</p>
                                <br>
                                <input type="checkbox" id="termsChkbx" name="termsChkbx" onclick="change_button(this,'sub1')" />
                                <label for="termsChkbx" style="font-weight: 400;">I have completed the payment and I have the transaction ID.</label><br><br>

                                <!-- <a id="sub1" title="Click here to generate the receipt" style="color:#444; text-decoration:none" disabled>
                                    <button type="button">Generate receipt</button>
                                </a> -->
                                <form target="_blank" action="https://docs.google.com/forms/d/e/1FAIpQLSczI-RQx4kx_i5Qp7sqfQel86WQDxqfuJlBzpxnneYGtbvMSQ/viewform">

                                    <input type="hidden" name="entry.626557897" value="<?php echo $studentname ?>" />
                                    <input type="hidden" name="entry.832187912" value="<?php echo $student_id ?>" />
                                    <input type="hidden" name="entry.481876319" value="<?php echo $emailaddress ?>" />
                                    <input type="hidden" name="entry.2017265393" value="<?php echo $contact ?>" />
                                    <input type="hidden" name="entry.905294006" value="INR" />
                                    <input type="submit" id="sub1" value="Generate receipt" disabled />
                                </form>
                            </section>
                        </div>

                    </div>
                </section>
            </div>
        </section>
    </section>
    <script>
        function change_button(checkbx, sub1) {
            var btn = document.getElementById(sub1);
            if (checkbx.checked == true) {
                btn.disabled = false;
            } else {
                btn.disabled = true;
                // btn.children[0].href = "javascript: void(0)";
            }
        }
    </script>
</body>

</html>