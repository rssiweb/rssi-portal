<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();

$directory_path = '/var/www/html/rssi-member/';
$page_files = array_diff(scandir($directory_path), array('.', '..'));

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $page_categories = $_POST['page_category'] ?? [];
    $roles_data = $_POST['roles'] ?? [];
    $allSuccess = true; // Tracks overall success

    // SQL Queries
    $insertPageSQL = "INSERT INTO pages (page_name, page_category) 
                      VALUES ($1, $2) 
                      ON CONFLICT (page_name) 
                      DO UPDATE SET page_category = EXCLUDED.page_category 
                      RETURNING id;";
    $insertRoleSQL = "INSERT INTO roles (role_name) 
                      VALUES ($1) 
                      ON CONFLICT (role_name) 
                      DO NOTHING RETURNING id;";
    $insertPageRoleSQL = "INSERT INTO page_roles (page_id, role_id, has_access) 
                          VALUES ($1, $2, $3)
                          ON CONFLICT (page_id, role_id) 
                          DO UPDATE SET has_access = EXCLUDED.has_access;";

    $roles = ['Admin', 'Offline Manager', 'Advanced User', 'User'];
    $roleIds = [];

    // Ensure all roles are in DB
    foreach ($roles as $roleName) {
        $result = pg_query_params($con, $insertRoleSQL, [$roleName]);
        if ($result && pg_num_rows($result) > 0) {
            $roleIds[$roleName] = pg_fetch_result($result, 0, 'id');
        } else {
            $selectRoleSQL = "SELECT id FROM roles WHERE role_name = $1;";
            $result = pg_query_params($con, $selectRoleSQL, [$roleName]);
            if ($result && pg_num_rows($result) > 0) {
                $roleIds[$roleName] = pg_fetch_result($result, 0, 'id');
            }
        }
    }

    // Process pages and roles
    foreach ($roles_data as $pageName => $roleAccess) {
        $pageCategory = $page_categories[$pageName] ?? null;

        $result = pg_query_params($con, $insertPageSQL, [$pageName, $pageCategory]);
        $pageId = $result ? pg_fetch_result($result, 0, 'id') : null;

        if ($pageId) {
            foreach ($roles as $roleName) {
                // Check if the role is marked as true (1) or false (0)
                $hasAccess = isset($roleAccess[$roleName]) && $roleAccess[$roleName] !== '0' ? 'true' : 'false';

                // Insert or update the role access for the page
                $updateResult = pg_query_params($con, $insertPageRoleSQL, [$pageId, $roleIds[$roleName], $hasAccess]);

                if (!$updateResult) {
                    $allSuccess = false; // Mark as failed if any query fails
                    error_log("Error updating role access for page: $pageName, role: $roleName");
                }
            }
        } else {
            $allSuccess = false; // Page insertion failed
            error_log("Error inserting/updating page: $pageName");
        }
    }

    // Display appropriate message
    if ($allSuccess) {
        echo "<script>
                alert('Roles and access levels have been successfully saved!');
                window.location.href = 'save_roles.php';
              </script>";
    } else {
        echo "<script>
                alert('An error occurred while saving roles and access levels. Check logs for details.');
              </script>";
    }
    exit;
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Roles Management</title>
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
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
            <h1>HR Interview</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">People Plus</a></li>
                    <li class="breadcrumb-item"><a href="interview_central.php">Interview Central</a></li>
                    <li class="breadcrumb-item active">HR Interview</li>
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
                            <div class="container mt-5">
                                <h2>Portal Roles Management</h2>

                                <form method="POST" action="#">
                                    <fieldset>
                                        <table id="rolesTable" class="table">
                                            <thead>
                                                <tr>
                                                    <th>Page Name</th>
                                                    <th>Page Category</th>
                                                    <th>Admin</th>
                                                    <th>Offline Manager</th>
                                                    <th>Advanced User</th>
                                                    <th>User</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                foreach ($page_files as $file) {
                                                    $pageName = htmlspecialchars($file);
                                                    $selectSQL = "SELECT p.page_category, 
                                COALESCE(json_object_agg(r.role_name, pr.has_access) FILTER (WHERE r.role_name IS NOT NULL), '{}') AS roles 
                                FROM pages p 
                                LEFT JOIN page_roles pr ON p.id = pr.page_id 
                                LEFT JOIN roles r ON pr.role_id = r.id 
                                WHERE p.page_name = $1 
                                GROUP BY p.id;";
                                                    $result = pg_query_params($con, $selectSQL, [$pageName]);
                                                    $row = $result && pg_num_rows($result) > 0 ? pg_fetch_assoc($result) : null;

                                                    $pageCategory = $row['page_category'] ?? '';
                                                    $roles = json_decode($row['roles'] ?? '{}', true);

                                                    echo '<tr>';
                                                    echo "<td>$pageName</td>";
                                                    echo "<td><input type='text' class='form-control' name='page_category[$pageName]' value='" . htmlspecialchars($pageCategory) . "' readonly></td>";

                                                    // Loop through roles to check if the role has access
                                                    foreach (['Admin', 'Offline Manager', 'Advanced User', 'User'] as $role) {
                                                        // Check the current role access (if it exists and is true)
                                                        $checked = isset($roles[$role]) && $roles[$role] === true ? 'checked' : '';

                                                        // Add hidden input to handle unchecked values
                                                        echo "<td>";
                                                        echo "<input type='hidden' name='roles[$pageName][$role]' value='0'>";  // Hidden input with value 0 for unchecked
                                                        echo "<input type='checkbox' name='roles[$pageName][$role]' value='1' $checked>"; // Checkbox input
                                                        echo "</td>";
                                                    }

                                                    echo '</tr>';
                                                }
                                                ?>
                                            </tbody>
                                        </table>

                                        <!-- Form Row -->
                                        <div class="row">
                                            <!-- Edit/Save Button (left side) -->
                                            <div class="col-6">
                                                <button type="button" id="toggleButton" class="btn btn-warning mt-3" onclick="toggleEdit()">Edit</button>
                                                <!-- Save/Submit Button -->
                                                <button type="submit" id="submitButton" class="btn btn-primary mt-3" style="display:none;">Save</button>
                                            </div>
                                            <!-- View Page Access Info Link (right side) -->
                                            <div class="col-6 text-end">
                                                <a href="#" data-bs-toggle="modal" data-bs-target="#accessControlModal">
                                                    View Page Access Info
                                                </a>
                                            </div>
                                        </div>
                                    </fieldset>
                                </form>

                            </div>
                        </div>
                    </div>
                </div><!-- End Reports -->
            </div>
        </section>

    </main><!-- End #main -->
    <!-- Modal -->
    <div class="modal fade" id="accessControlModal" tabindex="-1" aria-labelledby="accessControlModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="accessControlModalLabel">How the Page Access Control System Works</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h5>1. Default Access for Pages</h5>
                    <p>By default, if a page is not assigned to any specific role, it is accessible by everyone. For example, the page <strong>"allocation.php"</strong> might not be assigned to any role, so any user (Admin, User, Guest, etc.) can access it.</p>

                    <h5>2. Assigning Pages to Roles</h5>
                    <p>When you assign a page to a specific role (like <strong>Advanced User</strong>), that page becomes restricted for everyone except the role(s) it's assigned to. For example, if you assign the <strong>"allocation.php"</strong> page to <strong>Advanced User</strong>, only those with the <strong>Advanced User</strong> role can access it.</p>

                    <h5>3. What Happens When a Page Is Assigned to One Role?</h5>
                    <p>If a page is assigned to a role (like <strong>Advanced User</strong>), and no other role has access to it, only <strong>Advanced Users</strong> can access that page. Anyone else (like <strong>Admin</strong> or <strong>User</strong>) who tries to access it will see an "Access Denied" message unless they are given access by assigning the page to their role.</p>

                    <h5>4. How Admins Control Access</h5>
                    <p>Admins can assign or remove pages from any role. If an Admin assigns a page (e.g., <strong>"allocation.php"</strong>) to the <strong>Advanced User</strong> role, only users with the <strong>Advanced User</strong> role can access it. If the Admin then wants other roles (like <strong>User</strong> or <strong>Admin</strong>) to access the same page, they must assign it to those roles as well. Until a page is assigned to a specific role, that page will be accessible to everyone.</p>

                    <h5>5. How It Works for a New Page</h5>
                    <p>If a page is created but not assigned to any role, everyone can access it by default. Once an Admin decides to assign that page to a specific role, it will become restricted for everyone except the assigned role.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i
            class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4"
        crossorigin="anonymous"></script>

    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>
    <script>
        $(document).ready(function() {
            $('#rolesTable').DataTable();
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleButton = document.getElementById('toggleButton');
            const submitButton = document.getElementById('submitButton');
            const form = document.querySelector('form');
            let isEditMode = false; // Tracks if we are in edit mode

            // Function to toggle between editable and non-editable modes
            toggleButton.addEventListener('click', function() {
                const inputs = document.querySelectorAll('input[type="checkbox"], input[type="text"]');

                isEditMode = !isEditMode; // Toggle edit mode

                inputs.forEach(input => {
                    if (isEditMode) {
                        input.removeAttribute('readonly');
                        input.removeAttribute('disabled');
                    } else {
                        input.setAttribute('readonly', true);
                        input.setAttribute('disabled', true);
                    }
                });

                // Update button visibility and text
                toggleButton.style.display = isEditMode ? 'none' : 'inline-block';
                submitButton.style.display = isEditMode ? 'inline-block' : 'none';
            });

            // Disable checkboxes and text fields on page load in non-editable mode
            const inputs = document.querySelectorAll('input[type="checkbox"], input[type="text"]');
            inputs.forEach(input => {
                if (!isEditMode) {
                    input.setAttribute('readonly', true);
                    input.setAttribute('disabled', true);
                }
            });

            // Handle form submission
            form.addEventListener('submit', function(event) {
                // Ensure that unchecked checkboxes are sent to the server
                document.querySelectorAll('.page').forEach(page => {
                    const checkboxes = page.querySelectorAll('input[type="checkbox"]');
                    let allUnchecked = true; // To track if all checkboxes are unchecked

                    checkboxes.forEach(checkbox => {
                        if (checkbox.checked) {
                            allUnchecked = false; // At least one checkbox is checked
                        }
                    });

                    // If all checkboxes are unchecked, add hidden inputs with value 'false'
                    if (allUnchecked) {
                        checkboxes.forEach(checkbox => {
                            const hiddenInput = document.createElement('input');
                            hiddenInput.type = 'hidden';
                            hiddenInput.name = checkbox.name; // Use the checkbox's name
                            hiddenInput.value = 'false'; // Mark as unchecked
                            page.appendChild(hiddenInput); // Append to the form
                        });
                    }
                });
            });
        });
    </script>


</body>

</html>