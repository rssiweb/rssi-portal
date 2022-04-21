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
include("database.php");
$view_users_query = "select * from claim WHERE registrationid='$user_check'"; //select query for viewing users.  
$run = pg_query($con, $view_users_query); //here run the sql query.  

while ($row = pg_fetch_array($run)) //while look to fetch the result and store in a array $row.  
{
$id=$row[0];
$timestamp=$row[1];
$name=$row[2];
$registrationid=$row[3];
$mobilenumber=$row[4];
$email=$row[5];
$bankname=$row[6];
$accountnumber=$row[7];
$accountholdername=$row[8];
$ifsccode=$row[9];
$selectclaimheadfromthelistbelow=$row[10];
$billno=$row[11];
$currency=$row[12];
$totalbillamount=$row[13];
$uploadeddocuments=$row[14];
$ack=$row[15];
$termsofagreement=$row[16];
$year=$row[17];
$reimbid=$row[18];
$mergestatus=$row[19];
$claimstatus=$row[20];
$approvedamount=$row[21];
$transactionid=$row[22];
$transfereddate=$row[23];
$closedon=$row[24];
$mediremarks=$row[25];
$profile=$row[26];
$rlastupdatedon=$row[27]      

?>
<?php } ?>

<!DOCTYPE html>
<html>

<head>
    <meta name="description" content="">
    <meta name="author" content="">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Reimbursement</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <!-- Main css -->
    <style>
        <?php include '../css/style.css'; ?>
    </style>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
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

</head>

<body>
    <?php $reimbursement_active = 'active'; ?>
    <?php include 'header.php'; ?>

    <section id="main-content">
        <section class="wrapper main-wrapper row">
            <div class="col-md-12">
                <div class=col style="text-align: right;"><a href="reimbursementstatus.php"><button type="button" class="exam_btn"><i class="fas fa-child" style="font-size: 17px;"></i>
                Track Your Claim</button></a>
                </div>
                <section class="box" style="padding: 2%;">

                    <table class="table">
                        <thead>
                            <tr>
                                <th scope="col">Reimbursement policy</th>
                                <th scope="col">Submit claim</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="line-height: 2;"><span class="noticet"><a href="javascript:void(0)" target="_self">RSSI reimbursement policy</a></span></td>
                                <td style="line-height: 2;"><span class="noticet"><a href="https://docs.google.com/forms/d/e/1FAIpQLSeJvl1DPzvAHpEfGLdPhnjK1ojTcRNnLR_w1WCCswDxJTZxlg/viewform?usp=pp_url&entry.1268051974=<?php echo @$fullname ?>&entry.288127209=<?php echo @$associatenumber ?>&entry.995125243=<?php echo @$phone ?>&entry.605633398=<?php echo @$email ?>&entry.1867197840=<?php echo @$bankname ?>&entry.1288695359=<?php echo @$accountnumber ?>&entry.1236133419=<?php echo @$accountholdername ?>&entry.1547933107=<?php echo @$ifsccode ?>" target="_blank">Claim form</a></span></td>
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
                                <td style="line-height: 2; font-size:13px">
                                    <?php echo @$bankname ?><br>
                                    Account Number:&nbsp;<b><?php echo @$accountnumber ?></b><br>
                                    Account Holder Name:&nbsp;<?php echo @$accountholdername ?><br>
                                    IFSC Code:&nbsp;<?php echo @$ifsccode ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </section>
                <div style="font-size: 13px;">
                <p><b>Note:</b></p>
                    Once the claim is settled, amount will be credited to applicant's account within 5 to 6 working days from date of Settlement.</p>
                    <p>For the first time you have to fill the complete Domiciliary Claim Form. From the second time onwards your bank account details will be automatically updated based on your previous information.</p>
                    <br>Last updated on: <?php echo @$rlastupdatedon ?>
                    </div>
            </div>

            <div class="clearfix"></div>
            <!--**************clearfix**************

           <div class="col-md-12">
                <section class="box">cccccccccccee33</section>
            </div>-->

        </section>
    </section>
</body>

</html>