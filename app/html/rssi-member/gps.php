<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}

validation();

date_default_timezone_set('Asia/Kolkata'); ?>

<?php if ($role == 'Admin') {

    if (@$_POST['form-type'] == "addasset") {
        @$itemid = "A" . time();
        @$itemtype = $_POST['itemtype'];
        @$itemname = $_POST['itemname'];
        @$quantity = $_POST['quantity'];
        @$remarks = htmlspecialchars($_POST['remarks'], ENT_QUOTES, 'UTF-8');
        @$asset_status = $_POST['asset_status'];
        @$collectedby = strtoupper($_POST['collectedby']);
        @$now = date('Y-m-d H:i:s');
        if ($itemtype != "") {
            $gps = "INSERT INTO gps (itemid, date, itemtype, itemname, quantity, remarks, collectedby,asset_status) VALUES ('$itemid','$now','$itemtype','$itemname','$quantity','$remarks','$collectedby','$asset_status')";
            $gpshistory = "INSERT INTO gps_history (itemid, date, itemtype, itemname, quantity, remarks, collectedby,asset_status) VALUES ('$itemid','$now','$itemtype','$itemname','$quantity','$remarks','$collectedby','$asset_status')";
            $result = pg_query($con, $gps);
            $result = pg_query($con, $gpshistory);
            $cmdtuples = pg_affected_rows($result);
        }
    }

    @$taggedto = strtoupper($_GET['taggedto']);
    @$item_type = $_GET['item_type'];
    @$assetid = $_GET['assetid'];
    @$is_user = $_GET['is_user'];
    @$assetstatus = $_GET['assetstatus'];

    $conditions = [];

    if ($item_type != "ALL" && $item_type != "") {
        $conditions[] = "itemtype = '$item_type'";
    }

    if ($taggedto != "") {
        $conditions[] = "taggedto = '$taggedto'";
    }

    if ($assetid != "") {
        $conditions[] = "itemid = '$assetid'";
    }

    if ($assetstatus != "") {
        $conditions[] = "asset_status = '$assetstatus'";
    }

    $query = "SELECT * FROM gps
    LEFT JOIN (
        SELECT fullname AS tfullname, associatenumber AS tassociatenumber, phone AS tphone, email AS temail
        FROM rssimyaccount_members
    ) AS tmember ON gps.taggedto = tmember.tassociatenumber
    LEFT JOIN (
        SELECT fullname AS ifullname, associatenumber AS iassociatenumber, phone AS iphone, email AS iemail
        FROM rssimyaccount_members
    ) AS imember ON gps.collectedby = imember.iassociatenumber";

    if (!empty($conditions)) {
        $query .= " WHERE " . implode(" AND ", $conditions);
    }

    $query .= " ORDER BY date DESC";

    $gpsdetails = $query;
} ?>

<?php if ($role != 'Admin') {
    $gpsdetails = "SELECT * from gps 
    left join (select fullname as tfullname,associatenumber as tassociatenumber,phone as tphone,email as temail from rssimyaccount_members) as tmember ON gps.taggedto=tmember.tassociatenumber 
    left join (select fullname as ifullname,associatenumber as iassociatenumber,phone as iphone,email as iemail from rssimyaccount_members) as imember ON gps.collectedby=imember.iassociatenumber  where taggedto='$associatenumber' AND asset_status = 'Active' order by itemname asc";
} ?>
<?php
$result = pg_query($con, $gpsdetails);

if (!$result) {
    echo "An error occurred.\n";
    exit;
}

$resultArr = pg_fetch_all($result);

?>
<!doctype html>
<html lang="en">

<head>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=AW-11316670180"></script>
    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }
        gtag('js', new Date());

        gtag('config', 'AW-11316670180');
    </script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>GPS (Global Procurement System)</title>

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

    <style>
        .x-btn:focus,
        .button:focus,
        [type="submit"]:focus {
            outline: none;
        }

        #passwordHelpBlock {
            display: block;
        }

        .input-help {
            vertical-align: top;
            display: inline-block;
        }

        @media (max-width:767px) {

            #cw,
            #cw1 {
                width: 100% !important;
            }

        }

        #cw {
            width: 15%;
        }

        #cw1 {
            width: 20%;
        }
    </style>
   <!-- CSS Library Files -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
    <!-- JavaScript Library Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>
</head>

<!-- =========================
     NAVIGATION LINKS     
