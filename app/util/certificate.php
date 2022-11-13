<!DOCTYPE html>
<html>

<head>
    <meta name="description" content="">
    <meta name="author" content="">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>RSSI-CMS</title>
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
<div class="page-topbar">
    <div class="logo-area"> </div>
</div>
<?php
include("../rssi-member/database.php");
@$certificate_no = $_GET['certificate_no'];
$result = pg_query($con, "SELECT * FROM certificate WHERE certificate_no='$certificate_no'");
if (!$result) {
    echo "An error occurred.\n";
    exit;
}

$resultArr = pg_fetch_all($result);
//print_r($resultArr);

echo '
<section>
        <section class="wrapper main-wrapper row">
            <div class="col-md-12">
            <div style="font-family:Poppins; text-align:Center;font-size:20px;">Rina Shiksha Sahayak Foundation (RSSI)</div>
            <div style="font-family:Roboto; text-align:Center;font-size:20px; line-height:2">Certificate Details</div>
                <section class="box" style="padding: 2%;">
<table class="table">
<thead>
    <tr>
    <th scope="col">Certificate no</th>
       <th scope="col">Nominee id</th>
        <th scope="col">Nominee name</th>
        <th scope="col">Badge name</th>
        <th scope="col">Remarks</th>
        <th scope="col">Issued on</th>
        <th scope="col">Issued by</th>
        <th scope="col">Certificate</th>
        <th scope="col"></th>
        </tr>
        </thead>' ?>

<?php if (@$certificate_no > 0) {

    foreach ($resultArr as $array) {
        echo '<tbody><tr>
        <td>' . $array['certificate_no'] . '</td>
        <td>' . $array['awarded_to_id'] . '</td>
        <td>' . $array['awarded_to_name'] . '</td>
        <td>' . $array['badge_name'] . '</td>
        <td>' . $array['comment'] . '</td>'?>
        <?php if ($array['issuedon'] == null) { ?>
            <?php echo '<td></td>' ?>
        <?php } else { ?>
            <?php echo '<td>' . @date("d/m/Y g:i a", strtotime($array['issuedon'])) . '</td>' ?>
        <?php } ?>

        <?php echo '<td>' . $array['issuedby'] . '</td>' ?>

        <?php if ($array['certificate_url'] == null) { ?>
            <?php echo '<td></td>' ?>

        <?php } else { ?>

            <?php echo '<td><a href="' . $array['certificate_url'] . '" target="_blank"><i class="fa-regular fa-file-pdf" style="font-size: 16px ;color:#777777" title="' . $array['certificate_no'] . '" display:inline;></i></a></td>' ?>
        <?php } ?>

   <?php  }
} ?>
</section>
</div>
</section>
</section>