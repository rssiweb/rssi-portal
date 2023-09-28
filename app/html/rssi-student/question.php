<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");

if (!isLoggedIn("sid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

if ($password_updated_by == null || $password_updated_on < $default_pass_updated_on) {

    echo '<script type="text/javascript">';
    echo 'window.location.href = "defaultpasswordreset.php";';
    echo '</script>';
}

if ($filterstatus != 'Active') {

    echo '<script type="text/javascript">';
    echo 'alert("Access Denied. You are not authorized to access this web page.");';
    echo 'window.location.href = "home.php";';
    echo '</script>';
}

date_default_timezone_set('Asia/Kolkata');
$date = date('Y-m-d H:i:s');

@$category = $_POST['get_category'];
@$subject = $_POST['get_subject'];
@$year = $_POST['get_year'];
@$exam = $_POST['get_exam'];

if ($category != 'ALL' && $subject != 'ALL' && $year != 'ALL' && $exam == 'ALL') {
    $result = pg_query($con, "SELECT * FROM question WHERE category='$category' AND subject='$subject' AND year='$year'");
} else if ($category != 'ALL' && $subject != 'ALL' && $year != 'ALL' && $exam == null) {
    $result = pg_query($con, "SELECT * FROM question WHERE category='$category' AND subject='$subject' AND year='$year'");
} else {
    $result = pg_query($con, "SELECT * FROM question WHERE category='$category' AND subject='$subject' AND year='$year' AND examname='$exam'");
}

if (!$result) {
    echo "An error occurred.\n";
    exit;
}

$resultArr = pg_fetch_all($result);
?>

<!DOCTYPE html>
<html>

<head>
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=AW-11316670180"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'AW-11316670180');
</script>
    <meta name="description" content="">
    <meta name="author" content="">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Question Portal</title>
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
            opacity: 0.5;
        }
    </style>

</head>

<body>
    <?php include 'header.php'; ?>
    <section id="main-content">
        <section class="wrapper main-wrapper row">
            <div class="col-md-12">
                <div class="row">
                    <div class="col" style="display: inline-block; width:50%;margin-left:1.5%">
                        Record count:&nbsp;<?php echo sizeof($resultArr) ?>
                    </div>
                    <div class="col" style="display: inline-block; width:47%; text-align:right">
                    <span class="noticea"><a href="home.php" target="_self">Home</a></span> / <span class="noticea"><a href="exam.php" target="_self">Examination</a></span> / Question Portal
                    </div>
                </div>
                
                    <form action="" method="POST">
                        <div class="form-group" style="display: inline-block;">
                            <div class="col2" style="display: inline-block;">
                                <select name="get_category" class="form-control" style="width:max-content; display:inline-block" required>
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
                                </select>
                                <select name="get_subject" class="form-control" style="width:max-content; display:inline-block" required>
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
                                </select>
                                <select name="get_year" id="get_year" class="form-control" style="width:max-content;display:inline-block" required>
                                    <?php if ($year == null) { ?>
                                        <option value="" disabled selected hidden>Select Year</option>
                                    <?php
                                    } else { ?>
                                        <option hidden selected><?php echo $year ?></option>
                                    <?php }
                                    ?>
                                </select>
                                <select name="get_exam" class="form-control" style="width:max-content;display:inline-block">
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
                    <?php if (sizeof($resultArr) > 0) { ?>
                        <?php
                        echo '<tbody>';
                        foreach ($resultArr as $array) {
                            echo '<tr>
              <td>' . $array['category'] . '</td>
            <td>' . $array['subject'] . '</td>
            <td>' . $array['testcode'] . '&nbsp; <p class="badge label-default">' . $array['class'] . '</p></td>
            <td>' . $array['fullmarks'] . '</td>
            <td>' . $array['examname'] . '</td>
            <td>' . $array['topic'] . '</td>' ?>

                            <?php if (@strtotime($date) - @strtotime($array['flag']) > 0) { ?>
                                <?php echo '<td><a href="' . $array['url'] . '" target="_blank"><button type="button" class="btn btn-info btn-sm"><span class="glyphicon glyphicon-eye-open" aria-hidden="true"></span> Question</button></a></td>
            </tr>' ?>
                            <?php } else { ?>
                                <?php echo '<td><a href="' . $array['url'] . '" target="_blank" class="disabled"><button type="button" class="btn btn-info btn-sm"><span class="glyphicon glyphicon-eye-open" aria-hidden="true"></span> Question</button></a></td>
            </tr>' ?>
                            <?php } ?>

                        <?php } ?>
                    <?php
                    } else if ($category == "") {
                    ?>
                        <tr>
                            <td colspan="5">Please select Category.</td>
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
                </section>
            </div>

        </section>
    </section>
</body>

</html>