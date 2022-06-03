<?php
session_start();
// Storing Session
include("../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}
 if ($_SESSION['role'] != 'Admin') {

    //header("Location: javascript:history.back()"); //redirect to the login page to secure the welcome page without login access.
    echo '<script type="text/javascript">';
    echo 'alert("Access Denied. You are not authorized to access this web page.");';
    echo 'window.location.href = "home.php";';
    echo '</script>';
}
?>
<?php
include("member_data.php");
date_default_timezone_set('Asia/Kolkata');
$date = date('Y-m-d H:i:s');
?>
<?php
include("database.php");
@$id = $_POST['get_id'];

$result = pg_query($con, "SELECT rssimyaccount_members.doj,
rssimyaccount_members.associatenumber,
rssimyaccount_members.fullname,
rssimyaccount_members.email,
rssimyaccount_members.basebranch,
rssimyaccount_members.gender,
rssimyaccount_members.dateofbirth,
rssimyaccount_members.howyouwouldliketobeaddressed,
rssimyaccount_members.currentaddress,
rssimyaccount_members.permanentaddress,
rssimyaccount_members.languagedetailsenglish,
rssimyaccount_members.languagedetailshindi,
rssimyaccount_members.workexperience,
rssimyaccount_members.nationalidentifier,
rssimyaccount_members.yourthoughtabouttheworkyouareengagedwith,
rssimyaccount_members.applicationnumber,
rssimyaccount_members.position,
rssimyaccount_members.approvedby,
rssimyaccount_members.associationstatus,
rssimyaccount_members.effectivedate,
rssimyaccount_members.remarks,
rssimyaccount_members.phone,
rssimyaccount_members.identifier,
rssimyaccount_members.astatus,
rssimyaccount_members.badge,
rssimyaccount_members.colors,
rssimyaccount_members.gm,
rssimyaccount_members.lastupdatedon,
rssimyaccount_members.photo,
rssimyaccount_members.mydoc,
rssimyaccount_members.class,
rssimyaccount_members.notification,
rssimyaccount_members.age,
rssimyaccount_members.depb,
rssimyaccount_members.attd,
rssimyaccount_members.filterstatus,
rssimyaccount_members.today,
rssimyaccount_members.allocationdate,
rssimyaccount_members.maxclass,
rssimyaccount_members.classtaken,
rssimyaccount_members.leave,
rssimyaccount_members.ctp,
rssimyaccount_members.feedback,
rssimyaccount_members.evaluationpath,
rssimyaccount_members.leaveapply,
rssimyaccount_members.cl,
rssimyaccount_members.sl,
rssimyaccount_members.el,
rssimyaccount_members.engagement,
rssimyaccount_members.cltaken,
rssimyaccount_members.sltaken,
rssimyaccount_members.eltaken,
rssimyaccount_members.othtaken,
rssimyaccount_members.clbal,
rssimyaccount_members.slbal,
rssimyaccount_members.elbal,
rssimyaccount_members.officialdoc,
rssimyaccount_members.profile,
rssimyaccount_members.filename,
rssimyaccount_members.fname,
rssimyaccount_members.quicklink,
rssimyaccount_members.yos,
rssimyaccount_members.role,
rssimyaccount_members.originaldoj,
rssimyaccount_members.iddoc,
rssimyaccount_members.vaccination,
rssimyaccount_members.scode,
rssimyaccount_members.exitinterview,
rssimyaccount_members.questionflag,
rssimyaccount_members.googlechat,
rssimyaccount_members.adjustedleave,
rssimyaccount_members.ipfl,
rssimyaccount_members.eduq,
rssimyaccount_members.mjorsub,
rssimyaccount_members.disc,
rssimyaccount_members.hbday,
rssimyaccount_members.on_leave,
rssimyaccount_members.attd_pending,
rssimyaccount_members.approveddate,
asset.agreementname,
asset.status,
asset.category,
asset.assetdetails,
max(userlog_member.logintime) as logintime FROM rssimyaccount_members 
left join asset ON asset.userid=rssimyaccount_members.associatenumber 
left join userlog_member ON userlog_member.username=rssimyaccount_members.associatenumber WHERE filterstatus='$id' group by rssimyaccount_members.doj,
rssimyaccount_members.associatenumber,
rssimyaccount_members.fullname,
rssimyaccount_members.email,
rssimyaccount_members.basebranch,
rssimyaccount_members.gender,
rssimyaccount_members.dateofbirth,
rssimyaccount_members.howyouwouldliketobeaddressed,
rssimyaccount_members.currentaddress,
rssimyaccount_members.permanentaddress,
rssimyaccount_members.languagedetailsenglish,
rssimyaccount_members.languagedetailshindi,
rssimyaccount_members.workexperience,
rssimyaccount_members.nationalidentifier,
rssimyaccount_members.yourthoughtabouttheworkyouareengagedwith,
rssimyaccount_members.applicationnumber,
rssimyaccount_members.position,
rssimyaccount_members.approvedby,
rssimyaccount_members.associationstatus,
rssimyaccount_members.effectivedate,
rssimyaccount_members.remarks,
rssimyaccount_members.phone,
rssimyaccount_members.identifier,
rssimyaccount_members.astatus,
rssimyaccount_members.badge,
rssimyaccount_members.colors,
rssimyaccount_members.gm,
rssimyaccount_members.lastupdatedon,
rssimyaccount_members.photo,
rssimyaccount_members.mydoc,
rssimyaccount_members.class,
rssimyaccount_members.notification,
rssimyaccount_members.age,
rssimyaccount_members.depb,
rssimyaccount_members.attd,
rssimyaccount_members.filterstatus,
rssimyaccount_members.today,
rssimyaccount_members.allocationdate,
rssimyaccount_members.maxclass,
rssimyaccount_members.classtaken,
rssimyaccount_members.leave,
rssimyaccount_members.ctp,
rssimyaccount_members.feedback,
rssimyaccount_members.evaluationpath,
rssimyaccount_members.leaveapply,
rssimyaccount_members.cl,
rssimyaccount_members.sl,
rssimyaccount_members.el,
rssimyaccount_members.engagement,
rssimyaccount_members.cltaken,
rssimyaccount_members.sltaken,
rssimyaccount_members.eltaken,
rssimyaccount_members.othtaken,
rssimyaccount_members.clbal,
rssimyaccount_members.slbal,
rssimyaccount_members.elbal,
rssimyaccount_members.officialdoc,
rssimyaccount_members.profile,
rssimyaccount_members.filename,
rssimyaccount_members.fname,
rssimyaccount_members.quicklink,
rssimyaccount_members.yos,
rssimyaccount_members.role,
rssimyaccount_members.originaldoj,
rssimyaccount_members.iddoc,
rssimyaccount_members.vaccination,
rssimyaccount_members.scode,
rssimyaccount_members.exitinterview,
rssimyaccount_members.questionflag,
rssimyaccount_members.googlechat,
rssimyaccount_members.adjustedleave,
rssimyaccount_members.ipfl,
rssimyaccount_members.eduq,
rssimyaccount_members.mjorsub,
rssimyaccount_members.disc,
rssimyaccount_members.hbday,
rssimyaccount_members.on_leave,
rssimyaccount_members.attd_pending,
rssimyaccount_members.approveddate,
asset.agreementname,
asset.status,
asset.category,
asset.assetdetails
order by filterstatus asc,today desc");

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
    <style>
        <?php include '../css/style.css'; ?>
    </style>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
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
                                <select name="get_id" class="form-control" style="width:max-content;" placeholder="Appraisal type" required>
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
                            </div>
                        </div>
                        <div class="col2 left" style="display: inline-block;">
                            <button type="submit" name="search_by_id" class="btn btn-success btn-sm" style="outline: none;">
                                <i class="fa-solid fa-magnifying-glass"></i>&nbsp;Search</button>
                        </div>
                    </form>
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
            <td><div class="icon-container"><img src="' . $array['photo'] . '" class="img-circle img-inline" class="img-responsive img-circle" width="50" height="50"/>'
                        ?>

                            <?php if ($array['logintime'] != null) { ?>

                                <?php if (date('Y-m-d H:i:s', strtotime($array['logintime'] . ' + 24 minute')) > $date) { ?>

                                    <?php echo '<div class="status-circle" title="Online"></div>' ?>

                            <?php } else { ?> <?php echo '<div class="status-circle" style="background-color: #E5E5E5;" title="Offline"></div>'?>
                            
                            <?php } } else { ?> <?php echo '<div class="status-circle" style="background-color: #E5E5E5;" title="Offline"></div>'?>
                            
                            <?php } echo '</div></td>
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
                            <?php if ($array['today'] != 0 && $array['filterstatus'] != 'Inactive') { ?>
                <?php echo '<br><p class="label label-warning">Attd. pending</p>' ?>
              <?php    } ?>
                            <?php if ($array['assetdetails'] != null && $array['status'] != 'Closed' && $array['category'] == 'Asset') { ?>
                                <?php echo '<br><a href="asset-management.php?get_statuse=Associate&get_appid='.$array['associatenumber'].'" target="_blank" style="text-decoration:none" title="click here"><p class="label label-danger">asset</p></a>' ?>
                            <?php } else if ($array['agreementname'] != null && $array['status'] != 'Closed' && $array['category'] != 'Asset') { ?>
                                <?php echo '<br><a href="asset-management.php?get_statuse=Associate&get_appid='.$array['associatenumber'].'" target="_blank" style="text-decoration:none" title="click here"><p class="label label-warning">agreement</p></a>' ?>
                            <?php } ?>

                        <?php echo '<br><br>' . $array['effectivedate'] . '&nbsp;' . $array['remarks'] . '</td>
            <td>' . $array['classtaken'] . '/' . $array['maxclass'] . '&nbsp' . $array['ctp'] . '<br><span class="noticea"><a href="' . $array['leaveapply'] . '" target="_blank">Apply leave</a></span><br>s-' . $array['slbal'] . ',&nbsp;c-' . $array['clbal'] . '</td>
            <td style="white-space: unset;">
            
            <a id="profile" href="member-profile.php?get_id='. $array['associatenumber'] .'" target="_blank"><i class="fa-regular fa-file-pdf" style="font-size: 20px ;color:#777777" title="Profile" display:inline;></i></a> &nbsp;

                        <form name="ipfpush' . $array['associatenumber'] . '" action="#" method="POST" onsubmit="myFunction()" style="display:inline;">
                        <input type="hidden" name="form-type" type="text" value="ipfpush">
                        <input type="hidden" name="membername2" type="text" value="'. $array['fullname'] .'" readonly>
                        <input type="hidden" name="memberid2" type="text" value="'. $array['associatenumber'] .'" readonly>
                        <input type="hidden" type="text" name="ipf" id="ipf" value="'. $array['googlechat'] .'" readonly required>
                        <input type="hidden" name="flag" type="text" value="initiated" readonly>' ?>

                            <?php if ($role == 'Admin') { ?>

                                <?php echo '<button type="submit" id="yes" style=" outline: none;background: none;
                        padding: 0px;
                        border: none;" title="IPF Initiate"><i class="fa-solid fa-arrow-up-from-bracket" style="font-size: 20px ; color:#777777""></i></button>' ?>
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
    <script>
        function myFunction() {
            alert("IPF has been initiated in the system.");
        }
    </script>
    <script>
        var data = <?php echo json_encode($resultArr) ?>;
        var aid = <?php echo '"' . $_SESSION['aid'] . '"' ?>;

        const scriptURL = 'payment-api.php'

        data.forEach(item => {
            const form = document.forms['ipfpush' + item.associatenumber]
            form.addEventListener('submit', e => {
                e.preventDefault()
                fetch(scriptURL, {
                        method: 'POST',
                        body: new FormData(document.forms['ipfpush' + item.associatenumber])
                    })
                    .then(response => console.log('Success!', response))
                    .catch(error => console.error('Error!', error.message))
            })

            console.log(item)
        })
    </script>
</body>

</html>