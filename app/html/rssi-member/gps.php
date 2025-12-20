<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");
include("../../util/drive.php");

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
        // Generate a unique itemid with microseconds to avoid duplicates
        $microtime = microtime(true);
        $itemid = "A" . str_replace('.', '', sprintf('%.4f', $microtime));

        $itemtype = isset($_POST['itemtype']) ? $_POST['itemtype'] : '';
        $itemname = isset($_POST['itemname']) ? $_POST['itemname'] : '';
        $quantity = isset($_POST['quantity']) ? $_POST['quantity'] : '';
        $remarks = isset($_POST['remarks']) ? htmlspecialchars($_POST['remarks'], ENT_QUOTES, 'UTF-8') : '';
        $asset_status = isset($_POST['asset_status']) ? $_POST['asset_status'] : '';
        $now = date('Y-m-d H:i:s');
        $collectedby = $associatenumber; // Or whichever user is adding the asset
        $asset_category = isset($_POST['asset_category']) ? $_POST['asset_category'] : '';
        $unit_cost = isset($_POST['unit_cost']) ? $_POST['unit_cost'] : '';
        $purchase_date = isset($_POST['purchase_date']) ? $_POST['purchase_date'] : '';
        $photo_path = $_FILES['asset_photo'] ?? null;
        $bill_path = $_FILES['purchase_bill'] ?? null;

        // Initialize file links to null
        $doclink_photo_path = null;
        $doclink_bill_path = null;

        // Handle photo upload
        if (!empty($photo_path['name'])) {
            $filename_photo_path = "photo_path_" . "$itemid" . "_" . time();
            $parent_photo_path = '19maeFLJUscJcS6k2xwR6Y-Bg6LtHG7NR'; // GPS Photos folder ID
            $doclink_photo_path = uploadeToDrive($photo_path, $parent_photo_path, $filename_photo_path);
        }
        // Handle bill upload
        if (!empty($bill_path['name'])) {
            $filename_bill_path = "bill_path_" . "$itemid" . "_" . time();
            $parent_bill_path = '1TxjIHmYuvvyqe48eg9q_lnsyt1wDq6os'; // GPS Bills folder ID
            $doclink_bill_path = uploadeToDrive($bill_path, $parent_bill_path, $filename_bill_path);
        }

        if ($itemtype != "") {
            // Insert into gps table
            $gps_query = "INSERT INTO gps (itemid, date, itemtype, itemname, quantity, remarks, collectedby, asset_status, asset_category, unit_cost, asset_photo, purchase_bill, purchase_date) 
                      VALUES ('$itemid', '$now', '$itemtype', '$itemname', '$quantity', '$remarks', '$collectedby', '$asset_status', '$asset_category', '$unit_cost', '$doclink_photo_path', '$doclink_bill_path', '$purchase_date')";

            $gps_result = pg_query($con, $gps_query);

            if ($gps_result) {
                // Prepare the changes array (only what's relevant)
                $changes = [
                    'itemtype' => $itemtype,
                    'itemname' => $itemname,
                    'quantity' => $quantity,
                    'asset_status' => $asset_status,
                    'collectedby' => $collectedby,
                    'remarks' => $remarks,
                    'asset_category' => $asset_category,
                    'unit_cost' => $unit_cost,
                    'asset_photo' => $doclink_photo_path,
                    'purchase_bill' => $doclink_bill_path,
                    'purchase_date' => $purchase_date
                ];
                $changes_json = json_encode($changes);

                // Insert into gps_history table with only required columns
                $history_query = "INSERT INTO gps_history (itemid, update_type, updatedby, date, changes) 
                              VALUES ('$itemid', 'add_asset', '$collectedby', '$now', '$changes_json')";
                $history_result = pg_query($con, $history_query);

                $cmdtuples = pg_affected_rows($gps_result);

                // Set success message
                $_SESSION['success_message'] = "Asset $itemid has been added successfully!";
            } else {
                // Handle SQL error
                $error_message = pg_last_error($con);
                $_SESSION['error_message'] = "Error adding asset: $error_message";
                $cmdtuples = 0;
            }
        } else {
            $_SESSION['error_message'] = "Please select an asset type!";
            $cmdtuples = 0;
        }
    }

    $taggedto = isset($_GET['taggedto']) ? strtoupper($_GET['taggedto']) : '';
    $item_type = isset($_GET['item_type']) ? $_GET['item_type'] : '';
    $assetid = isset($_GET['assetid']) ? $_GET['assetid'] : '';
    $is_user = isset($_GET['is_user']) ? $_GET['is_user'] : '';
    $assetstatus = isset($_GET['assetstatus']) ? $_GET['assetstatus'] : 'Active';
    $assetcategory = isset($_GET['assetcategory']) ? $_GET['assetcategory'] : '';

    // Check for session messages
    if (isset($_SESSION['success_message'])) {
        $success_message = $_SESSION['success_message'];
        unset($_SESSION['success_message']);
    }

    if (isset($_SESSION['error_message'])) {
        $error_message = $_SESSION['error_message'];
        unset($_SESSION['error_message']);
    }

    $conditions = [];

    if ($item_type != "ALL" && $item_type != "") {
        $conditions[] = "itemtype = '$item_type'";
    }
    if ($assetcategory != "ALL" && $assetcategory != "") {
        $conditions[] = "asset_category = '$assetcategory'";
    }

    if ($taggedto != "") {
        $conditions[] = "taggedto = '$taggedto'";
    }

    if ($assetid != "") {
        // Check if it's comma-separated IDs
        if (strpos($assetid, ',') !== false) {
            $assetIds = array_map('trim', explode(',', $assetid));
            $sanitizedIds = array_map(function ($id) use ($con) {
                return pg_escape_string($con, $id);
            }, $assetIds);

            if (!empty($sanitizedIds)) {
                $idList = "'" . implode("','", $sanitizedIds) . "'";
                $conditions[] = "itemid IN ($idList)";
            }
        } else {
            // Single ID or name search
            $conditions[] = "(itemid = '$assetid' OR itemname ILIKE '%$assetid%')";
        }
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
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <div class="card">
                                            <div class="card-header">
                                                <h5 class="mb-0">Add New Asset</h5>
                                            </div>
                                            <div class="card-body">
                                                <form autocomplete="off" name="gps" id="gps" action="gps.php" method="POST" enctype="multipart/form-data" class="row g-3">
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

                                <div class="row">
                                    <div class="col-12">
                                        <div class="card">
                                            <div class="card-header">
                                                <h5 class="mb-0">Search Assets</h5>
                                            </div>
                                            <div class="card-body">
                                                <form name="gpsdetails" id="gpsdetails" action="" method="GET" class="row g-3">
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
                                                        <label for="taggedto" class="form-label">Tagged To</label>
                                                        <input type="text" name="taggedto" class="form-control" placeholder="Enter person name" value="<?php echo htmlspecialchars($taggedto) ?>">
                                                    </div>

                                                    <div class="col-md-3">
                                                        <label for="assetid" class="form-label">Asset ID or Name</label>
                                                        <input type="text" name="assetid" class="form-control" placeholder="Enter asset ID or name" value="<?php echo htmlspecialchars($assetid) ?>">
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
                                                        <button type="submit" name="search_by_id2" class="btn btn-primary">
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

                                <script>
                                    document.addEventListener('DOMContentLoaded', function() {
                                        const checkbox = document.getElementById('is_user');
                                        const assetIdInput = document.getElementsByName('assetid')[0];
                                        const itemTypeInput = document.getElementsByName('item_type')[0];
                                        const taggedToInput = document.getElementsByName('taggedto')[0];
                                        const itemStatusInput = document.getElementsByName('assetstatus')[0];
                                        const assetCategoryInput = document.getElementsByName('assetcategory')[0];

                                        function updateFormState() {
                                            if (checkbox.checked) {
                                                assetIdInput.disabled = false;
                                                itemTypeInput.disabled = true;
                                                itemStatusInput.disabled = true;
                                                taggedToInput.disabled = true;
                                                assetCategoryInput.disabled = true;

                                                // Change labels to indicate disabled state
                                                itemTypeInput.closest('.col-md-3').classList.add('text-muted');
                                                itemStatusInput.closest('.col-md-3').classList.add('text-muted');
                                                taggedToInput.closest('.col-md-3').classList.add('text-muted');
                                                assetIdInput.closest('.col-md-3').classList.remove('text-muted');
                                                assetCategoryInput.closest('.col-md-3').classList.add('text-muted');
                                            } else {
                                                assetIdInput.disabled = true;
                                                itemTypeInput.disabled = false;
                                                itemStatusInput.disabled = false;
                                                taggedToInput.disabled = false;
                                                assetCategoryInput.disabled = false;

                                                // Remove muted state
                                                itemTypeInput.closest('.col-md-3').classList.remove('text-muted');
                                                itemStatusInput.closest('.col-md-3').classList.remove('text-muted');
                                                taggedToInput.closest('.col-md-3').classList.remove('text-muted');
                                                assetIdInput.closest('.col-md-3').classList.add('text-muted');
                                                assetCategoryInput.closest('.col-md-3').classList.remove('text-muted');
                                            }
                                        }

                                        // Initial state
                                        updateFormState();

                                        // Add event listener
                                        checkbox.addEventListener('change', updateFormState);
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
                                    <input type="hidden" value="<?php echo @$assetcategory ?>" name="asset_category" />

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
                                                <th scope="col">Asset category</th>
                                                <th scope="col">Unit price</th>
                                                <th scope="col">Purchase Date</th>
                                                <th scope="col">Photo</th>
                                                <th scope="col">Bill</th>
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
                                                            <?php if (!empty($array['asset_photo'])): ?>
                                                                <span>Y</span>
                                                            <?php else: ?>
                                                                <span>N/A</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if (!empty($array['purchase_bill'])): ?>
                                                                <span>Y</span>
                                                            <?php else: ?>
                                                                <span>N/A</span>
                                                            <?php endif; ?>
                                                        </td>
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
                                                    <td><?= $array['ifullname'] ?></td>
                                                    <td><?= $array['tfullname'] ?></td>
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
                                                                <?php endif; ?>
                                                                <li>
                                                                    <!-- Asset History link styled like a button -->
                                                                    <a href="gps_history.php?assetid=<?= htmlspecialchars($array['itemid']) ?>" target="_blank" title="Asset History"
                                                                        class="btn btn-link dropdown-item text-start w-100" style="padding-left: 1rem;">
                                                                        <i class="bi bi-clock-history"></i> Asset History
                                                                    </a>
                                                                </li>
                                                                <li>
                                                                    <?php if (!empty($array['purchase_bill'])): ?>
                                                                        <a href="<?= htmlspecialchars($array['purchase_bill']) ?>"
                                                                            target="_blank"
                                                                            title="View Invoice"
                                                                            class="btn btn-link dropdown-item text-start w-100"
                                                                            style="padding-left: 1rem;">
                                                                            <i class="bi bi-receipt"></i> View Invoice
                                                                        </a>
                                                                    <?php else: ?>
                                                                        <span class="dropdown-item text-muted disabled" style="padding-left: 1rem;">
                                                                            <i class="bi bi-receipt"></i> View Invoice
                                                                        </span>
                                                                    <?php endif; ?>
                                                                </li>
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
                                                                <li>
                                                                    <a href="javascript:void(0)"
                                                                        onclick="showUploadFiles('<?= htmlspecialchars($array['itemid']) ?>')"
                                                                        title="Update Asset Photo & Bill"
                                                                        class="dropdown-item">
                                                                        <i class="bi bi-upload"></i> Update Photo & Bill
                                                                    </a>
                                                                </li>
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
                                        <form id="gpsform" action="#" method="POST" enctype="multipart/form-data">
                                            <div class="modal-body">
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
    <script>
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

        function showUploadFiles(id) {
            var item = findItem(id);
            if (!item) return;

            // Set global mode
            window.currentMode = 'upload';

            // Set modal title for upload
            document.getElementById('myModalLabel').textContent = 'Update Asset Files';

            // Populate item ID
            document.querySelector('#myModal .itemid').textContent = item.itemid;
            document.querySelector('#myModal .itemname').textContent = item.itemname;
            document.getElementById('itemid1').value = item.itemid;

            // Fill form with current data for context (BUT DON'T SHOW THESE FIELDS)
            <?php if ($role == 'Admin'): ?>
                // Store values but fields will be hidden
                document.getElementById('itemtype').value = item.itemtype || "";
                document.getElementById('itemname').value = item.itemname || "";
                document.getElementById('quantity').value = item.quantity || "";
                document.getElementById('asset_status').value = item.asset_status || "";
                document.getElementById('remarks').value = item.remarks || "";
                document.getElementById('unit_cost').value = item.unit_cost || "";
                document.getElementById('asset_category').value = item.asset_category || "";
                document.getElementById('purchase_date').value = item.purchase_date || "";

                // Handle Select2 fields
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

            // Hide ALL non-file fields
            hideNonFileFields();

            // NEW: Check if files exist and disable fields
            var assetPhotoField = document.getElementById('asset_photo');
            var purchaseBillField = document.getElementById('purchase_bill');

            if (item.asset_photo) {
                if (assetPhotoField) {
                    assetPhotoField.disabled = true;
                    assetPhotoField.placeholder = "Photo already uploaded (leave blank to keep)";
                }
            }

            if (item.purchase_bill) {
                if (purchaseBillField) {
                    purchaseBillField.disabled = true;
                    purchaseBillField.placeholder = "Bill already uploaded (leave blank to keep)";
                }
            }

            // Change button text
            document.querySelector('.button-text').textContent = 'Upload Files';

            // Clear file inputs (optional)
            document.getElementById('asset_photo').value = '';
            document.getElementById('purchase_bill').value = '';

            modal.show();
        }

        function showDetails(id) {
            var item = findItem(id);
            if (!item) return;

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
                document.getElementById('itemtype').value = item.itemtype || "";
                document.getElementById('itemname').value = item.itemname || "";
                document.getElementById('quantity').value = item.quantity || "";
                document.getElementById('asset_status').value = item.asset_status || "";
                document.getElementById('remarks').value = item.remarks || "";
                document.getElementById('unit_cost').value = item.unit_cost || "";
                document.getElementById('asset_category').value = item.asset_category || "";
                document.getElementById('purchase_date').value = item.purchase_date || "";

                // Handle Select2 field for collectedby
                var collectedbyValue = item.collectedby || "";
                if (collectedbyValue) {
                    // Create a new option and append it to the select
                    var newOption = new Option(collectedbyValue, collectedbyValue, true, true);
                    $('#collectedby').append(newOption).trigger('change');
                } else {
                    // Clear if no value
                    $('#collectedby').val(null).trigger('change');
                }

                // Handle Select2 field for taggedto
                var taggedtoValue = item.taggedto || "";
                if (taggedtoValue) {
                    // Create a new option and append it to the select
                    var newOption = new Option(taggedtoValue, taggedtoValue, true, true);
                    $('#taggedto').append(newOption).trigger('change');
                } else {
                    // Clear if no value
                    $('#taggedto').val(null).trigger('change');
                }
            <?php endif; ?>

            // NEW: Check existing files and disable fields if files exist
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
            <?php endif; ?>
        });
    </script>

    <script>
        const scriptURL = 'payment-api.php';
        const form = document.getElementById('gpsform');

        // Use the global currentMode variable (don't redeclare it)
        // window.currentMode is already defined in the first script block

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
                            location.reload();
                        } else if (result === 'nochange') {
                            alert("No changes detected.");
                        } else if (result === 'invalid') {
                            alert("Invalid asset ID.");
                        } else if (result === 'unauthorized') {
                            alert("You are not authorized to perform this action.");
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
    </script>
    <script>
        $(document).ready(function() {
            // Function to check if Asset ID field has value and update button visibility
            function updateClearButtonVisibility() {
                const assetIdValue = $('input[name="assetid"]').val().trim();
                if (assetIdValue) {
                    $('#clear-selection').show();
                } else {
                    $('#clear-selection').hide();
                }
            }

            // Initial check on page load
            updateClearButtonVisibility();

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

                    // NEW: Populate Asset ID search field with selected IDs
                    updateAssetIdSearchField();
                }
            });

            // NEW: Function to update Asset ID search field with selected IDs
            function updateAssetIdSearchField() {
                const selectedCheckboxes = $('.asset-checkbox:checked');
                const assetIds = [];

                selectedCheckboxes.each(function() {
                    // Get the itemid from the second column of the same row
                    const itemid = $(this).closest('tr').find('td:nth-child(2)').text().trim();
                    if (itemid) {
                        assetIds.push(itemid);
                    }
                });

                // Update the search field with comma-separated IDs
                if (assetIds.length > 0) {
                    // Enable the "Search by Asset ID or name only" checkbox
                    $('#is_user').prop('checked', true);

                    // Trigger the updateFormState function to enable the assetid field
                    updateFormState();

                    // Set the assetid field value
                    $('input[name="assetid"]').val(assetIds.join(', '));

                    // Show the clear button
                    updateClearButtonVisibility();
                } else {
                    // If no checkboxes are selected, clear the field and hide button
                    $('input[name="assetid"]').val('');
                    $('#is_user').prop('checked', false);
                    updateFormState();
                    updateClearButtonVisibility();
                }
            }

            // Function to update form state based on checkbox
            function updateFormState() {
                const checkbox = document.getElementById('is_user');
                const assetIdInput = document.getElementsByName('assetid')[0];
                const itemTypeInput = document.getElementsByName('item_type')[0];
                const taggedToInput = document.getElementsByName('taggedto')[0];
                const itemStatusInput = document.getElementsByName('assetstatus')[0];
                const assetCategoryInput = document.getElementsByName('assetcategory')[0];

                if (checkbox.checked) {
                    assetIdInput.disabled = false;
                    itemTypeInput.disabled = true;
                    itemStatusInput.disabled = true;
                    taggedToInput.disabled = true;
                    assetCategoryInput.disabled = true;

                    // Change labels to indicate disabled state
                    itemTypeInput.closest('.col-md-3').classList.add('text-muted');
                    itemStatusInput.closest('.col-md-3').classList.add('text-muted');
                    taggedToInput.closest('.col-md-3').classList.add('text-muted');
                    assetIdInput.closest('.col-md-3').classList.remove('text-muted');
                    assetCategoryInput.closest('.col-md-3').classList.add('text-muted');
                } else {
                    assetIdInput.disabled = true;
                    itemTypeInput.disabled = false;
                    itemStatusInput.disabled = false;
                    taggedToInput.disabled = false;
                    assetCategoryInput.disabled = false;

                    // Remove muted state
                    itemTypeInput.closest('.col-md-3').classList.remove('text-muted');
                    itemStatusInput.closest('.col-md-3').classList.remove('text-muted');
                    taggedToInput.closest('.col-md-3').classList.remove('text-muted');
                    assetIdInput.closest('.col-md-3').classList.add('text-muted');
                    assetCategoryInput.closest('.col-md-3').classList.remove('text-muted');
                }
            }

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

                // Also clear the Asset ID search field
                $('input[name="assetid"]').val('');
                $('#is_user').prop('checked', false);
                updateFormState();
                updateClearButtonVisibility();
            });

            // Clear selection button functionality
            $('#clear-selection').click(function(e) {
                e.preventDefault();

                // Clear the search field
                $('input[name="assetid"]').val('');

                // Uncheck the checkbox
                $('#is_user').prop('checked', false);

                // Update form state
                updateFormState();

                // Remove highlighting from all rows
                $('#table-id tbody tr').removeClass('table-primary');

                // Uncheck all checkboxes
                $('.asset-checkbox').prop('checked', false);
                $('#select-all-checkbox').prop('checked', false);
                updateRowSelection();

                // Hide the clear button
                updateClearButtonVisibility();

                // Get current URL
                const url = new URL(window.location.href);

                // Remove assetid and is_user parameters
                url.searchParams.delete('assetid');
                url.searchParams.delete('is_user');

                // Redirect to the updated URL (reloads the page)
                //window.location.href = url.toString();
            });

            // Also listen for manual input in the Asset ID field
            $('input[name="assetid"]').on('input', function() {
                updateClearButtonVisibility();

                // If user types something, check the checkbox automatically
                if ($(this).val().trim()) {
                    $('#is_user').prop('checked', true);
                    updateFormState();
                } else {
                    $('#is_user').prop('checked', false);
                    updateFormState();
                }
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

                        // Check if Asset ID field has value from URL
                        updateClearButtonVisibility();
                    }
                });
            <?php endif; ?>

            // Update search placeholder to indicate both ID and name search
            $('div.dataTables_filter input').attr('placeholder', 'Search by Asset ID or Name...');
        });
    </script>
    <script>
        $(document).ready(function() {
            // Common AJAX configuration for both selects
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

            // Function to initialize a Select2 on a given element ID
            function initSelect2(elementId, modalId = null) {
                const $element = $(`#${elementId}`);
                if (!$element.hasClass("select2-hidden-accessible")) {
                    $element.select2({
                        ajax: ajaxConfig,
                        minimumInputLength: 2,
                        placeholder: 'Select associate(s)',
                        allowClear: true,
                        width: '100%',
                        theme: 'bootstrap-5',
                        dropdownParent: modalId ? $(`#${modalId}`) : undefined
                    });
                }
            }

            // Initialize when modal is shown
            $('#myModal').on('shown.bs.modal', function() {
                initSelect2('taggedto', 'myModal');
            });
            // Initialize when modal is shown
            $('#myModal').on('shown.bs.modal', function() {
                initSelect2('collectedby', 'myModal');
            });

            // Or initialize immediately if not inside modal:
            initSelect2('tagged-to-select');
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
    <script>
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
    </script>
    <script>
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
    </script>
</body>

</html>