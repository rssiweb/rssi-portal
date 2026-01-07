<?php
require_once __DIR__ . "/../../bootstrap.php";
include(__DIR__ . "/../image_functions.php");

include("../../util/login_util.php");
include("../../util/drive.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}

validation();
date_default_timezone_set('Asia/Kolkata');

// ======================================================
// ADMIN: ADD ASSET
// ======================================================
if ($role === 'Admin') {

    if (isset($_POST['form-type']) && $_POST['form-type'] === "addasset") {

        $itemtype       = $_POST['itemtype'] ?? '';
        $itemname       = $_POST['itemname'] ?? '';
        $quantity       = (int)($_POST['quantity'] ?? 1);
        $remarks        = isset($_POST['remarks']) ? htmlspecialchars($_POST['remarks'], ENT_QUOTES, 'UTF-8') : '';
        $asset_status   = $_POST['asset_status'] ?? '';
        $asset_category = $_POST['asset_category'] ?? '';
        $unit_cost      = $_POST['unit_cost'] ?? '';
        $purchase_date  = $_POST['purchase_date'] ?? '';
        $now            = date('Y-m-d H:i:s');
        $collectedby    = $associatenumber;

        if ($itemtype === "") {
            $_SESSION['error_message'] = "Please select an asset type!";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }

        // Upload files once
        $doclink_photo_path = null;
        $doclink_bill_path  = null;

        if (!empty($_FILES['asset_photo']['name'])) {
            $doclink_photo_path = uploadeToDrive(
                $_FILES['asset_photo'],
                '19maeFLJUscJcS6k2xwR6Y-Bg6LtHG7NR',
                'photo_' . time()
            );
        }

        if (!empty($_FILES['purchase_bill']['name'])) {
            $doclink_bill_path = uploadeToDrive(
                $_FILES['purchase_bill'],
                '1TxjIHmYuvvyqe48eg9q_lnsyt1wDq6os',
                'bill_' . time()
            );
        }

        $successCount = 0;

        for ($i = 1; $i <= max(1, $quantity); $i++) {

            $itemid = "A" . str_replace('.', '', sprintf('%.4f', microtime(true) + $i));

            $insert = "
                INSERT INTO gps (
                    itemid, date, itemtype, itemname, quantity, remarks,
                    collectedby, asset_status, asset_category, unit_cost,
                    asset_photo, purchase_bill, purchase_date
                ) VALUES (
                    '$itemid', '$now', '$itemtype', '$itemname', 1, '$remarks',
                    '$collectedby', '$asset_status', '$asset_category', '$unit_cost',
                    '$doclink_photo_path', '$doclink_bill_path', '$purchase_date'
                )
            ";

            if (pg_query($con, $insert)) {

                $changes = json_encode([
                    'itemtype'       => $itemtype,
                    'itemname'       => $itemname,
                    'quantity'       => 1,
                    'asset_status'   => $asset_status,
                    'collectedby'    => $collectedby,
                    'remarks'        => $remarks,
                    'asset_category' => $asset_category,
                    'unit_cost'      => $unit_cost,
                    'asset_photo'    => $doclink_photo_path,
                    'purchase_bill'  => $doclink_bill_path,
                    'purchase_date'  => $purchase_date
                ]);

                pg_query($con, "
                    INSERT INTO gps_history (
                        itemid, update_type, updatedby, date, changes
                    ) VALUES (
                        '$itemid', 'add_asset', '$collectedby', '$now', '$changes'
                    )
                ");

                $successCount++;
            }
        }

        $_SESSION[$successCount ? 'success_message' : 'error_message']
            = $successCount ? "$successCount asset(s) added successfully!" : "Error adding assets.";

        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// ======================================================
// READ FILTERS (FOR BOTH ROLES)
// ======================================================
$taggedto       = strtoupper($_GET['taggedto'] ?? '');
$item_type      = $_GET['item_type'] ?? '';
$assetid        = trim($_GET['assetid'] ?? '');
$assetstatus    = $_GET['assetstatus'] ?? '';
$assetcategory  = $_GET['assetcategory'] ?? '';

// ======================================================
// SESSION MESSAGES
// ======================================================
$success_message = $_SESSION['success_message'] ?? null;
$error_message   = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);

// ======================================================
// BUILD CONDITIONS (ROLE SAFE)
// ======================================================
$conditions = [];

// Non-Admin base restriction
if ($role !== 'Admin') {
    $conditions[] = "gps.taggedto = '$associatenumber'";
}

// Asset ID search priority
$isAssetSearch = ($assetid !== '');

if ($isAssetSearch) {

    if (strpos($assetid, ',') !== false) {
        $ids = array_map('trim', explode(',', $assetid));
        $ids = array_map(fn($id) => pg_escape_string($con, $id), $ids);
        $conditions[] = "gps.itemid IN ('" . implode("','", $ids) . "')";
    } else {
        $safe = pg_escape_string($con, $assetid);
        $conditions[] = "(gps.itemid = '$safe' OR gps.itemname ILIKE '%$safe%')";
    }
} else {

    if ($item_type !== "" && $item_type !== "ALL") {
        $conditions[] = "gps.itemtype = '$item_type'";
    }

    if ($assetcategory !== "" && $assetcategory !== "ALL") {
        $conditions[] = "gps.asset_category = '$assetcategory'";
    }

    if ($assetstatus !== "") {
        $conditions[] = "gps.asset_status = '$assetstatus'";
    }

    // Admin-only taggedto filter
    if ($role === 'Admin' && $taggedto !== "") {
        $conditions[] = "gps.taggedto = '$taggedto'";
    }
}

// No filter → show nothing
$hasFilter =
    $isAssetSearch ||
    ($item_type) ||
    ($assetcategory) ||
    ($assetstatus) ||
    ($role === 'Admin' && $taggedto);

if (!$hasFilter) {
    $conditions[] = "1 = 0";
}

// ======================================================
// MAIN QUERY - UPDATED WITH LOCATION JOIN
// ======================================================
$query = "
SELECT 
    gps.*,
    office_locations.name as location_name,  -- ADD THIS
    tmember.fullname AS tfullname,
    tmember.phone AS tphone,
    tmember.email AS temail,
    imember.fullname AS ifullname,
    imember.phone AS iphone,
    imember.email AS iemail,
    v.verification_date,
    v.verified_by,
    verified_member.fullname AS verified_by_name,
    v.verification_status,
    v.admin_review_status
FROM gps
LEFT JOIN office_locations ON gps.location = office_locations.id  -- ADD THIS
LEFT JOIN rssimyaccount_members AS tmember
    ON gps.taggedto = tmember.associatenumber
LEFT JOIN rssimyaccount_members AS imember
    ON gps.collectedby = imember.associatenumber
LEFT JOIN (
    SELECT DISTINCT ON (asset_id)
        asset_id, verification_date, verified_by,
        verification_status, admin_review_status
    FROM gps_verifications
    ORDER BY asset_id, verification_date DESC
) v ON gps.itemid = v.asset_id
LEFT JOIN rssimyaccount_members AS verified_member
    ON v.verified_by = verified_member.associatenumber
";

if ($conditions) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}

$query .= " ORDER BY gps.itemname ASC, gps.purchase_date DESC";

// ======================================================
// EXECUTE
// ======================================================
$result = pg_query($con, $query);
$resultArr = $result ? pg_fetch_all($result) : [];
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

    <title>
        <?php
        echo ($role === 'Admin')
            ? 'GPS'
            : 'My Assets';
        ?>
    </title>

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

        /* Default state (row NOT selected) */
        #table-id tbody tr .photo-indicator {
            color: var(--bs-primary);
        }

        /* When row is selected */
        #table-id tbody tr.selected .photo-indicator {
            color: #fff;
        }
    </style>
    <!-- CSS Library Files -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
    <!-- JavaScript Library Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
    <!-- Include Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
