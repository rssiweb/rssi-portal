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

if ($role != 'Admin' && $role != 'Offline Manager') {
    echo '<script type="text/javascript">';
    echo 'alert("Access Denied. You are not authorized to access this web page.");';
    echo 'window.location.href = "home.php";';
    echo '</script>';
}

date_default_timezone_set('Asia/Kolkata');

if ($_POST) {
    @$itemid = "A" . time();
    @$itemtype = $_POST['itemtype'];
    @$itemname = $_POST['itemname'];
    @$quantity = $_POST['quantity'];
    @$remarks = $_POST['remarks'];
    @$collectedby = $_POST['collectedby'];
    @$now = date('Y-m-d H:i:s');
    if ($itemtype != "") {
        $gps = "INSERT INTO gps (itemid, date, itemtype, itemname, quantity, remarks, collectedby) VALUES ('$itemid','$now','$itemtype','$itemname','$quantity','$remarks','$collectedby')";
        $gpshistory = "INSERT INTO gps_history (itemid, date, itemtype, itemname, quantity, remarks, collectedby) VALUES ('$itemid','$now','$itemtype','$itemname','$quantity','$remarks','$collectedby')";
        $result = pg_query($con, $gps);
        $result = pg_query($con, $gpshistory);
        $cmdtuples = pg_affected_rows($result);
    }
}

@$taggedto = $_GET['taggedto'];
@$item_type = $_GET['item_type'];

if ($item_type == 'ALL' && $taggedto == "") {
    $gpsdetails = "SELECT * from gps 
    left join (select fullname as tfullname,associatenumber as tassociatenumber,phone as tphone,email as temail from rssimyaccount_members) as tmember ON gps.taggedto=tmember.tassociatenumber 
    left join (select fullname as ifullname,associatenumber as iassociatenumber,phone as iphone,email as iemail from rssimyaccount_members) as imember ON gps.collectedby=imember.iassociatenumber 
    order by date desc";
} else if ($item_type == 'ALL' && $taggedto != "") {
    $gpsdetails = "SELECT * from gps 
    left join (select fullname as tfullname,associatenumber as tassociatenumber,phone as tphone,email as temail from rssimyaccount_members) as tmember ON gps.taggedto=tmember.tassociatenumber 
    left join (select fullname as ifullname,associatenumber as iassociatenumber,phone as iphone,email as iemail from rssimyaccount_members) as imember ON gps.collectedby=imember.iassociatenumber 
    where taggedto='$taggedto' order by date desc";
} else if ($item_type == "" && $taggedto != "") {
    $gpsdetails = "SELECT * from gps 
    left join (select fullname as tfullname,associatenumber as tassociatenumber,phone as tphone,email as temail from rssimyaccount_members) as tmember ON gps.taggedto=tmember.tassociatenumber 
    left join (select fullname as ifullname,associatenumber as iassociatenumber,phone as iphone,email as iemail from rssimyaccount_members) as imember ON gps.collectedby=imember.iassociatenumber  
    where taggedto='$taggedto' order by date desc";
} else if ($item_type != "ALL" && $item_type != "" && $taggedto != "") {
    $gpsdetails = "SELECT * from gps 
    left join (select fullname as tfullname,associatenumber as tassociatenumber,phone as tphone,email as temail from rssimyaccount_members) as tmember ON gps.taggedto=tmember.tassociatenumber 
    left join (select fullname as ifullname,associatenumber as iassociatenumber,phone as iphone,email as iemail from rssimyaccount_members) as imember ON gps.collectedby=imember.iassociatenumber  
    where taggedto='$taggedto' and itemtype='$item_type' order by date desc";
} else if ($item_type != "ALL" && $item_type != "" && $taggedto == "") {
    $gpsdetails = "SELECT * from gps 
    left join (select fullname as tfullname,associatenumber as tassociatenumber,phone as tphone,email as temail from rssimyaccount_members) as tmember ON gps.taggedto=tmember.tassociatenumber 
    left join (select fullname as ifullname,associatenumber as iassociatenumber,phone as iphone,email as iemail from rssimyaccount_members) as imember ON gps.collectedby=imember.iassociatenumber 
    where itemtype='$item_type' order by date desc";
} else {
    $gpsdetails = "SELECT * from gps 
    left join (select fullname as tfullname,associatenumber as tassociatenumber,phone as tphone,email as temail from rssimyaccount_members) as tmember ON gps.taggedto=tmember.tassociatenumber 
    left join (select fullname as ifullname,associatenumber as iassociatenumber,phone as iphone,email as iemail from rssimyaccount_members) as imember ON gps.collectedby=imember.iassociatenumber  order by date desc";
}

