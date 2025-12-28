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

// Handle form submissions
if ($_POST) {
    if ($role == 'Admin') {
        // Existing policy creation code
        $policyid = uniqid();
        $policytype = $_POST['policytype'];
        $policyname = $_POST['policyname'];
        $remarks = $_POST['remarks'];
        $issuedon = date('Y-m-d H:i:s');

        if (!empty($_FILES['policydoc']['name'])) {
            $policydoc = $_FILES['policydoc'];
            $filename = $policyid . "_" . $policyname . "_" . time();
            $parent = '1KlYwKIuAHkWdYJkT3SdhW1nx_T6tZqXA';
            $doclink = uploadeToDrive($policydoc, $parent, $filename);
        }

        if ($doclink !== null) {
            $policy = "INSERT INTO policy (policyid, policytype, policyname, remarks, policydoc, issuedby, issuedon) 
                      VALUES ('$policyid', '$policytype', '$policyname', '$remarks', '$doclink', '$associatenumber', '$issuedon')";
            $result = pg_query($con, $policy);
            $cmdtuples = pg_affected_rows($result);
        }
    }
}

// Get filter parameters
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'active';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'hr-policy'; // Get active tab from URL

// Build the query based on filters
$query = "SELECT * FROM policy WHERE ";
if ($filter == 'active') {
    $query .= "(is_inactive IS NULL OR is_inactive = false)";
} elseif ($filter == 'inactive') {
    $query .= "is_inactive = true";
} else {
    $query .= "1=1"; // Show all
}

if (!empty($search)) {
    $search = pg_escape_string($con, $search);
    $query .= " AND (policyname ILIKE '%$search%' OR remarks ILIKE '%$search%' OR policytype ILIKE '%$search%')";
}

