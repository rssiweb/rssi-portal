<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}
validation();

@$id = strtoupper($_GET['get_id']);
date_default_timezone_set('Asia/Kolkata');
$date = date('Y-m-d H:i:s');

$result = pg_query($con, "SELECT * FROM rssimyprofile_student WHERE student_id='$id'");
$resultArr = pg_fetch_all($result);

if (!$result) {
    echo "An error occurred.\n";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="description" content="">
    <meta name="author" content="">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Filled Admission Form</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto&display=swap');
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@500&display=swap');
        @import url('https://fonts.googleapis.com/css2?family=Comfortaa');
        @import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@600&display=swap');

        /* General Styles */
        body {
            background: #ffffff;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
        }

        /* Responsive Margin Adjustments */
        @media (min-width: 768px) {

            /* Adjusted min-width for better practice */
            .top {
                margin-top: 2%;
            }
        }

        @media (max-width: 767px) {
            .top {
                margin-top: 10%;
            }

            .topbutton {
                margin-top: 5%;
            }
        }

        /* Print Styles */
        @media print {
            .noprint {
                visibility: hidden;
                position: absolute;
                left: 0;
                top: 0;
            }

            .footer {
                position: fixed;
                bottom: 0;
            }
        }

        /* Screen Styles */
        @media screen {
            .no-display {
                display: none;
            }
        }

        /* Footer */
        .report-footer {
            position: fixed;
            bottom: 0;
            height: 20px;
            width: 90%;
            border-top: 1px solid #ccc;
            overflow: visible;
        }

        /* Box Styles */
        .value,
        .box {
            display: inline-block;
            min-width: 100px;
            padding: 2px 4px;
            margin-bottom: 10px;
            text-align: center;
        }

        .value {
            border-bottom: 1px dashed #000;
        }

        .box {
            border: 1px solid #000;
        }

        /* Colored Area */
        .colored-area {
            background-color: #f2f2f2 !important;
            padding: 1px;
        }

        .signature-container {
            margin-top: 10px;
            /* Space between text and line */
            padding-top: 5px;
            /* Space between line and text */
            border-top: 1px solid black;
            /* Line above the text */
            display: inline-block;
        }

        .signature-section {
            margin-right: 20px;
            /* Space between signature sections */
            display: inline-block;
        }
    </style>
    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
    <script src="https://kit.fontawesome.com/58c4cdb942.js" crossorigin="anonymous"></script>
</head>

<body>
    <div class="container">
        <form action="" method="GET" class="noprint" style="display: flex; justify-content: right; gap: 10px; margin-bottom: 20px;">
            <input name="get_id" class="form-control" placeholder="Enter Student ID" value="<?php echo $id ?>" required style="width: 200px; padding: 5px;" />
            <button type="submit" name="search_by_id" class="btn btn-success" style="padding: 5px 10px;">Search</button>
            <button type="button" onclick="window.print()" name="print" class="btn btn-info" style="padding: 5px 10px;">Print</button>
        </form>

        <?php if ($resultArr) { ?>
            <?php foreach ($resultArr as $array) { ?>

                <table class="table" border="0">
                    <thead> <!--class="no-display"-->
                        <tr>
                            <td colspan=4>
                                <div class="row">
                                    <div class="col" style="display: inline-block; width:65%;">

                                        <?php
                                        if ($array['category'] == 'LG1') {
                                            echo '<p><b>KALPANA BUDS SCHOOL</b></p>';
                                        } else {
                                            echo '<p><b>RSSI NGO</b></p>';
                                        }
                                        ?>
                                        <p>(A division of Rina Shiksha Sahayak Foundation)</p>
                                        <p>NGO-DARPAN Id: WB/2021/0282726, CIN: U80101WB2020NPL237900</p>
                                        <p>Email: info@rssi.in, Website: www.rssi.in</p>
                                    </div>
                                    <div class="col" style="display: inline-block; width:32%; vertical-align: top;">
                                        <p style="font-size: small;">Scan QR code to check authenticity</p>
                                        <?php
                                        $student_id = $array['student_id'];
                                        $url = "https://login.rssi.in/rssi-student/verification.php?get_id=$student_id";
                                        $url_u = urlencode($url); ?>
                                        <img class="qrimage" src="https://qrcode.tec-it.com/API/QRCode?data=<?php echo $url_u ?>" width="80px" />&nbsp;
                                        <img src=<?php echo $array['photourl'] ?> width=80px height=80px />
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <div class="row colored-area">
                                    <h4 style="margin-left: 5px;">Student Information</h4>
                                </div>
                                <br>
                                <div>
                                    <span>Student ID:</span>
                                    <span class="value"><?php echo $array['student_id'] ?></span>
                                </div>
                                <div>
                                    <span>Full Name:</span>
                                    <span class="value"><?php echo $array['studentname'] ?></span>
                                </div>
                                <div>
                                    <span>Date of Birth (dd/mm/yyyy):</span>
                                    <span class="value"><?php echo (new DateTime($array['dateofbirth']))->format('d/m/Y'); ?></span>
                                </div>
                                <div>
                                    <span>Student's Aadhaar:</span>
                                    <span class="value"><?php if ($array['studentaadhar'] != null) {
                                                            echo substr_replace($array['studentaadhar'], str_repeat("X", 4), 4, 4);
                                                        } ?></span>
                                </div>
                                <div>
                                    <span>Guardian's Name:</span>
                                    <span class="value"><?php echo $array['guardiansname'] ?>&nbsp;(<?php echo $array['relationwithstudent'] ?>)</span>
                                </div>
                                <div>
                                    <span>Gender:</span>
                                    <span class="box"><?php echo $array['gender'] ?></span>
                                </div>
                                <div>
                                    <span>Category:</span>
                                    <span class="box"><?php echo $array['caste'] ?></span>
                                </div>

                                <div class="row colored-area">
                                    <h4 style="margin-left: 5px;">Contact Details</h4>
                                </div>
                                <br>
                                <div>
                                    <span>Address:</span>
                                    <span class="value"><?php echo $array['postaladdress'] ?></span>
                                </div>

                                <div>
                                    <span>Phone:</span>
                                    <span class="value"><?php echo $array['contact'] ?></span>
                                </div>
                                <div>
                                    <span>Email:</span>
                                    <span class="value"><?php echo $array['emailaddress'] ?></span>
                                </div>


                                <div class="row colored-area">
                                    <h4 style="margin-left: 5px;">Education Details</h4>
                                </div>
                                <br>
                                <div>
                                    <span>School Name:</span>
                                    <span class="value"><?php echo $array['nameoftheschool'] ?></span>
                                </div>
                                <div>
                                    <span>Medium:</span>
                                    <span class="value"><?php echo $array['medium'] ?></span>
                                </div>
                                <div>
                                    <span>Class:</span>
                                    <span class="value"><?php echo $array['class'] ?></span>
                                </div>
                                <div>
                                    <span>Preferred Branch:</span>
                                    <span class="value"><?php echo $array['preferredbranch'] ?></span>
                                </div>
                            </td>
                        <tr>
                            <td>
                                <div>
                                    <span>Registered in system:</span>
                                    <span class="value"><?php echo htmlspecialchars($array['doa'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>
                                <br><br>
                                <div>
                                    <div class="signature-section">Signature of Parent/Guardian</div>
                                    <div class="signature-section">Signature of Centre Incharge with office seal</div>
                                    <div class="signature-section">School seal</div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
    </div>
<?php } ?>
<?php } else { ?>
    <p>No records found for Student ID: <?php echo $id; ?></p>
<?php } ?>
</body>

</html>