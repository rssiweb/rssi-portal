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

    if (isset($_POST['form-type']) && $_POST['form-type'] == "addasset") {
        $itemid = "A" . time();
        $itemtype = isset($_POST['itemtype']) ? $_POST['itemtype'] : '';
        $itemname = isset($_POST['itemname']) ? $_POST['itemname'] : '';
        $quantity = isset($_POST['quantity']) ? $_POST['quantity'] : '';
        $remarks = isset($_POST['remarks']) ? htmlspecialchars($_POST['remarks'], ENT_QUOTES, 'UTF-8') : '';
        $asset_status = isset($_POST['asset_status']) ? $_POST['asset_status'] : '';
        $collectedby = isset($_POST['collectedby']) ? strtoupper($_POST['collectedby']) : '';
        $now = date('Y-m-d H:i:s');
        $updated_by = $collectedby; // Or whichever user is adding the asset

        if ($itemtype != "") {
            // Insert into gps table
            $gps_query = "INSERT INTO gps (itemid, date, itemtype, itemname, quantity, remarks, collectedby, asset_status) 
                      VALUES ('$itemid', '$now', '$itemtype', '$itemname', '$quantity', '$remarks', '$collectedby', '$asset_status')";
            pg_query($con, $gps_query);

            // Prepare the changes array (only what’s relevant)
            $changes = [
                'itemtype' => $itemtype,
                'itemname' => $itemname,
                'quantity' => $quantity,
                'asset_status' => $asset_status,
                'collectedby' => $collectedby,
                'remarks' => $remarks
            ];
            $changes_json = json_encode($changes);

            // Insert into gps_history table with only required columns
            $history_query = "INSERT INTO gps_history (itemid, update_type, updatedby, date, changes, remarks) 
                          VALUES ('$itemid', 'add_asset', '$updated_by', '$now', '$changes_json', '$remarks')";
            pg_query($con, $history_query);
        }
    }

    $taggedto = isset($_GET['taggedto']) ? strtoupper($_GET['taggedto']) : '';
    $item_type = isset($_GET['item_type']) ? $_GET['item_type'] : '';
    $assetid = isset($_GET['assetid']) ? $_GET['assetid'] : '';
    $is_user = isset($_GET['is_user']) ? $_GET['is_user'] : '';
    $assetstatus = isset($_GET['assetstatus']) ? $_GET['assetstatus'] : '';

    $conditions = [];

    if ($item_type != "ALL" && $item_type != "") {
        $conditions[] = "itemtype = '$item_type'";
    }

    if ($taggedto != "") {
        $conditions[] = "taggedto = '$taggedto'";
    }

    if ($assetid != "") {
        $conditions[] = "(itemid = '$assetid' OR itemname ILIKE '%$assetid%')";
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
}
?>


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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

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

        /* Add this to your style section */
        .table tbody tr {
            cursor: pointer;
        }

        .select-checkbox {
            width: 20px;
            height: 20px;
        }

        .table td:first-child {
            text-align: center;
            vertical-align: middle;
        }
    </style>
    <!-- CSS Library Files -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
    <!-- JavaScript Library Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <!-- Include Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
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
                                            <i class="bi bi-plus-circle"></i> Add Asset</button>
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
                                                <input type="text" name="assetid" class="form-control" style="width:max-content; display:inline-block" placeholder="Asset Id or name" value="<?php echo $assetid ?>" required>
                                                <small id="passwordHelpBlock" class="form-text text-muted">Asset Id or name</small>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col2 left" style="display: inline-block;">
                                        <button type="submit" name="search_by_id2" class="btn btn-primary btn-sm" style="outline: none;">
                                            <i class="bi bi-search"></i>&nbsp;Search</button>
                                    </div>
                                    <div id="filter-checks">
                                        <input class="form-check-input" type="checkbox" name="is_user" id="is_user" value="1" <?php if (isset($_GET['is_user'])) echo "checked='checked'"; ?> />
                                        <label for="is_user" style="font-weight: 400;">Search by Asset Id or name</label>
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
                            <?php if ($role == 'Admin'): ?>
                                <div class="bulk-update-section mb-3 p-3 border rounded" style="display: none;">
                                    <h5>Bulk Update Selected Assets <span id="selected-count-badge" class="badge bg-primary ms-2">0 selected</span></h5>
                                    <form id="bulk-update-form" method="POST" action="gps_bulk_update.php">
                                        <div id="selected-assets-container"></div>

                                        <div class="row">
                                            <div class="col-md-2">
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" id="update-tagged-to" name="update_tagged_to">
                                                    <label class="form-check-label" for="update-tagged-to">
                                                        Update Tagged To
                                                    </label>
                                                </div>
                                                <select class="form-select select2" id="tagged-to-select" name="tagged_to" disabled>
                                                    <option value="">Select Associate</option>
                                                    <!-- Options will be loaded via AJAX -->
                                                </select>
                                            </div>

                                            <div class="col-md-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="update-status" name="update_status">
                                                    <label class="form-check-label" for="update-status">
                                                        Update Status
                                                    </label>
                                                </div>
                                                <select class="form-select mt-2" id="status-select" name="status" disabled>
                                                    <option value="">Select Status</option>
                                                    <option value="Active">Active</option>
                                                    <option value="Inactive">Inactive</option>
                                                </select>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="update-remarks">Remarks (Optional)</label>
                                                    <textarea class="form-control" id="update-remarks" name="remarks" rows="2"></textarea>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mt-3">
                                            <button type="submit" class="btn btn-primary btn-sm">Apply Updates</button>
                                            <button type="button" class="btn btn-secondary btn-sm" id="cancel-bulk-update">Cancel</button>
                                        </div>
                                    </form>
                                </div>
                            <?php endif; ?>
                            <div class="table-responsive">
                                <table class="table" id="table-id">
                                    <thead>
                                        <tr>
                                            <th scope="col" width="50">
                                                <input type="checkbox" id="select-all-checkbox" class="form-check-input">
                                            </th>
                                            <th scope="col">Asset Id</th>
                                            <th scope="col" id="cw">Asset name</th>
                                            <th scope="col">Quantity</th>
                                            <?php if ($role == 'Admin'): ?>
                                                <th scope="col">Asset type</th>
                                                <th scope="col" id="cw1">Remarks</th>
                                            <?php endif; ?>
                                            <th scope="col">Issued by</th>
                                            <th scope="col">Tagged to</th>
                                            <th scope="col">Status</th>
                                            <th scope="col">Last updated on</th>
                                            <th scope="col"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (sizeof($resultArr) > 0): ?>
                                            <?php foreach ($resultArr as $array): ?>
                                                <tr>
                                                    <td>
                                                        <input type="checkbox" class="form-check-input asset-checkbox" name="selected_assets[]" value="<?= $array['itemid'] ?>">
                                                    </td>
                                                    <td>
                                                        <?= $array['itemid'] ?>
                                                    </td>
                                                    <td>
                                                        <?php if (strlen($array['itemname']) <= 50): ?>
                                                            <?= $array['itemname'] ?>
                                                        <?php else: ?>
                                                            <?= substr($array['itemname'], 0, 50) ?>&nbsp;...&nbsp;
                                                            <button class="dropdown-item" type="button" onclick="showName('<?= $array['itemid'] ?>')">
                                                                <i class="bi bi-box-arrow-up-right"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= $array['quantity'] ?></td>
                                                    <?php if ($role == 'Admin'): ?>
                                                        <td><?= $array['itemtype'] ?></td>
                                                        <td>
                                                            <?php if (isset($array['remarks']) && strlen($array['remarks']) <= 90): ?>
                                                                <?= htmlspecialchars($array['remarks']) ?>
                                                            <?php elseif (isset($array['remarks'])): ?>
                                                                <?= htmlspecialchars(substr($array['remarks'], 0, 90)) ?>&nbsp;...&nbsp;
                                                                <button class="dropdown-item" type="button" onclick="showRemarks('<?= htmlspecialchars($array['itemid']) ?>')">
                                                                    <i class="bi bi-box-arrow-up-right"></i>
                                                                </button>
                                                            <?php else: ?>
                                                                <span>No remarks</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    <?php endif; ?>
                                                    <td><?= $array['collectedby'] ?><br><?= $array['ifullname'] ?></td>
                                                    <td><?= $array['taggedto'] ?><br><?= $array['tfullname'] ?></td>
                                                    <td><?= $array['asset_status'] ?></td>
                                                    <td>
                                                        <?php if ($array['lastupdatedon'] != null): ?>
                                                            <?= date("d/m/Y g:i a", strtotime($array['lastupdatedon'])) ?>&nbsp;by&nbsp;<?= $array['lastupdatedby'] ?>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div class="dropdown">
                                                            <button class="btn btn-sm btn-link text-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="padding: 0.15rem 0.5rem;">
                                                                <i class="bi bi-three-dots-vertical"></i>
                                                            </button>
                                                            <ul class="dropdown-menu">
                                                                <?php if ($role == 'Admin'): ?>
                                                                    <?php if ($array['collectedby'] == $associatenumber): ?>
                                                                        <li>
                                                                            <a class="dropdown-item" href="javascript:void(0)" onclick="showDetails('<?= $array['itemid'] ?>')">
                                                                                <i class="bi bi-box-arrow-up-right"></i> Show Details
                                                                            </a>
                                                                        </li>
                                                                    <?php else: ?>
                                                                        <li>
                                                                            <span class="dropdown-item disabled">
                                                                                <i class="bi bi-box-arrow-up-right text-muted"></i> Show Details
                                                                            </span>
                                                                        </li>
                                                                    <?php endif; ?>

                                                                    <li>
                                                                        <?php if ($array['tphone'] != null && $array['collectedby'] == $associatenumber): ?>
                                                                            <a class="dropdown-item" href="https://api.whatsapp.com/send?phone=91<?= $array['tphone'] ?>&text=Dear <?= urlencode($array['tfullname']) ?>,%0A%0AAsset Allocation has been done in Global Procurement System.%0A%0AItem Name – <?= urlencode($array['itemname']) ?>%0AQuantity – <?= urlencode($array['quantity']) ?>%0AAllocated to – <?= urlencode($array['tfullname']) ?> (<?= urlencode($array['taggedto']) ?>)%0AAsset ID – <?= urlencode($array['itemid']) ?>%0AAllocated by – <?= urlencode($array['ifullname']) ?> (<?= urlencode($array['collectedby']) ?>)%0A%0AIn case of any concerns kindly contact Asset officer.%0A%0A--RSSI%0A%0A**This is an automatically generated SMS" target="_blank">
                                                                                <i class="bi bi-whatsapp"></i> Send WhatsApp
                                                                            </a>
                                                                        <?php else: ?>
                                                                            <span class="dropdown-item disabled">
                                                                                <i class="bi bi-whatsapp text-muted"></i> Send WhatsApp
                                                                            </span>
                                                                        <?php endif; ?>
                                                                    </li>

                                                                    <li>
                                                                        <?php if ($array['temail'] != null && $array['collectedby'] == $associatenumber): ?>
                                                                            <form action="#" name="email-form-<?= $array['itemid'] ?>" method="POST" class="dropdown-item p-0 m-0">
                                                                                <input type="hidden" name="template" value="gps">
                                                                                <input type="hidden" name="data[itemname]" value="<?= $array['itemname'] ?>">
                                                                                <input type="hidden" name="data[itemid]" value="<?= $array['itemid'] ?>">
                                                                                <input type="hidden" name="data[quantity]" value="<?= $array['quantity'] ?>">
                                                                                <input type="hidden" name="data[taggedto]" value="<?= $array['taggedto'] ?>">
                                                                                <input type="hidden" name="data[tfullname]" value="<?= $array['tfullname'] ?>">
                                                                                <input type="hidden" name="data[collectedby]" value="<?= $array['collectedby'] ?>">
                                                                                <input type="hidden" name="data[ifullname]" value="<?= $array['ifullname'] ?>">
                                                                                <input type="hidden" name="email" value="<?= $array['temail'] ?>">
                                                                                <button type="submit" class="btn btn-link dropdown-item text-start w-100" style="padding-left: 1rem;">
                                                                                    <i class="bi bi-envelope-at"></i> Send Email
                                                                                </button>
                                                                            </form>
                                                                        <?php else: ?>
                                                                            <span class="dropdown-item disabled">
                                                                                <i class="bi bi-envelope-at text-muted"></i> Send Email
                                                                            </span>
                                                                        <?php endif; ?>
                                                                    </li>
                                                                    <li>
                                                                        <!-- Asset History link styled like a button -->
                                                                        <a href="gps_history.php?assetid=<?= htmlspecialchars($array['itemid']) ?>" target="_blank" title="Asset History"
                                                                            class="btn btn-link dropdown-item text-start w-100" style="padding-left: 1rem;">
                                                                            <i class="bi bi-clock-history"></i> Asset History
                                                                        </a>
                                                                    </li>
                                                                <?php endif; ?>
                                                            </ul>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php elseif (@$taggedto == null && @$item_type == null && @$assetid == null && @$assetstatus == null): ?>
                                            <tr>
                                                <td colspan="11">Please select Filter value.</td>
                                            </tr>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="11">No record was found for the selected filter value.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- GPS Details Modal -->
                            <div class="modal fade" id="myModal" tabindex="-1" aria-labelledby="myModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-xl">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h1 class="modal-title fs-5" id="myModalLabel">GPS Details</h1>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <form id="gpsform" action="#" method="POST">
                                            <div class="modal-body">
                                                <div class="text-end mb-3">
                                                    <p class="badge bg-info"><span class="itemid"></span></p>
                                                </div>

                                                <input type="hidden" name="itemid1" id="itemid1">
                                                <input type="hidden" name="form-type" value="gpsedit">
                                                <input type="hidden" name="updatedby" value="<?= $associatenumber ?>">

                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <select name="itemtype" id="itemtype" class="form-select" required>
                                                            <option disabled selected hidden>Item type</option>
                                                            <option>Purchased</option>
                                                            <option>Donation</option>
                                                        </select>
                                                        <small class="text-muted">Item type*</small>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <input type="text" name="itemname" id="itemname" class="form-control" placeholder="Item name" required>
                                                        <small class="text-muted">Item name*</small>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <input type="number" name="quantity" id="quantity" class="form-control" placeholder="Quantity" min="1" required>
                                                        <small class="text-muted">Quantity*</small>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <select name="asset_status" id="asset_status" class="form-select" required>
                                                            <option disabled selected hidden>Asset status</option>
                                                            <option>Active</option>
                                                            <option>Inactive</option>
                                                        </select>
                                                        <small class="text-muted">Asset status*</small>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <textarea name="remarks" id="remarks" class="form-control" placeholder="Remarks"></textarea>
                                                        <small class="text-muted">Remarks (Optional)</small>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <input type="text" name="collectedby" id="collectedby" class="form-control" placeholder="Issued by" required>
                                                        <small class="text-muted">Issued by*</small>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <input type="text" name="taggedto" id="taggedto" class="form-control" placeholder="Tagged to">
                                                        <small class="text-muted">Tagged to</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                <button type="submit" name="search_by_id3" class="btn btn-primary" id="update-button">
                                                    <span class="button-text">Save changes</span>
                                                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Remarks Modal -->
                            <div class="modal fade" id="myModal1" tabindex="-1" aria-labelledby="myModal1Label" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h1 class="modal-title fs-5" id="myModal1Label">Remarks</h1>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="text-end mb-3">
                                                <p class="badge bg-info"><span class="itemid"></span></p>
                                            </div>
                                            <div class="remarks"></div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Item Name Modal -->
                            <div class="modal fade" id="myModal2" tabindex="-1" aria-labelledby="myModal2Label" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h1 class="modal-title fs-5" id="myModal2Label">Item Name</h1>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="text-end mb-3">
                                                <p class="badge bg-info"><span class="itemid"></span></p>
                                            </div>
                                            <div class="itemname"></div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
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
        // Assuming data is provided from PHP
        var data = <?= json_encode($resultArr) ?>;

        // Initialize modals
        var modal = new bootstrap.Modal(document.getElementById('myModal'));
        var modal1 = new bootstrap.Modal(document.getElementById('myModal1'));
        var modal2 = new bootstrap.Modal(document.getElementById('myModal2'));

        function findItem(id) {
            return data.find(item => item.itemid == id);
        }

        function showDetails(id) {
            var item = findItem(id);
            if (!item) return;

            document.querySelector('#myModal .itemid').textContent = item.itemid;
            document.getElementById('itemid1').value = item.itemid;
            document.getElementById('itemtype').value = item.itemtype || "";
            document.getElementById('itemname').value = item.itemname || "";
            document.getElementById('quantity').value = item.quantity || "";
            document.getElementById('asset_status').value = item.asset_status || "";
            document.getElementById('remarks').value = item.remarks || "";
            document.getElementById('collectedby').value = item.collectedby || "";
            document.getElementById('taggedto').value = item.taggedto || "";

            modal.show();
        }

        function showRemarks(id) {
            var item = findItem(id);
            if (!item) return;

            document.querySelector('#myModal1 .itemid').textContent = item.itemid;
            document.querySelector('#myModal1 .remarks').textContent = item.remarks || "No remarks available.";

            modal1.show();
        }

        function showName(id) {
            var item = findItem(id);
            if (!item) return;

            document.querySelector('#myModal2 .itemid').textContent = item.itemid;
            document.querySelector('#myModal2 .itemname').textContent = item.itemname || "No item name available.";

            modal2.show();
        }
    </script>

    <script>
        const scriptURL = 'payment-api.php';
        const form = document.getElementById('gpsform');
        const updateButton = document.getElementById('update-button');
        const buttonText = updateButton.querySelector('.button-text');
        const spinner = updateButton.querySelector('.spinner-border');

        form.addEventListener('submit', e => {
            e.preventDefault();

            // Show spinner and disable button
            buttonText.textContent = 'Updating...';
            spinner.classList.remove('d-none');
            updateButton.disabled = true;

            const formData = new FormData(form);

            fetch(scriptURL, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(result => {
                    // Reset button state
                    resetButton();

                    if (result === 'success') {
                        alert("Record has been successfully updated.");
                        location.reload();
                    } else if (result === 'nochange') {
                        alert("No changes detected.");
                    } else if (result === 'invalid') {
                        alert("Invalid asset ID.");
                    } else if (result === 'unauthorized') {
                        alert("You are not authorized to perform this action.");
                    } else {
                        alert("Error updating record. Please try again or contact support.");
                    }
                })
                .catch(error => {
                    // Reset button state
                    resetButton();

                    console.error('Error!', error.message);
                    alert("Network error or server issue occurred.");
                });
        });

        // Function to reset button to original state
        function resetButton() {
            if (buttonText && spinner && updateButton) {
                buttonText.textContent = 'Save changes';
                spinner.classList.add('d-none');
                updateButton.disabled = false;
            }
        }

        // Reset button state if modal is closed without submitting
        $('#myModal').on('hidden.bs.modal', function() {
            resetButton();
        });

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
        $(document).ready(function() {
            // Select all checkbox functionality
            $('#select-all-checkbox').change(function() {
                $('.asset-checkbox').prop('checked', this.checked);
                toggleBulkUpdateSection();
                updateRowSelection();
            });

            // Individual checkbox functionality using event delegation
            $(document).on('change', '.asset-checkbox', function() {
                toggleBulkUpdateSection();
                updateRowSelection();

                // If all checkboxes are unchecked, uncheck select all
                if ($('.asset-checkbox:checked').length === 0) {
                    $('#select-all-checkbox').prop('checked', false);
                }
                // If all checkboxes are checked, check select all
                else if ($('.asset-checkbox:checked').length === $('.asset-checkbox').length) {
                    $('#select-all-checkbox').prop('checked', true);
                }
            });

            // Row selection with pointer using event delegation
            $(document).on('click', '#table-id tbody tr', function(e) {
                // Don't select row if clicking on checkbox, link, or dropdown
                if (e.target.type !== 'checkbox' &&
                    e.target.tagName !== 'A' &&
                    !$(e.target).hasClass('dropdown-toggle') &&
                    !$(e.target).closest('.dropdown').length) {

                    const checkbox = $(this).find('.asset-checkbox');
                    checkbox.prop('checked', !checkbox.prop('checked'));
                    checkbox.trigger('change');
                }
            });

            // Update row selection styling
            function updateRowSelection() {
                $('#table-id tbody tr').removeClass('selected');
                $('.asset-checkbox:checked').each(function() {
                    $(this).closest('tr').addClass('selected');
                });
            }

            // Toggle bulk update section based on selected items
            function toggleBulkUpdateSection() {
                const selectedCount = $('.asset-checkbox:checked').length;
                const selectedCountBadge = $('#selected-count-badge');

                // Update the count badge
                selectedCountBadge.text(selectedCount + ' selected');

                if (selectedCount > 0 && '<?= $role ?>' === 'Admin') {
                    $('.bulk-update-section').show();

                    // Update the bulk form with selected assets
                    $('#selected-assets-container').empty();
                    $('.asset-checkbox:checked').each(function() {
                        $('#selected-assets-container').append('<input type="hidden" name="selected_assets[]" value="' + $(this).val() + '">');
                    });
                } else {
                    $('.bulk-update-section').hide();
                }
            }

            // Enable/disable form elements based on checkbox selection
            $('#update-tagged-to').change(function() {
                $('#tagged-to-select').prop('disabled', !this.checked);
                if (!this.checked) {
                    $('#tagged-to-select').val('');
                }
            });

            $('#update-status').change(function() {
                $('#status-select').prop('disabled', !this.checked);
                if (!this.checked) {
                    $('#status-select').val('');
                }
            });

            // Cancel bulk update
            $('#cancel-bulk-update').click(function() {
                $('.asset-checkbox').prop('checked', false);
                $('#select-all-checkbox').prop('checked', false);
                $('.bulk-update-section').hide();
                $('#table-id tbody tr').removeClass('selected');
                $('#update-tagged-to').prop('checked', false);
                $('#update-status').prop('checked', false);
                $('#tagged-to-select').prop('disabled', true).val('');
                $('#status-select').prop('disabled', true).val('');
                $('#update-remarks').val('');

                // Reset the count badge
                $('#selected-count-badge').text('0 selected');
            });

            // Initialize DataTable with proper configuration
            <?php if (!empty($resultArr)) : ?>
                $('#table-id').DataTable({
                    "order": [],
                    "columnDefs": [{
                        "orderable": false,
                        "targets": 0 // Disable sorting for checkbox column
                    }],
                    "drawCallback": function(settings) {
                        // Re-initialize checkboxes after DataTable redraws
                        $('.asset-checkbox').prop('checked', false);
                        $('#select-all-checkbox').prop('checked', false);
                        $('.bulk-update-section').hide();
                        $('#table-id tbody tr').removeClass('selected');

                        // Reset the count badge
                        $('#selected-count-badge').text('0 selected');
                    }
                });
            <?php endif; ?>

            // Update search placeholder to indicate both ID and name search
            $('div.dataTables_filter input').attr('placeholder', 'Search by Asset ID or Name...');
        });
    </script>
    <script>
        $(document).ready(function() {
            // Initialize Select2 for associate numbers
            $('#tagged-to-select').select2({
                ajax: {
                    url: 'fetch_associates.php?isActive=true',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data.results
                        };
                    },
                    cache: true
                },
                minimumInputLength: 1,
                placeholder: 'Select associate(s)',
                allowClear: true,
                width: '100%',
                theme: 'bootstrap-5',
                // multiple: true
            });
        });
    </script>
    <script>
        $(document).ready(function() {
            // Function to validate the form
            function validateBulkUpdateForm() {
                const updateTaggedTo = $('#update-tagged-to').is(':checked');
                const updateStatus = $('#update-status').is(':checked');
                const taggedToValue = $('#tagged-to-select').val();
                const statusValue = $('#status-select').val();
                const remarksValue = $('#update-remarks').val().trim();

                let isValid = true;
                let errorMessage = '';

                // Validation logic
                if (updateTaggedTo && updateStatus) {
                    // Both fields enabled - both are required
                    if (!taggedToValue) {
                        isValid = false;
                        errorMessage = 'Please select a Tagged To value';
                    }
                    if (!statusValue) {
                        isValid = false;
                        errorMessage = errorMessage ? errorMessage + ' and Status' : 'Please select a Status';
                    }
                } else if (updateTaggedTo) {
                    // Only Tagged To enabled - required
                    if (!taggedToValue) {
                        isValid = false;
                        errorMessage = 'Please select a Tagged To value';
                    }
                } else if (updateStatus) {
                    // Only Status enabled - required
                    if (!statusValue) {
                        isValid = false;
                        errorMessage = 'Please select a Status';
                    }
                } else {
                    // Both fields disabled - Remarks becomes required
                    if (!remarksValue) {
                        isValid = false;
                        errorMessage = 'Remarks are required when no other fields are selected';
                    }
                }

                return {
                    isValid,
                    errorMessage
                };
            }

            // Add change event listeners to checkboxes
            $('#update-tagged-to, #update-status').change(function() {
                // Re-validate when checkboxes change
                validateBulkUpdateForm();
            });

            // Handle form submission
            $('#bulk-update-form').on('submit', function(e) {
                e.preventDefault();

                const validation = validateBulkUpdateForm();

                if (!validation.isValid) {
                    // Show error message
                    alert('Error: ' + validation.errorMessage);
                    return false;
                }

                // If validation passes, submit the form
                this.submit();
            });

            // Also add validation when the bulk update section is shown
            $(document).on('change', '.asset-checkbox', function() {
                // Re-validate when assets are selected/deselected
                if ($('.bulk-update-section').is(':visible')) {
                    validateBulkUpdateForm();
                }
            });
        });
    </script>
</body>

</html>