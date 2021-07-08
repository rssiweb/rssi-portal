<?php
session_start();
// Storing Session
$user_check = $_SESSION['aid'];

if (!$_SESSION['aid']) {

    header("Location: index.php"); //redirect to the login page to secure the welcome page without login access.  
}
?>

<?php
include("member_data.php");
?>
<?php
include("database.php");
@$id = $_POST['get_id'];
$result = pg_query($con, "SELECT * FROM medimate_medimate WHERE id='$user_check' AND financialyear='$id' order by timestamp desc");
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
    <link href="//netdna.bootstrapcdn.com/bootstrap/3.1.0/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <!-- Main css -->
    <!-- Main css -->
    <style>
        <?php include '../css/style.css'; ?>
    </style>
    <script src="//netdna.bootstrapcdn.com/bootstrap/3.1.0/js/bootstrap.min.js"></script>
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
                                <?php if ($status ==null) { ?>
                                        <option value="" hidden selected>Select policy year</option>
                                    <?php
                                    } else { ?>
                                        <option hidden selected><?php echo $status ?></option>
                                    <?php }
                                    ?>
                                    <option>FY 2021-2022</option>
                                    <option>FY 2020-2021</option>
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
                        <thead>
                            <tr>
                                <th scope="col">Claim ID</th>
                                <th scope="col">Submission Date</th>
                                <th scope="col">Beneficiary</th>
                                <th scope="col">Account Number</th>
                                <th scope="col">Claimed Amount (&#8377;)</th>
                                <th scope="col">Approved Amount (&#8377;)</th>
                                <th scope="col">Current Claim Status</th>
                            </tr>
                        </thead>' ?>
                    <?php if ($resultArr != null) {
                        echo '<tbody>';
                        foreach ($resultArr as $array) {
                            echo '
                                <tr>
                                    <td>' . $array['claimid'] . '</td>
                                    <td>' . $array['timestamp'] . '</td>
                                    <td>' . $array['selectbeneficiary'] . '</td>
                                    <td>' . $array['accountnumber'] . '</td>
                                    <td>' . $array['totalbillamount'] . '</td>
                                    <td>' . $array['approved'] . '</td>
                                    <td>' . $array['currentclaimstatus'] . '</td>
                                    </tr>';
                        }
                    } else if ($id == null) {
                        echo '<tr>
                            <td>Please select policy year.</td>
                        </tr>';
                    } else {
                        echo '<tr>
                        <td>No record found for' ?>&nbsp;<?php echo $id ?>
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
    <a id="back-to-top" href="#" class="go-top" role="button"><i class="fa fa-angle-up"></i></a>
</body>

</html>