============================== -->

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>GPS</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Work</a></li>
                    <li class="breadcrumb-item active">GPS</li>
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
                            <?php if ($role == 'Admin') { ?>
                                <?php if (@$itemid != null && @$cmdtuples == 0) { ?>
                                    <div class="alert alert-danger alert-dismissible" role="alert" style="text-align: -webkit-center;">
                                        <a href="#" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></a>
                                        <span class="blink_me"><i class="bi bi-exclamation-triangle"></i></span>&nbsp;&nbsp;<span>ERROR: Oops, something wasn't right.</span>
                                    </div>
                                <?php } else if (@$cmdtuples == 1) { ?>
                                    <div class="alert alert-success alert-dismissible" role="alert" style="text-align: -webkit-center;">
                                        <a href="#" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></a>
                                        <i class="bi bi-check2-circle" style="font-size: medium;"></i>&nbsp;&nbsp;<span>Database has been updated successfully for asset id <?php echo @$itemid ?>.</span>
                                    </div>
                                    <script>
                                        if (window.history.replaceState) {
                                            window.history.replaceState(null, null, window.location.href);
                                        }
                                    </script>
                            <?php }
                            } ?>

                            <?php if ($role == 'Admin') { ?>
                                <form autocomplete="off" name="gps" id="gps" action="gps.php" method="POST">
                                    <div class="form-group" style="display: inline-block;">
                                        <div class="col2" style="display: inline-block;">

                                            <input type="hidden" name="form-type" type="text" value="addasset">

                                            <span class="input-help">
                                                <select name="itemtype" class="form-select" style="width:max-content; display:inline-block" required>
                                                    <?php if ($itemtype == null) { ?>
                                                        <option disabled selected hidden>Asset type</option>
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
                                                <input type="text" name="itemname" class="form-control" style="width:max-content; display:inline-block" placeholder="Item name" required>
                                                <small id="passwordHelpBlock" class="form-text text-muted">Asset name</small>
                                            </span>

                                            <span class="input-help">
                                                <input type="number" name="quantity" class="form-control" style="width:max-content; display:inline-block" placeholder="Quantity" min="1" required>
                                                <small id="passwordHelpBlock" class="form-text text-muted">Quantity</small>
                                            </span>
                                            <span class="input-help">
                                                <select name="asset_status" class="form-select" style="width:max-content; display:inline-block" required>
                                                    <?php if ($asset_status == null) { ?>
                                                        <option disabled selected hidden>Asset status</option>
                                                    <?php
                                                    } else { ?>
                                                        <option hidden selected><?php echo $asset_status ?></option>
                                                    <?php }
                                                    ?>
                                                    <option>Active</option>
                                                    <option>Inactive</option>
                                                </select>
                                                <small id="passwordHelpBlock" class="form-text text-muted">Asset status</small>
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
                                            Update</button>
                                    </div>
                                </form>

                                <form name="gpsdetails" id="gpsdetails" action="" method="GET">
                                    <div class="form-group" style="display: inline-block;">
                                        <div class="col2" style="display: inline-block;">

                                            <select name="item_type" class="form-select" style="width:max-content; display:inline-block">
                                                <?php if ($item_type == null) { ?>
                                                    <option disabled selected hidden>Asset type</option>
                                                <?php
                                                } else { ?>
                                                    <option hidden selected><?php echo $item_type ?></option>
                                                <?php }
                                                ?>
                                                <option>Purchased</option>
                                                <option>Donation</option>
                                                <option>ALL</option>
                                            </select>&nbsp;
                                            <span class="input-help">
                                                <select name="assetstatus" class="form-select" style="width:max-content; display:inline-block" required>
                                                    <?php if ($assetstatus == null) { ?>
                                                        <option disabled selected hidden>Asset status</option>
                                                    <?php
                                                    } else { ?>
                                                        <option hidden selected><?php echo $assetstatus ?></option>
                                                    <?php }
                                                    ?>
                                                    <option>Active</option>
                                                    <option>Inactive</option>
                                                </select>
                                                <small id="passwordHelpBlock" class="form-text text-muted">Asset status</small>
                                            </span>
                                            &nbsp;
                                            <input type="text" name="taggedto" class="form-control" style="width:max-content; display:inline-block" placeholder="Tagged to" value="<?php echo $taggedto ?>">
                                            &nbsp;
                                            <span class="input-help">
                                                <input type="text" name="assetid" class="form-control" style="width:max-content; display:inline-block" placeholder="Asset id" value="<?php echo $assetid ?>" required>
                                                <small id="passwordHelpBlock" class="form-text text-muted">Asset id</small>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col2 left" style="display: inline-block;">
                                        <button type="submit" name="search_by_id2" class="btn btn-primary btn-sm" style="outline: none;">
                                            <i class="bi bi-search"></i>&nbsp;Search</button>
                                    </div>
                                    <div id="filter-checks">
                                        <input type="checkbox" name="is_user" id="is_user" value="1" <?php if (isset($_GET['is_user'])) echo "checked='checked'"; ?> />
                                        <label for="is_user" style="font-weight: 400;">Search by Asset ID</label>
                                    </div>
                                </form>
                                <script>
                                    const checkbox = document.getElementById('is_user');
                                    const assetIdInput = document.getElementsByName('assetid')[0];
                                    const itemTypeInput = document.getElementsByName('item_type')[0];
                                    const taggedToInput = document.getElementsByName('taggedto')[0];
                                    const itemStatusInput = document.getElementsByName('assetstatus')[0];

                                    if ($('#is_user').not(':checked').length > 0) {

                                        assetIdInput.disabled = true;
                                        itemTypeInput.disabled = false;
                                        itemStatusInput.disabled = false;
                                        taggedToInput.disabled = false;

                                    } else {

                                        assetIdInput.disabled = false;
                                        itemTypeInput.disabled = true;
                                        itemStatusInput.disabled = true;
                                        taggedToInput.disabled = true;

                                    }
                                    checkbox.addEventListener('change', (event) => {
                                        if (event.target.checked) {
                                            assetIdInput.disabled = false;
                                            itemTypeInput.disabled = true;
                                            itemStatusInput.disabled = true;
                                            taggedToInput.disabled = true;

                                        } else {
                                            assetIdInput.disabled = true;
                                            itemTypeInput.disabled = false;
                                            itemStatusInput.disabled = false;
                                            taggedToInput.disabled = false;

                                        }
                                    });
                                </script>

                            <?php } ?>
                            <div class="col" style="display: inline-block; width:100%; text-align:right">
                                Record count:&nbsp;<?php echo sizeof($resultArr) ?><br><br>
                                <form method="POST" action="export_function.php">
                                    <input type="hidden" value="gps" name="export_type" />
                                    <input type="hidden" value="<?php echo @$item_type ?>" name="item_type" />
                                    <input type="hidden" value="<?php echo ($role !== 'Admin') ? $associatenumber : $taggedto; ?>" name="taggedto" />
                                    <input type="hidden" value="<?php echo @$assetid ?>" name="assetid" />
                                    <input type="hidden" value="<?php echo ($role !== 'Admin') ? 'Active' : $assetstatus; ?>" name="asset_status" />

                                    <button type="submit" id="export" name="export" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none;
                        padding: 0px;
                        border: none;" title="Export CSV"><i class="bi bi-file-earmark-excel" style="font-size:large;"></i></button>
                                </form>
                            </div>

                            <?php echo '
                        <div class="table-responsive">
                        <table class="table" id="table-id">
                        <thead>
                            <tr>
                                <th scope="col">Asset Id</th>
                                <th scope="col" id="cw">Asset name</th>
                                <th scope="col">Quantity</th>' ?>
                            <?php if ($role == 'Admin') { ?>
                                <?php echo '
                                <th scope="col">Asset type</th>
                                <th scope="col" id="cw1">Remarks</th>' ?>
                            <?php }
                            echo '
                                <th scope="col">Issued by</th>
                                <th scope="col">Tagged to</th>
                                <th scope="col">Status</th>
                                <th scope="col">Last updated on</th>
                                <th scope="col"></th></tr>
                        </thead>' ?>
                            <?php if (sizeof($resultArr) > 0) { ?>
                                <?php
                                echo '<tbody>';
                                foreach ($resultArr as $array) {
                                    echo '<tr>
                                <td>
                                <a href="gps_history.php?assetid=' . $array['itemid'] . '" target="_blank" title="Asset History">' . $array['itemid'] . '</a>
                                </td><td>' ?>

                                    <?php if (@strlen($array['itemname']) <= 50) {

                                        echo $array['itemname'] ?>

                                    <?php } else { ?>

                                        <?php echo substr($array['itemname'], 0, 50) .
                                            '&nbsp;...&nbsp;<button type="button" href="javascript:void(0)" onclick="showname(\'' . $array['itemid'] . '\')" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Details">
                        <i class="bi bi-box-arrow-up-right"></i></button>' ?>

                                    <?php }
                                    echo '</td><td>' . $array['quantity'] . '</td>' ?>


                                    <?php if ($role == 'Admin') { ?>
                                        <?php echo '<td>' . $array['itemtype'] . '</td><td>' ?>
                                        <?php if (@strlen($array['remarks']) <= 90) {

                                            echo $array['remarks'] ?>

                                        <?php } else { ?>

                                            <?php echo substr($array['remarks'], 0, 90) .
                                                '&nbsp;...&nbsp;<button type="button" href="javascript:void(0)" onclick="showremarks(\'' . $array['itemid'] . '\')" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Details">
                                <i class="bi bi-box-arrow-up-right"></i></button>' ?>
                                        <?php }
                                        echo '</td>' ?>
                                    <?php } ?>

                                    <?php echo '
                                <td>' . $array['collectedby'] . '<br>' . $array['ifullname'] . '</td>
                                <td>' . $array['taggedto'] . '<br>' . $array['tfullname'] . '</td>
                                <td>' . $array['asset_status'] . '</td>
                                <td>' ?>
                                    <?php if ($array['lastupdatedon'] != null) { ?>

                                        <?php echo @date("d/m/Y g:i a", strtotime($array['lastupdatedon'])) ?>&nbsp;by&nbsp;
                                        <?php echo $array['lastupdatedby'] ?>

                                    <?php } else {
                                    }
                                    echo '</td><td>' ?>


                                    <?php if ($role == 'Admin') { ?>

                                        <?php if ($array['collectedby'] == $associatenumber) { ?>
                                            <?php echo '
                                <button type="button" href="javascript:void(0)" onclick="showDetails(\'' . $array['itemid'] . '\')" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Details">
                                <i class="bi bi-box-arrow-up-right" style="color:#777777" title="Show Details" display:inline;></i></button>' ?>
                                        <?php } else { ?>
                                            <?php echo '&nbsp;<i class="bi bi-box-arrow-up-right" style="color:#A2A2A2;" title="Show Details"></i>' ?>
                                        <?php } ?>

                                        <?php if ($array['tphone'] != null && $array['collectedby'] == $associatenumber) { ?>
                                            <?php echo '

                                    &nbsp;<a href="https://api.whatsapp.com/send?phone=91' . $array['tphone'] . '&text=Dear ' . $array['tfullname'] . ',%0A%0AAsset Allocation has been done in Global Procurement System.%0A%0AAsset Details%0A%0AItem Name – ' . $array['itemname'] . '%0AQuantity – ' . $array['quantity'] . '%0AAllocated to – ' . $array['tfullname'] . ' (' . $array['taggedto'] . ')%0AAsset ID – ' . $array['itemid'] . '%0AAllocated by – ' . $array['ifullname'] . ' (' . $array['collectedby'] . ')%0A%0AIn case of any concerns kindly contact Asset officer (refer Allocated by in the table).%0A%0A--RSSI%0A%0A**This is an automatically generated SMS
                                " target="_blank"><i class="bi bi-whatsapp" style="color:#444444;" title="Send SMS ' . $array['tphone'] . '"></i></a>' ?>
                                        <?php } else { ?>
                                            <?php echo '&nbsp;<i class="bi bi-whatsapp" style="color:#A2A2A2;" title="Send SMS"></i>' ?>
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
                             type="submit"><i class="bi bi-envelope-at" style="color:#444444;" title="Send Email ' . $array['temail'] . '"></i></button>
                        </form>' ?>
                                        <?php } else { ?>
                                            <?php echo '&nbsp;<i class="bi bi-envelope-at" style="color:#A2A2A2;" title="Send Email"></i>' ?>
                                        <?php } ?>

                                        <!-- <?php if ($array['collectedby'] == $associatenumber) { ?>
                                        <?php echo '
                                <form id="gpsdelete" action="#" method="POST" style="display: -webkit-inline-box;">
                                <input type="hidden" name="form-type" type="text" value="gpsdelete">
                                <input type="hidden" name="gpsid" type="text" value="' . $array['itemid'] . '">

                                &nbsp;<button type="submit" onclick=validateForm() style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Delete ' . $array['itemid'] . '"><i class="bi bi-x-lg"></i></button> </form>' ?>
                                    <?php } else { ?>
                                        <?php echo '&nbsp;<i class="bi bi-x-lg" style="color:#A2A2A2;" title="Delete ' . $array['itemid'] . '"></i>' ?>
                                    <?php } ?> -->

                                <?php echo '</td></tr>';
                                    }
                                } ?>
                            <?php
                            } else if (@$taggedto == null && @$item_type == null && @$assetid == null && @$assetstatus == null) {
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
                            </table>
                            </div>';
                            ?>

                            <!--------------- POP-UP BOX ------------
-------------------------------------->
                            <style>
                                .modal {
                                    background-color: rgba(0, 0, 0, 0.4);
                                    /* Black w/ opacity */
                                }
                            </style>

                            <!-- Modal content -->
                            <div class="modal" id="myModal" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-xl">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h1 class="modal-title fs-5" id="exampleModalLabel">GPS Details</h1>
                                            <button type="button" id="closedetails-header" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">

                                            <div style="width:100%; text-align:right">
                                                <p class="badge bg-info"><span class="itemid"></span></p>

                                            </div>

                                            <form id="gpsform" action="#" method="POST">
                                                <div class="form-group">
                                                    <div class="col2" style="display: inline-block;">
                                                        <input type="hidden" class="form-control" name="itemid1" id="itemid1" type="text" readonly>
                                                        <input type="hidden" class="form-control" name="form-type" type="text" value="gpsedit" readonly>
                                                        <input type="hidden" class="form-control" name="updatedby" type="text" value="<?php echo $associatenumber ?>" readonly>

                                                        <span class="input-help">
                                                            <select name="itemtype" id="itemtype" class="form-select" style="width:max-content; display:inline-block" required>
                                                                <?php if ($itemtype == null) { ?>
                                                                    <option disabled selected hidden>Item type</option>
                                                                <?php } else { ?>
                                                                    <option hidden selected><?php echo $itemtype ?></option>
                                                                <?php } ?>
                                                                <option>Purchased</option>
                                                                <option>Donation</option>
                                                            </select>
                                                            <small id="passwordHelpBlock" class="form-text text-muted">Item type*</small>
                                                        </span>

                                                        <span class="input-help">
                                                            <input type="text" name="itemname" id="itemname" class="form-control" style="width:max-content; display:inline-block" placeholder="Item name" required>
                                                            <small id="passwordHelpBlock" class="form-text text-muted">Item name*</small>
                                                        </span>

                                                        <span class="input-help">
                                                            <input type="number" name="quantity" id="quantity" class="form-control" style="width:max-content; display:inline-block" placeholder="Quantity" min="1" required>
                                                            <small id="passwordHelpBlock" class="form-text text-muted">Quantity*</small>
                                                        </span>

                                                        <span class="input-help">
                                                            <select name="asset_status" id="asset_status" class="form-select" style="width:max-content; display:inline-block" required>
                                                                <?php if ($asset_status == null) { ?>
                                                                    <option disabled selected hidden>Asset status</option>
                                                                <?php
                                                                } else { ?>
                                                                    <option hidden selected><?php echo $asset_status ?></option>
                                                                <?php }
                                                                ?>
                                                                <option>Active</option>
                                                                <option>Inactive</option>
                                                            </select>
                                                            <small id="passwordHelpBlock" class="form-text text-muted">Asset status</small>
                                                        </span>

                                                        <span class="input-help">
                                                            <textarea type="text" name="remarks" id="remarks" class="form-control" style="width:max-content; display:inline-block" placeholder="Remarks" value=""></textarea>
                                                            <small id="passwordHelpBlock" class="form-text text-muted">Remarks (Optional)</small>
                                                        </span>

                                                        <span class="input-help">
                                                            <input type="text" name="collectedby" id="collectedby" class="form-control" style="width:max-content; display:inline-block" placeholder="Issued by" required>
                                                            <small id="passwordHelpBlock" class="form-text text-muted">Issued by*</small>
                                                        </span>
                                                    </div>

                                                    <div class="col2" style="display: inline-block;">
                                                        <span class="input-help">
                                                            <input type="text" name="taggedto" id="taggedto" class="form-control" style="width:max-content; display:inline-block" placeholder="Tagged to" value="">
                                                            <small id="passwordHelpBlock" class="form-text text-muted">Tagged to</small>
                                                        </span>

                                                        <button type="submit" name="search_by_id3" class="btn btn-danger btn-sm" style="outline: none;">Update</button>
                                                    </div>
                                                </div>
                                            </form>

                                            <div class="modal-footer">
                                                <button type="button" id="closedetails-footer" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <script>
                                var data = <?php echo json_encode($resultArr) ?>

                                // Get the modal
                                var modal = document.getElementById("myModal");
                                // Get the <span> element that closes the modal
                                var closedetails = [
                                    document.getElementById("closedetails-header"),
                                    document.getElementById("closedetails-footer")
                                ];

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
                                    if (mydata["asset_status"] !== null) {
                                        profile = document.getElementById("asset_status")
                                        profile.value = mydata["asset_status"]
                                    }
                                    profile = document.getElementById("itemid1")
                                    profile.value = mydata["itemid"]
                                }
                                // When the user clicks the button, open the modal 
                                // When the user clicks on <span> (x), close the modal
                                closedetails.forEach(function(element) {
                                    element.addEventListener("click", closeModal);
                                });

                                function closeModal() {
                                    var modal1 = document.getElementById("myModal");
                                    modal1.style.display = "none";
                                }
                                // When the user clicks anywhere outside of the modal, close it
                                window.onclick = function(event) {
                                    // if (event.target == modal) {
                                    //     modal.style.display = "none";
                                    // } else 
                                    if (event.target == modal1) {
                                        modal1.style.display = "none";
                                    } else if (event.target == modal2) {
                                        modal2.style.display = "none";
                                    }
                                }
                            </script>


                            <div class="modal" id="myModal1" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h1 class="modal-title fs-5" id="exampleModalLabel">Remarks</h1>
                                            <button type="button" id="closeremarks-header" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">

                                            <div style="width:100%; text-align:right">
                                                <p class="badge bg-info" style="display: inline !important;"><span class="itemid"></span></p>
                                            </div>

                                            <span class="remarks"></span>
                                            <div class="modal-footer">
                                                <button type="button" id="closeremarks-footer" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <script>
                                var data1 = <?php echo json_encode($resultArr) ?>

                                // Get the modal
                                var modal1 = document.getElementById("myModal1");
                                var closeremarks = [
                                    document.getElementById("closeremarks-header"),
                                    document.getElementById("closeremarks-footer")
                                ];

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
                                // When the user clicks on <span> (x), close the modal
                                closeremarks.forEach(function(element) {
                                    element.addEventListener("click", closeModal);
                                });

                                function closeModal() {
                                    var modal1 = document.getElementById("myModal1");
                                    modal1.style.display = "none";
                                }
                                // When the user clicks anywhere outside of the modal, close it SEE OTHER SCRIPT
                            </script>

                            <div class="modal" id="myModal2" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h1 class="modal-title fs-5" id="exampleModalLabel">Item name</h1>
                                            <button type="button" id="closename-header" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">

                                            <div style="width:100%; text-align:right">
                                                <p class="badge bg-info"><span class="itemid"></span></p>

                                            </div>
                                            <span class="itemname"></span>
                                            <div class="modal-footer">
                                                <button type="button" id="closename-footer" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <script>
                                var data2 = <?php echo json_encode($resultArr) ?>

                                // Get the modal
                                var modal2 = document.getElementById("myModal2");
                                var closename = [
                                    document.getElementById("closename-header"),
                                    document.getElementById("closename-footer")
                                ];

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
                                // When the user clicks on <span> (x), close the modal
                                closename.forEach(function(element) {
                                    element.addEventListener("click", closeModal);
                                });

                                function closeModal() {
                                    var modal1 = document.getElementById("myModal2");
                                    modal1.style.display = "none";
                                }
                                // When the user clicks anywhere outside of the modal, close it SEE OTHER SCRIPT
                            </script>

                            <script>
                                var data = <?php echo json_encode($resultArr) ?>;
                                //For form submission - to update Remarks
                                const scriptURL = 'payment-api.php'
                                const form = document.getElementById('gpsform')
                                form.addEventListener('submit', e => {
                                    e.preventDefault()
                                    fetch(scriptURL, {
                                            method: 'POST',
                                            body: new FormData(document.getElementById('gpsform'))
                                        })
                                        .then(response => response.text())
                                        .then(result => {
                                            if (result === 'success') {
                                                alert("Record has been updated.");
                                                location.reload();
                                            } else {
                                                alert("Error updating record. Please try again later or contact support.");
                                            }
                                        })
                                        .catch(error => {
                                            console.error('Error!', error.message);
                                        });
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
    <script>
        $(document).ready(function() {
            $('#table-id').DataTable({
                // paging: false,
                "order": [] // Disable initial sorting
                // other options...
            });
        });
    </script>

</body>

</html>