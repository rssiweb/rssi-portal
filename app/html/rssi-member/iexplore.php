<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");


if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
}

validation();

date_default_timezone_set('Asia/Kolkata');

if ($role == 'Admin') {

    if ($_POST) {
        @$courseid = $_POST['courseid'];
        @$coursename = $_POST['coursename'];
        @$language = $_POST['language'];
        @$passingmarks = $_POST['passingmarks'];
        @$url = $_POST['url'];
        @$validity = $_POST['validity'];
        @$issuedby = $_POST['issuedby'];
        @$now = date('Y-m-d H:i:s');
        if ($courseid != "") {
            $wbt = "INSERT INTO wbt (date, courseid, coursename, language, passingmarks, url, issuedby,validity) VALUES ('$now','$courseid','$coursename','$language','$passingmarks','$url','$issuedby','$validity')";
            $result = pg_query($con, $wbt);
            $cmdtuples = pg_affected_rows($result);
        }
    }
}


@$courseid1 = trim($_GET['courseid1']);
@$language1 = $_GET['language1'];

if (($courseid1 == null && $language1 == 'ALL')) {
    $result1 = pg_query($con, "select * from wbt order by language desc");
} else if (($courseid1 == null && ($language1 != 'ALL' && $language1 != null))) {
    $result1 = pg_query($con, "select * from wbt where language='$language1' order by language desc");
} else if (($courseid1 != null && ($language1 == null || $language1 == 'ALL'))) {
    $result1 = pg_query($con, "select * from wbt where courseid='$courseid1' order by language desc");
} else if (($courseid1 != null && ($language1 != 'ALL' && $language1 != null))) {
    $result1 = pg_query($con, "select * from wbt where courseid='$courseid1' AND language='$language1' order by language desc");
} else {
    $result1 = pg_query($con, "select * from wbt order by language desc");
}
if (!$result1) {
    echo "An error occurred.\n";
    exit;
}

$resultArr1 = pg_fetch_all($result1);
?>

<!DOCTYPE html>
<html lang="en">

