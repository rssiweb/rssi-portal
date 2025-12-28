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

// Handle editing and marking as inactive
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['editCategory'])) {
        // Handle the edit functionality
        $categoryId = $_POST['categoryId'];
        $categoryName = !empty(trim($_POST['categoryName'])) ? trim($_POST['categoryName']) : null;
        $categoryDescription = !empty(trim($_POST['categoryDescription'])) ? trim($_POST['categoryDescription']) : null;

        $updateQuery = "UPDATE test_categories SET name = $1, category_description = $2 WHERE id = $3";
        if ($stmt = pg_prepare($con, "update_category", $updateQuery)) {
            $result = pg_execute($con, "update_category", [$categoryName, $categoryDescription, $categoryId]);
            $message = $result ? 'Category updated successfully!' : 'Error updating category!';
            echo "<script>alert('$message');</script>";
        }
    } elseif (isset($_POST['toggleStatus'])) {
        // Ensure that 'isActive' is either 'true' or 'false'
        $categoryId = $_POST['categoryId'];
        $isActive = $_POST['isActive']; // This will already be 'true' or 'false' (string)

        // Update the status in the database
        $updateQuery = "UPDATE test_categories SET is_active = $1 WHERE id = $2";
        if ($stmt = pg_prepare($con, "toggle_status", $updateQuery)) {
            $result = pg_execute($con, "toggle_status", [$isActive, $categoryId]);
            $message = $result ? 'Category status updated successfully!' : 'Error updating category status!';
            echo "<script>alert('$message')
            if (window.history.replaceState) {
                        window.history.replaceState(null, null, window.location.href);
                    }
                    window.location.reload();</script>";
        }
    }
}

// Fetch the categories from the database
$query = "SELECT * FROM test_categories order by id desc";
$result = pg_query($con, $query);
?>
<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['addCategory'])) {
        // Handle the add functionality
        $newCategoryName = !empty(trim($_POST['newCategoryName'])) ? trim($_POST['newCategoryName']) : null;
        $newCategoryDescription = !empty(trim($_POST['newCategoryDescription'])) ? trim($_POST['newCategoryDescription']) : null;

        $insertQuery = "INSERT INTO test_categories (name, category_description, is_active) VALUES ($1, $2, $3)";
        if ($stmt = pg_prepare($con, "insert_category", $insertQuery)) {
            $result = pg_execute($con, "insert_category", [$newCategoryName, $newCategoryDescription, 'true']); // Set default as active
            $message = $result ? 'Category added successfully!' : 'Error adding category!';
            echo "<script>alert('$message'); if (window.history.replaceState) {
                        window.history.replaceState(null, null, window.location.href);
                    }
                    window.location.reload();</script>";
        }
    }
}
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Category</title>
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
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
            <h1>Manage Category</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item">iExplore Edge</li>
                    <li class="breadcrumb-item active">Manage Category</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section dashboard">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <br>
                            <!-- Add New Category Button -->
                            <div class="text-end mb-3">
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">Add New Category</button>
                            </div>
                            <div class="table-responsive">
                                <table class="table" id="table-id">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Category Name</th>
                                            <th>Category Description</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = pg_fetch_assoc($result)) : ?>
                                            <tr>
                                                <td><?php echo $row['id']; ?></td>
                                                <td><?php echo $row['name']; ?></td>
                                                <td><?php echo $row['category_description']; ?></td>
                                                <td>
                                                    <!-- Status Text -->
                                                    <span><?php echo $row['is_active'] === 't' ? 'Active' : 'Inactive'; ?></span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editCategoryModal<?php echo $row['id']; ?>">Edit</button>

                                                    <!-- Toggle Button -->
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="categoryId" value="<?php echo $row['id']; ?>">
                                                        <!-- Set isActive to the opposite of the current status -->
                                                        <input type="hidden" name="isActive" value="<?php echo $row['is_active'] === 't' ? 'false' : 'true'; ?>">

                                                        <!-- Change button style and text based on is_active status -->
                                                        <button type="submit" name="toggleStatus" class="btn btn-sm btn-<?php echo $row['is_active'] === 't' ? 'danger' : 'success'; ?>">
                                                            <i class="bi <?php echo $row['is_active'] === 't' ? 'bi-x-circle' : 'bi-check-circle'; ?>"></i>
                                                            <?php echo $row['is_active'] === 't' ? 'Deactivate' : 'Activate'; ?>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>

                                            <!-- Edit Category Modal -->
                                            <div class="modal fade" id="editCategoryModal<?php echo $row['id']; ?>" tabindex="-1" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="editCategoryModalLabel">Edit Category</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <form method="POST">
                                                                <input type="hidden" name="categoryId" value="<?php echo $row['id']; ?>">
                                                                <div class="mb-3">
                                                                    <label for="categoryName" class="form-label">Category Name</label>
                                                                    <input type="text" class="form-control" id="categoryName" name="categoryName" value="<?php echo $row['name']; ?>" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="categoryDescription" class="form-label">Category Description</label>
                                                                    <textarea class="form-control" id="categoryDescription" name="categoryDescription" rows="3"><?php echo $row['category_description']; ?></textarea>
                                                                </div>
                                                                <button type="submit" name="editCategory" class="btn btn-primary">Save Changes</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
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
        $(document).ready(function() {
            // Check if resultArr is empty
            <?php if (!empty($result)) : ?>
                // Initialize DataTables only if resultArr is not empty
                $('#table-id').DataTable({
                    // paging: false,
                    "order": [] // Disable initial sorting
                    // other options...
                });
            <?php endif; ?>
        });
    </script>
    <!-- Add New Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCategoryModalLabel">Add New Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="newCategoryName" class="form-label">Category Name</label>
                            <input type="text" class="form-control" id="newCategoryName" name="newCategoryName" required>
                        </div>
                        <div class="mb-3">
                            <label for="newCategoryDescription" class="form-label">Category Description</label>
                            <textarea class="form-control" id="newCategoryDescription" name="newCategoryDescription" rows="3"></textarea>
                        </div>
                        <button type="submit" name="addCategory" class="btn btn-primary">Add Category</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</body>

</html>