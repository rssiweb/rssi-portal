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
$view_users_query = "select * from medimate WHERE registrationid='$user_check'"; //select query for viewing users.  
$run = pg_query($con, $view_users_query); //here run the sql query.  

while ($row = pg_fetch_array($run)) //while look to fetch the result and store in a array $row.  
{
$id= $row[0];
$timestamp= $row[1];
$name= $row[2];
$registrationid= $row[3];
$mobilenumber= $row[4];
$email= $row[5];
$selectbeneficiary= $row[6];
$ageofbeneficiary= $row[7];
$bankname= $row[8];
$accountnumber= $row[9];
$accountholdername= $row[10];
$ifsccode= $row[11];
$clinicname= $row[12];
$clinicpincode= $row[13];
$doctorregistrationno= $row[14];
$nameoftreatingdoctor= $row[15];
$natureofillnessdiseaseaccident= $row[16];
$treatmentstartdate= $row[17];
$treatmentenddate= $row[18];
$billtype= $row[19];
$billnumber= $row[20];
$totalbillamount= $row[21];
$gstdlno= $row[22];
$uploadeddocuments= $row[23];
$uploadeddocumentscheck= $row[24];
$ack= $row[25];
$termsofagreement= $row[26];
$year= $row[27];
$claimid= $row[28];
$mergestatus= $row[29];
$claimstatus= $row[30];
$approvedamount= $row[31];
$transactionid= $row[32];
$transfereddate= $row[33];
$closedon= $row[34];
$mediremarks= $row[35];
$profile= $row[36];
$mlastupdatedon= $row[37]     

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
    <title>Medimate</title>
    <link href="//netdna.bootstrapcdn.com/bootstrap/3.1.0/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
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

</head>

<body>
    <?php $medimate_active = 'active'; ?>
    <?php include 'header.php'; ?>

    <section id="main-content">
        <section class="wrapper main-wrapper row">
            <div class="col-md-12">
                <div class=col style="text-align: right;"><a href="medistatus.php"><button type="button" class="exam_btn"><i class="fas fa-child" style="font-size: 17px;"></i>
                Track Your Claim</button></a>
                </div>
                <section class="box" style="padding: 2%;">

                    <table class="table">
                        <thead>
                            <tr>
                                <th scope="col">Health insurance policy</th>
                                <th scope="col">Submit Domiciliary Claim</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="line-height: 2;"><span class="noticet"><a href="https://drive.google.com/file/d/1pqXufu38P3T15L0jMeK_poRifJAMPPP1/view" target="_blank">RSSI Vaccination Policy</a></span></td>
                                <td style="line-height: 2;"><span class="noticet"><a href="https://docs.google.com/forms/d/e/1FAIpQLSePgeXEKY4R_WH_d6mOcHFPiEoMbbWnh2MxIxTojrxMzvckYA/viewform?usp=pp_url&entry.1268051974=<?php echo $fullname ?>&entry.288127209=<?php echo $associatenumber ?>&entry.995125243=<?php echo $phone ?>&entry.605633398=<?php echo $email ?>&entry.1867197840=<?php echo @$bankname ?>&entry.1288695359=<?php echo @$accountnumber ?>&entry.1236133419=<?php echo @$accountholdername ?>&entry.1547933107=<?php echo @$ifsccode ?>" target="_blank">Domiciliary Claim</a></span></td>
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
                    <br>Last updated on: <?php echo @$mlastupdatedon ?>
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