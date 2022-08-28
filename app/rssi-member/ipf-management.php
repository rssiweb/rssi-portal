<?php
session_start();
// Storing Session
include("../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];

    header("Location: index.php");
    exit;
}

@$id = $_GET['get_aid'];

if ($role == 'Admin') {
    if ($id != null) {

        $result = pg_query($con, "SELECT * FROM ipfsubmission 
    
    left join (SELECT associatenumber, ipfl FROM rssimyaccount_members) faculty ON ipfsubmission.memberid2=faculty.associatenumber
    
    WHERE substring(ipfsubmission.ipf, '\((.+)\)')='$id' order by id desc");
    }

    if ($id == null) {

        $result = pg_query($con, "SELECT * FROM ipfsubmission 
    
    left join (SELECT associatenumber,ipfl FROM rssimyaccount_members) faculty ON ipfsubmission.memberid2=faculty.associatenumber
    
    WHERE substring(ipfsubmission.ipf, '\((.+)\)')=null order by id desc");
    }
}
if ($role != 'Admin') {

    if ($id != null) {

        $result = pg_query($con, "SELECT * FROM ipfsubmission 
        
        left join (SELECT associatenumber, ipfl FROM rssimyaccount_members) faculty ON ipfsubmission.memberid2=faculty.associatenumber
        
        WHERE substring(ipfsubmission.ipf, '\((.+)\)')='$id' AND memberid2='$user_check' order by id desc");
    }

    if ($id == null) {

        $result = pg_query($con, "SELECT * FROM ipfsubmission 
        
        left join (SELECT associatenumber,ipfl FROM rssimyaccount_members) faculty ON ipfsubmission.memberid2=faculty.associatenumber
        
        WHERE substring(ipfsubmission.ipf, '\((.+)\)')=null AND memberid2='$user_check' order by id desc");
    }
}

if (!$result) {
    echo "An error occurred.\n";
    exit;
}

$resultArr = pg_fetch_all($result);
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="description" content="">
    <meta name="author" content="">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Appraisal Management System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <!-- Main css -->
    <style>
        <?php include '../css/style.css'; ?>
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
    <style>
        @media (min-width:767px) {
            .left {
                margin-left: 2%;
            }
        }

        #btn {
            border: none !important;
        }
    </style>

</head>

<!-- =========================
     NAVIGATION LINKS     
============================== -->

<body>

    <?php include 'header.php'; ?>
    <section id="main-content">
        <section class="wrapper main-wrapper row">
            <div class="col-md-12">
                <div class="row">
                    <div class="col" style="display: inline-block; width:50%;margin-left:1.5%; font-size:small">
                        Record count:&nbsp;<?php echo sizeof($resultArr) ?>
                    </div>
                    <?php if ($role == 'Admin') { ?>
                        <div class="col" style="display: inline-block; width:47%; text-align:right">
                            Home / Appraisal Management System<br><br>
                        </div>
                    <?php } ?>
                    <?php if ($role != 'Admin') { ?>
                        <div class="col" style="display: inline-block; width:47%; text-align:right">
                            <span class="noticea" style="line-height: 2;"><a href="#" onClick="javascript:history.go(-1)">Back to previous page</a></span>
                        </div>
                    <?php } ?>


                </div>
                <section class="box" style="padding: 2%;">
                    <form action="" method="GET">
                        <div class="form-group">
                            <div class="col2" style="display: inline-block;">

                                <select name="get_aid" class="form-control" style="width:max-content; display:inline-block" placeholder="Select policy year" required>
                                    <?php if ($id == null) { ?>
                                        <option value="" hidden selected>Select year</option>
                                    <?php
                                    } else { ?>
                                        <option hidden selected><?php echo $id ?></option>
                                    <?php }
                                    ?>
                                    <option>2022-2023</option>
                                    <option>2021-2022</option>
                                </select>
                            </div>
                            <div class="col2 left" style="display: inline-block;">
                                <button type="submit" name="search_by_id" class="btn btn-success btn-sm" style="outline: none;">
                                    <i class="fa-solid fa-magnifying-glass"></i>&nbsp;Search</button>
                            </div>
                    </form>
                    <br><br>
                    <?php echo '
                        <table class="table">
                            <thead style="font-size: 12px;">
                                <tr>
                                <th scope="col">Ref. number</th>
                                <th scope="col">Associate details</th>    
                                <th scope="col">Appraisal type</th>
                                <th scope="col">Initiated on</th>
                                <th scope="col">IPF</th>
                                <th scope="col">Status</th>
                                <th scope="col">Responded on</th>
                                <th scope="col">Closed on</th>
                                </tr>
                            </thead>' ?>
                    <?php if ($resultArr != null) {
                        echo '<tbody>';
                        foreach ($resultArr as $array) {
                            echo '<tr><td>' . $array['id'] . '</td>
                        <td>' . $array['memberid2'] . '/' . strtok($array['membername2'], ' ') . '</td>
                        <td>' . str_replace("(", "&nbsp;(", $array['ipf']) . '</td>
                        <td>' . @date("d/m/Y g:i a", strtotime($array['timestamp'])) . '</td> 
                        <td>' . $array['ipfl'] . '</td>     
                        <td>' . $array['status2'] . '</td>
                        <td>' . @date("d/m/Y g:i a", strtotime($array['respondedon'])) . '</td>
                        <td>

                        <form name="ipfclose' . $array['id'] . '" action="#" method="POST" onsubmit="myFunction()">
                        <input type="hidden" name="form-type" type="text" value="ipfclose">
                        <input type="hidden" name="ipfid" id="ipfid" type="text" value="' . $array['id'] . '">
                        <input type="hidden" name="ipfstatus" id="ipfstatus" type="text" value="closed">' ?>

                            <?php if ($array['ipfstatus'] != 'closed' && $role == 'Admin') { ?>

                                <?php echo '<button type="submit" id="yes" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none;
                        padding: 0px;
                        border: none;" title="Closed"><i class="fa-solid fa-arrow-up-from-bracket"></i></button>' ?>
                            <?php } ?>
                            <?php echo ' </form>

' . @date("d/m/Y g:i a", strtotime($array['closedon'])) . '
      </td>' ?>
                            <?php  }
                    } else if (@$id == null) {
                        echo '<tr>
                                <td colspan="6">Please select Filter value.</td>
                            </tr>';
                    } else {
                        echo '<tr>
                            <td colspan="6">No record found for' ?>&nbsp;<?php echo @$id ?>
                        <?php echo '</td>
                        </tr>';
                    }
                    echo '</tbody>
                        </table>';
                        ?>
                </section>
            </div>
        </section>
    </section>



    <script>
        function myFunction() {
            alert("The process has been closed in the system.");
        }
    </script>
    <script>
        var data = <?php echo json_encode($resultArr) ?>;
        var aid = <?php echo '"' . $_SESSION['aid'] . '"' ?>;

        const scriptURL = 'payment-api.php'

        data.forEach(item => {
            const form = document.forms['ipfclose' + item.id]
            form.addEventListener('submit', e => {
                e.preventDefault()
                fetch(scriptURL, {
                        method: 'POST',
                        body: new FormData(document.forms['ipfclose' + item.id])
                    })
                    .then(response => console.log('Success!', response))
                    .catch(error => console.error('Error!', error.message))
            })

            console.log(item)
        })
    </script>

    <!-- Back top -->
    <script>
        $(document).ready(function() {
            $(window).scroll(function() {
                if ($(this).scrollTop() > 50) {
                    $('#back-to-top').fadeIn();
                } else {
                    $('#back-to-top').fadeOut();
                }
            });
            // scroll body to 0px on click
            $('#back-to-top').click(function() {
                $('body,html').animate({
                    scrollTop: 0
                }, 400);
                return false;
            });
        });
    </script>
    <a id="back-to-top" href="#" class="go-top" role="button"><i class="fa fa-angle-up"></i></a>





</body>

</html>