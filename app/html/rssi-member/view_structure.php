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

if (!isset($_GET['id'])) {
    header("Location: salary_structure.php");
    exit;
}

$structure_id = $_GET['id'];

// Fetch structure header
$query = "SELECT ss.*, m.fullname 
          FROM salary_structures ss
          JOIN rssimyaccount_members m ON ss.associate_number = m.associatenumber
          WHERE ss.id = $1";
$result = pg_query_params($con, $query, [$structure_id]);
$structure = pg_fetch_assoc($result);

if (!$structure) {
    $_SESSION['error_message'] = "Salary structure not found";
    header("Location: salary_structure.php");
    exit;
}

// Fetch components
$query = "SELECT * FROM salary_components 
          WHERE structure_id = $1
          ORDER BY display_order, id";
$result = pg_query_params($con, $query, [$structure_id]);
$components = pg_fetch_all($result) ?: [];

// Group components by category
$grouped_components = [];
foreach ($components as $component) {
    $grouped_components[$component['category']][] = $component;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Salary Structure</title>
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">
    <style>
        .category-header {
            background-color: #f8f9fa;
            font-weight: bold;
        }

        .component-row {
            border-bottom: 1px solid #dee2e6;
            padding: 10px 0;
        }

        .total-row {
            font-weight: bold;
            background-color: #f1f1f1;
        }
    </style>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>View Salary Structure</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Payroll</a></li>
                    <li class="breadcrumb-item"><a href="#">Salary Structure Management</a></li>
                    <li class="breadcrumb-item active">View Salary Structure</li>
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
                            <div class="container-fluid py-4">
                                <div class="card mb-4">
                                    <div class="card-header bg-primary text-white">
                                        <h2 class="h5 mb-0">Structure Information</h2>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <p><strong>Associate:</strong> <?= htmlspecialchars($structure['fullname']) ?></p>
                                            </div>
                                            <div class="col-md-4">
                                                <p><strong>Associate Number:</strong> <?= htmlspecialchars($structure['associate_number']) ?></p>
                                            </div>
                                            <div class="col-md-4">
                                                <p><strong>CTC Amount:</strong> ₹ <?= number_format($structure['ctc_amount'], 2) ?></p>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <p><strong>Effective From:</strong> <?= date('d-M-Y', strtotime($structure['effective_from'])) ?></p>
                                            </div>
                                            <div class="col-md-4">
                                                <p><strong>Effective Till:</strong> <?= $structure['effective_till'] ? date('d-M-Y', strtotime($structure['effective_till'])) : 'Present' ?></p>
                                            </div>
                                            <div class="col-md-4">
                                                <?php
                                                $current_date = date('Y-m-d');
                                                $is_active = ($structure['effective_from'] <= $current_date) &&
                                                    ($structure['effective_till'] === null || $structure['effective_till'] >= $current_date);
                                                ?>
                                                <p><strong>Status:</strong>
                                                    <span class="badge bg-<?= $is_active ? 'success' : 'secondary' ?>">
                                                        <?= $is_active ? 'Active' : 'Inactive' ?>
                                                    </span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card">
                                    <div class="card-header bg-secondary text-white">
                                        <h2 class="h5 mb-0">Salary Components</h2>
                                    </div>
                                    <div class="card-body">
                                        <?php foreach ($grouped_components as $category => $category_components): ?>
                                            <div class="mb-4">
                                                <div class="category-header p-2 mb-2"><?= htmlspecialchars($category) ?></div>
                                                <div class="table-responsive">
                                                    <table class="table table-bordered">
                                                        <thead>
                                                            <tr>
                                                                <th width="50%">Component Name</th>
                                                                <th width="25%">Monthly (₹)</th>
                                                                <th width="25%">Annual (₹)</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($category_components as $component): ?>
                                                                <tr class="component-row">
                                                                    <td><?= htmlspecialchars($component['component_name']) ?></td>
                                                                    <td><?= $component['monthly_amount'] ? number_format($component['monthly_amount'], 2) : '-' ?></td>
                                                                    <td><?= number_format($component['annual_amount'], 2) ?></td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>

                                        <div class="total-row p-3 text-end">
                                            <h4 class="mb-0">Total Cost to Company (CTC): ₹ <?= number_format($structure['ctc_amount'], 2) ?></h4>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                    <a href="salary_structure.php?associate_number=<?= $structure['associate_number'] ?>" class="btn btn-primary">
                                        <i class="bi bi-arrow-left me-1"></i> Back to List
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div><!-- End Reports -->
            </div>
        </section>

    </main><!-- End #main -->

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>
</body>

</html>