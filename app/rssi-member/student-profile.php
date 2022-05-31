<?php
session_start();
// Storing Session
include("../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}
if ($_SESSION['role'] != 'Admin' && $_SESSION['role'] != 'Offline Manager') {
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
        <?php include '../css/style.css'; ?>;

        table {
            page-break-inside: avoid;
        }

        @media screen {
            .no-display {
                display: none;
            }
        }

        @media print {
            .footer {
                position: fixed;
                bottom: 0;
            }

            .no-print,
            .no-print * {
                display: none !important;
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

<body>
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

                <?php foreach ($resultArr as $array) {

                    echo '
    <table class="table" border="0">
        <thead style="font-size: 12px;" class="no-display">
            <tr>
            <td colspan=8>
            <div class="col" style="display: inline-block; width:55%; text-align:left;">

            <p><b>Rina Shiksha Sahayak Foundation (RSSI)</b></p>
            <p>1074/801, Jhapetapur, Backside of Municipality, West Midnapore, West Bengal 721301</p>
            </div>
            <div class="col" style="display: inline-block; width:42%;margin-left:1.5%;text-align:right;">
                <img class="qrimage" src="https://chart.googleapis.com/chart?chs=85x85&cht=qr&chl=https://login.rssi.in/rssi-member/verification.php?get_id=<?php echo $id ?>" width="74px" />
            </div>
            </td>
            </tr>
        </thead>
                        <tr>
                            <th >Photo</th>
                            <th  colspan=4>Student Details</th>
                            <th>Profile Status</th>
                            <th colspan=2>Badge</th>
                        </tr>
                    </thead>
                    <tbody>
                    <tr>
                            <td><img src= ' . $array['photourl'] . ' width=75px /></td>
                            <td colspan=4 style="line-height: 1.7;"><b>' . $array['studentname'] . '</b><br>Student ID - <b>' . $array['student_id'] . '</b>, Roll No - <b>' . $array['roll_number'] . '</b><br>
                                <span style="line-height: 3;">' . $array['gender'] . '&nbsp;(' . $array['age'] . '&nbsp;Years)</span>
                            </td>
                            <td>' . $array['filterstatus'] . '<br><br>' . $array['remarks1'] . '</td>
                            <td colspan=2>' . $array['badge'] . '</td>
                        </tr>
                    
                        <tr>
                            <th >Admission Date</th>
                            <th>Preferred Branch</th>
                            <th>Class/Category</th>
                            <th >Date of Birth</th>
                            <th>Student Aadhaar</th>
                            <th colspan=3>Aadhaar Card</th>
                        </tr>
                    
                        <tr>
                            <td>' . $array['doa'] . '</td>
                            <td>' . $array['preferredbranch'] . '</td>
                            <td>' . $array['class'] . '/' . $array['category'] . '</td>
                            <td>' . $array['dateofbirth'] . '</td>
                            <td>' . $array['studentaadhar'] . '</td>' ?>

                    <?php if ($array['upload_aadhar_card'] != null) {

                        echo '<td  colspan=3><iframe sandbox="allow-forms allow-modals allow-orientation-lock allow-pointer-lock allow-presentation allow-same-origin allow-scripts allow-top-navigation allow-top-navigation-by-user-activation" src="https://drive.google.com/file/d/' . substr(@$array['upload_aadhar_card'], strpos(@$array['upload_aadhar_card'], "=") + 1) . '/preview" width="300px" height="200px"/></iframe></td>' ?>
                        <?php  } else {
                        echo '<td colspan=3>No document uploaded.</td>'
                        ?><?php }
                        echo '</tr></tbody>
                
                        <tr>
                            <th colspan=2>Guardians Details</th>
                            <th colspan=3>Postal Address</th>
                            <th >Contact/Email Address</th>
                            <th>Family monthly income</th>
                            <th >Family members</th>
                        </tr>

                        <tr>
                            <td colspan=2>' . $array['guardiansname'] . ' - ' . $array['relationwithstudent'] . '<br>' . $array['guardianaadhar'] . '</td>
                            <td colspan=3>' . $array['postaladdress'] . '</td>
                            <td style="line-height: 1.5;">' . $array['contact'] . '<br>' . $array['emailaddress'] . '</td>
                            <td>' . $array['familymonthlyincome'] . '</td>
                            <td>' . $array['totalnumberoffamilymembers'] . '</td>
                        </tr>

                        <tr>
                            <th  colspan=5>Name Of The Subjects</th>
                            <th  colspan=3>School Admission Required</th>    
                        </tr>
                
                        <tr>
                            <td colspan=5>' . $array['nameofthesubjects'] . '</td>
                            <td colspan=3>' . $array['schooladmissionrequired'] . '</td>    
                        </tr>

                        <tr>    
                            <th  colspan=5>School Name</th>
                            <th  colspan=2>Name Of The Board</th>
                            <th >Medium</th>
                        </tr>
                    
                        <tr> 
                            <td colspan=5>' . $array['nameoftheschool'] . '</td>
                            <td colspan=2>' . $array['nameoftheboard'] . '</td>
                            <td>' . $array['medium'] . '</td>
                        </tr>
                        </table>
                         
                        <div class="footer no-display">
                        <p style="text-align:right;">Admission form generated:&nbsp;' ?><?php echo $date ?><?php echo '</p>
                        </div>' ?>
                    <?php }
            } else { ?> <p class="no-print">Please enter Student ID.</p> <?php } ?>
        </section>
    </div>
</body>

</html>