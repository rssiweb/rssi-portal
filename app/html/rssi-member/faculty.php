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

if ($role != 'Admin') {
    echo '<script type="text/javascript">';
    echo 'alert("Access Denied. You are not authorized to access this web page.");';
    echo 'window.location.href = "home.php";';
    echo '</script>';
}
?>
<?php
date_default_timezone_set('Asia/Kolkata');
$date = date('Y-m-d H:i:s');

@$id = $_POST['get_id'];
@$aaid = $_POST['get_aaid'];
@$is_user = $_POST['is_user'];

if ($id != null && $aaid == null) {
    $result = pg_query($con, "SELECT distinct * FROM rssimyaccount_members 
    left join (SELECT status,userid FROM asset) asset ON asset.userid=rssimyaccount_members.associatenumber 
    left join (SELECT distinct username, max(logintime) as logintime FROM userlog_member GROUP BY username) userlog_member ON rssimyaccount_members.associatenumber=userlog_member.username
    left join (SELECT taggedto FROM gps) gps ON rssimyaccount_members.associatenumber=gps.taggedto

    WHERE filterstatus='$id' order by filterstatus asc,today desc");
}

if ($id == null && $aaid != null) {
    $result = pg_query($con, "SELECT distinct * FROM rssimyaccount_members 
    
    left join (SELECT status,userid FROM asset) asset ON asset.userid=rssimyaccount_members.associatenumber 
    left JOIN (SELECT distinct username, max(logintime) as logintime FROM userlog_member GROUP BY username) userlog_member ON rssimyaccount_members.associatenumber=userlog_member.username
    left join (SELECT taggedto FROM gps) gps ON rssimyaccount_members.associatenumber=gps.taggedto
    
    WHERE associatenumber='$aaid' order by filterstatus asc,today desc");
}
if ($id == null && $aaid == null) {
    $result = pg_query($con, "SELECT * from rssimyaccount_members where associatenumber is null");
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
    <meta name="description" content="">
    <meta name="author" content="">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>RSSI Faculty</title>
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
            policyLink: 'https://drive.google.com/file/d/1o-ULIIYDLv5ipSRfUa6ROzxJZyoEZhDF/view'
        });
    </script>
    <style>
        @media (max-width:767px) {
            td {
                width: 100%
            }
        }

        td {

            /* css-3 */
            white-space: -o-pre-wrap;
            word-wrap: break-word;
            white-space: pre-wrap;
            white-space: -moz-pre-wrap;
            white-space: -pre-wrap;

        }

        table {
            table-layout: fixed;
            width: 100%
        }

        @media (min-width:767px) {
            .left {
                margin-left: 2%;
            }
        }

        @media (max-width:767px) {

            #cw,
            #cw1,
            #cw2,
            #cw3 {
                width: 100% !important;
            }

        }

        #cw {
            width: 7%;
        }

        #cw1 {
            width: 17%;
        }

        #cw2 {
            width: 15%;
        }

        #cw3 {
            width: 25%;
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
                        <a href="facultyexp.php" target="_self" class="btn btn-danger btn-sm" role="button">Faculty Details</a>
                    </div>
                </div>
                <section class="box" style="padding: 2%;">
                    <form action="" method="POST">
                        <div class="form-group" style="display: inline-block;">
                            <div class="col2" style="display: inline-block;">
                                <select name="get_id" id="get_id" class="form-control" style="width:max-content; display:inline-block" placeholder="Appraisal type" disabled>
                                    <?php if ($id == null) { ?>
                                        <option value="" disabled selected hidden>Select Status</option>
                                    <?php
                                    } else { ?>
                                        <option hidden selected><?php echo $id ?></option>
                                    <?php }
                                    ?>
                                    <option>Active</option>
                                    <option>Inactive</option>
                                </select>
                                <input name="get_aaid" id="get_aaid" class="form-control" style="width:max-content; display:inline-block" placeholder="Associate number" value="<?php echo $aaid ?>">
                            </div>
                        </div>
                        <div class="col2 left" style="display: inline-block;">
                            <button type="submit" name="search_by_id" class="btn btn-success btn-sm" style="outline: none;">
                                <i class="fa-solid fa-magnifying-glass"></i>&nbsp;Search</button>
                        </div>
                        <div id="filter-checks">
                            <input type="checkbox" name="is_user" id="is_user" value="1" <?php if (isset($_POST['is_user'])) echo "checked='checked'"; ?> />
                            <label for="is_user" style="font-weight: 400;">Search by Associate ID</label>
                        </div>
                    </form>
                    <script>
                        if ($('#is_user').not(':checked').length > 0) {

                            document.getElementById("get_id").disabled = false;
                            document.getElementById("get_aaid").disabled = true;

                        } else {

                            document.getElementById("get_id").disabled = true;
                            document.getElementById("get_aaid").disabled = false;

                        }

                        const checkbox = document.getElementById('is_user');

                        checkbox.addEventListener('change', (event) => {
                            if (event.target.checked) {
                                document.getElementById("get_id").disabled = true;
                                document.getElementById("get_aaid").disabled = false;
                            } else {
                                document.getElementById("get_id").disabled = false;
                                document.getElementById("get_aaid").disabled = true;
                            }
                        })
                    </script>
                    <?php
                    echo '<table class="table">
          <thead style="font-size: 12px;">
          <tr>
          <th scope="col" id="cw">Photo</th>
          <th scope="col" id="cw1">Volunteer Details</th>
          <th scope="col" id="cw2">Contact</th>
          <th scope="col">Designation</th>
          <th scope="col">Class URL</th>
          <th scope="col" id="cw2">Association Status</th>
          <th scope="col">Productivity</th>
          <th scope="col">Profile</th>
        </tr>
        </thead>' ?>
                    <?php if (sizeof($resultArr) > 0) { ?>
                        <?php
                        echo '<tbody>';
                        foreach ($resultArr as $array) {
                            echo '<tr>
            <td>' ?>
                            <?php if ($array['photo'] != null) { ?>
                                <?php echo '<div class="icon-container"><img src="' . $array['photo'] . '" class="img-circle img-inline" class="img-responsive img-circle" width="50" height="50"/>' ?>
                            <?php } else { ?> <?php echo '<div class="icon-container"><img src="https://res.cloudinary.com/hs4stt5kg/image/upload/v1609410219/faculties/blank.jpg" class="img-circle img-inline" class="img-responsive img-circle" width="50" height="50"/>'
                                                ?><?php } ?>

                                <?php if ($array['logintime'] != null) { ?>

                                    <?php if (date('Y-m-d H:i:s', strtotime($array['logintime'] . ' + 24 minute')) > $date) { ?>

                                        <?php echo '<div class="status-circle" title="Online"></div>' ?>

                                    <?php } else { ?> <?php echo '<div class="status-circle" style="background-color: #E5E5E5;" title="Offline"></div>' ?>

                                    <?php }
                                } else { ?> <?php echo '<div class="status-circle" style="background-color: #E5E5E5;" title="Offline"></div>' ?>

                                <?php }
                                echo '</div></td>
            <td>Name - <b>' . $array['fullname'] . '</b><br>Associate ID - <b>' . $array['associatenumber'] . '</b>
            <br><b>' . $array['gender'] . '&nbsp;(' . $array['age'] . ')</b><br><br>DOJ - ' . $array['originaldoj'] . '<br>' . $array['yos'] . '</td>
            <td>' . $array['phone'] . '<br>' . $array['email'] . '</td>
            <td>' . substr($array['position'], 0, strrpos($array['position'], "-")) . '</td>' ?>
                                <?php if ($id == "Active") { ?>
                                    <?php echo '<td><span class="noticea"><a href="' . $array['gm'] . '" target="_blank">' . substr($array['gm'], -12) . '</span></td>' ?>
                                <?php } else { ?> <?php echo '<td></td>' ?>
                                <?php } ?>

                                <?php echo '<td style="white-space:unset">' . $array['astatus'] ?><br>

                                <?php if ($array['on_leave'] != null && $array['filterstatus'] != 'Inactive') { ?>
                                    <?php echo '<br><p class="label label-danger">on leave</p>' ?>
                                <?php } else {
                                } ?>
                                <?php if ($array['today'] != 0 && $array['today'] != null && $array['filterstatus'] != 'Inactive') { ?>
                                    <?php echo '<br><p class="label label-warning">Attd. pending</p>' ?>
                                <?php    } ?>

                                <?php if ($array['userid'] != null && $array['status'] != 'Closed') { ?>
                                    <?php echo '<br><a href="asset-management.php?get_statuse=Associate&get_appid=' . $array['associatenumber'] . '" target="_blank" style="text-decoration:none" title="click here"><p class="label label-warning">agreement</p></a>' ?>
                                <?php } else { ?>
                                <?php } ?>

                                <?php if ($array['taggedto'] != null) { ?>
                                    <?php echo '<br><a href="gps.php?taggedto=' . $array['associatenumber'] . '" target="_blank" style="text-decoration:none" title="click here"><p class="label label-danger">asset</p></a>' ?>
                                <?php } else { ?>
                                <?php } ?>

                                <?php echo '<br><br>' . $array['effectivedate'] . '&nbsp;' . $array['remarks'] . '</td>
            <td>' . $array['classtaken'] . '/' . $array['maxclass'] . '&nbsp' . $array['ctp'] . '<br><span class="noticea"><a href="https://docs.google.com/forms/d/e/1FAIpQLScAuTVl6IirArMKi5yoj69z7NEYLKqvvNwn8SYo9UGa6RWT0A/viewform?entry.1592136078=' . $array['associatenumber'] . '&entry.593057865=' . $array['fullname'] . '&entry.1085056032=' . $array['email'] . '&entry.1932332750=' . strtok($array['position'],  '-') . '" target="_blank">Apply leave</a></span><br>s&nbsp;' . $array['slbal'] . ',&nbsp;c&nbsp;' . $array['clbal'] . '</td>
            <td style="white-space: unset;">
            
            
            <button type="button" href="javascript:void(0)" onclick="showDetails(\'' . $array['associatenumber'] . '\')" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Details">
                                <i class="fa-solid fa-list-ol" style="font-size: 16px ;color:#777777" title="Show Details" display:inline;></i></button>&nbsp;&nbsp;
            
            <a id="profile" href="myprofile.php?get_id=' . $array['associatenumber'] . '" target="_blank"><i class="fa-regular fa-user" style="font-size: 16px ;color:#777777" title="Profile" display:inline;></i></a> &nbsp;

            

                        <form name="ipfpush' . $array['associatenumber'] . '" action="#" method="POST" style="display:inline;">
                        <input type="hidden" name="form-type" type="text" value="ipfpush">
                        <input type="hidden" name="membername2" type="text" value="' . $array['fullname'] . '" readonly>
                        <input type="hidden" name="memberid2" type="text" value="' . $array['associatenumber'] . '" readonly>
                        <input type="hidden" type="text" name="ipf" id="ipf" value="' . $array['googlechat'] . '" readonly required>
                        <input type="hidden" name="flag" type="text" value="initiated" readonly>' ?>

                                <?php if ($role == 'Admin') { ?>

                                    <?php echo '<button type="submit" id="yes" onclick=validateForm() style=" outline: none;background: none;
                        padding: 0px;
                        border: none;" title="Release IPF ' . $array['googlechat'] . '/' . $array['ipfl'] . '"><i class="fa-solid fa-arrow-up-from-bracket" style="font-size: 16px ; color:#777777""></i></button>' ?>
                                <?php } ?>
                            <?php echo ' </form>
      </td>
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
                        </table>';
                        ?>
            </div>
            </div>
            </div>
        </section>
        </div>
    </section>
    </section>

    <!--------------- POP-UP BOX ------------
-------------------------------------->
    <style>
        .modal {
            display: none;
            /* Hidden by default */
            position: fixed;
            /* Stay in place */
            z-index: 100;
            /* Sit on top */
            padding-top: 100px;
            /* Location of the box */
            left: 0;
            top: 0;
            width: 100%;
            /* Full width */
            height: 100%;
            /* Full height */
            overflow: auto;
            /* Enable scroll if needed */
            background-color: rgb(0, 0, 0);
            /* Fallback color */
            background-color: rgba(0, 0, 0, 0.4);
            /* Black w/ opacity */
        }

        /* Modal Content */

        .modal-content {
            background-color: #fefefe;
            margin: auto;
            padding: 20px;
            border: 1px solid #888;
            width: 100vh;
        }

        @media (max-width:767px) {
            .modal-content {
                width: 50vh;
            }
        }

        /* The Close Button */

        .close {
            color: #aaaaaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            text-align: right;
        }

        .close:hover,
        .close:focus {
            color: #000;
            text-decoration: none;
            cursor: pointer;
        }
    </style>

    <div id="myModal" class="modal">

        <!-- Modal content -->
        <div class="modal-content">
            <span class="close">&times;</span>
            <div style="width:100%; text-align:right">
                <p id="status" class="label " style="display: inline !important;"><span class="fullname"></span></p>
            </div>

            WBT Completed:&nbsp;<span class="attd"></span>
            <div class="col" style="display: inline-block; text-align:right"><a id="wbt_details" href="#" target="_blank"><i class="fa-regular fa-eye" style="font-size: 20px ;color:#777777" title="WBT Details"></i></a></div><br>

            <span class="noticea"><a id="certificate_issue" href="#" target="_blank">Issue Document</a></span><br>
            <span class="noticea"><a id="certificate_view" href="#" target="_blank">View Document</a></span><br>
            <span class="noticea"><a id="experience_letter" href="#" target="_blank">Generate Experience Letter</a></span>

        </div>

    </div>
    <script>
        var data = <?php echo json_encode($resultArr) ?>

        // Get the modal
        var modal = document.getElementById("myModal");
        // Get the <span> element that closes the modal
        var span = document.getElementsByClassName("close")[0];

        function showDetails(id) {
            // console.log(modal)
            // console.log(modal.getElementsByClassName("data"))
            var mydata = undefined
            data.forEach(item => {
                if (item["associatenumber"] == id) {
                    mydata = item;
                }
            })

            var keys = Object.keys(mydata)
            keys.forEach(key => {
                var span = modal.getElementsByClassName(key)
                if (span.length > 0)
                    span[0].innerHTML = mydata[key];
            })
            modal.style.display = "block";

            //class add 
            var status = document.getElementById("status")
            if (mydata["filterstatus"] === "Active") {
                status.classList.add("label-success")
                status.classList.remove("label-danger")
            } else {
                status.classList.remove("label-success")
                status.classList.add("label-danger")
            }
            //class add end

            var profile = document.getElementById("wbt_details")
            profile.href = "/rssi-member/my_learning.php?get_aid=" + mydata["associatenumber"]
            profile = document.getElementById("certificate_issue")
            profile.href = "/rssi-member/my_certificate.php?awarded_to_id=" + mydata["associatenumber"] + "&awarded_to_name=" + mydata["fullname"]
            profile = document.getElementById("certificate_view")
            profile.href = "/rssi-member/my_certificate.php?get_nomineeid=" + mydata["associatenumber"]
            profile = document.getElementById("experience_letter")
            profile.href = "/rssi-member/expletter.php?get_id=" + mydata["associatenumber"]

        }
        // When the user clicks the button, open the modal 
        // When the user clicks on <span> (x), close the modal
        span.onclick = function() {
            modal.style.display = "none";
        }
        // When the user clicks anywhere outside of the modal, close it
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
    <!-- <script>
        function myFunction() {
            alert("IPF has been initiated in the system.");
        }
    </script> -->
    <script>
        var data = <?php echo json_encode($resultArr) ?>;
        var aid = <?php echo '"' . $_SESSION['aid'] . '"' ?>;

        const scriptURL = 'payment-api.php'

        function validateForm() {
            if (confirm('Are you sure you want to release IPF? Once you click on OK the associate will be notified.')) {

                data.forEach(item => {
                    const form = document.forms['ipfpush' + item.associatenumber]
                    form.addEventListener('submit', e => {
                        e.preventDefault()
                        fetch(scriptURL, {
                                method: 'POST',
                                body: new FormData(document.forms['ipfpush' + item.associatenumber])
                            })
                            .then(response =>
                                alert("IPF has been released in the system.") +
                                location.reload()
                            )
                            .catch(error => console.error('Error!', error.message))
                    })

                    console.log(item)
                })
            } else {
                alert("IPF is NOT released in the system.");
                return false;
            }
        }
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
