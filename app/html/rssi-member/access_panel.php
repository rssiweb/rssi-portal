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

$directory_path = '/var/www/html/rssi-member/';
$page_files = array_diff(scandir($directory_path), array('.', '..'));

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $page_categories = $_POST['page_category'] ?? [];
    $profile_checks = $_POST['profile_check'] ?? [];
    $roles_data = $_POST['roles'] ?? [];
    $allSuccess = true; // Tracks overall success

    // SQL Queries
    $insertPageSQL = "INSERT INTO pages (page_name, page_category,profile_check) 
                      VALUES ($1, $2, $3) 
                      ON CONFLICT (page_name) 
                      DO UPDATE SET page_category = EXCLUDED.page_category,
                      profile_check = EXCLUDED.profile_check  
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
        $profileCheck = $profile_checks[$pageName] ?? null;

        $result = pg_query_params($con, $insertPageSQL, [$pageName, $pageCategory, $profileCheck]);
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
                window.location.href = 'access_panel.php';
              </script>";
    } else {
        echo "<script>
                alert('An error occurred while saving roles and access levels. Check logs for details.');
              </script>";
    }
    exit;
}
// Define the edit mode variable for the frontend
$isEditMode = isset($_GET['edit']) && $_GET['edit'] == 'true'; // Example condition to toggle edit mode
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Panel</title>
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">
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
            <h1>Access Panel</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Work</a></li>
                    <li class="breadcrumb-item active">Access Panel</li>
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
                                <!-- <h2>Access Panel</h2> -->
                                <form method="POST" action="#">
                                    <fieldset>
                                        <table id="rolesTable" class="table">
                                            <thead>
                                                <tr>
                                                    <th>Page Name</th>
                                                    <th>Page Category</th>
                                                    <th>Profile Check</th>
                                                    <th>Admin</th>
                                                    <th>Offline Manager</th>
                                                    <th>Advanced User</th>
                                                    <th>User</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                foreach ($page_files as $file) {
                                                    $pageName = htmlspecialchars($file);  // Ensure this is correctly sanitized
                                                    $selectSQL = "SELECT p.page_category, p.profile_check,
                                                    COALESCE(json_object_agg(r.role_name, pr.has_access) FILTER (WHERE r.role_name IS NOT NULL), '{}') AS roles 
                                                    FROM pages p 
                                                    LEFT JOIN page_roles pr ON p.id = pr.page_id 
                                                    LEFT JOIN roles r ON pr.role_id = r.id 
                                                    WHERE p.page_name = $1 
                                                    GROUP BY p.id;";
                                                    $result = pg_query_params($con, $selectSQL, [$pageName]);
                                                    $row = $result && pg_num_rows($result) > 0 ? pg_fetch_assoc($result) : null;

                                                    $pageCategory = $row['page_category'] ?? '';
                                                    $profile_check = $row['profile_check'] ?? '';
                                                    $roles = json_decode($row['roles'] ?? '{}', true);

                                                    echo '<tr>';
                                                    echo "<td>$pageName</td>";

                                                    // Show either an input or plain text for page category depending on mode
                                                    if ($isEditMode) {
                                                        echo "<td><input type='text' class='form-control page-category' name='page_category[$pageName]' value='" . htmlspecialchars($pageCategory) . "' data-page-name='$pageName'></td>";
                                                    } else {
                                                        echo "<td><span class='form-control-plaintext page-category' data-page-name='$pageName'>" . htmlspecialchars($pageCategory) . "</span></td>";
                                                    }

                                                    // Show either a checkbox or Yes/No text for page validation depending on mode
                                                    if ($isEditMode) {
                                                        $checked = ($profile_check === 't') ? 'checked' : '';
                                                        echo "<td class='text-center'>
                                                        <input type='hidden' name='profile_check[$pageName]' value='f'>
                                                        <input type='checkbox' class='form-check-input page-validation' name='profile_check[$pageName]' value='t' $checked data-page-name='$pageName'>
                                                    </td>";
                                                    } else {
                                                        $validationText = ($profile_check === 't') ? 'Yes' : 'No';
                                                        echo "<td class='text-center'><span class='form-control-plaintext page-validation' data-page-name='$pageName'>" . htmlspecialchars($validationText) . "</span></td>";
                                                    }

                                                    // Loop through roles to check if the role has access
                                                    foreach (['Admin', 'Offline Manager', 'Advanced User', 'User'] as $role) {
                                                        // Check the current role access (if it exists and is true)
                                                        $checked = isset($roles[$role]) && $roles[$role] === true ? 'checked' : '';

                                                        // Add hidden input to handle unchecked values
                                                        echo "<td>";
                                                        echo "<input type='hidden' name='roles[$pageName][$role]' value='0'>";  // Hidden input with value 0 for unchecked
                                                        echo "<input type='checkbox' class='form-check-input' name='roles[$pageName][$role]' value='1' $checked>"; // Checkbox input
                                                        echo "<span class='role-text'>" . ($checked ? 'Yes' : 'No') . "</span>"; // Text representation
                                                        echo "</td>";
                                                    }

                                                    echo '</tr>';
                                                }
                                                ?>
                                            </tbody>
                                        </table>

                                        <!-- Form Row -->
                                        <div class="row align-items-center">
                                            <!-- Edit/Save Button (left side) -->
                                            <div class="col-6 d-flex">
                                                <button type="button" id="toggleButton" class="btn btn-warning me-2" onclick="toggleEdit()">Edit</button>
                                                <!-- Save/Submit Button -->
                                                <button type="submit" id="submitButton" class="btn btn-primary" style="display:none;">Save</button>
                                            </div>
                                            <!-- View Page Access Info Link (right side) -->
                                            <div class="col-6 text-end">
                                                <a href="#" data-bs-toggle="modal" data-bs-target="#accessControlModal">
                                                    How Access Control Works?
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
        let isEditMode = <?php echo $isEditMode ? 'true' : 'false'; ?>;

        document.addEventListener('DOMContentLoaded', function() {
            const toggleButton = document.getElementById('toggleButton');
            const submitButton = document.getElementById('submitButton');
            const pageCategoryElements = document.querySelectorAll('.page-category');
            const pageValidationElements = document.querySelectorAll('.page-validation');
            const inputs = document.querySelectorAll('input[type="checkbox"], input[type="text"], .role-text');

            toggleButton.addEventListener('click', function() {
                isEditMode = !isEditMode;
                toggleFormMode();
            });

            function toggleFormMode() {
                // Toggle regular input fields
                inputs.forEach(input => {
                    if (isEditMode) {
                        if (input.classList.contains('role-text')) {
                            input.style.display = 'none';
                        } else {
                            input.removeAttribute('readonly');
                            input.removeAttribute('disabled');
                        }
                    } else {
                        if (input.classList.contains('role-text')) {
                            input.style.display = 'inline';
                        } else {
                            input.setAttribute('readonly', true);
                            input.setAttribute('disabled', true);
                        }
                    }
                });

                // Handle page category fields
                pageCategoryElements.forEach(element => {
                    const pageName = element.getAttribute('data-page-name');
                    if (isEditMode) {
                        if (element.tagName === 'SPAN') {
                            const input = document.createElement('input');
                            input.type = 'text';
                            input.classList.add('form-control');
                            input.value = element.textContent.trim();
                            input.name = `page_category[${pageName}]`;
                            input.setAttribute('data-page-name', pageName);
                            element.replaceWith(input);
                        }
                    } else {
                        if (element.tagName === 'INPUT') {
                            const span = document.createElement('span');
                            span.classList.add('form-control-plaintext');
                            span.textContent = element.value;
                            span.setAttribute('data-page-name', pageName);
                            element.replaceWith(span);
                        }
                    }
                });

                // Handle page validation fields (simple checkbox)
                pageValidationElements.forEach(element => {
                    const pageName = element.getAttribute('data-page-name');
                    if (isEditMode) {
                        if (element.tagName === 'SPAN') {
                            const isChecked = element.textContent.trim() === 'Yes';
                            const checkbox = document.createElement('input');
                            checkbox.type = 'checkbox';
                            checkbox.classList.add('form-check-input', 'page-validation');
                            checkbox.name = `profile_check[${pageName}]`;
                            checkbox.value = '1';
                            if (isChecked) checkbox.checked = true;
                            checkbox.setAttribute('data-page-name', pageName);

                            // Add hidden input to ensure value is submitted when unchecked
                            const hiddenInput = document.createElement('input');
                            hiddenInput.type = 'hidden';
                            hiddenInput.name = `profile_check[${pageName}]`;
                            hiddenInput.value = '0';

                            const container = document.createElement('div');
                            container.appendChild(hiddenInput);
                            container.appendChild(checkbox);
                            element.replaceWith(container);
                        }
                    } else {
                        if (element.tagName === 'INPUT' && element.type === 'checkbox') {
                            const span = document.createElement('span');
                            span.classList.add('form-control-plaintext');
                            span.textContent = element.checked ? 'Yes' : 'No';
                            span.setAttribute('data-page-name', pageName);
                            element.replaceWith(span);
                        } else if (element.parentNode && element.parentNode.querySelector('input[type="checkbox"]')) {
                            const checkbox = element.parentNode.querySelector('input[type="checkbox"]');
                            const span = document.createElement('span');
                            span.classList.add('form-control-plaintext');
                            span.textContent = checkbox.checked ? 'Yes' : 'No';
                            span.setAttribute('data-page-name', pageName);
                            element.parentNode.replaceWith(span);
                        }
                    }
                });

                // Update button visibility
                toggleButton.style.display = isEditMode ? 'none' : 'inline-block';
                submitButton.style.display = isEditMode ? 'inline-block' : 'none';
            }

            // Initial toggle on page load
            toggleFormMode();
        });
    </script>
</body>

</html>