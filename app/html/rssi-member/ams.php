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

date_default_timezone_set('Asia/Kolkata');

// Get current date for academic year calculation
$current_date = date('Y-m-d');
$current_year = date('Y');
$current_month = date('m');

// Calculate academic year
if ($current_month >= 4) {
    // April or later - current academic year is current year to next year
    $current_academic_year_start = $current_year;
    $current_academic_year_end = $current_year + 1;
    $current_academic_year = $current_academic_year_start . '-' . $current_academic_year_end;
} else {
    // January-March - current academic year is previous year to current year
    $current_academic_year_start = $current_year - 1;
    $current_academic_year_end = $current_year;
    $current_academic_year = $current_academic_year_start . '-' . $current_academic_year_end;
}

// Generate academic year options for filter (current + last 3)
$academic_year_options = [];
for ($i = 0; $i < 4; $i++) {
    $year = $current_academic_year_start - $i;
    $academic_year_options[] = $year . '-' . ($year + 1);
}

// Handle form submission for Admin
if ($role == 'Admin') {
    if ($_POST) {
        $noticeid = uniqid();
        $refnumber = $_POST['refnumber'];
        $category = $_POST['category'];
        $noticesub = $_POST['noticesub'];
        $noticebody = htmlspecialchars($_POST['noticebody'], ENT_QUOTES, 'UTF-8');
        $now = date('Y-m-d H:i:s');

        // Check if using URL instead of file upload
        $doclink = null;

        if (isset($_POST['use_url']) && !empty($_POST['notice_url'])) {
            // Use URL provided
            $doclink = $_POST['notice_url'];
        } elseif (!empty($_FILES['notice']['name']) && $_FILES['notice']['error'] === UPLOAD_ERR_OK) {
            // Upload file
            $notice = $_FILES['notice'];
            $filename = $noticeid . "_notice_" . time();
            $parent = '1LPHtex89XQK_HPmMsbthQyQXlLmyQuJ0';
            $doclink = uploadeToDrive($notice, $parent, $filename);
        }

        if ($doclink !== null) {
            // Insert with URL/doclink
            $notice = "INSERT INTO notice (noticeid, refnumber, date, subject, url, issuedby, category, noticebody) VALUES ('$noticeid','$refnumber','$now','$noticesub','$doclink','$associatenumber','$category','$noticebody')";
        } else {
            // Insert without URL
            $notice = "INSERT INTO notice (noticeid, refnumber, date, subject, issuedby, category, noticebody) VALUES ('$noticeid','$refnumber','$now','$noticesub','$associatenumber','$category','$noticebody')";
        }
        $result = pg_query($con, $notice);
        $cmdtuples = pg_affected_rows($result);
    }
}

// Handle search/filter
$search_filter = '';
$academic_year_filter = '';

// Get filter parameters
$search_term = isset($_GET['search']) ? pg_escape_string($con, $_GET['search']) : '';
$selected_academic_year = isset($_GET['academic_year']) ? $_GET['academic_year'] : $current_academic_year;

// Build academic year filter
if ($selected_academic_year) {
    // Parse academic year (e.g., "2023-2024")
    $years = explode('-', $selected_academic_year);
    if (count($years) == 2) {
        $start_year = $years[0];
        $end_year = $years[1];
        // Academic year runs from April 1 to March 31
        $start_date = $start_year . '-04-01 00:00:00';
        $end_date = $end_year . '-03-31 23:59:59';
        $academic_year_filter = "AND date >= '$start_date' AND date <= '$end_date'";
    }
}

// Build search filter
if ($search_term) {
    $search_filter = "AND (
        noticeid ILIKE '%$search_term%' 
        OR refnumber ILIKE '%$search_term%' 
        OR category ILIKE '%$search_term%'
        OR subject ILIKE '%$search_term%'
        OR noticebody ILIKE '%$search_term%'
    )";
}