$result = pg_query($con, $gpsdetails);

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
    <title>RSSI-GPS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <!-- Main css -->
    <style>
        <?php include '../css/style.css'; ?>
    </style>

    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script src="https://kit.fontawesome.com/58c4cdb942.js" crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
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
        .checkbox {
            padding: 0;
            margin: 0;
            vertical-align: bottom;
            position: relative;
            top: 0px;
            overflow: hidden;
        }

        .x-btn:focus,
        .button:focus,
        [type="submit"]:focus {
            outline: none;
        }

        #passwordHelpBlock {
            font-size: x-small;
            display: block;
        }

        .input-help {
            vertical-align: top;
            display: inline-block;
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
                <?php if (@$itemid != null && @$cmdtuples == 0) { ?>

                    <div class="alert alert-danger alert-dismissible" role="alert" style="text-align: -webkit-center;">
                        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                        <span class="blink_me"><i class="glyphicon glyphicon-warning-sign"></i></span>&nbsp;&nbsp;<span>ERROR: Oops, something wasn't right.</span>
                    </div>
                <?php
                } else if (@$cmdtuples == 1) { ?>

                    <div class="alert alert-success alert-dismissible" role="alert" style="text-align: -webkit-center;">
                        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                        <i class="glyphicon glyphicon-ok" style="font-size: medium;"></i></span>&nbsp;&nbsp;<span>Database has been updated successfully for item id <?php echo @$itemid ?>.</span>
                    </div>
                <?php } ?>
                <div class="row">
                    <div class="col" style="display: inline-block; width:50%; text-align:left">
                        Home / GPS (Global Procurement System)<br><br>
                    </div>
                    <div class="col" style="display: inline-block; width:47%; text-align:right">
                        <span class="noticea"><a href="asset-management.php">Asset Movement</a></span><br><br>
                    </div>
                    <section class="box" style="padding: 2%;">

                        <form autocomplete="off" name="gps" id="gps" action="gps.php" method="POST">
                            <div class="form-group" style="display: inline-block;">
                                <div class="col2" style="display: inline-block;">

                                    <span class="input-help">
                                        <select name="itemtype" class="form-control" style="width:max-content; display:inline-block" required>
                                            <?php if ($itemtype == null) { ?>
                                                <option value="" disabled selected hidden>Item type</option>
                                            <?php
                                            } else { ?>
                                                <option hidden selected><?php echo $itemtype ?></option>
                                            <?php }
                                            ?>
                                            <option>Purchased</option>
                                            <option>Donation</option>
                                        </select>
                                        <small id="passwordHelpBlock" class="form-text text-muted">Item type</small>
                                    </span>

                                    <span class="input-help">
                                        <input type="text" name="itemname" class="form-control" style="width:max-content; display:inline-block" placeholder="Item name" value="" required>
                                        <small id="passwordHelpBlock" class="form-text text-muted">Item name</small>
                                    </span>

                                    <span class="input-help">
                                        <input type="number" name="quantity" class="form-control" style="width:max-content; display:inline-block" placeholder="Quantity" value="" min="1" required>
                                        <small id="passwordHelpBlock" class="form-text text-muted">Quantity</small>
                                    </span>

                                    <span class="input-help">
                                        <textarea type="text" name="remarks" class="form-control" style="width:max-content; display:inline-block" placeholder="Remarks" value=""></textarea>
                                        <small id="passwordHelpBlock" class="form-text text-muted">Remarks (Optional)</small>
                                    </span>

                                    <input type="hidden" name="collectedby" class="form-control" style="width:max-content; display:inline-block" placeholder="Collected by" value="<?php echo $associatenumber ?>" required readonly>

                                </div>

                            </div>

                            <div class="col2 left" style="display: inline-block;">
                                <button type="submit" name="search_by_id" class="btn btn-danger btn-sm" style="outline: none;">
                                    <i class="fa-solid fa-arrows-rotate"></i>&nbsp;&nbsp;Update</button>
                            </div>
                        </form>


                        <!-- <br><span class="heading">Asset details</span><br><br> -->
                        <form name="gpsdetails" id="gpsdetails" action="" method="GET">
                            <div class="form-group" style="display: inline-block;">
                                <div class="col2" style="display: inline-block;">

                                    <select name="item_type" class="form-control" style="width:max-content; display:inline-block">
                                        <?php if ($item_type == null) { ?>
                                            <option value="" disabled selected hidden>Item type</option>
                                        <?php
                                        } else { ?>
                                            <option hidden selected><?php echo $item_type ?></option>
                                        <?php }
                                        ?>
                                        <option>Purchased</option>
                                        <option>Donation</option>
                                        <option>ALL</option>
                                    </select>&nbsp;
                                    <input type="text" name="taggedto" class="form-control" style="width:max-content; display:inline-block" placeholder="Tagged to" value="<?php echo $taggedto ?>">
                                </div>
                            </div>
                            <div class="col2 left" style="display: inline-block;">
                                <button type="submit" name="search_by_idd" class="btn btn-primary btn-sm" style="outline: none;">
                                    <i class="fa-solid fa-magnifying-glass"></i>&nbsp;Search</button>
                            </div>
                        </form>
                        <div class="col" style="display: inline-block; width:99%; text-align:right">
                            Record count:&nbsp;<?php echo sizeof($resultArr) ?><br><br>
                            <form method="POST" action="export_function.php">
                                <input type="hidden" value="gps" name="export_type" />
                                <input type="hidden" value="<?php echo $item_type ?>" name="item_type" />
                                <input type="hidden" value="<?php echo $taggedto ?>" name="taggedto" />

                                <button type="submit" id="export" name="export" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none;
                        padding: 0px;
                        border: none;" title="Export CSV"><i class="fa-regular fa-file-excel" style="font-size:large;"></i></button>
                            </form>
                        </div>

                        <?php echo '
                        <table class="table">
                        <thead>
                            <tr>
                                <th scope="col">Item Id</th>
                                <th scope="col" width="15%">Item name</th>
                                <th scope="col">Item type</th>
                                <th scope="col">Quantity</th>
                                <th scope="col" width="20%">Remarks</th>
                                <th scope="col">Issued by</th>
                                <th scope="col">Tagged to</th>
                                <th scope="col"></th>
                                
                            </tr>
                        </thead>' ?>
                        <?php if (sizeof($resultArr) > 0) { ?>
                            <?php
                            echo '<tbody>';
                            foreach ($resultArr as $array) {
                                echo '<tr>
                                <td><span class="noticea"><a href="gps_history.php?assetid=' . $array['itemid'] . '" target="_blank" title="Asset History">' . $array['itemid'] . '</a></span></td>' ?>



                                <?php echo '<td>' . substr($array['itemname'], 0, 35) . '</td>
                                    <td>' . $array['itemtype'] . '</td>
                                <td>' . $array['quantity'] . '</td><td>' ?>

                                <?php if (@strlen($array['remarks']) <= 90) {

                                    echo $array['remarks'] ?>

                                <?php } else { ?>

                                    <?php echo substr($array['remarks'], 0, 90) .
                                        '&nbsp;...&nbsp;<button type="button" href="javascript:void(0)" onclick="showremarks(\'' . $array['itemid'] . '\')" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Details">
                                <i class="fa-solid fa-arrow-up-right-from-square"></i></button>' ?>
                                <?php } ?>


                            <?php echo '</td>
                                <td>' . $array['collectedby'] . '</td>
                                <td>' . $array['taggedto'] . '</td>
                                
                                <td>
                                <button type="button" href="javascript:void(0)" onclick="showDetails(\'' . $array['itemid'] . '\')" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Details">
                                <i class="fa-regular fa-pen-to-square" style="font-size: 14px ;color:#777777" title="Show Details" display:inline;></i></button>&nbsp;&nbsp;

                                <a href="https://api.whatsapp.com/send?phone=91' . $array['tphone'] . '&text=Dear ' . $array['tfullname'] . ',%0A%0AAsset Allocation has been done in Global Procurement System.%0A%0AAsset Details%0A%0AItem Name – ' . $array['itemname'] . '%0AQuantity – ' . $array['quantity'] . '%0AAllocated to – ' . $array['tfullname'] . ' (' . $array['taggedto'] . ')%0AAsset ID – ' . $array['itemid'] . '%0AAllocated by – ' . $array['ifullname'] . ' (' . $array['collectedby'] . ')%0A%0AIn case of any concerns kindly contact Asset officer (refer Allocated by in the table).%0A%0A--RSSI%0A%0A**This is an automatically generated SMS
                                " target="_blank"><i class="fa-brands fa-whatsapp" style="color:#444444;" title="Send SMS"></i></a>&nbsp;

                                <form name="gpsdelete_' . $array['itemid'] . '" action="#" method="POST" style="display: -webkit-inline-box;">
                                <input type="hidden" name="form-type" type="text" value="gpsdelete">
                                <input type="hidden" name="gpsid" id="gpsid" type="text" value="' . $array['itemid'] . '">
                                
                                <button type="submit" onclick=validateForm() style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Delete ' . $array['itemid'] . '"><i class="fa-solid fa-xmark"></i></button>
                                </form>
                                </td>
                                </tr>';
                            } ?>
                        <?php
                        } else if ($taggedto == null && $item_type == null) {
                        ?>
                            <tr>
                                <td colspan="5">Please select Filter value.</td>
                            </tr>
                        <?php
                        } else {
                        ?>
                            <tr>
                                <td colspan="5">No record was found for the selected filter value.</td>
                            </tr>
                        <?php }

                        echo '</tbody>
                            </table>';
                        ?>

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
                <p class="label label-info" style="display: inline !important;"><span class="itemid"></span></p>
            </div>


            <form id="gpsform" action="#" method="POST">
                <div class="form-group" style="display: inline-block;">
                    <div class="col2" style="display: inline-block;">

                        <input type="hidden" class="form-control" name="itemid1" id="itemid1" type="text" value="" readonly>

                        <input type="hidden" class="form-control" name="form-type" type="text" value="gpsedit" readonly>

                        <span class="input-help">
                            <select name="itemtype" id="itemtype" class="form-control" style="width:max-content; display:inline-block" required>
                                <?php if ($itemtype == null) { ?>
                                    <option value="" disabled selected hidden>Item type</option>
                                <?php
                                } else { ?>
                                    <option hidden selected><?php echo $itemtype ?></option>
                                <?php }
                                ?>
                                <option>Purchased</option>
                                <option>Donation</option>
                            </select>
                            <small id="passwordHelpBlock" class="form-text text-muted">Item type*</small>
                        </span>

                        <span class="input-help">
                            <input type="text" name="itemname" id="itemname" class="form-control" style="width:max-content; display:inline-block" placeholder="Item name" value="" required>
                            <small id="passwordHelpBlock" class="form-text text-muted">Item name*</small>
                        </span>

                        <span class="input-help">
                            <input type="number" name="quantity" id="quantity" class="form-control" style="width:max-content; display:inline-block" placeholder="Quantity" value="" min="1" required>
                            <small id="passwordHelpBlock" class="form-text text-muted">Quantity*</small>
                        </span>

                        <span class="input-help">
                            <textarea type="text" name="remarks" id="remarks" class="form-control" style="width:max-content; display:inline-block" placeholder="Remarks" value=""></textarea>
                            <small id="passwordHelpBlock" class="form-text text-muted">Remarks (Optional)</small>
                        </span>
                        <span class="input-help">
                            <input type="text" name="collectedby" id="collectedby" class="form-control" style="width:max-content; display:inline-block" placeholder="Issued by" value="<?php echo $associatenumber ?>" required>
                            <small id="passwordHelpBlock" class="form-text text-muted"> Issued by*</small>
                        </span>
                        <span class="input-help">
                            <input type="text" name="taggedto" id="taggedto" class="form-control" style="width:max-content; display:inline-block" placeholder="Tagged to" value="<?php echo $associatenumber ?>">
                            <small id="passwordHelpBlock" class="form-text text-muted"> Tagged to</small>
                        </span>

                    </div>

                </div>

                <div class="col2 left" style="display: inline-block;">
                    <button type="submit" name="search_by_id" class="btn btn-danger btn-sm" style="outline: none;">
                        <i class="fa-solid fa-arrows-rotate"></i>&nbsp;&nbsp;Update</button>
                </div>
            </form>
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
                if (item["itemid"] == id) {
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

            var profile = document.getElementById("itemtype")
            profile.value = mydata["itemtype"]
            if (mydata["itemtype"] !== null) {
                profile = document.getElementById("itemtype")
                profile.value = mydata["itemtype"]
            }
            if (mydata["itemname"] !== null) {
                profile = document.getElementById("itemname")
                profile.value = mydata["itemname"]
            }
            if (mydata["quantity"] !== null) {
                profile = document.getElementById("quantity")
                profile.value = mydata["quantity"]
            }
            if (mydata["remarks"] !== null) {
                profile = document.getElementById("remarks")
                profile.value = mydata["remarks"]
            }
            if (mydata["collectedby"] !== null) {
                profile = document.getElementById("collectedby")
                profile.value = mydata["collectedby"]
            }
            if (mydata["taggedto"] !== null) {
                profile = document.getElementById("taggedto")
                profile.value = mydata["taggedto"]
            }
            profile = document.getElementById("itemid1")
            profile.value = mydata["itemid"]
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
            } else if (event.target == modal1) {
                modal1.style.display = "none";
            }
        }
    </script>


    <div id="myModal1" class="modal">

        <!-- Modal content -->
        <div class="modal-content">
            <span id="closeremarks" class="close">&times;</span>

            <div style="width:100%; text-align:right">
                <p class="label label-info" style="display: inline !important;"><span class="itemid"></span></p>
            </div>

            <span class="remarks"></span>
        </div>

    </div>

    <script>
        var data1 = <?php echo json_encode($resultArr) ?>

        // Get the modal
        var modal1 = document.getElementById("myModal1");
        var closeremarks = document.getElementById("closeremarks");

        function showremarks(id1) {
            var mydata1 = undefined
            data1.forEach(item1 => {
                if (item1["itemid"] == id1) {
                    mydata1 = item1;
                }
            })
            var keys1 = Object.keys(mydata1)
            keys1.forEach(key => {
                var span1 = modal1.getElementsByClassName(key)
                if (span1.length > 0)
                    span1[0].innerHTML = mydata1[key];
            })
            modal1.style.display = "block";

        }
        closeremarks.onclick = function() {
            modal1.style.display = "none";
        }
        // When the user clicks anywhere outside of the modal, close it SEE OTHER SCRIPT
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

    <script>
        var data = <?php echo json_encode($resultArr) ?>;
        //For form submission - to update Remarks
        const scriptURL = 'payment-api.php'

        function validateForm() {
            if (confirm('Are you sure you want to delete this record? Once you click OK the record cannot be reverted.')) {

                data.forEach(item => {
                    const form = document.forms['gpsdelete_' + item.itemid]
                    form.addEventListener('submit', e => {
                        e.preventDefault()
                        fetch(scriptURL, {
                                method: 'POST',
                                body: new FormData(document.forms['gpsdelete_' + item.itemid])
                            })
                            .then(response =>
                                alert("Record has been deleted.") +
                                location.reload()
                            )
                            .catch(error => console.error('Error!', error.message))
                    })

                    console.log(item)
                })
            } else {
                alert("Record has NOT been deleted.");
                return false;
            }
        }

        const form = document.getElementById('gpsform')
        form.addEventListener('submit', e => {
            e.preventDefault()
            fetch(scriptURL, {
                    method: 'POST',
                    body: new FormData(document.getElementById('gpsform'))
                })
                .then(response =>
                    alert("Record has been updated.") +
                    location.reload()
                )
                .catch(error => console.error('Error!', error.message))
        })

        data.forEach(item => {
            const formId = 'email-form-' + item.redeem_id
            const form = document.forms[formId]
            form.addEventListener('submit', e => {
                e.preventDefault()
                fetch('mailer.php', {
                        method: 'POST',
                        body: new FormData(document.forms[formId])
                    })
                    .then(response =>
                        alert("Email has been send.")
                    )
                    .catch(error => console.error('Error!', error.message))
            })
        })
    </script>

    <a id="back-to-top" href="#" class="go-top" role="button"><i class="fa fa-angle-up"></i></a>
</body>

</html>