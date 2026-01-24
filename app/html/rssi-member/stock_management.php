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
?>

<!DOCTYPE html>
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

    <title>Stock Management</title>

    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">

    <style>
        .icon {
            font-size: 60px;
            margin-bottom: 1rem;
            transition: transform 0.3s ease;
        }

        .card-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }

        .card-text {
            color: #666;
            font-size: 0.9rem;
            flex-grow: 1;
        }

        #category-section .card {
            height: 100%;
            transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            background: #fff;
        }

        #category-section .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            /* border-color: #0d6efd; */
        }

        #category-section .card:hover .icon {
            transform: scale(1.1);
        }

        .category-header {
            font-size: 1.25rem;
            font-weight: 600;
            color: #444;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e0e0e0;
        }

        .category-section {
            margin-bottom: 2.5rem;
        }

        .icon-primary {
            color: #0d6efd;
        }

        .icon-success {
            color: #198754;
        }

        .icon-warning {
            color: #ffc107;
        }

        .icon-danger {
            color: #dc3545;
        }

        .icon-info {
            color: #0dcaf0;
        }

        .icon-secondary {
            color: #6c757d;
        }

        .icon-purple {
            color: #6f42c1;
        }

        .icon-pink {
            color: #d63384;
        }

        .icon-teal {
            color: #20c997;
        }

        .icon-orange {
            color: #fd7e14;
        }

        .icon-indigo {
            color: #6610f2;
        }

        .icon-cyan {
            color: #17a2b8;
        }
    </style>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1><?php echo getPageTitle(); ?></h1>
            <?php echo generateDynamicBreadcrumb(); ?>
        </div><!-- End Page Title -->

        <section class="section dashboard">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <br>
                            <div class="container mt-3" id="category-section">

                                <!-- Stock Operations Section -->
                                <div class="category-section">
                                    <h4 class="category-header">Stock Operations</h4>
                                    <div class="row g-4">
                                        <div class="col-md-4">
                                            <a href="stock_add.php" class="card-link">
                                                <div class="card text-center position-relative">
                                                    <div class="card-body">
                                                        <i class="bi bi-arrow-down-circle icon icon-success"></i>
                                                        <h5 class="card-title mt-2">Stock In</h5>
                                                        <p class="card-text">Record incoming stock items with supplier details and quantities.</p>
                                                    </div>
                                                </div>
                                            </a>
                                        </div>

                                        <div class="col-md-4">
                                            <a href="stock_out.php" class="card-link">
                                                <div class="card text-center position-relative">
                                                    <div class="card-body">
                                                        <i class="bi bi-arrow-up-circle icon icon-danger"></i>
                                                        <h5 class="card-title mt-2">Stock Out</h5>
                                                        <p class="card-text">Record outgoing stock items for distribution, sales, or usage.</p>
                                                    </div>
                                                </div>
                                            </a>
                                        </div>

                                        <div class="col-md-4">
                                            <a href="create_ticket.php" class="card-link">
                                                <div class="card text-center position-relative">
                                                    <div class="card-body">
                                                        <i class="bi bi-arrow-left-right icon icon-warning"></i>
                                                        <h5 class="card-title mt-2">Stock Adjustment</h5>
                                                        <p class="card-text">Adjust stock quantities for corrections, damages, or discrepancies.</p>
                                                    </div>
                                                </div>
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Items Management Section -->
                                <div class="category-section">
                                    <h4 class="category-header">Items Management</h4>
                                    <div class="row g-4">
                                        <div class="col-md-4">
                                            <a href="items_management.php" class="card-link">
                                                <div class="card text-center position-relative">
                                                    <div class="card-body">
                                                        <i class="bi bi-box-seam icon icon-primary"></i>
                                                        <h5 class="card-title mt-2">Items Management</h5>
                                                        <p class="card-text">Add, edit, and manage inventory items with categories, access scope, and pricing.</p>
                                                    </div>
                                                </div>
                                            </a>
                                        </div>

                                        <div class="col-md-4">
                                            <a href="item_prices_management.php" class="card-link">
                                                <div class="card text-center position-relative">
                                                    <div class="card-body">
                                                        <i class="bi bi-tag icon icon-success"></i>
                                                        <h5 class="card-title mt-2">Item Prices Management</h5>
                                                        <p class="card-text">Set and manage prices, discounts, and effective dates for items.</p>
                                                    </div>
                                                </div>
                                            </a>
                                        </div>

                                        <div class="col-md-4">
                                            <a href="units_management.php" class="card-link">
                                                <div class="card text-center position-relative">
                                                    <div class="card-body">
                                                        <i class="bi bi-rulers icon icon-warning"></i>
                                                        <h5 class="card-title mt-2">Units Management</h5>
                                                        <p class="card-text">Manage measurement units (kg, liter, piece) used for inventory items.</p>
                                                    </div>
                                                </div>
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Reports & Analytics Section -->
                                <div class="category-section">
                                    <h4 class="category-header">Reports & Analytics</h4>
                                    <div class="row g-4">
                                        <!-- <div class="col-md-4">
                                            <a href="#" class="card-link">
                                                <div class="card text-center position-relative">
                                                    <div class="card-body">
                                                        <i class="bi bi-bar-chart icon icon-info"></i>
                                                        <h5 class="card-title mt-2">Inventory Insights</h5>
                                                        <p class="card-text">Analytics dashboard with stock levels, trends, and performance metrics.</p>
                                                    </div>
                                                </div>
                                            </a>
                                        </div> -->

                                        <div class="col-md-4">
                                            <a href="stock_in.php" class="card-link">
                                                <div class="card text-center position-relative">
                                                    <div class="card-body">
                                                        <i class="bi bi-file-earmark-text icon icon-secondary"></i>
                                                        <h5 class="card-title mt-2">Stock Report</h5>
                                                        <p class="card-text">Generate detailed stock reports with filtering and export options.</p>
                                                    </div>
                                                </div>
                                            </a>
                                        </div>

                                        <div class="col-md-4">
                                            <a href="inventory-insights.php" class="card-link">
                                                <div class="card text-center position-relative">
                                                    <div class="card-body">
                                                        <i class="bi bi-clock-history icon icon-purple"></i>
                                                        <h5 class="card-title mt-2">Transaction History</h5>
                                                        <p class="card-text">View complete history of all stock movements and transactions.</p>
                                                    </div>
                                                </div>
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Advanced Management Section -->
                                <div class="category-section">
                                    <h4 class="category-header">Advanced Management</h4>
                                    <div class="row g-4">
                                        <div class="col-md-4">
                                            <a href="group_management.php" class="card-link">
                                                <div class="card text-center position-relative">
                                                    <div class="card-body">
                                                        <i class="bi bi-collection icon icon-teal"></i>
                                                        <h5 class="card-title mt-2">Item Group Management</h5>
                                                        <p class="card-text">Create and manage item groups for bulk operations and categorization.</p>
                                                    </div>
                                                </div>
                                            </a>
                                        </div>

                                        <!-- <div class="col-md-4">
                                            <a href="supplier_management.php" class="card-link">
                                                <div class="card text-center position-relative">
                                                    <div class="card-body">
                                                        <i class="bi bi-truck icon icon-orange"></i>
                                                        <h5 class="card-title mt-2">Supplier Management</h5>
                                                        <p class="card-text">Manage supplier information, contracts, and performance tracking.</p>
                                                    </div>
                                                </div>
                                            </a>
                                        </div>

                                        <div class="col-md-4">
                                            <a href="category_management.php" class="card-link">
                                                <div class="card text-center position-relative">
                                                    <div class="card-body">
                                                        <i class="bi bi-tags icon icon-indigo"></i>
                                                        <h5 class="card-title mt-2">Category Management</h5>
                                                        <p class="card-text">Manage item categories and subcategories for better organization.</p>
                                                    </div>
                                                </div>
                                            </a>
                                        </div>

                                        <div class="col-md-4">
                                            <a href="location_management.php" class="card-link">
                                                <div class="card text-center position-relative">
                                                    <div class="card-body">
                                                        <i class="bi bi-geo-alt icon icon-cyan"></i>
                                                        <h5 class="card-title mt-2">Location Management</h5>
                                                        <p class="card-text">Manage storage locations, racks, and bins for inventory items.</p>
                                                    </div>
                                                </div>
                                            </a>
                                        </div>

                                        <div class="col-md-4">
                                            <a href="batch_management.php" class="card-link">
                                                <div class="card text-center position-relative">
                                                    <div class="card-body">
                                                        <i class="bi bi-boxes icon icon-pink"></i>
                                                        <h5 class="card-title mt-2">Batch Management</h5>
                                                        <p class="card-text">Track items by batch numbers, expiry dates, and manufacturing details.</p>
                                                    </div>
                                                </div>
                                            </a>
                                        </div>

                                        <div class="col-md-4">
                                            <a href="user_access_management.php" class="card-link">
                                                <div class="card text-center position-relative">
                                                    <div class="card-body">
                                                        <i class="bi bi-shield-lock icon icon-danger"></i>
                                                        <h5 class="card-title mt-2">User Access Control</h5>
                                                        <p class="card-text">Manage user permissions and access levels for inventory operations.</p>
                                                    </div>
                                                </div>
                                            </a>
                                        </div> -->
                                    </div>
                                </div>

                                <!-- Quick Actions Section -->
                                <!-- <div class="category-section">
                                    <h4 class="category-header">Quick Actions</h4>
                                    <div class="row g-4">
                                        <div class="col-md-3">
                                            <a href="items_management.php?add=" class="card-link">
                                                <div class="card text-center position-relative">
                                                    <div class="card-body">
                                                        <i class="bi bi-plus-circle icon icon-success"></i>
                                                        <h5 class="card-title mt-2">Quick Add Item</h5>
                                                        <p class="card-text">Quickly add a new item to inventory</p>
                                                    </div>
                                                </div>
                                            </a>
                                        </div>

                                        <div class="col-md-3">
                                            <a href="stock_in.php?quick=" class="card-link">
                                                <div class="card text-center position-relative">
                                                    <div class="card-body">
                                                        <i class="bi bi-download icon icon-primary"></i>
                                                        <h5 class="card-title mt-2">Quick Stock In</h5>
                                                        <p class="card-text">Quick stock entry for urgent items</p>
                                                    </div>
                                                </div>
                                            </a>
                                        </div>

                                        <div class="col-md-3">
                                            <a href="low_stock_alert.php" class="card-link">
                                                <div class="card text-center position-relative">
                                                    <div class="card-body">
                                                        <i class="bi bi-exclamation-triangle icon icon-warning"></i>
                                                        <h5 class="card-title mt-2">Low Stock Alerts</h5>
                                                        <p class="card-text">Items below reorder level</p>
                                                    </div>
                                                </div>
                                            </a>
                                        </div>

                                        <div class="col-md-3">
                                            <a href="expiry_alert.php" class="card-link">
                                                <div class="card text-center position-relative">
                                                    <div class="card-body">
                                                        <i class="bi bi-exclamation-circle icon icon-danger"></i>
                                                        <h5 class="card-title mt-2">Expiry Alerts</h5>
                                                        <p class="card-text">Items nearing expiry date</p>
                                                    </div>
                                                </div>
                                            </a>
                                        </div>
                                    </div>
                                </div> -->

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets_new/js/main.js"></script>

</body>

</html>