// Build the query
$query = "SELECT * FROM notice WHERE 1=1 $academic_year_filter $search_filter ORDER BY date DESC";
$result = pg_query($con, $query);

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
    <?php include 'includes/meta.php' ?>



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

        .file-url-container {
            position: relative;
            display: inline-block;
        }

        .file-field,
        .url-field {
            position: relative;
            width: 100%;
        }

        .url-field {
            display: none;
        }

        .filter-form {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }

        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
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
    <?php include 'includes/header.php'; ?>
    <?php include 'inactive_session_expire_check.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1><?php echo getPageTitle(); ?></h1>
            <?php echo generateDynamicBreadcrumb(); ?>
        </div><!-- End Page Title -->

        <section class="section dashboard">
            <div class="row">

                <!-- Reports -->
                <div class="col-12">
                    <div class="card">

                        <div class="card-body">
                            <br>
                            <?php if (isset($noticeid) && isset($cmdtuples) && $cmdtuples == 0) { ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert" style="text-align: -webkit-center;">
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    <i class="bi bi-exclamation-triangle"></i>
                                    <span>ERROR: Oops, something wasn't right.</span>
                                </div>
                            <?php } else if (isset($cmdtuples) && $cmdtuples == 1) { ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert" style="text-align: -webkit-center;">
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    <i class="bi bi-check2-circle"></i>
                                    <span>Database has been updated successfully for notice id <?php echo @$noticeid ?>.</span>
                                </div>
                                <script>
                                    if (window.history.replaceState) {
                                        window.history.replaceState(null, null, window.location.href);
                                    }
                                </script>
                            <?php } ?>
                            <?php if ($role == 'Admin') { ?>
                                <form autocomplete="off" name="ams" id="ams" action="ams.php" method="POST" enctype="multipart/form-data">
                                    <div class="form-group" style="display: inline-block;">
                                        <div class="col2" style="display: inline-block;">

                                            <span class="input-help">
                                                <input type="text" name="refnumber" class="form-control" style="width:max-content; display:inline-block" placeholder="Ref. Number" required>
                                                <small id="passwordHelpBlock" class="form-text text-muted">Ref. Number</small>
                                            </span>

                                            <span class="input-help">
                                                <select name="category" class="form-select" style="width:max-content; display:inline-block" required>
                                                    <option value="" selected hidden>Category</option>
                                                    <option>Internal</option>
                                                    <option>Public</option>
                                                    <option>News & Press Releases</option>
                                                </select>
                                                <small id="passwordHelpBlock" class="form-text text-muted">Category</small>
                                            </span>

                                            <span class="input-help">
                                                <input type="text" name="noticesub" class="form-control" style="width:max-content; display:inline-block" placeholder="Notice Subject" required></input>
                                                <small id="passwordHelpBlock" class="form-text text-muted">Subject</small>
                                            </span>

                                            <span class="input-help">
                                                <textarea type="text" name="noticebody" class="form-control" style="width:max-content; display:inline-block" placeholder="Notice Body"></textarea>
                                                <small id="passwordHelpBlock" class="form-text text-muted">Details</small>
                                            </span>

                                            <!-- File/URL Container -->
                                            <span class="input-help file-url-container" style="display: inline-block; width: max-content;">
                                                <!-- File Upload Field (initially visible) -->
                                                <span class="file-field">
                                                    <input class="form-control" type="file" id="notice" name="notice" style="width: max-content;" required>
                                                    <div class="form-text">Upload File</div>
                                                </span>

                                                <!-- URL Field (initially hidden) -->
                                                <span class="url-field">
                                                    <input type="url" name="notice_url" class="form-control" style="width: max-content;" placeholder="Enter URL (e.g., https://example.com/file.pdf)">
                                                    <div class="form-text">Enter URL</div>
                                                </span>
                                            </span>

                                        </div>
                                    </div>

                                    <div class="col2 left" style="display: inline-block;">
                                        <button type="submit" name="search_by_id" class="btn btn-danger btn-sm" style="outline: none;">
                                            <i class="bi bi-plus-lg"></i>&nbsp;&nbsp;Add</button>
                                    </div>
                                </form>
                                <!-- Toggle checkbox placed after the Add button but inside the form -->
                                <div class="form-check mb-3" style="display: inline-block; margin-left: 10px; vertical-align: middle;">
                                    <input class="form-check-input" type="checkbox" id="useUrlToggle" name="use_url">
                                    <label class="form-check-label" for="useUrlToggle" style="font-size: 0.9em;">
                                        Use URL instead of file upload
                                    </label>
                                </div>

                                <script>
                                    document.addEventListener('DOMContentLoaded', function() {
                                        const useUrlToggle = document.getElementById('useUrlToggle');
                                        const fileField = document.querySelector('.file-field');
                                        const urlField = document.querySelector('.url-field');
                                        const fileInput = document.querySelector('input[name="notice"]');
                                        const urlInput = document.querySelector('input[name="notice_url"]');

                                        // Set file input as required initially since it's visible by default
                                        if (fileInput) {
                                            fileInput.setAttribute('required', 'required');
                                        }
                                        // Remove required from URL input initially since it's hidden
                                        if (urlInput) {
                                            urlInput.removeAttribute('required');
                                        }

                                        // Toggle between file upload and URL fields
                                        if (useUrlToggle) {
                                            useUrlToggle.addEventListener('change', function() {
                                                if (this.checked) {
                                                    fileField.style.display = 'none';
                                                    urlField.style.display = 'block';
                                                    // Make URL required, remove required from file
                                                    if (urlInput) {
                                                        urlInput.setAttribute('required', 'required');
                                                    }
                                                    if (fileInput) {
                                                        fileInput.removeAttribute('required');
                                                    }
                                                } else {
                                                    fileField.style.display = 'block';
                                                    urlField.style.display = 'none';
                                                    // Make file required, remove required from URL
                                                    if (fileInput) {
                                                        fileInput.setAttribute('required', 'required');
                                                    }
                                                    if (urlInput) {
                                                        urlInput.removeAttribute('required');
                                                    }
                                                }
                                            });
                                        }

                                        // Also add a small script to handle form submission to remove required validation
                                        const form = document.getElementById('ams');
                                        if (form) {
                                            form.addEventListener('submit', function() {
                                                // If URL is being used, clear the file input
                                                if (useUrlToggle && useUrlToggle.checked) {
                                                    fileInput.value = '';
                                                }
                                                // If file is being used, clear the URL input
                                                else if (urlInput) {
                                                    urlInput.value = '';
                                                }
                                            });
                                        }
                                    });
                                </script>
                            <?php } ?>

                            <!-- Filters -->
                            <form method="GET" action="" class="filter-form">
                                <div class="filter-row">
                                    <div class="filter-group">
                                        <label for="academic_year" class="form-label">Academic Year</label>
                                        <select name="academic_year" id="academic_year" class="form-select" onchange="this.form.submit()">
                                            <?php foreach ($academic_year_options as $year_option): ?>
                                                <option value="<?php echo $year_option; ?>" <?php echo ($selected_academic_year == $year_option) ? 'selected' : ''; ?>>
                                                    <?php echo $year_option; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="filter-group">
                                        <label for="search" class="form-label">Search (Category/Notice ID/Ref No/Subject)</label>
                                        <div class="input-group">
                                            <input type="text" name="search" id="search" class="form-control" placeholder="Search..." value="<?php echo htmlspecialchars($search_term); ?>">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bi bi-search"></i>
                                            </button>
                                            <?php if ($search_term || $selected_academic_year != $current_academic_year): ?>
                                                <a href="ams.php" class="btn btn-secondary">
                                                    <i class="bi bi-x-circle"></i> Clear
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </form>

                            <div class="table-responsive">
                                <table class="table" id="table-id">
                                    <thead>
                                        <tr>
                                            <th>Notice Id</th>
                                            <th>Ref. Number</th>
                                            <th>Category</th>
                                            <th>Date</th>
                                            <th>Subject</th>
                                            <th>Details</th>
                                            <th>Document</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if ($resultArr) {
                                            foreach ($resultArr as $array) {
                                        ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($array['noticeid']); ?></td>
                                                    <td><?php echo htmlspecialchars($array['refnumber']); ?></td>
                                                    <td><?php echo htmlspecialchars($array['category']); ?></td>
                                                    <td><?php echo @date("d/m/Y g:i a", strtotime($array['date'])); ?></td>
                                                    <td><?php echo htmlspecialchars($array['subject']); ?></td>
                                                    <td>
                                                        <form name="noticebody_<?php echo $array['noticeid']; ?>" action="#" method="POST" style="display: -webkit-inline-box;">
                                                            <input type="hidden" name="form-type" value="noticebodyedit">
                                                            <input type="hidden" name="noticeid" value="<?php echo $array['noticeid']; ?>">
                                                            <textarea id="inp_<?php echo $array['noticeid']; ?>" name="noticebody" disabled><?php echo htmlspecialchars($array['noticebody']); ?></textarea>

                                                            <?php if ($role == 'Admin') { ?>
                                                                &nbsp;
                                                                <button type="button" id="edit_<?php echo $array['noticeid']; ?>" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Edit">
                                                                    <i class="bi bi-pencil-square"></i>
                                                                </button>&nbsp;
                                                                <button type="submit" id="save_<?php echo $array['noticeid']; ?>" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Save">
                                                                    <i class="bi bi-save"></i>
                                                                </button>
                                                            <?php } ?>
                                                        </form>
                                                    </td>
                                                    <?php if ($array['url'] == null) { ?>
                                                        <td></td>
                                                    <?php } else { ?>
                                                        <td><a href="<?php echo htmlspecialchars($array['url']); ?>" target="_blank"><i class="bi bi-file-earmark-pdf" style="font-size: 16px; color:#777777" title="<?php echo htmlspecialchars($array['noticeid']); ?>"></i></a></td>
                                                    <?php } ?>
                                                    </td>
                                                </tr>
                                            <?php }
                                        } else { ?>
                                            <tr>
                                                <td colspan="7" class="text-center">No notices found for the selected filters.</td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>

                            <?php if ($resultArr) { ?>
                                <script>
                                    var data = <?php echo json_encode($resultArr) ?>;

                                    data.forEach(item => {
                                        const editButton = document.getElementById('edit_' + item.noticeid);
                                        if (editButton) {
                                            editButton.addEventListener('click', function() {
                                                document.getElementById('inp_' + item.noticeid).disabled = false;
                                            });
                                        }
                                    })

                                    //For form submission - to update Remarks
                                    const scriptURL = 'payment-api.php'

                                    data.forEach(item => {
                                        const form = document.forms['noticebody_' + item.noticeid]
                                        if (form) {
                                            form.addEventListener('submit', e => {
                                                e.preventDefault()
                                                fetch(scriptURL, {
                                                        method: 'POST',
                                                        body: new FormData(document.forms['noticebody_' + item.noticeid])
                                                    })
                                                    .then(response => alert("Notice body has been updated.") +
                                                        location.reload())
                                                    .catch(error => console.error('Error!', error.message))
                                            })
                                        }
                                    })
                                </script>
                            <?php } ?>

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
            // Check if resultArr is empty
            <?php if (!empty($resultArr)) : ?>
                // Initialize DataTables only if resultArr is not empty
                $('#table-id').DataTable({
                    // paging: false,
                    "order": [] // Disable initial sorting
                    // other options...
                });
            <?php endif; ?>
        });
    </script>

</body>

</html>