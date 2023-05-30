<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");
include("../../util/email.php");
include("../../util/drive.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

if ($password_updated_by == null || $password_updated_on < $default_pass_updated_on) {

    echo '<script type="text/javascript">';
    echo 'window.location.href = "defaultpasswordreset.php";';
    echo '</script>';
}

$result = pg_query($con, "select panno,bankname,accountnumber,ifsccode from rssimyaccount_members WHERE associatenumber='$associatenumber'"); //select query for viewing users.  

if (!$result) {
    echo "An error occurred.\n";
    exit;
}
$resultArr = pg_fetch_all($result);

if (@$_POST['form-type'] == "reimbursementapply") {
    @$claimid = 'RSC' . time();
    @$claimhead = $_POST['claimhead'];
    @$claimheaddetails = $_POST['claimheaddetails'];
    @$billno = $_POST['billno'];
    //echo json_encode($_FILES);
    @$uploadedFile = $_FILES['billdoc'];
    @$currency = $_POST['currency'];
    @$amount = $_POST['amount'];
    @$appliedby = $_POST['appliedby'];
    @$ack = $_POST['ack'] ?? 0;
    @$email = $email;
    @$applicantid = $associatenumber;
    @$now = date('Y-m-d H:i:s');
    if (date('m') == 1 || date('m') == 2 || date('m') == 3) { //Upto March
        $currentAcademicYear = (date('Y') - 1) . '-' . date('Y');
    } else { //After MARCH
        $currentAcademicYear = date('Y') . '-' . (date('Y') + 1);
    }

    // send $file to google =======> google (rssi.in) // robotic service account credential.json

    if ($claimid != "") {

        if (empty($_FILES['billdoc']['name'])) {
            $doclink = null;
        } else {
            $filename = "doc_" . $claimid . "_" . $applicantid;
            $parent = '1MPw1VqHe_dvY3bZ-O1EWYYRsXGEx2wilyEGaCdHOq4HG2Fhg8qgNWfOejgB0USBGfZJNlnsC';
            $doclink = uploadeToDrive($uploadedFile, $parent, $filename);
        }
        $claimsubmit = "INSERT INTO claim (timestamp,reimbid,registrationid,selectclaimheadfromthelistbelow,claimheaddetails,billno,currency,totalbillamount,uploadeddocuments,ack,year) VALUES ('$now','$claimid','$applicantid','$claimhead','$claimheaddetails','$billno','$currency','$amount','$doclink','$ack','$currentAcademicYear')";

        $result = pg_query($con, $claimsubmit);
        $cmdtuples = pg_affected_rows($result);
    }
    if (@$cmdtuples == 1 && $email != "") {
        sendEmail("claimapply", array(
            "reimbid" => $claimid,
            "registrationid" => @$associatenumber,
            "fullname" => @$fullname,
            "totalbillamount" => $amount,
            "currency" => $currency,
            "timestamp" => @date("d/m/Y g:i a", strtotime($now))
        ), $email);
    }
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Reimbursement</title>

    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/gh/manucaralmo/GlowCookies@3.0.1/src/glowCookies.min.js"></script>
    <!-- Glow Cookies v3.0.1 -->
    <script>
        glowCookies.start('en', {
            analytics: 'G-S25QWTFJ2S',
            //facebookPixel: '',
            policyLink: 'https://www.rssi.in/disclaimer'
        });
    </script>

    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <!---change option value based on other dropdown PHP, Have created another file process-request.php to get the creason option---->
    <script type="text/javascript" src="http://code.jquery.com/jquery.js"> </script>
    <!--Here .typeofleave is a class and has been assigned to the input filed id=typeofleave-->
    <script type="text/javascript">
        $(document).ready(function() {
            $("select.typeofleave").change(function() {
                var selectedtypeofleave = $(".typeofleave option:selected").val();
                $.ajax({
                    type: "POST",
                    url: "process-request.php",
                    data: {
                        typeofleave: selectedtypeofleave
                    }
                }).done(function(data) {
                    $("#response").html(data);
                });
            });
        });
    </script>
    <style>
        .checkbox {
            padding: 0;
            margin: 0;
            vertical-align: bottom;
            position: relative;
            top: 0px;
            overflow: hidden;
        }

        .x-btn:focus,
        .button:focus,
        [type="submit"]:focus {
            outline: none;
        }

        #passwordHelpBlock {
            display: block;
        }

        .input-help {
            vertical-align: top;
            display: inline-block;
        }

        #hidden-panel,
        #hidden-panel_ack {
            display: none;
        }
    </style>

</head>

