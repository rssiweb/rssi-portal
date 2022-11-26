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

date_default_timezone_set('Asia/Kolkata'); ?>

<?php if ($role == 'Admin') {

    if (@$_POST['form-type'] == "addasset") {
        @$itemid = "A" . time();
        @$itemtype = $_POST['itemtype'];
        @$itemname = $_POST['itemname'];
        @$quantity = $_POST['quantity'];
        @$remarks = $_POST['remarks'];
        @$collectedby = strtoupper($_POST['collectedby']);
        @$now = date('Y-m-d H:i:s');
        if ($itemtype != "") {
            $gps = "INSERT INTO gps (itemid, date, itemtype, itemname, quantity, remarks, collectedby) VALUES ('$itemid','$now','$itemtype','$itemname','$quantity','$remarks','$collectedby')";
            $gpshistory = "INSERT INTO gps_history (itemid, date, itemtype, itemname, quantity, remarks, collectedby) VALUES ('$itemid','$now','$itemtype','$itemname','$quantity','$remarks','$collectedby')";
            $result = pg_query($con, $gps);
            $result = pg_query($con, $gpshistory);
            $cmdtuples = pg_affected_rows($result);
        }
    }

    @$taggedto = strtoupper($_GET['taggedto']);
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
} ?>

<?php if ($role != 'Admin') {

    @$taggedto = $associatenumber;

    $gpsdetails = "SELECT * from gps 
    left join (select fullname as tfullname,associatenumber as tassociatenumber,phone as tphone,email as temail from rssimyaccount_members) as tmember ON gps.taggedto=tmember.tassociatenumber 
    left join (select fullname as ifullname,associatenumber as iassociatenumber,phone as iphone,email as iemail from rssimyaccount_members) as imember ON gps.collectedby=imember.iassociatenumber  where taggedto='$associatenumber' order by date desc";
} ?>
<?php
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
                <?php if ($role == 'Admin') { ?>
                    <?php if (@$itemid != null && @$cmdtuples == 0) { ?>

                        <div class="alert alert-danger alert-dismissible" role="alert" style="text-align: -webkit-center;">
                            <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                            <span class="blink_me"><i class="glyphicon glyphicon-warning-sign"></i></span>&nbsp;&nbsp;<span>ERROR: Oops, something wasn't right.</span>
                        </div>
                    <?php
                    } else if (@$cmdtuples == 1) { ?>

                        <div class="alert alert-success alert-dismissible" role="alert" style="text-align: -webkit-center;">
                            <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                            <i class="glyphicon glyphicon-ok" style="font-size: medium;"></i></span>&nbsp;&nbsp;<span>Database has been updated successfully for asset id <?php echo @$itemid ?>.</span>
                        </div>
                        <script>
                            if (window.history.replaceState) {
                                window.history.replaceState(null, null, window.location.href);
                            }
                        </script>
                <?php }
                } ?>

                <div class="row">
                    <div class="col" style="display: inline-block; width:100%; text-align:right">
                        Home / <span class="noticea"><a href="document.php">My Document</a></span> / GPS (Global Procurement System)
                    </div>
                    <!-- <div class="col" style="display: inline-block; width:47%; text-align:right">
                        <span class="noticea"><a href="asset-management.php">Asset Movement</a></span>
                    </div> -->
                    <section class="box" style="padding: 2%;">
                        <?php if ($role == 'Admin') { ?>
                            <form autocomplete="off" name="gps" id="gps" action="gps.php" method="POST">
                                <div class="form-group" style="display: inline-block;">
                                    <div class="col2" style="display: inline-block;">

                                        <input type="hidden" name="form-type" type="text" value="addasset">

                                        <span class="input-help">
                                            <select name="itemtype" class="form-control" style="width:max-content; display:inline-block" required>
                                                <?php if ($itemtype == null) { ?>
                                                    <option value="" disabled selected hidden>Asset type</option>
                                                <?php
                                                } else { ?>
                                                    <option hidden selected><?php echo $itemtype ?></option>
                                                <?php }
                                                ?>
                                                <option>Purchased</option>
                                                <option>Donation</option>
                                            </select>
                                            <small id="passwordHelpBlock" class="form-text text-muted">Asset type</small>
                                        </span>

                                        <span class="input-help">
                                            <input type="text" name="itemname" class="form-control" style="width:max-content; display:inline-block" placeholder="Item name" value="" required>
                                            <small id="passwordHelpBlock" class="form-text text-muted">Asset name</small>
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
                                                <option value="" disabled selected hidden>Asset type</option>
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
                                    <button type="submit" name="search_by_id2" class="btn btn-primary btn-sm" style="outline: none;">
                                        <i class="fa-solid fa-magnifying-glass"></i>&nbsp;Search</button>
                                </div>
                            </form>
                        <?php } ?>
                        <div class="col" style="display: inline-block; width:99%; text-align:right">
                            Record count:&nbsp;<?php echo sizeof($resultArr) ?><br><br>
                            <form method="POST" action="export_function.php">
                                <input type="hidden" value="gps" name="export_type" />
                                <input type="hidden" value="<?php echo @$item_type ?>" name="item_type" />
                                <input type="hidden" value="<?php echo @$taggedto ?>" name="taggedto" />

                                <button type="submit" id="export" name="export" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none;
                        padding: 0px;
                        border: none;" title="Export CSV"><i class="fa-regular fa-file-excel" style="font-size:large;"></i></button>
                            </form>
                        </div>

                        <?php echo '
                        <p>Select Number Of Rows</p>
                        <div class="form-group">
                            <select class="form-control" name="state" id="maxRows">
                                <option value="5000">Show ALL Rows</option>
                                <option value="5">5</option>
                                <option value="10">10</option>
                                <option value="15">15</option>
                                <option value="20">20</option>
                                <option value="50">50</option>
                                <option value="70">70</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                        <table class="table" id="table-id" style="font-size: 12px;">
                        <thead>
                            <tr>
                                <th scope="col">Asset Id</th>
                                <th scope="col" width="15%">Asset name</th>
                                <th scope="col">Quantity</th>' ?>
                        <?php if ($role == 'Admin') { ?>
                            <?php echo '
                                <th scope="col">Asset type</th>
                                <th scope="col" width="20%">Remarks</th>' ?>
                        <?php }
                        echo '
                                <th scope="col">Issued by</th>
                                <th scope="col">Tagged to</th>
                                <th scope="col">Last updated on</th></tr>
                        </thead>' ?>
                        <?php if (sizeof($resultArr) > 0) { ?>
                            <?php
                            echo '<tbody>';
                            foreach ($resultArr as $array) {
                                echo '<tr>
                                <td>
                                <span class="noticeas"><a href="gps_history.php?assetid=' . $array['itemid'] . '" target="_blank" title="Asset History">' . $array['itemid'] . '</a></span>
                                </td><td>' ?>

                                <?php if (@strlen($array['itemname']) <= 50) {

                                    echo $array['itemname'] ?>

                                <?php } else { ?>

                                    <?php echo substr($array['itemname'], 0, 50) .
                                        '&nbsp;...&nbsp;<button type="button" href="javascript:void(0)" onclick="showname(\'' . $array['itemid'] . '\')" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Details">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i></button>' ?>

                                <?php }
                                echo '</td><td>' . $array['quantity'] . '</td>' ?>


                                <?php if ($role == 'Admin') { ?>
                                    <?php echo '<td>' . $array['itemtype'] . '</td><td>' ?>
                                    <?php if (@strlen($array['remarks']) <= 90) {

                                        echo $array['remarks'] ?>

                                    <?php } else { ?>

                                        <?php echo substr($array['remarks'], 0, 90) .
                                            '&nbsp;...&nbsp;<button type="button" href="javascript:void(0)" onclick="showremarks(\'' . $array['itemid'] . '\')" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Details">
                                <i class="fa-solid fa-arrow-up-right-from-square"></i></button>' ?>
                                    <?php }
                                    echo '</td>' ?>
                                <?php } ?>

                                <?php echo '
                                <td>' . $array['collectedby'] . '<br>' . $array['ifullname'] . '</td>
                                <td>' . $array['taggedto'] . '<br>' . $array['tfullname'] . '</td>
                                <td>' ?>
                                <?php if ($array['lastupdatedon'] != null) { ?>

                                    <?php echo @date("d/m/Y g:i a", strtotime($array['lastupdatedon'])) ?>

                                <?php } else {
                                }
                                echo '</td><td>' ?>


                                <?php if ($role == 'Admin') { ?>

                                    <?php if ($array['collectedby'] == $associatenumber) { ?>
                                        <?php echo '
                                <button type="button" href="javascript:void(0)" onclick="showDetails(\'' . $array['itemid'] . '\')" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Details">
                                <i class="fa-regular fa-pen-to-square" style="color:#777777" title="Show Details" display:inline;></i></button>' ?>
                                    <?php } else { ?>
                                        <?php echo '&nbsp;<i class="fa-regular fa-pen-to-square" style="color:#A2A2A2;" title="Show Details"></i>' ?>
                                    <?php } ?>

                                    <?php if ($array['tphone'] != null && $array['collectedby'] == $associatenumber) { ?>
                                        <?php echo '

                                    &nbsp;<a href="https://api.whatsapp.com/send?phone=91' . $array['tphone'] . '&text=Dear ' . $array['tfullname'] . ',%0A%0AAsset Allocation has been done in Global Procurement System.%0A%0AAsset Details%0A%0AItem Name – ' . $array['itemname'] . '%0AQuantity – ' . $array['quantity'] . '%0AAllocated to – ' . $array['tfullname'] . ' (' . $array['taggedto'] . ')%0AAsset ID – ' . $array['itemid'] . '%0AAllocated by – ' . $array['ifullname'] . ' (' . $array['collectedby'] . ')%0A%0AIn case of any concerns kindly contact Asset officer (refer Allocated by in the table).%0A%0A--RSSI%0A%0A**This is an automatically generated SMS
                                " target="_blank"><i class="fa-brands fa-whatsapp" style="color:#444444;" title="Send SMS ' . $array['tphone'] . '"></i></a>' ?>
                                    <?php } else { ?>
                                        <?php echo '&nbsp;<i class="fa-brands fa-whatsapp" style="color:#A2A2A2;" title="Send SMS"></i>' ?>
                                    <?php } ?>

                                    <?php if ($array['temail'] != null && $array['collectedby'] == $associatenumber) { ?>
                                        <?php echo '<form  action="#" name="email-form-' . $array['itemid'] . '" method="POST" style="display: -webkit-inline-box;" >
                            <input type="hidden" name="template" type="text" value="gps">
                            <input type="hidden" name="data[itemname]" type="text" value="' . $array['itemname'] . '">
                            <input type="hidden" name="data[itemid]" type="text" value="' . $array['itemid'] . '">
                            <input type="hidden" name="data[quantity]" type="text" value="' . $array['quantity'] . '">
                            <input type="hidden" name="data[taggedto]" type="text" value="' . $array['taggedto'] . '">
                            <input type="hidden" name="data[tfullname]" type="text" value="' . $array['tfullname'] . '">
                            <input type="hidden" name="data[collectedby]" type="text" value="' . $array['collectedby'] . '">
                            <input type="hidden" name="data[ifullname]" type="text" value="' . $array['ifullname'] . '">
                            <input type="hidden" name="email" type="text" value="' . $array['temail'] . '">
                            &nbsp;<button  style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;"
                             type="submit"><i class="fa-regular fa-envelope" style="color:#444444;" title="Send Email ' . $array['temail'] . '"></i></button>
                        </form>' ?>
                                    <?php } else { ?>
                                        <?php echo '&nbsp;<i class="fa-regular fa-envelope" style="color:#A2A2A2;" title="Send Email"></i>' ?>
                                    <?php } ?>

                                    <?php if ($array['collectedby'] == $associatenumber) { ?>
                                        <?php echo '
                                <form id="gpsdelete" action="#" method="POST" style="display: -webkit-inline-box;">
                                <input type="hidden" name="form-type" type="text" value="gpsdelete">
                                <input type="hidden" name="gpsid" type="text" value="' . $array['itemid'] . '">

                                &nbsp;<button type="submit" onclick=validateForm() style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Delete ' . $array['itemid'] . '"><i class="fa-solid fa-xmark"></i></button> </form>' ?>
                                    <?php } else { ?>
                                        <?php echo '&nbsp;<i class="fa-solid fa-xmark" style="color:#A2A2A2;" title="Delete ' . $array['itemid'] . '"></i>' ?>
                                    <?php } ?>

                            <?php echo '</td></tr>';
                                }
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

                        <!--		Start Pagination -->
                        <div class='pagination-container'>
                            <nav>
                                <ul class="pagination">

                                    <li data-page="prev">
                                        <span>
                                            < <span class="sr-only">(current)
                                        </span></span>
                                    </li>
                                    <!--	Here the JS Function Will Add the Rows -->
                                    <li data-page="next" id="prev">
                                        <span> > <span class="sr-only">(current)</span></span>
                                    </li>
                                </ul>
                            </nav>
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
            <div class="col2 left" style="display: inline-block;">
                <span class="close">&times;</span>

                <div style="width:100%; text-align:right">
                    <p class="label label-info"><span class="itemid"></span></p>

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
                                <input type="text" name="collectedby" id="collectedby" class="form-control" style="width:max-content; display:inline-block" placeholder="Issued by" value="" required>
                                <small id="passwordHelpBlock" class="form-text text-muted"> Issued by*</small>
                            </span>
                            <span class="input-help">
                                <input type="text" name="taggedto" id="taggedto" class="form-control" style="width:max-content; display:inline-block" placeholder="Tagged to" value="">
                                <small id="passwordHelpBlock" class="form-text text-muted"> Tagged to</small>
                            </span>

                        </div>

                    </div>

                    <button type="submit" name="search_by_id3" class="btn btn-danger btn-sm" style="outline: none;">
                        <i class="fa-solid fa-arrows-rotate"></i>&nbsp;&nbsp;Update</button>
                </form>
            </div>
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
            } else if (event.target == modal2) {
                modal2.style.display = "none";
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

    <div id="myModal2" class="modal">

        <!-- Modal content -->
        <div class="modal-content">
            <span id="closename" class="close">&times;</span>

            <div style="width:100%; text-align:right">
                <p class="label label-info" style="display: inline !important;"><span class="itemid"></span></p>
            </div>

            <span class="itemname"></span>
        </div>

    </div>

    <script>
        var data2 = <?php echo json_encode($resultArr) ?>

        // Get the modal
        var modal2 = document.getElementById("myModal2");
        var closeremarks = document.getElementById("closename");

        function showname(id2) {
            var mydata2 = undefined
            data2.forEach(item2 => {
                if (item2["itemid"] == id2) {
                    mydata2 = item2;
                }
            })
            var keys2 = Object.keys(mydata2)
            keys2.forEach(key => {
                var span2 = modal2.getElementsByClassName(key)
                if (span2.length > 0)
                    span2[0].innerHTML = mydata2[key];
            })
            modal2.style.display = "block";

        }
        closename.onclick = function() {
            modal2.style.display = "none";
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

                const form = document.getElementById('gpsdelete')
                form.addEventListener('submit', e => {
                    e.preventDefault()
                    fetch(scriptURL, {
                            method: 'POST',
                            body: new FormData(document.getElementById('gpsdelete'))
                        })
                        .then(response =>
                            alert("Record has been updated.") +
                            location.reload()
                        )
                        .catch(error => console.error('Error!', error.message))
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
            const formId = 'email-form-' + item.itemid
            const form = document.forms[formId]
            form.addEventListener('submit', e => {
                e.preventDefault()
                fetch('mailer.php', {
                        method: 'POST',
                        body: new FormData(document.forms[formId])
                    })
                    .then(response =>
                        alert("Email has been sent.")
                    )
                    .catch(error => console.error('Error!', error.message))
            })
        })
    </script>

    <script>
        getPagination('#table-id');

        function getPagination(table) {
            var lastPage = 1;

            $('#maxRows')
                .on('change', function(evt) {
                    //$('.paginationprev').html('');						// reset pagination

                    lastPage = 1;
                    $('.pagination')
                        .find('li')
                        .slice(1, -1)
                        .remove();
                    var trnum = 0; // reset tr counter
                    var maxRows = parseInt($(this).val()); // get Max Rows from select option

                    if (maxRows == 5000) {
                        $('.pagination').hide();
                    } else {
                        $('.pagination').show();
                    }

                    var totalRows = $(table + ' tbody tr').length; // numbers of rows
                    $(table + ' tr:gt(0)').each(function() {
                        // each TR in  table and not the header
                        trnum++; // Start Counter
                        if (trnum > maxRows) {
                            // if tr number gt maxRows

                            $(this).hide(); // fade it out
                        }
                        if (trnum <= maxRows) {
                            $(this).show();
                        } // else fade in Important in case if it ..
                    }); //  was fade out to fade it in
                    if (totalRows > maxRows) {
                        // if tr total rows gt max rows option
                        var pagenum = Math.ceil(totalRows / maxRows); // ceil total(rows/maxrows) to get ..
                        //	numbers of pages
                        for (var i = 1; i <= pagenum;) {
                            // for each page append pagination li
                            $('.pagination #prev')
                                .before(
                                    '<li data-page="' +
                                    i +
                                    '">\
								  <span>' +
                                    i++ +
                                    '<span class="sr-only">(current)</span></span>\
								</li>'
                                )
                                .show();
                        } // end for i
                    } // end if row count > max rows
                    $('.pagination [data-page="1"]').addClass('active'); // add active class to the first li
                    $('.pagination li').on('click', function(evt) {
                        // on click each page
                        evt.stopImmediatePropagation();
                        evt.preventDefault();
                        var pageNum = $(this).attr('data-page'); // get it's number

                        var maxRows = parseInt($('#maxRows').val()); // get Max Rows from select option

                        if (pageNum == 'prev') {
                            if (lastPage == 1) {
                                return;
                            }
                            pageNum = --lastPage;
                        }
                        if (pageNum == 'next') {
                            if (lastPage == $('.pagination li').length - 2) {
                                return;
                            }
                            pageNum = ++lastPage;
                        }

                        lastPage = pageNum;
                        var trIndex = 0; // reset tr counter
                        $('.pagination li').removeClass('active'); // remove active class from all li
                        $('.pagination [data-page="' + lastPage + '"]').addClass('active'); // add active class to the clicked
                        // $(this).addClass('active');					// add active class to the clicked
                        limitPagging();
                        $(table + ' tr:gt(0)').each(function() {
                            // each tr in table not the header
                            trIndex++; // tr index counter
                            // if tr index gt maxRows*pageNum or lt maxRows*pageNum-maxRows fade if out
                            if (
                                trIndex > maxRows * pageNum ||
                                trIndex <= maxRows * pageNum - maxRows
                            ) {
                                $(this).hide();
                            } else {
                                $(this).show();
                            } //else fade in
                        }); // end of for each tr in table
                    }); // end of on click pagination list
                    limitPagging();
                })
                .val(5)
                .change();

            // end of on select change

            // END OF PAGINATION
        }

        function limitPagging() {
            // alert($('.pagination li').length)

            if ($('.pagination li').length > 7) {
                if ($('.pagination li.active').attr('data-page') <= 3) {
                    $('.pagination li:gt(5)').hide();
                    $('.pagination li:lt(5)').show();
                    $('.pagination [data-page="next"]').show();
                }
                if ($('.pagination li.active').attr('data-page') > 3) {
                    $('.pagination li:gt(0)').hide();
                    $('.pagination [data-page="next"]').show();
                    for (let i = (parseInt($('.pagination li.active').attr('data-page')) - 2); i <= (parseInt($('.pagination li.active').attr('data-page')) + 2); i++) {
                        $('.pagination [data-page="' + i + '"]').show();

                    }

                }
            }
        }
    </script>

    <a id="back-to-top" href="#" class="go-top" role="button"><i class="fa fa-angle-up"></i></a>
</body>

</html>