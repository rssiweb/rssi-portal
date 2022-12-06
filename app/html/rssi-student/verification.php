<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");
// @$id = strtoupper($_POST['get_id']);
@$id = strtoupper($_GET['get_id']);
$view_users_query = "select * from rssimyprofile_student WHERE student_id='$id'"; //select query for viewing users.  
$run = pg_query($con, $view_users_query); //here run the sql query.  

while ($row = pg_fetch_array($run)) //while look to fetch the result and store in a array $row.  
{
    $student_id = $row[1];
    $studentname = $row[3];
    $gender = $row[4];
    $age = $row[5];
    $status = $row[21];
    $remarks = $row[22];
    $photourl = $row[24];
    $filterstatus = $row[33];
    $doa = $row[40];
    $special_service = $row[50];} ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="description" content="">
    <meta name="author" content="">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Details Verification</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <!-- Main css -->
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
            policyLink: 'https://drive.google.com/file/d/1o-ULIIYDLv5ipSRfUa6ROzxJZyoEZhDF/view'
        });
    </script>
    <style>
        @media (max-width:767px) {
            td {
                width: 100%
            }

            .page-topbar .logo-area {
                width: 240px !important;
                margin-top: 2.5%;
            }
        }

        .page-topbar,
        .logo-area {
            -webkit-transition: 0ms;
            -moz-transition: 0ms;
            -o-transition: 0ms;
            transition: 0ms;
        }
    </style>

</head>

<body>
    <div class="page-topbar">
        <div class="logo-area"> </div>
    </div>
    <section class="wrapper main-wrapper row">
        <div class="col-md-12">
            <section class="box" style="padding: 2%;">
                <form id="myform" action="" method="GET" onsubmit="process()">
                    <div class="form-group" style="display: inline-block;">
                        <div class="col2" style="display: inline-block;">
                            <input name="get_id" class="form-control" style="width:max-content; display:inline-block" placeholder="Student ID" value="<?php echo $id ?>">
                        </div>
                    </div>
                    <div class="col2 left" style="display: inline-block;">
                        <button type="submit" name="search_by_id" class="btn btn-primary btn-sm" style="outline: none;">
                            <i class="fa-solid fa-magnifying-glass"></i>&nbsp;Search</button>
                    </div>
                </form>

                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Photo</th>
                            <th scope="col" id="cw2">Student details</th>
                            <th scope="col" id="cw1">Current Status</th>
                            <th scope="col" id="cw1">Date of Admission</th>
                        </tr>
                    </thead>
                    <?php if (@$student_id > 0) {
                    ?>
                        <tbody>
                            <tr>

                                <td style="line-height: 1.7;"><img src="<?php echo $photourl ?>" width=100px /></td>
                                <td id="cw2" style="line-height: 1.7;"><b><?php echo $studentname ?> (<?php echo $student_id ?>)</b><br>
                                    <span style="line-height: 3;"><?php echo $gender ?> (<?php echo $age ?>&nbsp;Years)
                                </td>
                                <td id="cw1" style="line-height: 1.7;"><?php echo $filterstatus ?></td>
                                <td id="cw1" style="line-height: 1.7;"><?php echo $doa ?></td>
                            </tr>
                        </tbody>
                </table>
            <?php
                    } else if ($id == "") {
            ?>
                <tr>
                    <td>Please enter Student ID.</td>
                </tr>
            <?php
                    } else {
            ?>
                <tr>
                    <td>No record found for <?php echo $id ?></td>
                </tr>
            <?php }
            ?>
            </tbody>
            </table>


        </div>
        </div>
        </div>
    </section>
    </div>
    </section>

    <script>
        function process() {
            var form = document.getElementById('myform');
            var elements = form.elements;
            var values = [];

            for (var i = 0; i < elements.length; i++)
                values.push(encodeURIComponent(elements[i].name) + '=' + encodeURIComponent(elements[i].value));

            form.action += '?' + values.join('&');
        }
    </script>
</body>

</html>
