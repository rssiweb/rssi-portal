<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}

if ($password_updated_by == null || $password_updated_on < $default_pass_updated_on) {

    echo '<script type="text/javascript">';
    echo 'window.location.href = "defaultpasswordreset.php";';
    echo '</script>';
}

if ($filterstatus != 'Active') {

    //header("Location: javascript:history.back()"); //redirect to the login page to secure the welcome page without login access.
    echo '<script type="text/javascript">';
    echo 'alert("Access Denied. You are not authorized to access this web page.");';
    echo 'window.location.href = "home.php";';
    echo '</script>';
}

// Set the new timezone
date_default_timezone_set('Asia/Kolkata');
$date = date('Y-d-m h:i:s');
//echo $date;

// @$category = $_GET['get_category'];
// @$subject = $_GET['get_subject'];
// @$year = $_GET['get_year'];
// @$exam = $_GET['get_exam'];

// if ($category != 'ALL' && $subject != 'ALL' && $year != 'ALL' && $exam == 'ALL') {
//     $result = pg_query($con, "SELECT * FROM question WHERE category='$category' AND subject='$subject' AND year='$year'");
// } else if ($category != 'ALL' && $subject != 'ALL' && $year != 'ALL' && $exam == null) {
//     $result = pg_query($con, "SELECT * FROM question WHERE category='$category' AND subject='$subject' AND year='$year'");
// } else {
//     $result = pg_query($con, "SELECT * FROM question WHERE category='$category' AND subject='$subject' AND year='$year' AND examname='$exam'");
// }

// if (!$result) {
//     echo "An error occurred.\n";
//     exit;
// }

// $resultArr = pg_fetch_all($result);
$category = isset($_GET['get_category']) ? $_GET['get_category'] : null;
$subject = isset($_GET['get_subject']) ? $_GET['get_subject'] : null;
$year = isset($_GET['get_year']) ? $_GET['get_year'] : null;
$exam = isset($_GET['get_exam']) ? $_GET['get_exam'] : null;

// Build the SQL query dynamically based on the filter values
$sql = "SELECT * FROM question WHERE 1=1";

if ($category !== null && $category !== 'ALL') {
    $sql .= " AND category='$category'";
}

if ($subject !== null && $subject !== 'ALL') {
    $sql .= " AND subject='$subject'";
}

if ($year !== null && $year !== 'ALL') {
    $sql .= " AND year='$year'";
}

if ($exam !== null && $exam !== 'ALL') {
    $sql .= " AND examname='$exam'";
}
// Add an ORDER BY clause
$sql .= " ORDER BY year desc"; // Replace 'your_column_name' with the column you want to order by

if ($category === null && $subject === null && $year === null && $exam === null) {
    $resultArr = null; // No filters selected, result is null
} else {
    $result = pg_query($con, $sql);

    if (!$result) {
        echo "An error occurred.\n";
        exit;
    }

    $resultArr = pg_fetch_all($result);
}

?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Question paper</title>
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>

    <script src="https://cdn.jsdelivr.net/gh/manucaralmo/GlowCookies@3.0.1/src/glowCookies.min.js"></script>
    <!-- Glow Cookies v3.0.1 -->
    <script>
        glowCookies.start('en', {
            analytics: 'G-S25QWTFJ2S',
            //facebookPixel: '',
            policyLink: 'https://www.rssi.in/disclaimer'
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
            width: 10%;
        }

        #cw1 {
            width: 15%;
        }

        #cw2 {
            width: 20%;
        }

        #cw3 {
            width: 25%;
        }

        #btn {
            background-color: DodgerBlue;
            border: none;
            cursor: pointer;
        }

        /* Darker background on mouse-over */
        #btn:hover {
            background-color: #90BAA4;
        }

        .visited {
            background-color: #90BAA4;
        }

        a.disabled {
            pointer-events: none;
            cursor: default;
        }
    </style>

</head>

