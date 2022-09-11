<?php
session_start();
// Storing Session
include("../util/login_util.php");

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

if ($role == 'Admin') {

    @$id = strtoupper($_POST['get_aid']);

    if ($id > 0) {
        $result = pg_query($con, "select * from allocationdb_allocationdb WHERE associatenumber='$id'");
        $resultc = pg_query($con, "select * from rssimyaccount_members WHERE associatenumber='$id'");
    } else {
        $result = pg_query($con, "select * from allocationdb_allocationdb WHERE associatenumber='$user_check'"); //select query for viewing users.
        $resultc = pg_query($con, "select * from rssimyaccount_members WHERE associatenumber='$user_check'");
    }
}

if ($role != 'Admin') {

    $result = pg_query($con, "select * from allocationdb_allocationdb WHERE associatenumber='$user_check'"); //select query for viewing users.
    $resultc = pg_query($con, "select * from rssimyaccount_members WHERE associatenumber='$user_check'");
}
if (!$result) {
    echo "An error occurred.\n";
    exit;
}
if (!$resultc) {
    echo "An error occurred.\n";
    exit;
}


$resultArr = pg_fetch_all($result);
$resultArrc = pg_fetch_all($resultc);
?>

<!DOCTYPE html>
<html>

<head>
    <meta name="description" content="">
    <meta name="author" content="">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>My Allocation</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <!-- Main css -->
    <style>
        <?php include '../css/style.css'; ?>

        /*
 CSS for the main interaction
*/
        .tabset>input[type="radio"] {
            position: absolute;
            left: -200vw;
        }

        .tabset .tab-panel {
            display: none;
        }

        .tabset>input:first-child:checked~.tab-panels>.tab-panel:first-child,
        .tabset>input:nth-child(3):checked~.tab-panels>.tab-panel:nth-child(2),
        .tabset>input:nth-child(5):checked~.tab-panels>.tab-panel:nth-child(3),
        .tabset>input:nth-child(7):checked~.tab-panels>.tab-panel:nth-child(4),
        .tabset>input:nth-child(9):checked~.tab-panels>.tab-panel:nth-child(5),
        .tabset>input:nth-child(11):checked~.tab-panels>.tab-panel:nth-child(6) {
            display: block;
        }

        .tabset>label {
            position: relative;
            display: inline-block;
            padding: 15px 15px 25px;
            border: 1px solid transparent;
            border-bottom: 0;
            cursor: pointer;
            font-weight: 600;
        }

        .tabset>label::after {
            content: "";
            position: absolute;
            left: 15px;
            bottom: 10px;
            width: 22px;
            height: 4px;
            background: #8d8d8d;
        }

        .tabset>label:hover,
        .tabset>input:focus+label {
            color: #06c;
        }

        .tabset>label:hover::after,
        .tabset>input:focus+label::after,
        .tabset>input:checked+label::after {
            background: #06c;
        }

        .tabset>input:checked+label {
            border-color: #ccc;
            border-bottom: 1px solid #fff;
            margin-bottom: -1px;
        }

        .tab-panel {
            padding: 30px 0;
            border-top: 1px solid #ccc;
        }
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

</head>

<body>
    <?php $allocation_active = 'active'; ?>
    <?php include 'header.php'; ?>

    <section id="main-content">
        <section class="wrapper main-wrapper row">
            <div class="col-md-12">
                <div class="col" style="display: inline-block; width:99%; text-align:right">
                    Home / My Allocation
                </div>
                <section class="box" style="padding: 2%;">
                    <?php if ($role == 'Admin') { ?>
                        <form action="" method="POST">
                            <div class="form-group" style="display: inline-block;">
                                <div class="col2" style="display: inline-block;">
                                    <?php if ($role == 'Admin') { ?>
                                        <input name="get_aid" class="form-control" style="width:max-content; display:inline-block" placeholder="Associate number" value="<?php echo $id ?>">
                                    <?php } ?>
                                </div>
                            </div>
                            <div class="col2 left" style="display: inline-block;">
                                <button type="submit" name="search_by_id" class="btn btn-success btn-sm" style="outline: none;">
                                    <i class="fa-solid fa-magnifying-glass"></i>&nbsp;Search</button>
                            </div>
                        </form>
                    <?php } ?>

                    <div class="tabset">
                        <!-- Tab 1 -->
                        <input type="radio" name="tabset" id="tab1" aria-controls="marzen" checked>
                        <label for="tab1">Current Allocation</label>
                        <!-- Tab 2 -->
                        <input type="radio" name="tabset" id="tab2" aria-controls="rauchbier">
                        <label for="tab2">History Allocation</label>
                        <!-- Tab 3 -->
                        <input type="radio" name="tabset" id="tab3" aria-controls="dunkles">
                        <label for="tab3">Future Allocation</label>

                        <div class="tab-panels">
                            <section id="marzen" class="tab-panel">
                                <?php echo ' <table class="table">
                        <thead style="font-size: 12px;">
                            <tr>
                            <th scope="col">Allocation Date</th>
                            <th scope="col">Max. Class<br>(Allocation start date to today)</th>
                            <th scope="col">Class Taken<br>(Inc. Extra class)</th>
                            <th scope="col">Off Class</th>
                            <th scope="col">Allocation Index</th>
                            </tr>
                        </thead>
                        <tbody>';
                                foreach ($resultArrc as $array) {
                                    echo '
                            <tr>
                            <td style="line-height: 2;">' . $array['allocationdate'] . '</td>
                            <td style="line-height: 2;">' . $array['maxclass'] . '</td>
                            <td style="line-height: 2;">' . $array['classtaken'] . '</td>
                            <td style="line-height: 2;">' . $array['leave'] . '</td>
                            <td style="line-height: 2;">' ?>
                                    <?php if (@$array['allocationdate'] != null) {
                                        echo $array['ctp'] . '&nbsp;<meter id="disk_c" value="' . strtok($array['ctp'], '%') . '" min="0" max="100"></meter>' ?>
                                    <?php
                                    } else {
                                    }
                                    ?>
                                <?php echo '</td>
                            </tr>';
                                }
                                echo '</tbody>
                                </table>'; ?>
                            </section>
                            <section id="rauchbier" class="tab-panel">
                                <?php echo '
                     <table class="table">
                        <thead style="font-size: 12px;">
                            <tr>
                                <th scope="col">Allocation Date</th>
                                <th scope="col">Max. Class<br>(Allocation start date to end date)</th>
                                <th scope="col">Class Taken<br>(Inc. Extra class)</th>
                            </tr>
                        </thead>
                        <tbody>';
                                foreach ($resultArr as $array) {
                                    echo '
                            <tr>
                            <td style="line-height: 2;">' . $array['hallocationdate'] . '</td>
                            <td style="line-height: 2;">' . $array['hmaxclass'] . '</td>
                            <td style="line-height: 2;">' . $array['hclasstaken'] ?>
                                    <?php if ($array['hmaxclass'] != "Unallocated") { ?>
                                        <?php echo   '&nbsp;(' . number_format($array['hclasstaken'] / $array['hmaxclass'] * '100', '2', '.', '') . '%)' ?>
                                        <?php
                                    } else {
                                    }
                                        ?><?php echo '</td>
                            </tr>';
                                        }
                                        echo '</tbody>
                                </table>';
                                            ?>
                            </section>
                            <section id="dunkles" class="tab-panel">
                                <p>No data available</p>
                            </section>
                        </div>

                    </div>
                </section>
            </div>

        </section>
    </section>
</body>

</html>