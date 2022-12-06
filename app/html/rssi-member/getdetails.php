<?php require_once __DIR__ . "/../../bootstrap.php"; ?>
<!DOCTYPE html>
<html>

<head>
    <meta name="description" content="">
    <meta name="author" content="">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Experience Report</title>
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

@$id = $_GET['scode'];
$result = pg_query($con, "SELECT * FROM rssimyaccount_members WHERE scode='$id'");
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
            <div style="font-family:Roboto; text-align:Center;font-size:20px; line-height:2">Associate Details</div>
                <section class="box" style="padding: 2%;">
<table class="table">
<thead>
    <tr>
         <th>Photo</th>
         <th>Volunteer Details</th>
         <th>Role</th>
         <th>Designation</th>
         <th>Service Period</th>
         <th>IPF<br>(Individual Performance Factor)/5</th>
         <th>Current Status</th>
         <th>Certificate Date</th>
         <th>Certifying Authority</th>
        </tr>
        </thead>' ?>

<?php if (@$id > 0) {

    foreach ($resultArr as $array) {
        echo '<tbody><tr>
            <td><img src="' . $array['photo'] . '" width=100px/></td>
            <td style="line-height:2">Name - <b>' . $array['fullname'] . '</b><br>Associate ID - <b>' . $array['associatenumber'] . '</b></td>
            <td>' . $array['engagement'] . '</td>
            <td>' . substr($array['position'], 0, strrpos($array['position'], '-')) . '</td>
            <td style="line-height:2">' . $array['originaldoj'] . '&nbsp;to&nbsp;' ?>

        <?php if ($array['associationstatus'] != null) { ?>
            <?php echo $array['effectivedate'] ?>
        <?php } else { ?> <?php echo 'Present' ?>
        <?php } ?>



        <?php echo  '<br>' . $array['yos'] . '</td>
            <td>' . $array['ipfl'] . '</td>
            <td>' . $array['astatus'] . '</td>' ?>

        <?php if ($array['associationstatus'] != null) { ?>
            <?php echo '<td>' . $array['effectivedate'] . '</td>' ?>
        <?php } else { ?> <?php echo '<td>' . $today = date('d/m/Y') . '</td>' ?>
        <?php } ?>

<?php echo '<td>' . $array['exitinterview'] . '</td>
            </tr>';
    }
} ?>
</section>
</div>
</section>
</section>