$query .= " ORDER BY issuedon DESC";
$result = pg_query($con, $query);
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

    <title>Resource Hub</title>

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

        .nav-tabs .nav-link.active {
            font-weight: bold;
            border-bottom: 3px solid #0d6efd;
        }

        .tab-pane {
            padding: 20px 0;
        }

        .inactive-document {
            opacity: 0.7;
            background-color: #f8f9fa;
        }

        .status-badge {
            font-size: 0.7em;
            margin-left: 5px;
        }

        .highlight {
            background-color: #fff3cd !important;
            border: 1px solid #ffeaa7;
        }
    </style>
    <!-- CSS Library Files -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
    <!-- JavaScript Library Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Resource Hub</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">My Services</a></li>
                    <li class="breadcrumb-item active">Resource Hub</li>
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
                                <?php if (@$policyid != null && @$cmdtuples == 0) { ?>

                                    <div class="alert alert-danger alert-dismissible" role="alert" style="text-align: -webkit-center;">
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                        <i class="bi bi-exclamation-triangle"></i>
                                        <span>ERROR: Oops, something wasn't right.</span>
                                    </div>
                                <?php
                                } else if (@$cmdtuples == 1) { ?>

                                    <div class="alert alert-success alert-dismissible" role="alert" style="text-align: -webkit-center;">
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                        <i class="bi bi-check2-circle"></i>
                                        <span>Database has been updated successfully for policy id <?php echo @$policyid ?>.</span>
                                    </div>
                                    <script>
                                        if (window.history.replaceState) {
                                            window.history.replaceState(null, null, window.location.href);
                                        }
                                    </script>
                                <?php } ?>
                            <?php } ?>

                            <?php if ($role == 'Admin') { ?>
                                <form autocomplete="off" name="policy" id="policy" action="resourcehub.php" method="POST" enctype="multipart/form-data">
                                    <div class="form-group" style="display: inline-block;">
                                        <div class="col2" style="display: inline-block;">

                                            <span class="input-help">
                                                <select name="policytype" class="form-select" style="width:max-content; display:inline-block" required>
                                                    <?php if ($category == null) { ?>
                                                        <option value="" disabled selected hidden>Category</option>
                                                    <?php } else { ?>
                                                        <option value="<?php echo $category ?>" selected hidden><?php echo $category ?></option>
                                                    <?php } ?>

                                                    <option value="Internal">Internal</option>
                                                    <option value="Confidential">Confidential</option>
                                                    <option value="Public">Public</option>
                                                    <option value="HR Policy">HR Policy</option>
                                                </select>

                                                <small id="passwordHelpBlock" class="form-text text-muted">Category</small>
                                            </span>

                                            <span class="input-help">
                                                <input type="text" name="policyname" class="form-control" style="width:max-content; display:inline-block" placeholder="Policy name" required></input>
                                                <small id="passwordHelpBlock" class="form-text text-muted">Policy name</small>
                                            </span>

                                            <span class="input-help">
                                                <textarea type="text" name="remarks" class="form-control" style="width:max-content; display:inline-block" placeholder="Remarks" value=""></textarea>
                                                <small id="passwordHelpBlock" class="form-text text-muted">Remarks</small>
                                            </span>

                                            <span class="input-help">
                                                <input class="form-control" type="file" id="policydoc" name="policydoc" required>
                                                <div class="form-text">Upload File</div>
                                            </span>

                                        </div>

                                    </div>

                                    <div class="col2 left" style="display: inline-block;">
                                        <button type="submit" name="search_by_id" class="btn btn-danger btn-sm" style="outline: none;">
                                            <i class="bi bi-plus-lg"></i>&nbsp;&nbsp;Add</button>
                                    </div>
                                </form>
                            <?php } ?>

                            <!-- Filter and Search Controls -->
                            <div class="filter-container">
                                <form method="get" action="resourcehub.php" class="search-box mb-3" id="searchForm">
                                    <input type="hidden" name="tab" id="activeTabInput" value="<?php echo isset($_GET['tab']) ? $_GET['tab'] : 'hr-policy'; ?>">
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="search" placeholder="Search documents..."
                                            value="<?php echo htmlspecialchars($search); ?>">
                                        <button class="btn btn-outline-secondary" type="submit">
                                            <i class="bi bi-search"></i>
                                        </button>
                                        <?php if (!empty($search)): ?>
                                            <a href="resourcehub.php?filter=<?php echo $filter; ?>" class="btn btn-outline-danger">
                                                <i class="bi bi-x"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </form>

                                <div class="filter-buttons mb-3">
                                    <a href="?tab=<?php echo $active_tab; ?>&filter=active<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"
                                        class="btn btn-sm btn-filter <?php echo $filter == 'active' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                        Active
                                    </a>
                                    <a href="?tab=<?php echo $active_tab; ?>&filter=inactive<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"
                                        class="btn btn-sm btn-filter <?php echo $filter == 'inactive' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                        Inactive
                                    </a>
                                    <a href="?tab=<?php echo $active_tab; ?>&filter=all<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"
                                        class="btn btn-sm btn-filter <?php echo $filter == 'all' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                        All
                                    </a>
                                </div>
                            </div>

                            <!-- Tabs for HR Policy and Other Policies -->
                            <ul class="nav nav-tabs" id="policyTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <a href="?tab=hr-policy&filter=<?php echo $filter; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"
                                        class="nav-link <?php echo ($active_tab == 'hr-policy') ? 'active' : ''; ?>"
                                        id="hr-policy-tab"
                                        data-bs-toggle="tab"
                                        data-bs-target="#hr-policy"
                                        type="button"
                                        role="tab"
                                        aria-controls="hr-policy"
                                        aria-selected="<?php echo ($active_tab == 'hr-policy') ? 'true' : 'false'; ?>">
                                        HR Policies
                                    </a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a href="?tab=other-policy&filter=<?php echo $filter; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"
                                        class="nav-link <?php echo ($active_tab == 'other-policy') ? 'active' : ''; ?>"
                                        id="other-policy-tab"
                                        data-bs-toggle="tab"
                                        data-bs-target="#other-policy"
                                        type="button"
                                        role="tab"
                                        aria-controls="other-policy"
                                        aria-selected="<?php echo ($active_tab == 'other-policy') ? 'true' : 'false'; ?>">
                                        Other Documents
                                    </a>
                                </li>
                            </ul>

                            <div class="tab-content" id="policyTabsContent">
                                <!-- HR Policy Tab -->
                                <div class="tab-pane fade <?php echo ($active_tab == 'hr-policy') ? 'show active' : ''; ?>" id="hr-policy" role="tabpanel" aria-labelledby="hr-policy-tab">
                                    <div class="table-responsive">
                                        <table class="table hr-policy-table" id="hr-policy-table">
                                            <thead>
                                                <tr>
                                                    <th>Policy Id</th>
                                                    <th>Category</th>
                                                    <th>Date</th>
                                                    <th>Policy name</th>
                                                    <th>Details</th>
                                                    <th>Policy document</th>
                                                    <?php if ($role == 'Admin') { ?>
                                                        <th>Action</th>
                                                    <?php } ?>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $hrPolicies = array_filter($resultArr, function ($array) {
                                                    return $array['policytype'] === 'HR Policy';
                                                });

                                                // Check if search results are in HR Policies
                                                $searchInHrPolicies = false;
                                                if (!empty($search) && !empty($hrPolicies)) {
                                                    foreach ($hrPolicies as $policy) {
                                                        if (
                                                            stripos($policy['policyname'], $search) !== false ||
                                                            stripos($policy['remarks'], $search) !== false ||
                                                            stripos($policy['policytype'], $search) !== false
                                                        ) {
                                                            $searchInHrPolicies = true;
                                                            break;
                                                        }
                                                    }
                                                }
                                                ?>
                                                <?php if (!empty($hrPolicies)): ?>
                                                    <?php foreach ($hrPolicies as $array):
                                                        $isInactive = $array['is_inactive'] === 't' || $array['is_inactive'] === true;
                                                        $isSearchMatch = !empty($search) &&
                                                            (stripos($array['policyname'], $search) !== false ||
                                                                stripos($array['remarks'], $search) !== false ||
                                                                stripos($array['policytype'], $search) !== false);
                                                    ?>
                                                        <tr class="<?php echo $isInactive ? 'inactive-document' : ''; ?> <?php echo $isSearchMatch ? 'highlight' : ''; ?>">
                                                            <td><?php echo $array['policyid']; ?></td>
                                                            <td><?php echo $array['policytype']; ?></td>
                                                            <td><?php echo @date("d/m/Y g:i a", strtotime($array['issuedon'])); ?></td>
                                                            <td>
                                                                <?php echo $array['policyname']; ?>
                                                                <?php if ($isInactive): ?>
                                                                    <span class="badge bg-secondary status-badge">Inactive</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <form name="policybody_<?php echo $array['policyid']; ?>" action="#" method="POST" style="display: -webkit-inline-box;">
                                                                    <input type="hidden" name="form-type" value="policybodyedit">
                                                                    <input type="hidden" name="policyid" value="<?php echo $array['policyid']; ?>">
                                                                    <textarea id="inp_<?php echo $array['policyid']; ?>" name="remarks" disabled><?php echo $array['remarks']; ?></textarea>

                                                                    <?php if ($role == 'Admin') { ?>
                                                                        &nbsp;
                                                                        <button type="button" id="edit_<?php echo $array['policyid']; ?>" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Edit">
                                                                            <i class="bi bi-pencil-square"></i>
                                                                        </button>&nbsp;
                                                                        <button type="submit" id="save_<?php echo $array['policyid']; ?>" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Save">
                                                                            <i class="bi bi-save"></i>
                                                                        </button>
                                                                    <?php } ?>
                                                                </form>
                                                            </td>

                                                            <?php if ($array['policydoc'] == null) { ?>
                                                                <td></td>
                                                            <?php } else { ?>
                                                                <td><a href="<?php echo $array['policydoc']; ?>" target="_blank"><i class="bi bi-file-earmark-pdf" style="color:#777777" title="<?php echo $array['policyid']; ?>"></i></a></td>
                                                            <?php } ?>

                                                            <?php if ($role == 'Admin') { ?>
                                                                <td>
                                                                    <!-- Toggle status button -->
                                                                    <form name="toggleInactive_<?php echo $array['policyid']; ?>" action="#" method="POST" style="display: inline-block;">
                                                                        <input type="hidden" name="form-type" value="toggleInactive">
                                                                        <input type="hidden" name="policyid" value="<?php echo $array['policyid']; ?>">
                                                                        <input type="hidden" name="currentStatus" value="<?php echo $isInactive ? 'true' : 'false'; ?>">
                                                                        <button type="submit" class="btn btn-sm <?php echo $isInactive ? 'btn-success' : 'btn-warning'; ?>"
                                                                            title="<?php echo $isInactive ? 'Activate document' : 'Deactivate document'; ?>">
                                                                            <i class="bi <?php echo $isInactive ? 'bi-check-circle' : 'bi-slash-circle'; ?>"></i>
                                                                        </button>
                                                                    </form>
                                                                </td>
                                                            <?php } ?>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="7" class="text-center">No HR Policies found</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Other Policy Tab -->
                                <div class="tab-pane fade <?php echo ($active_tab == 'other-policy') ? 'show active' : ''; ?>" id="other-policy" role="tabpanel" aria-labelledby="other-policy-tab">
                                    <div class="table-responsive">
                                        <table class="table other-policy-table" id="other-policy-table">
                                            <thead>
                                                <tr>
                                                    <th>Policy Id</th>
                                                    <th>Category</th>
                                                    <th>Date</th>
                                                    <th>Policy name</th>
                                                    <th>Details</th>
                                                    <th>Policy document</th>
                                                    <?php if ($role == 'Admin') { ?>
                                                        <th>Action</th>
                                                    <?php } ?>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $otherPolicies = array_filter($resultArr, function ($array) {
                                                    return $array['policytype'] !== 'HR Policy';
                                                });

                                                // Check if search results are in Other Policies
                                                $searchInOtherPolicies = false;
                                                if (!empty($search) && !empty($otherPolicies)) {
                                                    foreach ($otherPolicies as $policy) {
                                                        if (
                                                            stripos($policy['policyname'], $search) !== false ||
                                                            stripos($policy['remarks'], $search) !== false ||
                                                            stripos($policy['policytype'], $search) !== false
                                                        ) {
                                                            $searchInOtherPolicies = true;
                                                            break;
                                                        }
                                                    }
                                                }
                                                ?>
                                                <?php if (!empty($otherPolicies)): ?>
                                                    <?php foreach ($otherPolicies as $array):
                                                        $isInactive = $array['is_inactive'] === 't' || $array['is_inactive'] === true;
                                                        $isSearchMatch = !empty($search) &&
                                                            (stripos($array['policyname'], $search) !== false ||
                                                                stripos($array['remarks'], $search) !== false ||
                                                                stripos($array['policytype'], $search) !== false);
                                                    ?>
                                                        <tr class="<?php echo $isInactive ? 'inactive-document' : ''; ?> <?php echo $isSearchMatch ? 'highlight' : ''; ?>">
                                                            <td><?php echo $array['policyid']; ?></td>
                                                            <td><?php echo $array['policytype']; ?></td>
                                                            <td><?php echo @date("d/m/Y g:i a", strtotime($array['issuedon'])); ?></td>
                                                            <td>
                                                                <?php echo $array['policyname']; ?>
                                                                <?php if ($isInactive): ?>
                                                                    <span class="badge bg-secondary status-badge">Inactive</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <form name="policybody_<?php echo $array['policyid']; ?>" action="#" method="POST" style="display: -webkit-inline-box;">
                                                                    <input type="hidden" name="form-type" value="policybodyedit">
                                                                    <input type="hidden" name="policyid" value="<?php echo $array['policyid']; ?>">
                                                                    <textarea id="inp_<?php echo $array['policyid']; ?>" name="remarks" disabled><?php echo $array['remarks']; ?></textarea>

                                                                    <?php if ($role == 'Admin') { ?>
                                                                        &nbsp;
                                                                        <button type="button" id="edit_<?php echo $array['policyid']; ?>" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Edit">
                                                                            <i class="bi bi-pencil-square"></i>
                                                                        </button>&nbsp;
                                                                        <button type="submit" id="save_<?php echo $array['policyid']; ?>" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Save">
                                                                            <i class="bi bi-save"></i>
                                                                        </button>
                                                                    <?php } ?>
                                                                </form>
                                                            </td>

                                                            <?php if ($array['policydoc'] == null) { ?>
                                                                <td></td>
                                                            <?php } else { ?>
                                                                <td><a href="<?php echo $array['policydoc']; ?>" target="_blank"><i class="bi bi-file-earmark-pdf" style="color:#777777" title="<?php echo $array['policyid']; ?>"></i></a></td>
                                                            <?php } ?>

                                                            <?php if ($role == 'Admin') { ?>
                                                                <td>
                                                                    <!-- Toggle status button -->
                                                                    <form name="toggleInactive_<?php echo $array['policyid']; ?>" action="#" method="POST" style="display: inline-block;">
                                                                        <input type="hidden" name="form-type" value="toggleInactive">
                                                                        <input type="hidden" name="policyid" value="<?php echo $array['policyid']; ?>">
                                                                        <input type="hidden" name="currentStatus" value="<?php echo $isInactive ? 'true' : 'false'; ?>">
                                                                        <button type="submit" class="btn btn-sm <?php echo $isInactive ? 'btn-success' : 'btn-warning'; ?>"
                                                                            title="<?php echo $isInactive ? 'Activate document' : 'Deactivate document'; ?>">
                                                                            <i class="bi <?php echo $isInactive ? 'bi-check-circle' : 'bi-slash-circle'; ?>"></i>
                                                                        </button>
                                                                    </form>
                                                                </td>
                                                            <?php } ?>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="7" class="text-center">No other documents found</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <script>
                                var data = <?php echo json_encode($resultArr) ?>;
                                var searchTerm = "<?php echo $search ?>";
                                var searchInHrPolicies = <?php echo $searchInHrPolicies ? 'true' : 'false' ?>;
                                var searchInOtherPolicies = <?php echo $searchInOtherPolicies ? 'true' : 'false' ?>;
                                var activeTab = "<?php echo isset($_GET['tab']) ? $_GET['tab'] : 'hr-policy'; ?>";
                                var currentFilter = "<?php echo isset($_GET['filter']) ? $_GET['filter'] : 'active'; ?>";

                                // Switch to the tab with search results if applicable
                                $(document).ready(function() {
                                    // Set the active tab based on URL parameter
                                    if (activeTab === 'hr-policy') {
                                        $('#hr-policy-tab').tab('show');
                                    } else if (activeTab === 'other-policy') {
                                        $('#other-policy-tab').tab('show');
                                    }

                                    // If we have a search term, switch to the appropriate tab (overrides URL if needed)
                                    if (searchTerm) {
                                        if (searchInHrPolicies && !searchInOtherPolicies) {
                                            // Only HR policies have matches
                                            $('#hr-policy-tab').tab('show');
                                            $('#activeTabInput').val('hr-policy');
                                            updateUrlTabParam('hr-policy');
                                        } else if (searchInOtherPolicies && !searchInHrPolicies) {
                                            // Only other policies have matches
                                            $('#other-policy-tab').tab('show');
                                            $('#activeTabInput').val('other-policy');
                                            updateUrlTabParam('other-policy');
                                        }
                                        // If both have matches, stay on the current tab from URL
                                    }

                                    // Update the active tab input and URL when tabs are changed
                                    $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
                                        var target = $(e.target).attr("data-bs-target");
                                        var tabValue = '';

                                        if (target === "#hr-policy") {
                                            tabValue = 'hr-policy';
                                        } else if (target === "#other-policy") {
                                            tabValue = 'other-policy';
                                        }

                                        if (tabValue) {
                                            $('#activeTabInput').val(tabValue);
                                            updateUrlTabParam(tabValue);
                                        }
                                    });

                                    // Initialize DataTables for both tables
                                    <?php if (!empty($hrPolicies)) : ?>
                                        $('#hr-policy-table').DataTable({
                                            "order": [], // Disable initial sorting
                                            "searching": false, // Disable DataTables search as we have our own
                                            "stateSave": true,
                                            "stateDuration": -1
                                        });
                                    <?php endif; ?>

                                    <?php if (!empty($otherPolicies)) : ?>
                                        $('#other-policy-table').DataTable({
                                            "order": [], // Disable initial sorting
                                            "searching": false, // Disable DataTables search as we have our own
                                            "stateSave": true,
                                            "stateDuration": -1
                                        });
                                    <?php endif; ?>

                                    // Highlight search terms in the tables
                                    if (searchTerm) {
                                        $('table td').each(function() {
                                            var text = $(this).text();
                                            if (text.toLowerCase().indexOf(searchTerm.toLowerCase()) !== -1) {
                                                $(this).addClass('highlight');
                                            }
                                        });
                                    }

                                    // Handle browser back/forward buttons
                                    window.addEventListener('popstate', function() {
                                        var urlParams = new URLSearchParams(window.location.search);
                                        var tabParam = urlParams.get('tab') || 'hr-policy';

                                        // Show the correct tab based on URL
                                        if (tabParam === 'hr-policy') {
                                            $('#hr-policy-tab').tab('show');
                                        } else if (tabParam === 'other-policy') {
                                            $('#other-policy-tab').tab('show');
                                        }
                                    });
                                });

                                // Function to update URL tab parameter without reloading
                                function updateUrlTabParam(tabValue) {
                                    var urlParams = new URLSearchParams(window.location.search);
                                    urlParams.set('tab', tabValue);

                                    // Keep other existing parameters
                                    if (!urlParams.has('filter') && currentFilter) {
                                        urlParams.set('filter', currentFilter);
                                    }

                                    if (searchTerm && !urlParams.has('search')) {
                                        urlParams.set('search', searchTerm);
                                    }

                                    var newUrl = window.location.pathname + '?' + urlParams.toString();
                                    history.pushState(null, '', newUrl);
                                }

                                data.forEach(item => {
                                    const form = document.getElementById('edit_' + item.policyid);
                                    if (form) {
                                        form.addEventListener('click', function() {
                                            document.getElementById('inp_' + item.policyid).disabled = false;
                                        });
                                    }
                                })

                                //For form submission - to update Remarks
                                const scriptURL = 'payment-api.php'

                                data.forEach(item => {
                                    const form = document.forms['policybody_' + item.policyid]
                                    if (form) {
                                        form.addEventListener('submit', e => {
                                            e.preventDefault()
                                            fetch(scriptURL, {
                                                    method: 'POST',
                                                    body: new FormData(document.forms['policybody_' + item.policyid])
                                                })
                                                .then(response => {
                                                    // Preserve tab parameter when reloading
                                                    var urlParams = new URLSearchParams(window.location.search);
                                                    var currentTab = urlParams.get('tab') || 'hr-policy';
                                                    var currentFilter = urlParams.get('filter') || 'active';
                                                    var currentSearch = urlParams.get('search') || '';

                                                    var reloadUrl = 'resourcehub.php?tab=' + currentTab +
                                                        '&filter=' + currentFilter;

                                                    if (currentSearch) {
                                                        reloadUrl += '&search=' + encodeURIComponent(currentSearch);
                                                    }

                                                    alert("Record has been updated.");
                                                    window.location.href = reloadUrl;
                                                })
                                                .catch(error => console.error('Error!', error.message))
                                        })
                                    }
                                })

                                // Handle toggle inactive/active form submission
                                document.querySelectorAll('form[name^="toggleInactive_"]').forEach(form => {
                                    form.addEventListener('submit', function(e) {
                                        e.preventDefault();
                                        const policyId = this.querySelector('input[name="policyid"]').value;
                                        const currentStatus = this.querySelector('input[name="currentStatus"]').value;
                                        const newStatus = currentStatus === 'true' ? 'false' : 'true';
                                        const action = currentStatus === 'true' ? 'activate' : 'deactivate';

                                        if (confirm(`Are you sure you want to ${action} this document?`)) {
                                            fetch('payment-api.php', {
                                                    method: 'POST',
                                                    headers: {
                                                        'Content-Type': 'application/x-www-form-urlencoded',
                                                    },
                                                    body: `form-type=toggleInactive&policyid=${policyId}&is_inactive=${newStatus}`
                                                })
                                                .then(response => response.json())
                                                .then(data => {
                                                    if (data.success) {
                                                        // Preserve tab parameter when reloading
                                                        var urlParams = new URLSearchParams(window.location.search);
                                                        var currentTab = urlParams.get('tab') || 'hr-policy';
                                                        var currentFilter = urlParams.get('filter') || 'active';
                                                        var currentSearch = urlParams.get('search') || '';

                                                        var reloadUrl = 'resourcehub.php?tab=' + currentTab +
                                                            '&filter=' + currentFilter;

                                                        if (currentSearch) {
                                                            reloadUrl += '&search=' + encodeURIComponent(currentSearch);
                                                        }

                                                        window.location.href = reloadUrl;
                                                    } else {
                                                        alert('Error updating document status: ' + (data.error || 'Unknown error'));
                                                    }
                                                })
                                                .catch(error => {
                                                    console.error('Error:', error);
                                                    alert('Error updating document status');
                                                });
                                        }
                                    });
                                });
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
  <script src="../assets_new/js/text-refiner.js?v=1.1.0"></script>

</body>

</html>