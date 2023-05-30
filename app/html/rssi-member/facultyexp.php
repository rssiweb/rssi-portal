<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

if ($password_updated_by == null || $password_updated_on < $default_pass_updated_on) {

    echo '<script type="text/javascript">';
    echo 'window.location.href = "defaultpasswordreset.php";';
    echo '</script>';
}

if ($role != 'Admin') {

    //header("Location: javascript:history.back()"); //redirect to the login page to secure the welcome page without login access.
    echo '<script type="text/javascript">';
    echo 'alert("Access Denied. You are not authorized to access this web page.");';
    echo 'window.location.href = "home.php";';
    echo '</script>';
}
?>

<?php

@$id = $_POST['get_id'];
$result = pg_query($con, "SELECT * FROM rssimyaccount_members WHERE filterstatus='$id' and position LIKE '%-Faculty%' order by filterstatus asc,today desc");
if (!$result) {
    echo "An error occurred.\n";
    exit;
}

$resultArr = pg_fetch_all($result);
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>RSSI Faculty Experience</title>

    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/gh/manucaralmo/GlowCookies@3.0.1/src/glowCookies.min.js"></script>
    <!-- Glow Cookies v3.0.1 -->
    <script>
        glowCookies.start('en', {
            analytics: 'G-S25QWTFJ2S',
            //facebookPixel: '',
            policyLink: 'https://www.rssi.in/disclaimer'
        });
    </script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
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
    </style>

</head>

<body>

    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>RSSI Faculty Experience</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Work</a></li>
                    <li class="breadcrumb-item"><a href="faculty.php">RSSI Faculty</a></li>
                    <li class="breadcrumb-item active">RSSI Faculty Experience</li>
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

                            <div class="col" style="display: inline-block;">
                                Record count:&nbsp;<?php echo sizeof($resultArr) ?>
                            </div>

                            <form action="" method="POST">
                                <div class="form-group" style="display: inline-block;">
                                    <div class="col2" style="display: inline-block;">
                                        <select name="get_id" class="form-select" style="width:max-content;" placeholder="Appraisal type" required>
                                            <?php if ($id == null) { ?>
                                                <option value="" disabled selected hidden>Select Status</option>
                                            <?php
                                            } else { ?>
                                                <option hidden selected><?php echo $id ?></option>
                                            <?php }
                                            ?>
                                            <option>Active</option>
                                            <!--<option>Inactive</option>-->
                                        </select>
                                    </div>
                                </div>
                                <div class="col2 left" style="display: inline-block;">
                                    <button type="submit" name="search_by_id" class="btn btn-success btn-sm" style="outline: none;">
                                        <i class="bi bi-search"></i>&nbsp;Search</button>
                                </div>
                            </form>
                            <?php
                            echo '
                            <div class="table-responsive">
                            <table class="table">
                            <thead>
                            <tr>
                            <th scope="col" id="cw">Photo</th>
                            <th scope="col" id="cw1">Name</th>
                            <th scope="col" id="cw2">Highest Educational Qualifications</th>
                            <th scope="col" id="cw3">Area of specialization</th>
                            <th scope="col">Work Experience</th>
                            </tr>
                            </thead>' ?>
                            <?php if (sizeof($resultArr) > 0) { ?>
                                <?php
                                echo '<tbody>';
                                foreach ($resultArr as $array) {
                                    echo '<tr>' ?>
                                    <?php if ($array['disc'] == null) { ?>
                                        <?php echo '<td><img src="' . $array['photo'] . '" width=50px/></td>' ?>
                                    <?php } else { ?> <?php echo '<td><img src="https://res.cloudinary.com/hs4stt5kg/image/upload/v1609410219/faculties/blank.jpg" width=50px/></td>' ?>
                                    <?php } ?>
                                    <?php echo '<td>' . $array['fullname']  ?>

                                    <?php if ($array['on_leave'] != null) { ?>
                                        <?php echo '<br><span class="badge label-danger">on leave</span>'
                                        ?>
                                    <?php    } else {
                                    } ?>
                                <?php
                                    echo '
                                    </td><td>' . $array['eduq'] . '</td>
                                    <td>' . $array['mjorsub'] . '</td>
                                    <td>' . $array['workexperience'] . '</td>
                                    </tr>';
                                } ?>
                            <?php
                            } else if ($id == "") {
                            ?>
                                <tr>
                                    <td colspan="5">Please select Status.</td>
                                </tr>
                            <?php
                            } else {
                            ?>
                                <tr>
                                    <td colspan="5">No record found for <?php echo $id ?></td>
                                </tr>
                            <?php }

                            echo '</tbody>
                        </table>
                        </div>';
                            ?>
                        </div>
                    </div>
                </div><!-- End Reports -->
            </div>
        </section>

    </main><!-- End #main -->

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>

</body>