<head>
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=AW-11316670180"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'AW-11316670180');
</script>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <title>iExplore</title>
    <meta content="" name="description">
    <meta content="" name="keywords">

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
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>iExplore</h1>
            <nav>
                <!-- <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item active">iExplore</li>
                </ol> -->
                <div class="row">
                    <div class="col" style="display: inline-block; width:50%;">
                        <?php if ($role == 'Admin') { ?>
                            Home / iExplore Management System
                        <?php } else { ?>
                            Home / iExplore Web-based training (WBT)
                        <?php } ?>
                    </div>
                    <div class="col" style="display: inline-block; width:48%; text-align:right">
                        <a href="my_learning.php" target="_self" class="btn btn-danger btn-sm" role="button">My Learnings</a>
                    </div>
                </div>

            </nav>
        </div><!-- End Page Title -->

        <section class="section dashboard">
            <div class="row">

                <!-- Reports -->
                <div class="col-12">
                    <div class="card">

                        <div class="card-body">
                            <br>
                            <?php if ($role == 'Admin') { ?>

                                <?php if (@$courseid != null && @$cmdtuples == 0) { ?>
                                    <div class="alert alert-danger alert-dismissible text-center" role="alert">
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                        <i class="bi bi-exclamation-triangle"></i>
                                        <span>ERROR: Oops, something wasn't right.</span>
                                    </div>
                                <?php } else if (@$cmdtuples == 1) { ?>
                                    <div class="alert alert-success alert-dismissible text-center" role="alert">
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                        <i class="bi bi-check2-circle"></i>
                                        <span>Database has been updated successfully for course id <?php echo @$courseid ?>.</span>
                                    </div>
                                <?php } ?>
                                <form autocomplete="off" name="wbt" id="wbt" action="iexplore.php" method="POST">
                                    <div class="row">
                                        <div class="col">
                                            <div class="form-group">
                                                <input type="text" name="courseid" class="form-control" placeholder="Course ID" required>
                                                <small class="form-text text-muted">Course ID</small>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="form-group">
                                                <input type="text" name="coursename" class="form-control" placeholder="Course Name" required>
                                                <small class="form-text text-muted">Course Name</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col">
                                            <div class="form-group">
                                                <select name="language" class="form-select" required>
                                                    <option value="" disabled selected hidden>Language</option>
                                                    <option>English</option>
                                                    <option>Hindi</option>
                                                    <option>Bengali</option>
                                                </select>
                                                <small class="form-text text-muted">Language</small>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="form-group">
                                                <input type="number" name="passingmarks" max="100" accuracy="2" min="0" class="form-control" placeholder="Mastery Score" required>
                                                <small class="form-text text-muted">Mastery Score (%)</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col">
                                            <div class="form-group">
                                                <input type="url" name="url" class="form-control" placeholder="URL" required>
                                                <small class="form-text text-muted">URL</small>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="form-group">
                                                <select name="validity" class="form-select" required>
                                                    <option value="" disabled selected hidden>Validity</option>
                                                    <option>0.5</option>
                                                    <option>1</option>
                                                    <option>2</option>
                                                    <option>3</option>
                                                    <option>5</option>
                                                </select>
                                                <small class="form-text text-muted">Validity (Year)</small>
                                            </div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="issuedby" class="form-control" value="<?php echo $fullname ?>" required readonly>
                                    <button type="submit" name="search_by_id" class="btn btn-danger btn-sm">
                                        <i class="bi bi-plus-lg"></i>&nbsp;Add
                                    </button>
                                </form>

                                <br>
                            <?php } ?>
                            <?php if ($role != 'Admin') { ?>
                            <?php } ?>
                            <form action="" method="GET">
                                <div class="row">
                                    <div class="col">
                                        <div class="form-group">
                                            <input type="text" name="courseid1" class="form-control" placeholder="Course ID" value="<?php echo @$courseid1 ?>">
                                            <small class="form-text text-muted">Course ID</small>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="form-group">
                                            <select name="language1" class="form-select">
                                                <option value="" disabled selected hidden>Language</option>
                                                <?php if ($language1 == null) { ?>
                                                    <option>ALL</option>
                                                <?php } else { ?>
                                                    <option hidden selected><?php echo $language1 ?></option>
                                                <?php } ?>
                                                <option>English</option>
                                                <option>Hindi</option>
                                                <option>Bengali</option>
                                            </select>
                                            <small class="form-text text-muted">Language</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col">
                                        <button type="submit" name="search_by_id" class="btn btn-primary btn-sm">
                                            <i class="bi bi-search"></i>&nbsp;Search
                                        </button>
                                    </div>
                                </div>
                            </form>


                            <div class="col" style="display: inline-block; width:99%; text-align:right">
                                Record count:&nbsp;<?php echo sizeof($resultArr1) ?>
                            </div>
                            <?php echo '
                            <div class="table-responsive">
                    <table class="table" id="table-id">
                        <thead>
                            <tr>
                                <th scope="col">Course id</th>
                                <th scope="col">Course name</th>
                                <th scope="col">Language</th>
                                <th scope="col">Mastery Score</th>
                                <th scope="col">Validity (Year)</th>
                                <th scope="col">Assesment</th>
                            </tr>
                        </thead>' ?>
                            <?php if ($resultArr1 != null) {
                                echo '<tbody>';
                                foreach ($resultArr1 as $array) {
                                    echo '
                            <tr>
                                <td>' . $array['courseid'] . '</td>
                                <td>' . $array['coursename'] . '</td>
                                <td>' . $array['language'] . '</td>
                                <td>' . $array['passingmarks'] . '%</td>
                                <td>' . $array['validity'] . '</td>
                                <td><a href="' . $array['url'] ?><?php echo $associatenumber ?><?php echo '" target="_blank" title="' . $array['coursename'] . '-' . $array['language'] . '"><button type="button" id="btn" class="btn btn-warning btn-sm" style="outline: none; color:#fff"></span>Launch&nbsp;' . $array['courseid'] . '</button></a></td>
                            </tr>';
                                                                                            }
                                                                                        } else if ($courseid1 == null && $language1 == null) {
                                                                                            echo '<tr>
                                          <td colspan="5">Please enter at least one value to get the WBT details.</td>
                                      </tr>';
                                                                                        } else {
                                                                                            echo '<tr>
                                      <td colspan="5">No record found for' ?>&nbsp;<?php echo $courseid1 ?>&nbsp;<?php echo $language1 ?>
                        <?php echo '</td>
                                  </tr>';
                                                                                        }
                                                                                        echo '</tbody>
                        </table>
                        </div>';
                        ?>
                        </div>
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

</html>