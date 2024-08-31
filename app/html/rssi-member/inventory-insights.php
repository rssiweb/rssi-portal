<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">
    <!-- CSS Library Files -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
    <!-- JavaScript Library Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>
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
                                <!-- <h1>Inventory Insights</h1> -->
                                <nav class="navbar navbar-expand-lg navbar-light bg-light">
                                    <div class="navbar-nav">
                                        <a class="nav-item nav-link" href="#" data-view="stock_add">Detailed Stock Add</a>
                                        <a class="nav-item nav-link" href="#" data-view="stock_distribution">Detailed Distribution Record</a>
                                        <a class="nav-item nav-link" href="#" data-view="user_distribution">User-Based Distribution Record</a>
                                    </div>
                                </nav>
                                <div id="view-container" class="mt-4">
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
            // Load default view
            loadView('stock_add');

            // Handle navigation clicks
            $('.nav-link').click(function() {
                var view = $(this).data('view');
                loadView(view);
            });

            function loadView(view) {
                $.ajax({
                    url: 'load_data.php',
                    type: 'POST',
                    data: {
                        view: view
                    },
                    success: function(response) {
                        $('#view-container').html(response);
                        // Initialize DataTables after the data is loaded
                        $('#table-id').DataTable({
                            "order": [] // Disable initial sorting
                        });
                    },
                    error: function() {
                        $('#view-container').html('<p>Error loading data.</p>');
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