<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

@$cid = $_GET['get_cid'];
@$wbtstatus = $_GET['wbtstatus'];

if ($role == 'Admin') {
    @$aid = $_GET['get_aid'];


    if ($aid != null && $cid == null && ($wbtstatus == null || $wbtstatus == 'ALL')) {
        $result = pg_query($con, "SELECT * FROM wbt_status 
        left join wbt ON wbt.courseid=wbt_status.courseid WHERE wassociatenumber='$aid' order by timestamp desc");
    } else if ($aid != null && $cid == null && ($wbtstatus == 'Completed')) {
        $result = pg_query($con, "SELECT * FROM wbt_status 
        left join wbt ON wbt.courseid=wbt_status.courseid WHERE wassociatenumber='$aid' AND passingmarks <= f_score * 100 order by timestamp desc");
    } else if ($aid != null && $cid == null && ($wbtstatus == 'Incomplete')) {
        $result = pg_query($con, "SELECT * FROM wbt_status 
        left join wbt ON wbt.courseid=wbt_status.courseid WHERE wassociatenumber='$aid' AND passingmarks > f_score * 100 order by timestamp desc");
    } else if ($aid == null && $cid != null && ($wbtstatus == null || $wbtstatus == 'ALL')) {
        $result = pg_query($con, "SELECT * FROM wbt_status left join wbt ON wbt.courseid=wbt_status.courseid WHERE wbt_status.courseid='$cid' order by timestamp desc");
    } else if ($aid == null && $cid != null && ($wbtstatus == 'Completed')) {
        $result = pg_query($con, "SELECT * FROM wbt_status left join wbt ON wbt.courseid=wbt_status.courseid WHERE wbt_status.courseid='$cid' AND passingmarks <= f_score * 100 order by timestamp desc");
    } else if ($aid == null && $cid != null && ($wbtstatus == 'Incomplete')) {
        $result = pg_query($con, "SELECT * FROM wbt_status left join wbt ON wbt.courseid=wbt_status.courseid WHERE wbt_status.courseid='$cid' AND passingmarks > f_score * 100 order by timestamp desc");
    } else if ($aid != null && $cid != null && ($wbtstatus == null || $wbtstatus == 'ALL')) {
        $result = pg_query($con, "SELECT * FROM wbt_status left join wbt ON wbt.courseid=wbt_status.courseid WHERE wbt_status.courseid='$cid' AND wassociatenumber='$aid' order by timestamp desc");
    } else if ($aid != null && $cid != null && $wbtstatus == 'Completed') {
        $result = pg_query($con, "SELECT * FROM wbt_status left join wbt ON wbt.courseid=wbt_status.courseid WHERE wbt_status.courseid='$cid' AND wassociatenumber='$aid' AND passingmarks <= f_score * 100 order by timestamp desc");
    } else if ($aid != null && $cid != null && $wbtstatus == 'Incomplete') {
        $result = pg_query($con, "SELECT * FROM wbt_status left join wbt ON wbt.courseid=wbt_status.courseid WHERE wbt_status.courseid='$cid' AND wassociatenumber='$aid' AND passingmarks > f_score * 100 order by timestamp desc");
    } else {
        $result = pg_query($con, "SELECT * FROM wbt_status 
        left join wbt ON wbt.courseid=wbt_status.courseid WHERE wbt_status.courseid='' order by timestamp desc");
    }
}
if ($role != 'Admin' && $cid != null && ($wbtstatus == null || $wbtstatus == 'ALL')) {
    $result = pg_query($con, "SELECT * FROM wbt_status left join wbt ON wbt.courseid=wbt_status.courseid WHERE wassociatenumber='$user_check' AND wbt_status.courseid='$cid' order by timestamp desc");
} else if ($role != 'Admin' && $cid == null && ($wbtstatus == null || $wbtstatus == 'ALL')) {
    $result = pg_query($con, "SELECT * FROM wbt_status left join wbt ON wbt.courseid=wbt_status.courseid WHERE wassociatenumber='$user_check' order by timestamp desc");
} else if ($role != 'Admin' && $cid == null && $wbtstatus == 'Completed') {
    $result = pg_query($con, "SELECT * FROM wbt_status left join wbt ON wbt.courseid=wbt_status.courseid WHERE wassociatenumber='$user_check' AND passingmarks <= f_score * 100 order by timestamp desc");
} else if ($role != 'Admin' && $cid == null && $wbtstatus == 'Incomplete') {
    $result = pg_query($con, "SELECT * FROM wbt_status left join wbt ON wbt.courseid=wbt_status.courseid WHERE wassociatenumber='$user_check' AND passingmarks > f_score * 100 order by timestamp desc");
} else if ($role != 'Admin' && $cid != null && $wbtstatus == 'Completed') {
    $result = pg_query($con, "SELECT * FROM wbt_status left join wbt ON wbt.courseid=wbt_status.courseid WHERE wassociatenumber='$user_check' AND passingmarks <= f_score * 100 AND wbt_status.courseid='$cid' order by timestamp desc");
} else if ($role != 'Admin' && $cid != null && $wbtstatus == 'Incomplete') {
    $result = pg_query($con, "SELECT * FROM wbt_status left join wbt ON wbt.courseid=wbt_status.courseid WHERE wassociatenumber='$user_check' AND passingmarks > f_score * 100 AND wbt_status.courseid='$cid' order by timestamp desc");
}

if (!$result) {
    echo "An error occurred.\n";
    exit;
}

$resultArr = pg_fetch_all($result);
?>


<!DOCTYPE html>
<html lang="en">

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

<!-- =========================
     NAVIGATION LINKS     
============================== -->

<body>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>My Learning</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Learning & Collaboration</a></li>
                    <li class="breadcrumb-item"><a href="iexplore.php">iExplore</a></li>
                    <li class="breadcrumb-item active">My Learning</li>
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
                            <div style="text-align: right;">
                                <div class="col" style="display: inline-block;">
                                    Record count:&nbsp;<?php echo sizeof($resultArr) ?>
                                </div>
                            </div>

                            <form action="" method="GET">
                                <div class="form-group" style="display: inline-block;">
                                    <div class="col2" style="display: inline-block;">
                                        <?php if ($role == 'Admin') { ?>
                                            <input name="get_aid" class="form-control" style="width:max-content; display:inline-block" placeholder="Associate number" value="<?php echo $aid ?>">
                                        <?php } ?>

                                        <select name="wbtstatus" class="form-select" style="width:max-content; display:inline-block">
                                            <?php if ($wbtstatus == null) { ?>
                                                <option value="" disabled selected hidden>Status</option>
                                            <?php
                                            } else { ?>
                                                <option hidden selected><?php echo $wbtstatus ?></option>
                                            <?php }
                                            ?>
                                            <option>Completed</option>
                                            <option>Incomplete</option>
                                            <option>ALL</option>
                                        </select>

                                        <input name="get_cid" class="form-control" style="width:max-content; display:inline-block" placeholder="Course id" value="<?php echo $cid ?>">
                                    </div>
                                </div>
                                <div class="col2 left" style="display: inline-block;">
                                    <button type="submit" name="search_by_id" class="btn btn-success btn-sm" style="outline: none;">
                                        <i class="bi bi-search"></i>&nbsp;Search</button>
                                </div>
                            </form>
                            <?php echo '
                            <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                            <th scope="col">Associate number</th>
                            <th scope="col">Completed on</th>
                            <th scope="col">Course id</th>    
                            <th scope="col">Course name</th>    
                            <th scope="col">Score</th>
                            <th scope="col">Status</th>
                            <th scope="col">Valid upto</th>
                            </tr>
                        </thead>' ?>
                            <?php if ($resultArr != null) {
                                echo '<tbody>';
                                foreach ($resultArr as $array) {
                                    echo '
                                <tr><td>' . substr($array['wassociatenumber'], 0, 10) . '</td>
                                    <td>' . @date("d/m/Y g:i a", strtotime($array['timestamp'])) . '</td>
                                    <td>' . $array['courseid'] . '</td>
                                    <td>' . $array['coursename'] . '</td>
                                    <td>' . round((float)$array['f_score'] * 100) . '%' . '</td><td>' ?>

                                    <?php
                                    $validity = $array['validity'];
                                    $date = date_create($array['timestamp']);
                                    date_add($date, date_interval_create_from_date_string("$validity years"));
                                    date_format($date, "d/m/Y g:i a");

                                    if (($array['passingmarks'] <= round((float)$array['f_score'] * 100))) { ?>

                                        <?php echo 'Completed' ?>

                                    <?php } else { ?>

                                        <?php echo 'Incomplete' ?>
                                    <?php } ?>

                                    <?php echo
                                    '</td><td>' ?>
                                    <?php if ($array['passingmarks'] <= round((float)$array['f_score'] * 100)) { ?>
                                        <?php
                                        // $validity = $array['validity'];
                                        // $date = date_create($array['timestamp']);
                                        // date_add($date, date_interval_create_from_date_string("$validity years"));
                                        echo date_format($date, "d/m/Y g:i a");
                                        ?>&nbsp;



                                        <?php if ((date_format($date, "Y-m-d") > date('Y-m-d', time()))) { ?>

                                            <?php echo '<span class="badge bg-success">Active</span>' ?>

                                        <?php } else { ?>

                                            <?php echo '<span class="badge bg-secondary">Expired</span>' ?>

                                        <?php
                                        } ?>
                                    <?php } ?>
                                <?php echo '</td></tr>';
                                }
                            } else if ($role == 'Admin' && $cid == null && $aid == null) { ?>
                                <?php echo '<tr><td colspan="5">Please select Filter value.</td> </tr>'; ?>
                            <?php } else if ($role != 'Admin' && $cid == null) { ?>
                                <?php echo '<tr><td colspan="5">Please select Filter value.</td> </tr>'; ?>
                            <?php } else {
                                echo '<tr>
                        <td colspan="5">No record was found for the selected filter value.' ?>
                            <?php echo '</td>
                    </tr>';
                            }
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

</html>