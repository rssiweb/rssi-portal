<?php
session_start();
// Storing Session
include("../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}
if ($_SESSION['role'] != 'Admin') {
    echo '<script type="text/javascript">';
    echo 'alert("Access Denied. You are not authorized to access this web page.");';
    echo 'window.location.href = "home.php";';
    echo '</script>';
}
?>

<?php
include("member_data.php");
@$id = strtoupper($_GET['get_id']);
date_default_timezone_set('Asia/Kolkata');
$date = date('Y-m-d H:i:s');
?>

<?php
include("database.php");
$result = pg_query($con, "select * from rssimyaccount_members WHERE associatenumber='$id'"); //select query for viewing users.    
$resultArr = pg_fetch_all($result);

if (!$result) {
    echo "An error occurred.\n";
    exit;
}

?>

<!DOCTYPE html>
<html>

<head>
    <meta name="description" content="">
    <meta name="author" content="">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title><?php echo $id ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <!-- Main css -->
    <style>
        <?php include '../css/style.css'; ?>;

        @page {
            size: A4 landscape;
        }

        table {
            page-break-inside: avoid
        }
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

<body style="margin: 0mm;">
    <div class="col-md-12">

        <section class="box" style="padding: 2%;">

            <form action="" method="GET" class="no-print">
                <div class="form-group" style="display: inline-block;">
                    <div class="col2" style="display: inline-block;">

                        <input name="get_id" class="form-control" style="width:max-content; display:inline-block" placeholder="Associate Id" value="<?php echo $id ?>" required>
                    </div>
                </div>
                <div class="col2 left" style="display: inline-block;">
                    <button type="submit" name="search_by_id" class="btn btn-success btn-sm" style="outline: none;">
                        <i class="fa-solid fa-magnifying-glass"></i>&nbsp;Search</button>
                    <button type="button" onclick="window.print()" name="print" class="btn btn-info btn-sm" style="outline: none;"><i class="fa-regular fa-floppy-disk"></i>&nbsp;Save</button>
                </div><br><br>
            </form>
            <?php if ($resultArr != null) { ?>
                <div class="col" style="display: inline-block; width:55%; text-align:left">

                    <p><b>Rina Shiksha Sahayak Foundation (RSSI)</b></p>
                    <p>1074/801, Jhapetapur, Backside of Municipality, West Midnapore, West Bengal 721301</p>
                </div>
                <div class="col" style="display: inline-block; width:42%;margin-left:1.5%;text-align:right;">
                    <img class="qrimage" src="https://chart.googleapis.com/chart?chs=85x85&cht=qr&chl=https://login.rssi.in/rssi-member/verification.php?get_id=<?php echo $id ?>" width="74px" />
                </div>

                <?php foreach ($resultArr as $array) {

                    echo '<table class="table">
                    <thead style="font-size: 12px;">
                        <tr>
                            <th scope="col">Photo</th>    
                            <th scope="col">Associate Details</th>
                            <th scope="col">Date of Join</th>
                            <th scope="col">Association Status</th>
                            <th scope="col" class="no-print">Badge</th>
                        </tr>
                    </thead>
                    <tbody>
                    <tr>
                            <td><img src= ' . $array['photo'] . ' width=75px /></td>
                            <td style="line-height: 1.7;"><b>' . $array['fullname'] . '</b><br>Associate ID - <b>' . $array['associatenumber'] . '</b><br><span style="line-height: 3;">' . $array['engagement'] . ',&nbsp;' . $array['gender'] . '&nbsp;(' . $array['age'] . '&nbsp;Years)</span>
                            </td>
                            <td>' . $array['doj'] . '</td>
                            <td>' . $array['filterstatus'] . '<br><br>' . $array['remarks'] . '</td>
                            <td class="no-print">' . $array['badge'] . '</td>
                        </tr>
                    </tbody>
                </table>

                <table class="table">
                    <thead style="font-size: 12px;">
                        <tr>
                            <th scope="col">Date of Birth</th>
                            <th scope="col" class="no-print">National Identifier</th>
                            <th scope="col">Last 4 digits of Identifier</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>' . $array['dateofbirth'] . '</td>' ?>

                    <?php if ($array['iddoc'] != null) {

                        echo '<td class="no-print">
                        <iframe sandbox="allow-forms allow-modals allow-orientation-lock allow-pointer-lock allow-presentation allow-same-origin allow-scripts allow-top-navigation allow-top-navigation-by-user-activation" src="' . $array['iddoc'] . '" width="300px" height="200px" /></iframe></td>' ?>
                        <?php  } else {
                        echo '<td class="no-print">No document uploaded.</td>'
                        ?><?php }
                        echo '
                            <td>' . $array['identifier'] . '</td>' ?>

                        <?php echo '</tr></tbody>
                </table>

                <table class="table">
                    <thead style="font-size: 12px;">
                        <tr>
                            <th scope="col">Application Number</th>
                            <th scope="col">Designation</th>
                            <th scope="col">Base Branch</th>
                            <th scope="col">Deputed Branch</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>' . $array['applicationnumber'] . '</td>
                            <td>' . substr($array['position'], 0, strrpos($array['position'], "-")) . '</td>
                            <td>' . $array['basebranch'] . '</td>
                            <td>' . $array['depb'] . '</td>
                        </tr>
                    </tbody>
                </table>
               <table class="table">
                    <thead style="font-size: 12px;">
                        <tr>
                            <th scope="col">Current Address</th>
                            <th scope="col">Permanent Address</th>
                            <th scope="col">Contact/Email Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>' . $array['currentaddress'] . '</td>
                            <td>' . $array['permanentaddress'] . '</td>
                            <td>' . $array['phone'] . '<br>' . $array['email'] . '</td>
                        </tr>
                    </tbody>
                </table>
                
                <table class="table">
                    <thead style="font-size: 12px;">
                        <tr>
                            <th scope="col">Language Details</th>
                            <th scope="col">Educational qualifications</th>
                            <th scope="col">Area of specialization</th>
                            <th scope="col">Work Experience</th>
                            <th scope="col">Account Approved by</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>English - ' . $array['languagedetailsenglish'] . '<br>Hindi - ' . $array['languagedetailshindi'] . '</td>
                            <td>' . $array['eduq'] . '</td>
                            <td>' . $array['mjorsub'] . '</td>
                            <td>' . $array['workexperience'] . '</td>
                            <td>' . $array['approvedby'] . '</td>
                        </tr>
                    </tbody>
                </table>
                
                
                
                <br>

                <p style="text-align:right;">Document generated:' ?><?php echo $date ?><?php echo '</p>' ?>

                    <?php }
            } else { ?>
                    <p class="no-print">Please enter Associate ID.</p> <?php } ?>
        </section>
    </div>
</body>

</html>