<body>

    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Reimbursement</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Claims and Advances</a></li>
                    <li class="breadcrumb-item active">Reimbursement</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section dashboard">
            <div class="row">

                <!-- Reports -->
                <div class="col-12">
                    <div class="card">

                        <div class="card-body">
                            <br>
                            <?php if ($role == 'Admin') { ?>
                                <?php if (@$claimid != null && @$cmdtuples == 0) { ?>

                                    <div class="alert alert-danger alert-dismissible" role="alert" style="text-align: -webkit-center;">
                                        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                                        <span class="blink_me"><i class="bi bi-exclamation-triangle"></i></span>&nbsp;&nbsp;<span>ERROR: Oops, something wasn't right.</span>
                                    </div>
                                <?php
                                } else if (@$cmdtuples == 1) { ?>

                                    <div class="alert alert-success alert-dismissible" role="alert" style="text-align: -webkit-center;">
                                        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                                        <i class="bi bi-check2" style="font-size: medium;"></i></span>&nbsp;&nbsp;<span>Claim no <?php echo @$claimid ?> has been submitted.</span>
                                    </div>
                                    <script>
                                        if (window.history.replaceState) {
                                            window.history.replaceState(null, null, window.location.href);
                                        }
                                    </script>
                                <?php } ?>
                            <?php } ?>
                            <div class=col style="text-align: right;">
                                <a href="reimbursementstatus.php" target="_self" class="btn btn-danger btn-sm" role="button">Track Your Claim</a>
                            </div>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th scope="col">Submit claim</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>
                                                <form autocomplete="off" name="reimbursement" id="reimbursement" action="reimbursement.php" method="POST" enctype="multipart/form-data">
                                                    <div class="form-group" style="display: inline-block;">

                                                        <input type="hidden" name="form-type" value="reimbursementapply">

                                                        <span class="input-help">
                                                            <select name="claimhead" id="claimhead" class="form-select" required>
                                                                <option disabled selected hidden value="">Select</option>
                                                                <option value="Business Meeting Expenses">Business Meeting Expenses</option>
                                                                <option value="Client Entertainment">Client Entertainment</option>
                                                                <option value="Communication Expenses">Communication Expenses</option>
                                                                <option value="Conference and Training Courses">Conference and Training Courses</option>
                                                                <option value="IT Peripherals">IT Peripherals</option>
                                                                <option value="Local Conveyance">Local Conveyance</option>
                                                                <option value="Marketing Expenses">Marketing Expenses</option>
                                                                <option value="Office Expense">Office Expense</option>
                                                                <option value="Professional Membership">Professional Membership</option>
                                                                <option value="Shift Working / Extended Hours">Shift Working / Extended Hours</option>
                                                                <option value="Staff Welfare">Staff Welfare</option>
                                                                <option value="Student Welfare">Student Welfare</option>
                                                                <option value="Visa & Passport Expenses">Visa & Passport Expenses</option>

                                                            </select>
                                                            <small id="passwordHelpBlock" class="form-text text-muted">Claim head<span style="color:red">*</span></small>
                                                        </span>

                                                        <span class="input-help">
                                                            <textarea class="form-control" name="claimheaddetails" id="claimheaddetails" placeholder="Claim head details" required></textarea>
                                                            <small id="passwordHelpBlock" class="form-text text-muted">Claim head details<span style="color:red">*</span><br>(Please mention the purpose of the expenditure.)</small>
                                                        </span>

                                                        <span class="input-help">
                                                            <input type="text" class="form-control" name="billno" id="billno" placeholder="Bill no" required>
                                                            <small id="passwordHelpBlock" class="form-text text-muted">Bill no<span style="color:red">*</span><br>(In case of multiple values ​​write it with comma (,).)</small>
                                                        </span>

                                                        <span class="input-help">
                                                            <select name="currency" id="currency" class="form-select" required>
                                                                <option disabled selected hidden value="">Select</option>
                                                                <option value="INR" selected>INR</option>
                                                            </select>
                                                            <small id="passwordHelpBlock" class="form-text text-muted">Currency<span style="color:red">*</span></small>
                                                        </span>

                                                        <span class="input-help">
                                                            <input type="number" class="form-control" name="amount" id="amount" placeholder="Amount" step="0.01" pattern="^\d+(?:\.\d{1,2})?$" required>
                                                            <small id="passwordHelpBlock" class="form-text text-muted">Amount<span style="color:red">*</span></small>
                                                        </span>

                                                        <span class="input-help">
                                                            <input type="file" name="billdoc" class="form-control" required />
                                                            <small id="passwordHelpBlock" class="form-text text-muted">Documents<span style="color:red">*</span></small>
                                                        </span>

                                                        <div id="filter-checksh">
                                                            <input type="checkbox" name="ack" id="ack" value="1" required />
                                                            <label for="ack" style="font-weight: 400;">I, hereby authorize Insurer/TPA to audit/investigate my claims also authorize to share the claim details with Government authorities/IRDA for audit requirements. I also agree and certify that all of the information pertaining to this claim is true and correct if it is found to be false and/or if it is proved that claim documents are manipulated then, I understand and agree that RSSI will initiate appropriate disciplinary proceedings which may also lead to termination of my employment with RSSI.</label>
                                                        </div>
                                                        </span>
                                                        <br>
                                                        <button type="Submit" name="search_by_id" class="btn btn-danger btn-sm" style="outline: none;">Submit</button>

                                                    </div>

                                                </form>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>

                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th scope="col">Bank Account Details</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td style="line-height: 2;">
                                                <?php if ($resultArr != null) { ?>
                                                    <?php foreach ($resultArr as $array) { ?>

                                                        <?php echo $array['bankname'] ?><br>
                                                        Account Number:&nbsp;<b><?php echo $array['accountnumber'] ?></b><br>
                                                        IFSC Code:&nbsp;<?php echo $array['ifsccode'] ?><br>
                                                        PAN:&nbsp;<?php echo $array['panno'] ?>
                                                <?php }
                                                } ?>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div><!-- End Reports -->
            </div>
        </section>

    </main><!-- End #main -->

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>

</body>

</html>