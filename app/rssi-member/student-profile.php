<?php
session_start();
// Storing Session
include("../util/login_util.php");

if (!isLoggedIn("aid")) {
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
@$id = $_GET['get_id'];
date_default_timezone_set('Asia/Kolkata');
$date = date('Y-m-d H:i:s');
?>

<?php
include("database.php");
$result = pg_query($con, "select * from rssimyprofile_student WHERE student_id='$id'"); //select query for viewing users.    
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
        <?php include '../css/style.css'; ?>@media print {
            @page {
                size: A4 landscape;
            }
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

                        <input name="get_id" class="form-control" style="width:max-content; display:inline-block" placeholder="Student Id" value="<?php echo $id ?>" required>
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
                    <img class="qrimage" src="https://chart.googleapis.com/chart?chs=85x85&cht=qr&chl=https://login.rssi.in/rssi-student/verification.php?get_id=<?php echo $id ?>" width="74px" />
                </div>

                <?php foreach ($resultArr as $array) {

                    echo '<table class="table">
                    <thead style="font-size: 12px;">
                        <tr>
                            <th scope="col">Photo</th>
                            <th scope="col">Student Details</th>
                            <th scope="col">Profile Status</th>
                            <th scope="col" class="no-print">Badge</th>
                        </tr>
                    </thead>
                    <tbody>
                    <tr>
                            <td><img src= ' . $array['photourl'] . ' width=75px /></td>
                            <td style="line-height: 1.7;"><b>' . $array['studentname'] . '</b><br>Student ID - <b>' . $array['student_id'] . '</b>, Roll No - <b>' . $array['roll_number'] . '</b><br>
                                <span style="line-height: 3;">' . $array['gender'] . '(' . $array['age'] . 'Years)</span>
                            </td>
                            <td>' . $array['filterstatus'] . '<br><br>' . $array['remarks1'] . '</td>
                            <td class="no-print">' . $array['badge'] . '</td>
                        </tr>
                    </tbody>
                </table>

                <table class="table">
                    <thead style="font-size: 12px;">
                        <tr>
                            <th scope="col">Admission Date</th>
                            <th scope="col">Preferred Branch of RSSI</th>
                            <th scope="col">Class/Category</th>
                            <th scope="col">Date of Birth</th>
                            <th scope="col">Student Aadhaar</th>
                            <th scope="col" class="no-print">Aadhaar Card</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>' . $array['doa'] . '</td>
                            <td>' . $array['preferredbranch'] . '</td>
                            <td>' . $array['class'] . '/' . $array['category'] . '</td>
                            <td>' . $array['dateofbirth'] . '</td>
                            <td>' . $array['studentaadhar'] . '</td>' ?>

                    <?php if ($array['upload_aadhar_card'] != null) {

                        echo '<td class="no-print"><iframe sandbox="allow-forms allow-modals allow-orientation-lock allow-pointer-lock allow-presentation allow-same-origin allow-scripts allow-top-navigation allow-top-navigation-by-user-activation" src="https://drive.google.com/file/d/' . substr(@$array['upload_aadhar_card'], strpos(@$array['upload_aadhar_card'], "=") + 1) . '/preview" width="300px" height="200px" /></iframe></td>' ?>
                        <?php  } else {
                        echo '<td class="no-print">No document uploaded.</td>'
                        ?><?php }
                        echo '</tr></tbody>
                </table>

                <table class="table">
                    <thead style="font-size: 12px;">
                        <tr>
                            <th scope="col">Guardians Name</th>
                            <th scope="col">Guardian Aadhaar</th>
                            <th scope="col">Postal Address</th>
                            <th scope="col">Contact/Email Address</th>
                            <th scope="col">Family monthly income</th>
                            <th scope="col">Total number of family members</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>' . $array['guardiansname'] . ' - ' . $array['relationwithstudent'] . '</td>
                            <td>' . $array['guardianaadhar'] . '</td>
                            <td>' . $array['postaladdress'] . '</td>
                            <td style="line-height: 1.5;">' . $array['contact'] . '<br>' . $array['emailaddress'] . '</td>
                            <td>' . $array['familymonthlyincome'] . '</td>
                            <td>' . $array['totalnumberoffamilymembers'] . '</td>
                        </tr>
                    </tbody>
                </table>
                <table class="table">
                    <thead style="font-size: 12px;">
                        <tr>
                            <th scope="col">School Admission Required</th>
                            <th scope="col">Name Of The Subjects</th>
                            <th scope="col">Name Of The School</th>
                            <th scope="col">Name Of The Board</th>
                            <th scope="col">Medium</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>' . $array['schooladmissionrequired'] . '</td>
                            <td>' . $array['nameofthesubjects'] . '</td>
                            <td>' . $array['nameoftheschool'] . '</td>
                            <td>' . $array['nameoftheboard'] . '</td>
                            <td>' . $array['medium'] . '</td>
                        </tr>
                    </tbody>
                </table><br>

                <p style="text-align:right;">Admission form generated:' ?><?php echo $date ?><?php echo '</p>' ?>

                    <?php }
            } else { ?>
                    <p class="no-print">Please enter student ID.</p> <?php } ?>
        </section>
    </div>
</body>

</html>