<body>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>My Allocation</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">My Services</a></li>
                    <li class="breadcrumb-item active">My Allocation</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section dashboard">
            <div class="row">

                <!-- Reports -->
                <div class="col-12">
                    <div class="card">

                        <div class="card-body">
                            <br>

                            <form action="" method="GET">
                                <div class="form-group" style="display: inline-block;">
                                    <div class="col2" style="display: inline-block;">
                                        <select name="get_category" class="form-select" style="width:max-content; display:inline-block">
                                            <?php if ($category == null) { ?>
                                                <option value="" disabled selected hidden>Select Category</option>
                                            <?php
                                            } else { ?>
                                                <option hidden selected><?php echo $category ?></option>
                                            <?php }
                                            ?>
                                            <option>LG2A</option>
                                            <option>LG2B</option>
                                            <option>LG3</option>
                                            <option>LG4</option>
                                            <option>LG4S1</option>
                                            <option>LG4S2</option>
                                            <option>WLG3</option>
                                            <option>WLG4S1</option>
                                            <option>ALL</option>
                                        </select>
                                        <select name="get_subject" class="form-select" style="width:max-content; display:inline-block">
                                            <?php if ($subject == null) { ?>
                                                <option value="" disabled selected hidden>Select Subject</option>
                                            <?php
                                            } else { ?>
                                                <option hidden selected><?php echo $subject ?></option>
                                            <?php }
                                            ?>
                                            <option> Hindi </option>
                                            <option> English </option>
                                            <option> Science </option>
                                            <option> Physics </option>
                                            <option> Physical science </option>
                                            <option> Chemistry </option>
                                            <option> Biology </option>
                                            <option> Life science </option>
                                            <option> Mathematics </option>
                                            <option> Social Science </option>
                                            <option> Accountancy </option>
                                            <option> Computer </option>
                                            <option> GK </option>
                                            <option> ALL </option>
                                        </select>
                                        <select name="get_year" id="get_year" class="form-select" style="width:max-content;display:inline-block">
                                            <?php if ($year == null) { ?>
                                                <option value="" disabled selected hidden>Select Year</option>
                                            <?php
                                            } else { ?>
                                                <option hidden selected><?php echo $year ?></option>
                                            <?php }
                                            ?>
                                        </select>
                                        <select name="get_exam" class="form-select" style="width:max-content;display:inline-block">
                                            <?php if ($exam == null) { ?>
                                                <option value="" disabled selected hidden>Select Exam</option>
                                            <?php
                                            } else { ?>
                                                <option hidden selected><?php echo $exam ?></option>
                                            <?php }
                                            ?>
                                            <option>First Term Exam</option>
                                            <option>Half Yearly Exam</option>
                                            <option>Annual Exam</option>
                                            <option>ALL</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col2 left" style="display: inline-block;">
                                    <button type="submit" name="search_by_id" class="btn btn-success btn-sm" style="outline: none;">
                                        <i class="bi bi-search"></i>&nbsp;Search</button>
                                </div>
                            </form>
                            <script>
                                <?php if (date('m') == 1 || date('m') == 2 || date('m') == 3) { ?>
                                    var currentYear = new Date().getFullYear() - 1;
                                <?php } else { ?>
                                    var currentYear = new Date().getFullYear();
                                <?php } ?>

                                for (var i = 0; i < 5; i++) {
                                    var next = currentYear + 1;
                                    var year = currentYear + '-' + next;
                                    //next.toString().slice(-2) 
                                    $('#get_year').append(new Option(year, year));
                                    currentYear--;
                                }
                            </script>
                            <?php
                            echo '<table class="table">
                                <thead>
                                <tr>
                                <th scope="col">Category</th>
                                <th scope="col">Subject</th>
                                <th scope="col">Test ID</th>
                                <th scope="col">Full marks</th>
                                <th scope="col">Exam name</th>
                                <th scope="col">Password</th>
                                <th scope="col">Question paper</th>
                                </tr>
                                </thead>' ?>
                            <?php if (is_array($resultArr) && sizeof($resultArr) > 0) { ?>
                                <?php
                                echo '<tbody>';
                                foreach ($resultArr as $array) {
                                    echo '<tr>
                                <td>' . $array['category'] . '</td>
                                <td>' . $array['subject'] . '</td>
                                <td>' . $array['testcode'] . '&nbsp; <p class="badge bg-secondary">' . $array['class'] . '</p></td>
                                <td>' . $array['fullmarks'] . '</td>
                                <td>' . $array['examname'] . '-' . $array['year'] . '</td>
                                <td>' . $array['topic'] . '</td>
                                <td><a href="' . $array['url'] . '" target="_blank"><button type="button" id="btn" class="btn btn-sm" style="outline: none; color:#fff"><span class="glyphicon glyphicon-eye-open" aria-hidden="true"></span> Question</button></a></td>
                                </tr>';
                                } ?>
                            <?php
                            } else if ($resultArr = null) {
                            ?>
                                <tr>
                                    <td colspan="7">Please select Filter value.</td>
                                </tr>
                            <?php
                            } else {
                            ?>
                                <tr>
                                    <td colspan="5">No record found for <?php echo $category ?> <?php echo $subject ?> <?php echo $year ?> <?php echo $exam ?></td>
                                </tr>
                            <?php }

                            echo '</tbody>
                        </table>';
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>
</body>

</html>