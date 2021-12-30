<?php
include("database.php");
@$id = strtoupper($_POST['get_id']);
$view_users_query = "select * from rssimyaccount_members WHERE associatenumber='$id'"; //select query for viewing users.  
$run = pg_query($con, $view_users_query); //here run the sql query.  

while ($row = pg_fetch_array($run)) //while look to fetch the result and store in a array $row.  
{
    $doj = $row[0];
    $associatenumber = $row[1];
    $fullname = $row[2];
    $email = $row[3];
    $basebranch = $row[4];
    $gender = $row[5];
    $dateofbirth = $row[6];
    $howyouwouldliketobeaddressed = $row[7];
    $currentaddress = $row[8];
    $permanentaddress = $row[9];
    $languagedetailsenglish = $row[10];
    $languagedetailshindi = $row[11];
    $workexperience = $row[12];
    $nationalidentifier = $row[13];
    $yourthoughtabouttheworkyouareengagedwith = $row[14];
    $applicationnumber = $row[15];
    $position = $row[16];
    $approvedby = $row[17];
    $associationstatus = $row[18];
    $effectivedate = $row[19];
    $remarks = $row[20];
    $phone = $row[21];
    $identifier = $row[22];
    $astatus = $row[23];
    $badge = $row[24];
    $colors = $row[25];
    $gm = $row[26];
    $lastupdatedon = $row[27];
    $photo = $row[28];
    $mydoc = $row[29];
    $class = $row[30];
    $notification = $row[31];
    $age = $row[32];
    $depb = $row[33];
    $attd = $row[34];
    $filterstatus = $row[35];
    $today = $row[36];
    $allocationdate = $row[37];
    $maxclass = $row[38];
    $classtaken = $row[39];
    $leave = $row[40];
    $ctp = $row[41];
    $feedback = $row[42];
    $evaluationpath = $row[43];
    $leaveapply = $row[44];
    $cl = $row[45];
    $sl = $row[46];
    $el = $row[47];
    $engagement = $row[48];
    $cltaken = $row[49];
    $sltaken = $row[50];
    $eltaken = $row[51];
    $othtaken = $row[52];
    $clbal = $row[53];
    $slbal = $row[54];
    $elbal = $row[55];
    $officialdoc = $row[56];
    $profile = $row[57];
    $filename = $row[58];
    $fname = $row[59];
    $quicklink = $row[60];
    $yos = $row[61];
    $role = $row[62];
    $originaldoj = $row[63];
    $iddoc = $row[64];
    $vaccination = $row[65];
    $scode = $row[66];
    $exitinterview = $row[67];
    $questionflag = $row[68];
    $googlechat = $row[69];
    $adjustedleave = $row[70];
    $eduq = $row[72];
    $mjorsub = $row[73];
    $disc = $row[74];
    $hbday = $row[75];
    $on_leave = $row[76];
    $attd_pending = $row[77];
    $ipfl = $row[71];
?>
<?php } ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="description" content="">
    <meta name="author" content="">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Details Verification</title>
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
            td {
                width: 100%
            }

            .page-topbar .logo-area {
                width: 240px !important;
                margin-top: 2.5%;
            }
        }

        .page-topbar,
        .logo-area {
            -webkit-transition: 0ms;
            -moz-transition: 0ms;
            -o-transition: 0ms;
            transition: 0ms;
        }
    </style>

</head>

<body>
    <div class="page-topbar">
        <div class="logo-area"> </div>
    </div>
    <section class="wrapper main-wrapper row">
        <div class="col-md-12">
            <section class="box" style="padding: 2%;">
                <form action="" method="POST">
                    <div class="form-group" style="display: inline-block;">
                        <div class="col2" style="display: inline-block;">
                        <input name="get_id" class="form-control" style="width:max-content; display:inline-block" placeholder="Associate ID" value="<?php echo $id ?>">
                        </div>
                    </div>
                    <div class="col2 left" style="display: inline-block;">
                        <button type="submit" name="search_by_id" class="btn btn-primary" style="outline: none;">
                            <span class="glyphicon glyphicon-search"></span>&nbsp;Search</button>
                    </div>
                </form>

                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Photo</th>
                            <th scope="col" id="cw2">Associate details</th>
                            <th scope="col" id="cw1">Offer letter issued on</th>
                        </tr>
                    </thead>
                    <?php if (@$associatenumber > 0) {
                    ?>
                        <tbody>
                            <tr>

                                <td style="line-height: 1.7;"><img src="<?php echo $photo ?>" width=100px /></td>
                                <td id="cw1" style="line-height: 1.7;"><b><?php echo $fullname ?> (<?php echo $associatenumber ?>)</b><br>
                                    <span style="line-height: 3;"><?php echo $engagement ?>
                                </td>

                                <td id="cw" style="line-height: 1.7;"><?php echo $doj ?></td>
                            </tr>
                        </tbody>
                </table>
            <?php
                    } else if ($id == "") {
            ?>
                <tr>
                    <td>Please enter Associate ID.</td>
                </tr>
            <?php
                    } else {
            ?>
                <tr>
                    <td>No record found for <?php echo $id ?></td>
                </tr>
            <?php }
            ?>
            </tbody>
            </table>


        </div>
        </div>
        </div>
    </section>
    </div>
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