</head>

<!-- =========================
     NAVIGATION LINKS     
============================== -->

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>
                <?php
                echo ($role === 'Admin')
                    ? 'GPS Admin'
                    : 'My Assets';
                ?>
            </h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">GPS</a></li>
                    <li class="breadcrumb-item active">My Assets</li>
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
                                <?php if (isset($successCount) && $successCount == 0) { ?>
                                    <div class="alert alert-danger alert-dismissible" role="alert" style="text-align: -webkit-center;">
                                        <a href="#" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></a>
                                        <span class="blink_me">
                                            <i class="bi bi-exclamation-triangle"></i>
                                        </span>
                                        &nbsp;&nbsp;
                                        <span>ERROR: Oops, something wasn’t right while adding the asset(s).</span>
                                    </div>

                                <?php } else if (isset($successCount) && $successCount > 0) { ?>
                                    <div class="alert alert-success alert-dismissible" role="alert" style="text-align: -webkit-center;">
                                        <a href="#" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></a>
                                        <i class="bi bi-check2-circle" style="font-size: medium;"></i>
                                        &nbsp;&nbsp;
                                        <span>
                                            <?php echo $successCount; ?> asset<?php echo ($successCount > 1) ? 's have' : ' has'; ?>
                                            been added successfully.
                                        </span>
                                    </div>

                                    <script>
                                        if (window.history.replaceState) {
                                            window.history.replaceState(null, null, window.location.href);
                                        }
                                    </script>
                            <?php }
                            } ?>


                            <?php if ($role == 'Admin') { ?>
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <div class="card">
                                            <div class="card-header">
                                                <h5 class="mb-0">Add New Asset</h5>
                                            </div>
                                            <div class="card-body">
                                                <form autocomplete="off" name="gps" id="gps" action="" method="POST" enctype="multipart/form-data" class="row g-3">
                                                    <input type="hidden" name="form-type" value="addasset">

                                                    <div class="col-md-4">
                                                        <label for="itemtype" class="form-label">Asset Type</label>
                                                        <select name="itemtype" class="form-select" required>
                                                            <?php if ($itemtype == null) { ?>
                                                                <option value="" disabled selected hidden>Select asset type</option>
                                                            <?php } else { ?>
                                                                <option hidden selected><?php echo htmlspecialchars($itemtype) ?></option>
                                                            <?php } ?>
                                                            <option value="Purchased">Purchased</option>
                                                            <option value="Donation">Donation</option>
                                                        </select>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <label for="asset_category" class="form-label">Asset Category</label>
                                                        <select name="asset_category" class="form-select" required>
                                                            <option value="" disabled selected hidden>Select category</option>
                                                            <option value="fixed">Fixed Asset</option>
                                                            <option value="consumable">Consumable</option>
                                                        </select>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <label for="itemname" class="form-label">Asset Name</label>
                                                        <input type="text" name="itemname" class="form-control" placeholder="Enter asset name" required>
                                                    </div>

                                                    <div class="col-md-3">
                                                        <label for="quantity" class="form-label">Quantity</label>
                                                        <input type="number" name="quantity" class="form-control" placeholder="Enter quantity" min="1" required>
                                                    </div>

                                                    <div class="col-md-3">
                                                        <label for="unit_cost" class="form-label">Unit Cost (Optional)</label>
                                                        <input type="number" name="unit_cost" class="form-control" placeholder="0.00" min="0" step="0.01" required>
                                                    </div>

                                                    <div class="col-md-3">
                                                        <label for="asset_status" class="form-label">Asset Status</label>
                                                        <select name="asset_status" class="form-select" required>
                                                            <option value="" disabled selected hidden>Select status</option>
                                                            <option value="Active">Active</option>
                                                            <option value="Inactive">Inactive</option>
                                                        </select>
                                                    </div>

                                                    <div class="col-md-3">
                                                        <label for="asset_photo" class="form-label">Asset Photo</label>
                                                        <input type="file" name="asset_photo" class="form-control" accept="image/*" onchange="compressImageBeforeUpload(this)">
                                                        <div class="form-text">JPG/PNG format</div>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label for="purchase_bill" class="form-label">Purchase Bill / Invoice</label>
                                                        <input type="file" name="purchase_bill" class="form-control" accept=".pdf,image/*">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label for="purchase_date" class="form-label">Purchase Date</label>
                                                        <input type="date" name="purchase_date" class="form-control" required>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label for="remarks" class="form-label">Remarks (Optional)</label>
                                                        <textarea name="remarks" class="form-control" placeholder="Enter any remarks" rows="1"></textarea>
                                                    </div>

                                                    <div class="col-12">
                                                        <button type="submit" name="search_by_id" class="btn btn-primary">
                                                            <i class="bi bi-plus-circle"></i> Add Asset
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                            <div class="row">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="mb-0">Search Assets</h5>
                                        </div>
                                        <div class="card-body">
                                            <form name="gpsdetails" id="gpsdetails" action="" method="GET" class="row g-3">
                                                <?php if ($role == 'Admin') { ?>
                                                    <div class="col-md-3">
                                                        <label for="item_type" class="form-label">Asset Type</label>
                                                        <select name="item_type" class="form-select">
                                                            <?php if ($item_type == null) { ?>
                                                                <option disabled selected hidden>Select asset type</option>
                                                            <?php } else { ?>
                                                                <option hidden selected><?php echo htmlspecialchars($item_type) ?></option>
                                                            <?php } ?>
                                                            <option>Purchased</option>
                                                            <option>Donation</option>
                                                            <option>ALL</option>
                                                        </select>
                                                    </div>

                                                    <div class="col-md-3">
                                                        <label for="assetcategory" class="form-label">Asset Category</label>
                                                        <select name="assetcategory" class="form-select">
                                                            <?php if ($assetcategory == null) { ?>
                                                                <option disabled selected hidden>Select asset category</option>
                                                            <?php } else { ?>
                                                                <option hidden selected><?php echo htmlspecialchars($assetcategory) ?></option>
                                                            <?php } ?>
                                                            <option value="fixed">Fixed Asset</option>
                                                            <option value="consumable">Consumable</option>
                                                            <option>ALL</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label for="taggedto" class="form-label">Tagged To</label>
                                                        <input type="text" name="taggedto" class="form-control" placeholder="Enter person name" value="<?php echo htmlspecialchars($taggedto) ?>">
                                                    </div>
                                                <?php } ?>
                                                <div class="col-md-3">
                                                    <label for="assetstatus" class="form-label">Asset Status</label>
                                                    <select name="assetstatus" class="form-select">
                                                        <?php if ($assetstatus == null) { ?>
                                                            <option disabled selected hidden>Select status</option>
                                                        <?php } else { ?>
                                                            <option hidden selected><?php echo htmlspecialchars($assetstatus) ?></option>
                                                        <?php } ?>
                                                        <option>Active</option>
                                                        <option>Inactive</option>
                                                    </select>
                                                </div>

                                                <div class="col-md-3">
                                                    <label for="assetid" class="form-label">Asset ID or Name</label>
                                                    <input type="text" name="assetid" id="assetid" class="form-control" placeholder="Enter asset ID or name" value="<?php echo htmlspecialchars($assetid) ?>">
                                                </div>

                                                <div class="col-12">
                                                    <div class="form-check mb-3">
                                                        <input class="form-check-input" type="checkbox" name="is_user" id="is_user" value="1" <?php if (isset($_GET['is_user'])) echo "checked"; ?>>
                                                        <label class="form-check-label" for="is_user">
                                                            Search by Asset ID or name only
                                                        </label>
                                                    </div>
                                                </div>

                                                <div class="col-12">
                                                    <button type="submit" name="search_by_id2" onclick="document.getElementById('assetid').value = document.getElementById('assetid').value.trim();" class="btn btn-primary">
                                                        <i class="bi bi-search"></i> Search
                                                    </button>
                                                    <button type="button" id="clear-selection" class="btn btn-secondary" style="display: none;">
                                                        <i class="bi bi-x-circle"></i> Clear Selection
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col" style="display: inline-block; width:100%; text-align:right">
                                Record count:&nbsp;<?php echo sizeof($resultArr) ?><br><br>
                                <form method="POST" action="export_function.php">
                                    <input type="hidden" value="gps" name="export_type" />
                                    <input type="hidden" value="<?php echo $item_type ?>" name="item_type" />
                                    <input type="hidden" value="<?php echo $taggedto; ?>" name="taggedto" />
                                    <input type="hidden" value="<?php echo $assetid ?>" name="assetid" />
                                    <input type="hidden" value="<?php echo $assetstatus; ?>" name="asset_status" />
                                    <input type="hidden" value="<?php echo $assetcategory ?>" name="asset_category" />

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
                                            <th width="50">
                                                <input type="checkbox" id="select-all-checkbox" class="form-check-input">
                                            </th>
                                            <th>Asset Id</th>
                                            <th>Asset name</th>
                                            <th>Quantity</th>
                                            <?php if ($role == 'Admin'): ?>
                                                <th>Asset type</th>
                                                <th>Asset category</th>
                                                <th>Unit price</th>
                                                <th>Purchase Date</th>
                                                <th>Photo</th>
                                                <th>Bill</th>
                                                <th>Linked Assets</th>
                                                <th>Remarks</th>
                                                <th>Last Verified</th>
                                                <th>Verified By</th>
                                                <th>Verification Status</th>
                                                <th>Review Status</th>
                                            <?php endif; ?>
                                            <th>Issued by</th>
                                            <th>Tagged to</th>
                                            <th>Status</th>
                                            <!-- <th>Last updated on</th> -->
                                            <th></th>
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
                                                        <?php if (strlen($array['itemname']) <= 20): ?>
                                                            <?= $array['itemname'] ?>
                                                        <?php else: ?>
                                                            <?= substr($array['itemname'], 0, 20) ?>&nbsp;...&nbsp;
                                                            <button class="dropdown-item" type="button" onclick="showName('<?= $array['itemid'] ?>')">
                                                                <i class="bi bi-box-arrow-up-right"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= $array['quantity'] ?></td>
                                                    <?php if ($role == 'Admin'): ?>
                                                        <td><?= $array['itemtype'] ?></td>
                                                        <td><?= $array['asset_category'] ?></td>
                                                        <td>
                                                            <?php if ($array['unit_cost'] != null): ?>
                                                                ₹&nbsp;<?= number_format((float)$array['unit_cost'], 2, '.', ',') ?>
                                                            <?php else: ?>
                                                                <span>N/A</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if ($array['purchase_date'] != null): ?>
                                                                <?= date("d/m/Y", strtotime($array['purchase_date'])) ?>
                                                            <?php else: ?>
                                                                <span>N/A</span>
                                                            <?php endif; ?>
                                                        <td>
                                                            <?php
                                                            $photoUrl = !empty($array['asset_photo']) ? processImageUrl($array['asset_photo']) : null;
                                                            $originalPhotoUrl = !empty($array['asset_photo']) ? $array['asset_photo'] : null;
                                                            $isPhotoPdf = !empty($originalPhotoUrl) && strtolower(pathinfo($originalPhotoUrl, PATHINFO_EXTENSION)) === 'pdf';
                                                            ?>
                                                            <?php if (!empty($photoUrl)): ?>
                                                                <button type="button"
                                                                    class="btn btn-link p-0 border-0 view-photo-btn"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#imageModal"
                                                                    data-proxy-url="<?= htmlspecialchars($photoUrl) ?>"
                                                                    data-original-url="<?= htmlspecialchars($originalPhotoUrl) ?>"
                                                                    data-is-pdf="<?= $isPhotoPdf ? 'true' : 'false' ?>"
                                                                    data-title="<?= htmlspecialchars($array['itemname'] ?? 'Asset') ?> (ID: <?= htmlspecialchars($array['itemid'] ?? '') ?>)">
                                                                    <span class="photo-indicator">Y</span>
                                                                </button>
                                                            <?php else: ?>
                                                                <span class="text-muted">N/A</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php
                                                            $billUrl = !empty($array['purchase_bill']) ? processImageUrl($array['purchase_bill']) : null;
                                                            $originalBillUrl = !empty($array['purchase_bill']) ? $array['purchase_bill'] : null;
                                                            $isBillPdf = !empty($originalBillUrl) && strtolower(pathinfo($originalBillUrl, PATHINFO_EXTENSION)) === 'pdf';
                                                            ?>
                                                            <?php if (!empty($billUrl)): ?>
                                                                <button type="button"
                                                                    class="btn btn-link p-0 border-0 view-photo-btn"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#imageModal"
                                                                    data-proxy-url="<?= htmlspecialchars($billUrl) ?>"
                                                                    data-original-url="<?= htmlspecialchars($originalBillUrl) ?>"
                                                                    data-is-pdf="<?= $isBillPdf ? 'true' : 'false' ?>"
                                                                    data-title="<?= htmlspecialchars($array['itemname'] ?? 'Asset') ?> (ID: <?= htmlspecialchars($array['itemid'] ?? '') ?>)">
                                                                    <span class="photo-indicator">Y</span>
                                                                </button>
                                                            <?php else: ?>
                                                                <span class="text-muted">N/A</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <!-- In the table body for each row: -->
                                                        <td>
                                                            <?php
                                                            // Check if asset has linked assets
                                                            $linked_assets_query = "
                                                            SELECT COUNT(*) as link_count 
                                                            FROM asset_links 
                                                            WHERE asset_itemid = '" . $array['itemid'] . "' 
                                                            AND is_active = TRUE";
                                                            $linked_result = pg_query($con, $linked_assets_query);
                                                            $link_data = pg_fetch_assoc($linked_result);
                                                            $link_count = $link_data['link_count'];

                                                            if ($link_count > 0): ?>
                                                                <button type="button"
                                                                    class="btn btn-sm btn-outline-info view-linked-assets"
                                                                    data-asset-id="<?= $array['itemid'] ?>"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#linkedAssetsModal">
                                                                    <i class="bi bi-link-45deg"></i> View Links (<?= $link_count ?>)
                                                                </button>
                                                            <?php else: ?>
                                                                <span class="text-muted">None</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if (isset($array['remarks']) && strlen($array['remarks']) <= 5): ?>
                                                                <?= htmlspecialchars($array['remarks']) ?>
                                                            <?php elseif (isset($array['remarks'])): ?>
                                                                <?= htmlspecialchars(substr($array['remarks'], 0, 5)) ?>&nbsp;...&nbsp;
                                                                <button class="dropdown-item" type="button" onclick="showRemarks('<?= htmlspecialchars($array['itemid']) ?>')">
                                                                    <i class="bi bi-box-arrow-up-right"></i>
                                                                </button>
                                                            <?php else: ?>
                                                                <span>No remarks</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if (!empty($array['verification_date'])): ?>
                                                                <?= date('d/m/Y', strtotime($array['verification_date'])) ?>
                                                            <?php else: ?>
                                                                <span class="text-muted">Never verified</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if (!empty($array['verified_by_name'])): ?>
                                                                <?= htmlspecialchars($array['verified_by_name']) ?>
                                                            <?php elseif (!empty($array['verified_by'])): ?>
                                                                <?= htmlspecialchars($array['verified_by']) ?>
                                                            <?php else: ?>
                                                                <span class="text-muted">N/A</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if (!empty($array['verification_status'])): ?>
                                                                <?= $array['verification_status'] ?>
                                                            <?php else: ?>
                                                                <span class="badge bg-secondary">Not Verified</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if (!empty($array['admin_review_status'])): ?>
                                                                <?= $array['admin_review_status'] ?>
                                                            <?php else: ?>
                                                                <span class="text-muted">-</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    <?php endif; ?>
                                                    <td><?= $array['ifullname'] ?></td>
                                                    <td><?= $array['tfullname'] ?></td>
                                                    <td><?= $array['asset_status'] ?></td>
                                                    <!-- <td>
                                                        <?php if ($array['lastupdatedon'] != null): ?>
                                                            <?= date("d/m/Y g:i a", strtotime($array['lastupdatedon'])) ?>&nbsp;by&nbsp;<?= $array['lastupdatedby'] ?>
                                                        <?php endif; ?>
                                                    </td> -->
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
                                                                <?php if ($role !== 'Admin'): ?>
                                                                    <li>
                                                                        <?php if (!empty($array['asset_photo'])): ?>
                                                                            <a href="<?= htmlspecialchars($array['asset_photo']) ?>"
                                                                                target="_blank"
                                                                                title="View Asset Photo"
                                                                                class="btn btn-link dropdown-item text-start w-100"
                                                                                style="padding-left: 1rem;">
                                                                                <i class="bi bi-image"></i> View Photo
                                                                            </a>
                                                                        <?php else: ?>
                                                                            <span class="dropdown-item text-muted disabled" style="padding-left: 1rem;">
                                                                                <i class="bi bi-image"></i> View Photo
                                                                            </span>
                                                                        <?php endif; ?>
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
                                <div class="modal-dialog modal-dialog-scrollable modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h1 class="modal-title fs-5" id="myModalLabel">GPS Details</h1>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <form id="gpsform" action="#" method="POST" enctype="multipart/form-data">

                                                <div class="text-end mb-3">
                                                    <p class="badge bg-info"><span class="itemid"></span></p>
                                                </div>
                                                <p><span class="itemname"></span></p>
                                                <input type="hidden" name="itemid1" id="itemid1">
                                                <input type="hidden" name="form-type" value="gpsedit">
                                                <input type="hidden" name="updatedby" value="<?= $associatenumber ?>">

                                                <div class="row g-3">
                                                    <?php if ($role == "Admin"): ?>
                                                        <!-- Item Type -->
                                                        <div class="col-md-6">
                                                            <select name="itemtype" id="itemtype" class="form-select" required>
                                                                <option value="" disabled selected>Select item type</option>
                                                                <option>Purchased</option>
                                                                <option>Donation</option>
                                                            </select>
                                                            <small class="text-muted">Item type*</small>
                                                        </div>

                                                        <!-- Asset Category -->
                                                        <div class="col-md-6">
                                                            <select name="asset_category" id="asset_category" class="form-select" required>
                                                                <option value="" disabled selected>Select asset category</option>
                                                                <option value="fixed">Fixed Asset</option>
                                                                <option value="consumable">Consumable</option>
                                                            </select>
                                                            <small class="text-muted">Asset category*</small>
                                                        </div>

                                                        <!-- Item Name -->
                                                        <div class="col-md-6">
                                                            <input type="text" name="itemname" id="itemname" class="form-control" placeholder="Item name" required>
                                                            <small class="text-muted">Item name*</small>
                                                        </div>

                                                        <!-- Quantity -->
                                                        <div class="col-md-6">
                                                            <input type="number" name="quantity" id="quantity" class="form-control" placeholder="Quantity" min="1" required>
                                                            <small class="text-muted">Quantity*</small>
                                                        </div>

                                                        <!-- Unit Cost -->
                                                        <div class="col-md-6">
                                                            <input type="number" name="unit_cost" id="unit_cost" class="form-control" placeholder="Unit cost" min="0" step="0.01" required>
                                                            <small class="text-muted">Unit cost</small>
                                                        </div>

                                                        <!-- Asset Status -->
                                                        <div class="col-md-6">
                                                            <select name="asset_status" id="asset_status" class="form-select" required>
                                                                <option value="" disabled selected>Select asset status</option>
                                                                <option>Active</option>
                                                                <option>Inactive</option>
                                                            </select>
                                                            <small class="text-muted">Asset status*</small>
                                                        </div>

                                                        <!-- Purchase Date -->
                                                        <div class="col-md-6">
                                                            <label for="purchase_date" class="form-label">Purchase Date</label>
                                                            <input type="date" name="purchase_date" id="purchase_date" class="form-control" required>
                                                            <small class="text-muted">Purchase date*</small>
                                                        </div>

                                                        <!-- Remarks -->
                                                        <div class="col-md-6">
                                                            <textarea name="remarks" id="remarks" class="form-control" placeholder="Remarks"></textarea>
                                                            <small class="text-muted">Remarks (Optional)</small>
                                                        </div>

                                                        <!-- Issued By -->
                                                        <div class="col-md-6">
                                                            <select name="collectedby" id="collectedby" class="form-select select2" required>
                                                                <option value="">Select associate</option>
                                                            </select>
                                                            <small class="text-muted">Issued by*</small>
                                                        </div>

                                                        <!-- Tagged To -->
                                                        <div class="col-md-6">
                                                            <select name="taggedto" id="taggedto" class="form-select select2">
                                                                <option value="">Select associate</option>
                                                            </select>
                                                            <small class="text-muted">Tagged to</small>
                                                        </div>

                                                        <!-- Location Field -->
                                                        <div class="col-md-6">
                                                            <label for="location" class="form-label">Location</label>
                                                            <select name="location" id="location" class="form-select select2">
                                                                <option value="">Select location</option>
                                                                <!-- Options will be loaded via AJAX -->
                                                            </select>
                                                            <small class="text-muted">Office location</small>
                                                        </div>

                                                        <!-- Asset Links Field -->
                                                        <div class="col-md-12">
                                                            <label for="linked_assets" class="form-label">Linked Assets</label>
                                                            <select name="linked_assets[]" id="linked_assets" class="form-select select2" multiple="multiple" style="width: 100%;">
                                                                <!-- Options will be loaded via AJAX -->
                                                            </select>
                                                            <small class="text-muted">Link other assets (use CTRL/CMD to select multiple)</small>
                                                        </div>
                                                    <?php endif; ?>
                                                    <!-- Asset Photo (Replace) -->
                                                    <div class="col-md-6">
                                                        <input type="file" name="asset_photo" id="asset_photo" class="form-control" accept="image/*" onchange="compressImageBeforeUpload(this)">
                                                        <small class="text-muted">Replace asset photo (optional)</small>
                                                    </div>

                                                    <!-- Purchase Bill (Replace) -->
                                                    <div class="col-md-6">
                                                        <input type="file" name="purchase_bill" id="purchase_bill" class="form-control" accept=".pdf,image/*">
                                                        <small class="text-muted">Replace purchase bill (optional)</small>
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
                            <!-- Simple Image/File Modal with Spinner -->
                            <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-xl">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="imageModalLabel">Loading...</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body p-0 position-relative" style="min-height: 500px;">
                                            <!-- Loading Spinner -->
                                            <div id="modalSpinner" class="position-absolute top-50 start-50 translate-middle" style="display: none;">
                                                <div class="spinner-border text-primary" role="status">
                                                    <span class="visually-hidden">Loading...</span>
                                                </div>
                                            </div>

                                            <!-- For PDFs (iframe) -->
                                            <iframe id="modalIframe" style="width: 100%; height: 70vh; border: none; display: none;"></iframe>

                                            <!-- For Images (img tag) -->
                                            <div id="imageContainer" style="display: none; text-align: center; padding: 20px;">
                                                <img id="modalImage" class="img-fluid rounded" alt="" style="max-height: 65vh;">
                                                <div class="mt-2 text-muted">
                                                    <small id="imageCaption"></small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            <a href="#" id="downloadBtn" class="btn btn-primary" style="display: none;" download>
                                                <i class="bi bi-download"></i> Download
                                            </a>
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

    <script src="../assets_new/js/image-compressor-100kb.js"></script>
    <!-- Bootstrap Modal -->
    <div class="modal fade" id="submissionModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p id="loadingMessage">Submission in progress.
                            Please do not close or reload this page.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Linked Assets Modal -->
    <div class="modal fade" id="linkedAssetsModal" tabindex="-1" aria-labelledby="linkedAssetsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="linkedAssetsModalLabel">Linked Assets</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-end mb-3">
                        <p class="badge bg-info"><span id="current-asset-id"></span></p>
                    </div>
                    <div id="linked-assets-container">
                        <!-- Linked assets will be loaded here -->
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p>Loading linked assets...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</body>
<script>
    // ======================================================
    // ASSET LINKING FUNCTIONS - GLOBAL SCOPE
    // ======================================================

    // Function to load asset options for linking (excluding current asset)
    function loadAssetOptions(currentAssetId = null) {
        $.ajax({
            url: 'fetch_assets_for_linking.php',
            type: 'GET',
            data: {
                exclude: currentAssetId
            },
            dataType: 'json',
            success: function(data) {
                const linkedAssetsSelect = $('#linked_assets');
                linkedAssetsSelect.empty();

                if (data && data.length > 0) {
                    data.forEach(function(asset) {
                        const optionText = `${asset.itemid} - ${asset.itemname}`;
                        linkedAssetsSelect.append(
                            `<option value="${asset.itemid}">${optionText}</option>`
                        );
                    });
                }

                // Re-initialize Select2 WITHOUT bootstrap theme
                linkedAssetsSelect.select2({
                    width: '100%',
                    dropdownParent: $('#myModal'),
                    placeholder: 'Select assets to link',
                    allowClear: true
                });
            },
            error: function() {
                console.error('Failed to load assets for linking');
                $('#linked_assets').select2({
                    width: '100%',
                    dropdownParent: $('#myModal'),
                    placeholder: 'Select assets to link',
                    allowClear: true
                });
            }
        });
    }

    // Function to load already linked assets for the current asset
    function loadLinkedAssets(assetId) {
        if (!assetId) return;

        $.ajax({
            url: 'fetch_linked_assets.php',
            type: 'GET',
            data: {
                asset_id: assetId
            },
            dataType: 'json',
            success: function(data) {
                const linkedAssetsSelect = $('#linked_assets');
                if (data && data.length > 0) {
                    const selectedValues = data.map(asset => asset.linked_asset_itemid);
                    linkedAssetsSelect.val(selectedValues).trigger('change');
                }
            },
            error: function() {
                console.error('Failed to load linked assets');
            }
        });
    }

    // Function to load linked assets for modal view - UPDATED VERSION
    function loadLinkedAssetsForModal(assetId) {
        const container = document.getElementById('linked-assets-container');

        $.ajax({
            url: 'get_linked_assets_details.php',
            type: 'GET',
            data: {
                asset_id: assetId
            },
            dataType: 'json',
            success: function(data) {
                if (data.length > 0) {
                    let html = '<div class="table-responsive"><table class="table table-sm">';
                    html += '<thead><tr><th>Asset ID</th><th>Asset Name</th><th>Status</th><th>Tagged To</th><th>Actions</th></tr></thead>';
                    html += '<tbody>';

                    data.forEach(function(asset) {
                        html += `<tr>
                        <td>${asset.itemid}</td>
                        <td>${asset.itemname}</td>
                        <td><span class="badge bg-${asset.asset_status === 'Active' ? 'success' : 'secondary'}">${asset.asset_status}</span></td>
                        <td>${asset.taggedto_name || asset.taggedto}</td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-primary view-linked-asset" 
                                data-asset-id="${asset.itemid}">
                                <i class="bi bi-eye"></i> View
                            </button>
                        </td>
                    </tr>`;
                    });

                    html += '</tbody></table></div>';
                    container.innerHTML = html;

                    // Add event listener for the view buttons
                    $('.view-linked-asset').on('click', function() {
                        const assetId = $(this).data('asset-id');
                        showDetails(assetId); // This will now work for all assets!
                        $('#linkedAssetsModal').modal('hide');
                    });
                } else {
                    container.innerHTML = '<div class="alert alert-info">No linked assets found.</div>';
                }
            },
            error: function() {
                container.innerHTML = '<div class="alert alert-danger">Failed to load linked assets.</div>';
            }
        });
    }

    // Function to initialize a Select2 on a given element ID
    function initSelect2(elementId, modalId = null) {
        const ajaxConfig = {
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
        };

        const $element = $(`#${elementId}`);
        if (!$element.hasClass("select2-hidden-accessible")) {
            $element.select2({
                ajax: ajaxConfig,
                minimumInputLength: 2,
                placeholder: 'Select associate(s)',
                allowClear: true,
                width: '100%',
                dropdownParent: modalId ? $(`#${modalId}`) : undefined
            });
        }
    }

    // ======================================================
    // MAIN FUNCTIONS
    // ======================================================

    // Assuming data is provided from PHP
    var data = <?= json_encode($resultArr) ?>;

    // Initialize modals
    var modal = new bootstrap.Modal(document.getElementById('myModal'));
    var modal1 = new bootstrap.Modal(document.getElementById('myModal1'));
    var modal2 = new bootstrap.Modal(document.getElementById('myModal2'));

    // Make currentMode a global variable
    window.currentMode = 'details'; // 'details' or 'upload'

    function findItem(id) {
        return data.find(item => item.itemid == id);
    }

    function showDetails(id) {
        // First, try to find the item in the current data (filtered results)
        var item = findItem(id);

        if (item) {
            // Asset is in current filtered list - use existing logic
            openDetailsModal(item);
        } else {
            // Asset is NOT in current filtered list - fetch it from server
            fetchAssetDetails(id);
        }
    }

    // Helper function to open modal with item data
    function openDetailsModal(item) {
        // Set global mode
        window.currentMode = 'details';

        // Reset to default state
        resetModalToDefault();

        // Set modal title
        document.getElementById('myModalLabel').textContent = 'GPS Details';

        // Populate form fields
        document.querySelector('#myModal .itemid').textContent = item.itemid;
        document.querySelector('#myModal .itemname').textContent = item.itemname;
        document.getElementById('itemid1').value = item.itemid;

        <?php if ($role == 'Admin'): ?>
            // Existing fields...
            document.getElementById('itemtype').value = item.itemtype || "";
            document.getElementById('itemname').value = item.itemname || "";
            document.getElementById('quantity').value = item.quantity || "";
            document.getElementById('asset_status').value = item.asset_status || "";
            document.getElementById('remarks').value = item.remarks || "";
            document.getElementById('unit_cost').value = item.unit_cost || "";
            document.getElementById('asset_category').value = item.asset_category || "";
            document.getElementById('purchase_date').value = item.purchase_date || "";

            // NEW: Initialize location field and load options
            // First, initialize the Select2 (empty)
            $('#location').select2({
                width: '100%',
                dropdownParent: $('#myModal'),
                placeholder: 'Select location',
                allowClear: true
            });

            // Then load options and set value
            loadLocationOptionsAndSetValue(item.location ? item.location.toString() : null);

            // Load asset options for linking
            if (typeof loadAssetOptions === 'function') {
                loadAssetOptions(item.itemid);
            } else {
                console.error('loadAssetOptions function not found');
            }

            // Load already linked assets
            if (typeof loadLinkedAssets === 'function') {
                // Wait a bit for the select2 to be initialized
                setTimeout(() => {
                    loadLinkedAssets(item.itemid);
                }, 200);
            }

            // Handle Select2 fields for collectedby and taggedto...
            var collectedbyValue = item.collectedby || "";
            if (collectedbyValue) {
                var newOption = new Option(collectedbyValue, collectedbyValue, true, true);
                $('#collectedby').append(newOption).trigger('change');
            } else {
                $('#collectedby').val(null).trigger('change');
            }

            var taggedtoValue = item.taggedto || "";
            if (taggedtoValue) {
                var newOption = new Option(taggedtoValue, taggedtoValue, true, true);
                $('#taggedto').append(newOption).trigger('change');
            } else {
                $('#taggedto').val(null).trigger('change');
            }
        <?php endif; ?>

        // Existing file handling...
        var assetPhotoField = document.getElementById('asset_photo');
        var purchaseBillField = document.getElementById('purchase_bill');

        if (item.asset_photo && assetPhotoField) {
            assetPhotoField.disabled = true;
            assetPhotoField.placeholder = "Photo already uploaded";
        }

        if (item.purchase_bill && purchaseBillField) {
            purchaseBillField.disabled = true;
            purchaseBillField.placeholder = "Bill already uploaded";
        }

        modal.show();
    }

    // Function to fetch asset details from server
    function fetchAssetDetails(assetId) {
        // Show loading message in modal
        document.querySelector('#myModal .itemid').textContent = assetId;
        document.querySelector('#myModal .itemname').textContent = 'Loading...';
        document.getElementById('itemid1').value = assetId;

        // Show modal with loading state
        modal.show();

        // Fetch asset details
        $.ajax({
            url: 'fetch_asset.php',
            type: 'GET',
            data: {
                asset_id: assetId
            },
            dataType: 'json',
            success: function(asset) {
                if (asset.error) {
                    alert('Error: ' + asset.error);
                    modal.hide();
                    return;
                }

                // Now open the modal with the fetched data
                openDetailsModal(asset);
            },
            error: function() {
                alert('Failed to load asset details. Please try again.');
                modal.hide();
            }
        });
    }

    // Function to load location options and set value
    function loadLocationOptionsAndSetValue(selectedValue = null) {
        $.ajax({
            url: 'fetch_locations.php',
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                const locationSelect = $('#location');

                // Store current value before emptying
                const currentValue = locationSelect.val();

                locationSelect.empty();
                locationSelect.append('<option value="">Select location</option>');

                if (data && data.length > 0) {
                    data.forEach(function(location) {
                        if (location.is_active) {
                            locationSelect.append(
                                `<option value="${location.id.toString()}">${location.name}</option>`
                            );
                        }
                    });
                }

                // Set the value - use provided selectedValue or keep current
                const valueToSet = selectedValue !== null ? selectedValue : currentValue;
                if (valueToSet) {
                    locationSelect.val(valueToSet).trigger('change');
                }
            },
            error: function() {
                console.error('Failed to load locations');
            }
        });
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

    // Helper function to hide non-file fields - FIXED VERSION
    function hideNonFileFields() {
        // Hide ALL form groups except file uploads
        var allFormGroups = document.querySelectorAll('#gpsform .col-md-6');

        allFormGroups.forEach(function(formGroup) {
            // Check if this form group contains file inputs
            var fileInput = formGroup.querySelector('input[type="file"]');

            if (fileInput) {
                // This is a file upload field - show it
                formGroup.style.display = 'block';
                fileInput.disabled = false;
                // Ensure file inputs are NOT required when in upload mode
                fileInput.required = window.currentMode === 'upload' ? false : true;
            } else {
                // This is NOT a file upload field - hide it
                formGroup.style.display = 'none';

                // Remove required attribute from all inputs in hidden groups
                var formElements = formGroup.querySelectorAll('input, select, textarea');
                formElements.forEach(function(element) {
                    element.disabled = false; // Keep enabled
                    element.removeAttribute('required'); // Remove required attribute
                    element.setAttribute('data-hidden', 'true');
                    element.setAttribute('data-was-required', element.hasAttribute('required')); // Store if it was required
                });
            }
        });

        // Also handle form elements not in .col-md-6
        var allFormElements = document.querySelectorAll('#gpsform input, #gpsform select, #gpsform textarea');
        allFormElements.forEach(function(element) {
            if (element.type !== 'file') {
                // For non-file fields, remove required if they're in upload mode
                if (window.currentMode === 'upload') {
                    element.removeAttribute('required');
                }
            }
        });
    }

    // Helper function to reset modal to default state
    function resetModalToDefault() {
        // Show all form groups
        var allFormGroups = document.querySelectorAll('#gpsform .col-md-6');
        allFormGroups.forEach(function(formGroup) {
            formGroup.style.display = 'block';
        });

        // Enable all fields and restore required attributes
        var allFields = document.querySelectorAll('#gpsform input, #gpsform select, #gpsform textarea');
        allFields.forEach(function(field) {
            field.disabled = false;

            // Restore required attribute if it was originally required
            if (field.getAttribute('data-was-required') === 'true') {
                field.setAttribute('required', 'required');
            }
            // Remove the data attributes
            field.removeAttribute('data-hidden');
            field.removeAttribute('data-was-required');
        });

        // Reset button text
        document.querySelector('.button-text').textContent = 'Save changes';
    }

    // Reset modal when it's closed
    document.getElementById('myModal').addEventListener('hidden.bs.modal', function() {
        resetModalToDefault();
        // Reset modal title
        document.getElementById('myModalLabel').textContent = 'GPS Details';
        // Reset mode
        window.currentMode = 'details';

        // Clear any Select2 appended options
        <?php if ($role == 'Admin'): ?>
            $('#collectedby').val(null).trigger('change');
            $('#taggedto').val(null).trigger('change');
            $('#location').val(null).trigger('change');
            $('#linked_assets').val(null).trigger('change');
        <?php endif; ?>
    });

    // ======================================================
    // FORM SUBMISSION HANDLER
    // ======================================================

    const scriptURL = 'payment-api.php';
    const form = document.getElementById('gpsform');

    // In your form submission handler, update it to send linked assets properly
    if (form) {
        const updateButton = document.getElementById('update-button');
        const buttonText = updateButton.querySelector('.button-text');
        const spinner = updateButton.querySelector('.spinner-border');

        form.addEventListener('submit', e => {
            e.preventDefault();

            // Add novalidate to form in upload mode to bypass HTML5 validation
            if (window.currentMode === 'upload') {
                form.setAttribute('novalidate', 'novalidate');
            }

            // Show spinner and disable button - use window.currentMode
            buttonText.textContent = window.currentMode === 'upload' ? 'Uploading...' : 'Updating...';
            spinner.classList.remove('d-none');
            updateButton.disabled = true;

            const formData = new FormData(form);
            formData.append('form-type', 'gpsedit');

            // Add the current mode to the form data
            formData.append('mode', window.currentMode);

            // Get selected linked assets and add to form data
            const linkedAssets = $('#linked_assets').val() || [];
            formData.append('linked_assets', JSON.stringify(linkedAssets));

            // Get location value (CHANGED: use locationValue instead of location)
            const locationValue = $('#location').val() || '';
            formData.append('location', locationValue);

            fetch(scriptURL, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(result => {
                    // Reset button state
                    resetButton();

                    if (result === 'success') {
                        let message = window.currentMode === 'upload' ?
                            "Files have been uploaded successfully." :
                            "Record has been successfully updated.";
                        alert(message);
                        window.location.reload(); // CHANGED: Use window.location.reload()
                    } else if (result === 'nochange') {
                        alert("No changes detected.");
                    } else if (result === 'invalid') {
                        alert("Invalid asset ID.");
                    } else if (result === 'unauthorized') {
                        alert("You are not authorized to perform this action.");
                    } else if (result === 'nofiles') {
                        alert("No files selected for upload.");
                    } else {
                        let errorMsg = window.currentMode === 'upload' ?
                            "Error uploading files. Please try again." :
                            "Error updating record. Please try again or contact support.";
                        alert(errorMsg);
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
                buttonText.textContent = window.currentMode === 'upload' ? 'Upload Files' : 'Save changes';
                spinner.classList.add('d-none');
                updateButton.disabled = false;
            }
        }

        // Reset button state and mode when modal is closed
        $('#myModal').on('hidden.bs.modal', function() {
            window.currentMode = 'details'; // Reset to default mode
            resetButton();
        });
    }

    // Email form event listeners - check if data exists
    if (typeof data !== 'undefined') {
        data.forEach(item => {
            const formId = 'email-form-' + item.itemid;
            const form = document.forms[formId];

            // Check if form exists before adding event listener
            if (form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    fetch('mailer.php', {
                            method: 'POST',
                            body: new FormData(form)
                        })
                        .then(response => {
                            alert("Email has been sent.");
                        })
                        .catch(error => console.error('Error!', error.message));
                });
            }
        });
    }

    // ======================================================
    // COMMON SAFE HELPERS
    // ======================================================

    function setFieldState(field, enabled) {
        if (!field) return;

        field.disabled = !enabled;

        const wrapper = field.closest('.col-md-3');
        if (wrapper) {
            wrapper.classList.toggle('text-muted', !enabled);
        }
    }

    // ======================================================
    // FORM STATE HANDLING
    // ======================================================

    function updateFormState() {
        const checkbox = document.getElementById('is_user');
        if (!checkbox) return;

        const assetIdInput = document.getElementsByName('assetid')[0] || null;
        const itemTypeInput = document.getElementsByName('item_type')[0] || null;
        const taggedToInput = document.getElementsByName('taggedto')[0] || null;
        const itemStatusInput = document.getElementsByName('assetstatus')[0] || null;
        const assetCategoryInput = document.getElementsByName('assetcategory')[0] || null;

        if (checkbox.checked) {
            setFieldState(assetIdInput, true);
            setFieldState(itemTypeInput, false);
            setFieldState(itemStatusInput, false);
            setFieldState(taggedToInput, false);
            setFieldState(assetCategoryInput, false);
        } else {
            setFieldState(assetIdInput, false);
            setFieldState(itemTypeInput, true);
            setFieldState(itemStatusInput, true);
            setFieldState(taggedToInput, true);
            setFieldState(assetCategoryInput, true);
        }
    }

    // ======================================================
    // DOM READY - INITIALIZATION
    // ======================================================

    $(document).ready(function() {

        /* -------- Clear button visibility -------- */
        function updateClearButtonVisibility() {
            const val = $('input[name="assetid"]').val()?.trim();
            $('#clear-selection').toggle(!!val);
        }

        updateClearButtonVisibility();
        updateFormState();

        $('#is_user').on('change', updateFormState);

        /* -------- Select all -------- */
        $('#select-all-checkbox').change(function() {
            $('.asset-checkbox').prop('checked', this.checked);
            toggleBulkUpdateSection();
            updateRowSelection();
            updateAssetIdSearchField();
        });

        /* -------- Individual checkbox -------- */
        $(document).on('change', '.asset-checkbox', function() {
            toggleBulkUpdateSection();
            updateRowSelection();

            const total = $('.asset-checkbox').length;
            const checked = $('.asset-checkbox:checked').length;
            $('#select-all-checkbox').prop('checked', total === checked);

            updateAssetIdSearchField();
        });

        /* -------- Row click selection -------- */
        $(document).on('click', '#table-id tbody tr', function(e) {
            if (
                e.target.type === 'checkbox' ||
                e.target.tagName === 'A' ||
                $(e.target).hasClass('dropdown-toggle') ||
                $(e.target).closest('.dropdown').length
            ) return;

            const checkbox = $(this).find('.asset-checkbox');
            checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
        });

        /* -------- Populate Asset ID field -------- */
        function updateAssetIdSearchField() {
            const assetIds = [];

            $('.asset-checkbox:checked').each(function() {
                const itemid = $(this).closest('tr').find('td:nth-child(2)').text().trim();
                if (itemid) assetIds.push(itemid);
            });

            if (assetIds.length) {
                $('#is_user').prop('checked', true);
                updateFormState();
                $('input[name="assetid"]').val(assetIds.join(', '));
            } else {
                $('input[name="assetid"]').val('');
                $('#is_user').prop('checked', false);
                updateFormState();
            }

            updateClearButtonVisibility();
        }

        /* -------- Row highlight -------- */
        function updateRowSelection() {
            $('#table-id tbody tr').removeClass('selected');
            $('.asset-checkbox:checked').closest('tr').addClass('selected');
        }

        /* -------- Bulk update section -------- */
        function toggleBulkUpdateSection() {
            const count = $('.asset-checkbox:checked').length;
            $('#selected-count-badge').text(count + ' selected');

            if (count > 0 && '<?= $role ?>' === 'Admin') {
                $('.bulk-update-section').show();
                $('#selected-assets-container').empty();

                $('.asset-checkbox:checked').each(function() {
                    $('#selected-assets-container')
                        .append('<input type="hidden" name="selected_assets[]" value="' + $(this).val() + '">');
                });
            } else {
                $('.bulk-update-section').hide();
            }
        }

        /* -------- Clear selection -------- */
        $('#clear-selection').click(function(e) {
            e.preventDefault();

            $('input[name="assetid"]').val('');
            $('#is_user').prop('checked', false);
            $('.asset-checkbox, #select-all-checkbox').prop('checked', false);

            updateFormState();
            updateRowSelection();
            updateClearButtonVisibility();
        });

        /* -------- Manual typing in assetid -------- */
        $('input[name="assetid"]').on('input', function() {
            $('#is_user').prop('checked', !!$(this).val().trim());
            updateFormState();
            updateClearButtonVisibility();
        });

        /* -------- DataTable -------- */
        <?php if (!empty($resultArr)) : ?>
            $('#table-id').DataTable({
                order: [],
                columnDefs: [{
                    orderable: false,
                    targets: 0
                }],
                drawCallback: function() {
                    $('.asset-checkbox, #select-all-checkbox').prop('checked', false);
                    $('.bulk-update-section').hide();
                    $('#selected-count-badge').text('0 selected');
                    updateClearButtonVisibility();
                }
            });
        <?php endif; ?>

        $('div.dataTables_filter input')
            .attr('placeholder', 'Search by Asset ID or Name...');

        // Initialize bulk update select2
        function initBulkUpdateSelect2() {
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
                minimumInputLength: 2,
                placeholder: 'Select associate',
                allowClear: true,
                width: '100%'
            });
        }

        // Initialize when bulk section is shown
        $(document).on('change', '.asset-checkbox', function() {
            if ($('.bulk-update-section').is(':visible')) {
                initBulkUpdateSelect2();
            }
        });

        // Also initialize when page loads if bulk section is visible
        if ($('.bulk-update-section').is(':visible')) {
            initBulkUpdateSelect2();
        }

        // Also initialize the select2 for linked_assets when modal is shown
        $('#myModal').on('shown.bs.modal', function() {
            // Existing initializations...
            initSelect2('taggedto', 'myModal');
            initSelect2('collectedby', 'myModal');

            // Initialize new fields WITHOUT bootstrap theme
            $('#linked_assets').select2({
                width: '100%',
                dropdownParent: $('#myModal'),
                placeholder: 'Select assets to link',
                allowClear: true
            });

            // Initialize location field WITHOUT bootstrap theme
            $('#location').select2({
                width: '100%',
                dropdownParent: $('#myModal'),
                placeholder: 'Select location',
                allowClear: true
            });
        });
    });

    // ======================================================
    // BULK UPDATE FORM HANDLING
    // ======================================================

    $(document).ready(function() {

        function toggleBulkFields() {
            const updateTaggedTo = $('#update-tagged-to').is(':checked');
            const updateStatus = $('#update-status').is(':checked');

            // Enable / disable Tagged To
            $('#tagged-to-select')
                .prop('disabled', !updateTaggedTo)
                .toggleClass('bg-light', !updateTaggedTo);

            // Enable / disable Status
            $('#status-select')
                .prop('disabled', !updateStatus)
                .toggleClass('bg-light', !updateStatus);
        }

        // Run on checkbox change
        $('#update-tagged-to, #update-status').on('change', function() {
            toggleBulkFields();
        });

        // Run once when bulk section becomes visible
        toggleBulkFields();
    });

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

        $('#cancel-bulk-update').on('click', function() {

            // Uncheck all asset checkboxes
            $('.asset-checkbox, #select-all-checkbox').prop('checked', false);

            // Hide bulk update section
            $('.bulk-update-section').hide();

            // Reset bulk update form
            $('#bulk-update-form')[0].reset();

            // Disable fields again
            $('#tagged-to-select').prop('disabled', true).val(null).trigger('change');
            $('#status-select').prop('disabled', true).val('');

            // Reset selected count badge
            $('#selected-count-badge').text('0 selected');

            // Remove row highlights
            $('#table-id tbody tr').removeClass('selected');

        });
    });

    // ======================================================
    // LOADING MODAL HANDLING
    // ======================================================

    // Create a new Bootstrap modal instance with backdrop: 'static' and keyboard: false options
    const myModal = new bootstrap.Modal(document.getElementById("submissionModal"), {
        backdrop: 'static',
        keyboard: false
    });
    // Add event listener to intercept Escape key press
    document.body.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            // Prevent default behavior of Escape key
            event.preventDefault();
        }
    });

    // Function to show loading modal
    function showLoadingModal() {
        $('#submissionModal').modal('show');
    }

    // Function to hide loading modal
    function hideLoadingModal() {
        $('#submissionModal').modal('hide');
    }

    const gpsForm = document.getElementById('gps');
    if (gpsForm) {
        gpsForm.addEventListener('submit', function(event) {
            // Show loading modal when form is submitted
            showLoadingModal();
        });
    }

    // Optional: Close loading modal when the page is fully loaded
    window.addEventListener('load', function() {
        // Hide loading modal
        hideLoadingModal();
    });

    // ======================================================
    // IMAGE MODAL HANDLING
    // ======================================================

    // Simple Modal Handler with Spinner - Fixed Version
    document.addEventListener('DOMContentLoaded', function() {
        const imageModal = document.getElementById('imageModal');

        if (imageModal) {
            // Use Bootstrap's modal events properly
            imageModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const proxyUrl = button.getAttribute('data-proxy-url');
                const originalUrl = button.getAttribute('data-original-url');
                const isPdf = button.getAttribute('data-is-pdf') === 'true';
                const title = button.getAttribute('data-title') || 'View File';

                // Determine which URL to use for viewing
                const viewUrl = isPdf ? originalUrl : proxyUrl;
                // For download: ALWAYS use original URL
                const downloadUrl = originalUrl;

                // Set modal title immediately
                const modalTitle = imageModal.querySelector('#imageModalLabel');
                if (modalTitle) {
                    modalTitle.textContent = title;
                }

                // Show spinner, hide other content
                const modalSpinner = imageModal.querySelector('#modalSpinner');
                const modalIframe = imageModal.querySelector('#modalIframe');
                const imageContainer = imageModal.querySelector('#imageContainer');
                const downloadBtn = imageModal.querySelector('#downloadBtn');

                if (modalSpinner) modalSpinner.style.display = 'block';
                if (modalIframe) modalIframe.style.display = 'none';
                if (imageContainer) imageContainer.style.display = 'none';
                if (downloadBtn) {
                    downloadBtn.style.display = 'none';
                    downloadBtn.href = downloadUrl; // Always original URL
                    downloadBtn.download = title.replace(/[^a-z0-9]/gi, '_') + (isPdf ? '.pdf' : '.jpg');
                }

                // Use setTimeout to ensure modal is visible
                setTimeout(function() {
                    if (isPdf) {
                        // Handle PDF
                        if (modalIframe) {
                            modalIframe.style.display = 'block';
                            modalIframe.onload = function() {
                                if (modalSpinner) modalSpinner.style.display = 'none';
                                if (downloadBtn) downloadBtn.style.display = 'inline-block';
                            };
                            modalIframe.onerror = function() {
                                if (modalSpinner) modalSpinner.style.display = 'none';
                                showError(imageModal, 'Failed to load PDF');
                            };
                            modalIframe.src = viewUrl;
                        }
                    } else {
                        // Handle Image
                        const modalImage = imageModal.querySelector('#modalImage');
                        const imageCaption = imageModal.querySelector('#imageCaption');

                        if (modalImage && imageCaption) {
                            imageCaption.textContent = title;
                            modalImage.onload = function() {
                                if (modalSpinner) modalSpinner.style.display = 'none';
                                if (imageContainer) imageContainer.style.display = 'block';
                                if (downloadBtn) downloadBtn.style.display = 'inline-block';
                            };
                            modalImage.onerror = function() {
                                if (modalSpinner) modalSpinner.style.display = 'none';
                                showError(imageModal, 'Failed to load image');
                            };
                            modalImage.src = viewUrl;
                            modalImage.alt = title;
                        }
                    }
                }, 100); // Small delay to ensure modal is rendered
            });

            // Clear on hide
            imageModal.addEventListener('hidden.bs.modal', function() {
                const modalIframe = imageModal.querySelector('#modalIframe');
                const modalImage = imageModal.querySelector('#modalImage');
                const modalSpinner = imageModal.querySelector('#modalSpinner');
                const downloadBtn = imageModal.querySelector('#downloadBtn');

                if (modalIframe) {
                    modalIframe.src = '';
                    modalIframe.onload = null;
                    modalIframe.onerror = null;
                }

                if (modalImage) {
                    modalImage.src = '';
                    modalImage.onload = null;
                    modalImage.onerror = null;
                }

                if (modalSpinner) modalSpinner.style.display = 'none';
                if (downloadBtn) downloadBtn.style.display = 'none';
            });
        }

        function showError(modal, message) {
            const imageContainer = modal.querySelector('#imageContainer');
            if (imageContainer) {
                imageContainer.innerHTML = '<div class="alert alert-danger mt-5">' + message + '</div>';
                imageContainer.style.display = 'block';
            }
        }
    });

    // ======================================================
    // LINKED ASSETS MODAL HANDLER
    // ======================================================

    document.addEventListener('DOMContentLoaded', function() {
        const linkedAssetsModal = document.getElementById('linkedAssetsModal');

        if (linkedAssetsModal) {
            linkedAssetsModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const assetId = button.getAttribute('data-asset-id');

                // Set current asset ID
                document.getElementById('current-asset-id').textContent = assetId;

                // Load linked assets
                loadLinkedAssetsForModal(assetId);
            });
        }
    });
</script>

</html>