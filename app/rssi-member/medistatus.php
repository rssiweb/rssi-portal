<?php
session_start();
// Storing Session
include("../util/login_util.php");

if(! isLoggedIn("aid")){
    header("Location: index.php");
}
$user_check = $_SESSION['aid'];

if (!$_SESSION['aid']) {

    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}
?>

<?php
include("member_data.php");
?>
<?php
include("database.php");
@$id = $_POST['get_id'];
$result = pg_query($con, "SELECT * FROM medimate WHERE registrationid='$user_check' AND year='$id' order by id desc");
if (!$result) {
    echo "An error occurred.\n";
    exit;
}

$resultArr = pg_fetch_all($result);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="description" content="">
    <meta name="author" content="">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Claim Status</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <!-- Main css -->
    <!-- Main css -->
    <style>
        <?php include '../css/style.css'; ?>
    </style>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
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
        @media (max-width:767px) {

            #cw,
            #cw1,
            #cw2,
            #cw3 {
                width: 100% !important;
            }

        }

        #cw {
            width: 15%;
        }

        #cw1 {
            width: 20%;
        }

        #cw2 {
            width: 25%;
        }

        #cw3 {
            width: 20%;
        }

        @media (min-width:767px) {
            .left {
                margin-left: 2%;
            }
        }
    </style>
</head>

<!-- =========================
     NAVIGATION LINKS     
============================== -->

<body>
    <?php $medimate_active = 'active'; ?>
    <?php include 'header.php'; ?>
    <section id="main-content">
        <section class="wrapper main-wrapper row">
            <div class="col-md-12">
                <div class=col style="text-align: right;">
                    <!--<span class="noticet" style="line-height: 2;"><a href="#" onClick="javascript:history.go(-1)">Back to previous page</a></span><br>-->
                    Policy year: <?php echo $id ?>
                </div>
                <section class="box" style="padding: 2%;">
                    <form action="" method="POST">
                        <div class="form-group" style="display: inline-block;">
                            <div class="col2" style="display: inline-block;">
                                <select name="get_id" class="form-control" style="width:max-content;" placeholder="Select policy year" required>
                                    <?php if ($id == null) { ?>
                                        <option value="" hidden selected>Select policy year</option>
                                    <?php
                                    } else { ?>
                                        <option hidden selected><?php echo $id ?></option>
                                    <?php }
                                    ?>
                                    <option>2022</option>
                                </select>
                            </div>
                        </div>
                        <div class="col2 left" style="display: inline-block;">
                            <button type="submit" name="search_by_id" class="btn btn-primary" style="outline: none;">
                                <span class="glyphicon glyphicon-search"></span>&nbsp;Search</button>
                        </div>
                    </form>
                    <?php echo '
                    <table class="table">
                        <thead style="font-size: 12px;">
                        <tr>    
                        <th scope="col">Claim Number</th>
                        <th scope="col">Registered On</th>
                        <th scope="col">Beneficiary</th>
                        <th scope="col">Bills</th>
                        <th scope="col">Account Number</th>
                        <th scope="col">Claimed Amount (&#8377;)</th>
                        <th scope="col">Amount Transfered (&#8377;)</th>
                        <th scope="col">Transaction Reference Number</th>
                        <th scope="col">Transfered Date</th>
                        <th scope="col">Claim Status</th>
                        <th scope="col">Closed on</th>
                        <th scope="col">Remarks</th>
                    </tr>
                        </thead>' ?>
                    <?php if ($resultArr != null) {
                        echo '<tbody style="font-size: 13px;">';
                        foreach ($resultArr as $array) {
                            echo '
                            <tr>
                                    <td>' . $array['claimid'] . '</td>
                                    <td>' . substr($array['timestamp'], 0, 10) . '</td>
                                    <td>' . $array['selectbeneficiary'] . '</td>
                                    <td><span><a href="' . $array['uploadeddocuments'] . '" target="_blank"><i class="far fa-file-pdf" style="font-size:17px;color: #767676;"></i></a></span></td>
                                    <td>' . $array['accountnumber'] . '</td>
                                    <td>' . $array['totalbillamount'] . '</td>
                                    <td>' . $array['approvedamount'] . '</td>
                                    <td>' . $array['transactionid'] . '</td>
                                    <td>' . $array['transfereddate'] . '</td>'
                    ?>
                            <?php if ($array['claimstatus'] == 'review' || $array['claimstatus'] == 'in progress' || $array['claimstatus'] == 'withdrawn') { ?>
                                <?php echo '<td> <p class="label label-warning">' . $array['claimstatus'] . '</p>' ?>

                            <?php } else if ($array['claimstatus'] == 'approved' || $array['claimstatus'] == 'claim settled') { ?>
                                <?php echo '<td><p class="label label-success">' . $array['claimstatus'] . '</p>' ?>
                            <?php    } else if ($array['claimstatus'] == 'rejected' || $array['claimstatus'] == 'on hold') { ?>
                                <?php echo '<td><p class="label label-danger">' . $array['claimstatus'] . '</p>' ?>
                            <?php    } else { ?>
                                <?php echo '<td><p class="label label-info">' . $array['claimstatus'] . '</p>' ?>
                            <?php } ?>


                            <?php echo
                            '</td><td>' . $array['closedon'] . '</td>
                                    <td>' . $array['mediremarks'] . '</td>
                                    </tr>';
                        }
                    } else if ($id == null) {
                        echo '<tr>
                            <td  colspan="2">Please select policy year.</td>
                        </tr>';
                    } else {
                        echo '<tr>
                        <td  colspan="2">No record found for' ?>&nbsp;<?php echo $id ?>
                        <?php echo '</td>
                    </tr>';
                    }
                    echo '</tbody>
                     </table>';
                        ?>
            </div>
            <div class="col-md-12" style="text-align: right;">
                <span class="noticet" style="line-height: 2;"><a href="medimate.php">Back to Medimate</a></span>
            </div>
            </div>
        </section>
        </div>
    </section>
    </section>


    <!-- Back top -->
    <script>
        $(document).ready(function() {
            $(window).scroll(function() {
                if ($(this).scrollTop() > 50) {
                    $('#back-to-top').fadeIn();
                } else {
                    $('#back-to-top').fadeOut();
                }
            });
            // scroll body to 0px on click
            $('#back-to-top').click(function() {
                $('body,html').animate({
                    scrollTop: 0
                }, 400);
                return false;
            });
        });
    </script>
    <!-- TEXT CLICK POPUP CSS and SCRIPT-->
    <!--<script>
        // When the user clicks on div, open the popup
        function myFunction() {
            var popup = document.getElementById("myPopup");
            popup.classList.toggle("show");
        }
    </script>-->
    <a id="back-to-top" href="#" class="go-top" role="button"><i class="fa fa-angle-up"></i></a>
</body>

</html>