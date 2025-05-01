<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();

// Generate academic years (current and past 4 years)
$currentYear = date('Y');
$currentMonth = date('m');

// Determine current academic year
if ($currentMonth >= 4) { // April or later
    $currentAcademicYearStart = $currentYear;
} else { // January-March
    $currentAcademicYearStart = $currentYear - 1;
}

$academicYears = [];
for ($i = 4; $i >= 0; $i--) {
    $year = $currentAcademicYearStart - $i;
    $academicYears[] = $year . '-' . ($year + 1);
}

// Sort the array to show most recent first
rsort($academicYears);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Inventory Insights</title>
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">
    <!-- CSS Library Files -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
    <!-- JavaScript Library Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>
    <style>
        #navbarNav .nav-link.active {
            font-weight: bold;
            color: #0d6efd !important;
            border-bottom: 2px solid #0d6efd;
        }

        .loading-spinner {
            display: none;
            text-align: center;
            padding: 20px;
        }

        .filter-container {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
    </style>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Inventory Insights</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Stock Management</a></li>
                    <li class="breadcrumb-item active">Inventory Insights</li>
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
                            <div class="container">
                                <!-- Academic Year Filter -->
                                <div class="filter-container">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <label for="academicYear" class="form-label">Academic Year</label>
                                            <select class="form-select" id="academicYear">
                                                <?php foreach ($academicYears as $year): ?>
                                                    <option value="<?php echo $year; ?>" <?php echo ($year === $academicYears[0]) ? 'selected' : ''; ?>>
                                                        <?php echo $year; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <!-- <h1>Inventory Insights</h1> -->
                                <!-- Navigation Tabs -->
                                <nav class="navbar navbar-expand-lg navbar-light bg-light mb-4">
                                    <div class="navbar-nav" id="navbarNav">
                                        <a class="nav-item nav-link active" href="#" data-view="stock_add">Detailed Stock Add</a>
                                        <a class="nav-item nav-link" href="#" data-view="stock_distribution">Detailed Distribution Record</a>
                                        <a class="nav-item nav-link" href="#" data-view="user_distribution">User-Based Distribution Record</a>
                                    </div>
                                </nav>

                                <!-- Loading Spinner -->
                                <div class="loading-spinner" id="loadingSpinner">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <p class="mt-2">Loading data...</p>
                                </div>

                                <!-- Content Container -->
                                <div id="view-container">
                                    <!-- Data will be loaded here -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script>
        $(document).ready(function() {
            // Set default academic year
            let academicYear = $('#academicYear').val();

            // Load default view
            loadView('stock_add', academicYear);

            // Handle navigation clicks
            $('#navbarNav .nav-link').click(function(e) {
                e.preventDefault();

                // Update active tab styling
                $('.nav-link').removeClass('active');
                $(this).addClass('active');

                // Load the selected view
                academicYear = $('#academicYear').val();
                loadView($(this).data('view'), academicYear);
            });

            // Handle academic year filter change
            $('#academicYear').change(function() {
                academicYear = $(this).val();

                // Get the currently active tab
                const activeTab = $('.nav-link.active').data('view');
                if (activeTab) {
                    loadView(activeTab, academicYear);
                }
            });

            function loadView(view, academicYear) {
                // Show loading spinner
                $('#loadingSpinner').show();
                $('#view-container').empty();

                $.ajax({
                    url: 'load_data.php',
                    type: 'POST',
                    data: {
                        view: view,
                        academic_year: academicYear
                    },
                    success: function(response) {
                        $('#view-container').html(response);
                        // Initialize DataTables after the data is loaded
                        $('#table-id').DataTable({
                            "order": [] // Disable initial sorting
                        });
                    },
                    error: function() {
                        $('#view-container').html('<div class="alert alert-danger">Error loading data. Please try again.</div>');
                    },
                    complete: function() {
                        // Hide loading spinner
                        $('#loadingSpinner').hide();
                    }
                });
            }
        });
    </script>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>
</body>

</html>