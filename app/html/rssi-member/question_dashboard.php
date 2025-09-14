<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

// Ensure the user is logged in
if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}
validation();

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    $action = $_POST['bulk_action'];
    $selectedIds = $_POST['selected_ids'] ?? [];

    if (!empty($selectedIds)) {
        // Convert comma-separated string to array if needed
        $idsArray = is_array($selectedIds) ? $selectedIds : explode(',', $selectedIds);
        $ids = implode(",", array_map('intval', $idsArray));

        if ($action === 'inactive') {
            $updateQuery = "UPDATE test_questions SET is_active = FALSE WHERE id IN ($ids)";
        } elseif ($action === 'active') {
            $updateQuery = "UPDATE test_questions SET is_active = TRUE WHERE id IN ($ids)";
        }

        $updateResult = pg_query($con, $updateQuery);

        if ($updateResult) {
            $actionText = $action === 'inactive' ? 'inactive' : 'active';
            echo "<script>
                    alert('Questions marked as $actionText successfully!');
                    if (window.history.replaceState) {
                        window.history.replaceState(null, null, window.location.href);
                    }
                    window.location.reload();
                  </script>";
        } else {
            echo "<script>
                    alert('Error updating questions.');
                  </script>";
        }
    } else {
        echo "<script>
                alert('Please select at least one question.');
              </script>";
    }
}

// Fetch categories for the filter dropdown
$categoryQuery = "SELECT id, name FROM test_categories WHERE is_active=true ORDER BY name";
$categoryResult = pg_query($con, $categoryQuery);
$categories = pg_fetch_all($categoryResult);

// Handle the form submission when editing a question
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && !isset($_POST['bulk_action'])) {
    $questionId = $_POST['id'];
    $questionText = $_POST['question_text'];
    $categoryId = $_POST['category'];
    $correctOption = $_POST['correct_option'];
    $options = $_POST['options']; // Options array
    $modifiedAt = date('Y-m-d H:i:s');

    // Update the question text
    $updateQuery = "
        UPDATE test_questions
        SET question_text = $1, category_id = $2, correct_option = $3, created_at=$5, created_by=$6
        WHERE id = $4
    ";
    $result = pg_query_params($con, $updateQuery, array($questionText, $categoryId, $correctOption, $questionId, $modifiedAt, $associatenumber));

    if ($result) {
        // Update the options
        foreach ($options as $key => $optionText) {
            $updateOptionQuery = "
                UPDATE test_options
                SET option_text = $1
                WHERE question_id = $2 AND option_key = $3
            ";
            pg_query_params($con, $updateOptionQuery, array($optionText, $questionId, $key));
        }

        echo "<script>
                alert('Question and options updated successfully!');
                if (window.history.replaceState) {
                        // Update the URL without causing a page reload or resubmission
                        window.history.replaceState(null, null, window.location.href);
                    }
                    window.location.reload(); // Trigger a page reload to reflect changes
              </script>";
    } else {
        echo "<script>
                alert('Error updating question.');
              </script>";
    }
}

// Handle the mark inactive request for a question
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['inactive_id']) && !isset($_POST['bulk_action'])) {
    $inactiveId = $_POST['inactive_id'];
    $updateQuery = "UPDATE test_questions SET is_active = FALSE WHERE id = $1";
    $updateResult = pg_query_params($con, $updateQuery, array($inactiveId));

    if ($updateResult) {
        echo "<script>
                alert('Question marked as inactive successfully!');
                if (window.history.replaceState) {
                    window.history.replaceState(null, null, window.location.href);
                }
                window.location.reload();
              </script>";
    } else {
        echo "<script>
                alert('Error marking question as inactive.');
              </script>";
    }
}
?>
<?php
// Get current date
$currentDate = date('Y-m-d');

// Get user role and ID (assumed to be already set earlier)
$role = $role;
$associatenumber = $associatenumber;

// Determine if ignore_date checkbox is set (for admins only)
$ignoreDate = isset($_GET['ignore_date']) && $_GET['ignore_date'] == '1';

// Default filters
$dateFromFilter = $currentDate;
$dateToFilter = $currentDate;

// For admins, use provided filters unless ignore_date is checked
if ($role === 'Admin') {
    if (!$ignoreDate) {
        $dateFromFilter = $_GET['date_from'] ?? $currentDate;
        $dateToFilter = $_GET['date_to'] ?? $currentDate;
    }
} else {
    // For non-admins, force today's date and restrict by creator
    $dateFromFilter = $currentDate;
    $dateToFilter = $currentDate;
}

// Capture category filter
$categoryFilter = $_GET['category'] ?? '';

// Capture question ID filter
$questionIdFilter = $_GET['question_ids'] ?? '';

// Capture status filter
$statusFilter = $_GET['status'] ?? '';

// Build WHERE clause
$whereClauses = [];

// Category filter applies to all
if ($categoryFilter) {
    $whereClauses[] = "q.category_id = '$categoryFilter'";
}

// Question ID filter
if ($questionIdFilter) {
    $ids = explode(',', $questionIdFilter);
    $sanitizedIds = array_map('intval', $ids);
    $idsString = implode(',', $sanitizedIds);
    $whereClauses[] = "q.id IN ($idsString)";
}

// Status filter (only if valid value is selected)
if ($statusFilter === 't' || $statusFilter === 'f') {
    $whereClauses[] = "q.is_active = '$statusFilter'";
}

// Date filter logic
if ($role !== 'Admin' || ($role === 'Admin' && !$ignoreDate)) {
    $whereClauses[] = "q.created_at >= '$dateFromFilter 00:00:00' AND q.created_at <= '$dateToFilter 23:59:59.999999'";
}

// For non-admins, restrict by creator
if ($role !== 'Admin') {
    $whereClauses[] = "q.created_by = '$associatenumber'";
}

$whereSql = count($whereClauses) > 0 ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

// Final query
$query = "
    SELECT q.id, q.question_text, q.correct_option, q.created_at, q.created_by, q.is_active, c.id AS category_id, c.name AS category_name
    FROM test_questions q
    LEFT JOIN test_categories c ON q.category_id = c.id
    $whereSql
    ORDER BY q.created_at DESC
";

$result = pg_query($con, $query);
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
    <title>Question Dashboard</title>
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
    <style>
        .bulk-actions {
            margin-bottom: 15px;
        }

        .selected-count {
            margin-left: 10px;
            font-weight: bold;
        }

        tr.selected {
            background-color: #f0f8ff !important;
        }

        tr[data-id] {
            cursor: pointer;
        }

        tr[data-id]:hover {
            background-color: #f5f5f5 !important;
        }
    </style>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Question Dashboard</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item">iExplore Edge</li>
                    <li class="breadcrumb-item active">Question Dashboard</li>
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
                                <!-- Bulk Actions -->
                                <?php if ($role === 'Admin'): ?>
                                    <form method="POST" id="bulkActionForm" class="bulk-actions">
                                        <div class="d-flex align-items-center">
                                            <button type="submit" name="bulk_action" value="inactive" class="btn btn-warning me-2">Bulk Mark Inactive</button>
                                            <button type="submit" name="bulk_action" value="active" class="btn btn-success me-2">Bulk Mark Active</button>
                                            <span id="selectedCount" class="selected-count">0 selected</span>
                                        </div>
                                        <input type="hidden" name="selected_ids" id="selectedIds">
                                    </form>
                                <?php endif; ?>

                                <!-- Filter Section -->
                                <form method="GET" id="filterForm" class="mb-4">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label for="categoryFilter" class="form-label">Category</label>
                                            <select id="categoryFilter" name="category" class="form-select">
                                                <option value="">All Categories</option>
                                                <?php foreach ($categories as $category): ?>
                                                    <option value="<?= $category['id'] ?>" <?= isset($_GET['category']) && $_GET['category'] == $category['id'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($category['name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <!-- Status Filter -->
                                        <div class="col-md-4">
                                            <label for="statusFilter" class="form-label">Status</label>
                                            <select id="statusFilter" name="status" class="form-select">
                                                <option value="">All Statuses</option>
                                                <option value="t" <?= isset($_GET['status']) && $_GET['status'] === 't' ? 'selected' : '' ?>>Active</option>
                                                <option value="f" <?= isset($_GET['status']) && $_GET['status'] === 'f' ? 'selected' : '' ?>>Inactive</option>
                                            </select>
                                        </div>

                                        <div class="col-md-4">
                                            <label for="questionIdFilter" class="form-label">Question IDs (comma separated)</label>
                                            <input type="text" id="questionIdFilter" name="question_ids" class="form-control"
                                                value="<?= isset($_GET['question_ids']) ? htmlspecialchars($_GET['question_ids']) : '' ?>"
                                                placeholder="e.g., 1,5,7">
                                        </div>

                                        <?php if ($role === 'Admin'): ?>
                                            <div class="col-md-4">
                                                <label for="dateFromFilter" class="form-label">Creation Date From</label>
                                                <input type="date" id="dateFromFilter" name="date_from" class="form-control" value="<?= isset($_GET['date_from']) ? $_GET['date_from'] : $currentDate ?>">
                                            </div>
                                            <div class="col-md-4">
                                                <label for="dateToFilter" class="form-label">Creation Date To</label>
                                                <input type="date" id="dateToFilter" name="date_to" class="form-control" value="<?= isset($_GET['date_to']) ? $_GET['date_to'] : $currentDate ?>">
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-check mt-4">
                                                    <input class="form-check-input" type="checkbox" id="ignoreDate" name="ignore_date" value="1" <?= $ignoreDate ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="ignoreDate">
                                                        Ignore Date Range
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mt-3">
                                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                                    </div>
                                </form>

                                <!-- Question Table -->
                                <div class="table-responsive">
                                    <table class="table" id="table-id">
                                        <thead>
                                            <tr>
                                                <th>
                                                    <?php if ($role === 'Admin'): ?>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" id="selectAll">
                                                        </div>
                                                    <?php endif; ?>
                                                </th>
                                                <th>#</th>
                                                <th>Question Text</th>
                                                <th>Category</th>
                                                <th>Correct Option</th>
                                                <th>Options</th>
                                                <th>Last Updated</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (pg_num_rows($result) > 0): ?>
                                                <?php while ($row = pg_fetch_assoc($result)):
                                                    // Fetch options for each question
                                                    $optionsQuery = "SELECT option_key, option_text FROM test_options WHERE question_id = $1 ORDER BY option_key";
                                                    $optionsResult = pg_query_params($con, $optionsQuery, array($row['id']));
                                                    $options = [];
                                                    while ($optionRow = pg_fetch_assoc($optionsResult)) {
                                                        $options[] = $optionRow;
                                                    }

                                                    // Prepare the options display
                                                    $optionsDisplay = '';
                                                    foreach ($options as $option) {
                                                        $optionsDisplay .= "{$option['option_key']}: {$option['option_text']}<br>";
                                                    }
                                                ?>
                                                    <tr data-id="<?= $row['id'] ?>">
                                                        <td>
                                                            <?php if ($role === 'Admin'): ?>
                                                                <div class="form-check">
                                                                    <input class="form-check-input row-checkbox" type="checkbox" value="<?= $row['id'] ?>">
                                                                </div>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?= $row['id'] ?></td>
                                                        <td><?= htmlspecialchars($row['question_text']) ?></td>
                                                        <td><?= htmlspecialchars($row['category_name']) ?></td>
                                                        <td><?= $row['correct_option'] ?></td>
                                                        <td><?= $optionsDisplay ?></td>
                                                        <td><?= (new DateTime($row['created_at']))->format('d/m/Y h:i A') ?> by <?= $row['created_by'] ?></td>
                                                        <td><?= $row['is_active'] === 't' ? 'Active' : 'Inactive' ?></td>
                                                        <td>
                                                            <div class="dropdown">
                                                                <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                                    <i class="bi bi-three-dots-vertical"></i>
                                                                </button>
                                                                <ul class="dropdown-menu">
                                                                    <?php if ($role === 'Admin' || $row['created_by'] === $associatenumber): ?>
                                                                        <li>
                                                                            <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#editModal"
                                                                                data-id="<?= $row['id'] ?>" data-question="<?= htmlspecialchars($row['question_text']) ?>"
                                                                                data-category="<?= $row['category_id'] ?>" data-correct="<?= $row['correct_option'] ?>"
                                                                                data-options='<?= json_encode($options) ?>'>
                                                                                <i class="bi bi-pencil-square me-2"></i> Edit
                                                                            </button>
                                                                        </li>
                                                                    <?php endif; ?>

                                                                    <?php if ($role === 'Admin'): ?>
                                                                        <li>
                                                                            <form action="" method="POST" onsubmit="return confirm('Are you sure you want to mark this question as inactive?');">
                                                                                <input type="hidden" name="inactive_id" value="<?= $row['id'] ?>">
                                                                                <button type="submit" class="dropdown-item" <?= $row['is_active'] === 't' ? '' : 'disabled' ?>>
                                                                                    <i class="bi bi-x-circle me-2"></i> <?= $row['is_active'] === 't' ? 'Mark Inactive' : 'Disabled' ?>
                                                                                </button>
                                                                            </form>
                                                                        </li>
                                                                    <?php endif; ?>
                                                                </ul>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <p>No questions found.</p>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div><!-- End Reports -->
            </div>
        </section>

    </main><!-- End #main -->

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="#">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel">Edit Question</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="editQuestionId">
                        <div class="mb-3">
                            <label for="editQuestionText" class="form-label">Question Text</label>
                            <textarea class="form-control" id="editQuestionText" name="question_text" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="editCategory" class="form-label">Category</label>
                            <select id="editCategory" name="category" class="form-select" required>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editCorrectOption" class="form-label">Correct Option</label>
                            <input type="text" class="form-control" id="editCorrectOption" name="correct_option" required>
                        </div>
                        <div id="editOptionsContainer">
                            <!-- Dynamic options will be added here -->
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Modal handling for editing questions
        const editModal = document.getElementById('editModal');
        editModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const question = button.getAttribute('data-question');
            const category = button.getAttribute('data-category');
            const correct = button.getAttribute('data-correct');
            const options = JSON.parse(button.getAttribute('data-options')); // Parse the options JSON

            // Set values for question and other fields
            document.getElementById('editQuestionId').value = id;
            document.getElementById('editQuestionText').value = question;
            document.getElementById('editCategory').value = category;
            document.getElementById('editCorrectOption').value = correct;

            // Clear the options container
            const optionsContainer = document.getElementById('editOptionsContainer');
            optionsContainer.innerHTML = ''; // Clear any previous options

            // Add option fields dynamically
            options.forEach((option, index) => {
                const optionDiv = document.createElement('div');
                optionDiv.classList.add('mb-3');
                optionDiv.innerHTML = `
            <label for="editOption${index}" class="form-label">Option ${option.option_key}</label>
            <input type="text" class="form-control" id="editOption${index}" name="options[${option.option_key}]" value="${option.option_text}" required>
        `;
                optionsContainer.appendChild(optionDiv);
            });
        });

        // Bulk selection functionality
        document.addEventListener('DOMContentLoaded', function() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.row-checkbox');
            const selectedCount = document.getElementById('selectedCount');
            const selectedIds = document.getElementById('selectedIds');
            const bulkActionForm = document.getElementById('bulkActionForm');
            const questionIdFilter = document.getElementById('questionIdFilter');

            // Select all functionality
            if (selectAll) {
                selectAll.addEventListener('change', function() {
                    checkboxes.forEach(checkbox => {
                        checkbox.checked = selectAll.checked;
                        updateRowSelection(checkbox);
                    });
                    updateSelectionCount();
                });
            }

            // Individual checkbox functionality
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    updateRowSelection(this);
                    updateSelectionCount();

                    // Update Select All checkbox state
                    if (selectAll) {
                        selectAll.checked = Array.from(checkboxes).every(cb => cb.checked);
                    }
                });
            });

            // Row click selection
            document.querySelectorAll('tbody tr').forEach(row => {
                row.addEventListener('click', function(e) {
                    // Don't trigger if clicking on a checkbox or dropdown
                    if (e.target.tagName !== 'INPUT' && e.target.tagName !== 'BUTTON' &&
                        !e.target.closest('.dropdown-menu') && !e.target.closest('.form-check')) {
                        const checkbox = this.querySelector('.row-checkbox');
                        if (checkbox) {
                            checkbox.checked = !checkbox.checked;
                            updateRowSelection(checkbox);
                            updateSelectionCount();
                        }
                    }
                });
            });

            // Update selection count and hidden field
            function updateSelectionCount() {
                const selected = Array.from(checkboxes).filter(cb => cb.checked);
                selectedCount.textContent = `${selected.length} selected`;

                // Update hidden field with selected IDs
                selectedIds.value = selected.map(cb => cb.value).join(',');

                // Update question ID filter with selected IDs
                questionIdFilter.value = selected.map(cb => cb.value).join(',');
            }

            // Update row visual selection state
            function updateRowSelection(checkbox) {
                const row = checkbox.closest('tr');
                if (checkbox.checked) {
                    row.classList.add('selected');
                } else {
                    row.classList.remove('selected');
                }
            }

            // Confirm bulk actions
            if (bulkActionForm) {
                bulkActionForm.addEventListener('submit', function(e) {
                    const selected = Array.from(checkboxes).filter(cb => cb.checked);
                    if (selected.length === 0) {
                        e.preventDefault();
                        alert('Please select at least one question.');
                        return;
                    }

                    const action = document.querySelector('button[name="bulk_action"]:focus')?.value;
                    const actionText = action === 'inactive' ? 'inactive' : 'active';

                    if (!confirm(`Are you sure you want to mark ${selected.length} question(s) as ${actionText}?`)) {
                        e.preventDefault();
                    }
                });
            }

            const ignoreDate = document.getElementById('ignoreDate');
            const dateFrom = document.getElementById('dateFromFilter');
            const dateTo = document.getElementById('dateToFilter');

            if (ignoreDate) {
                function toggleDateFields() {
                    const disabled = ignoreDate.checked;
                    dateFrom.disabled = disabled;
                    dateTo.disabled = disabled;
                }
                ignoreDate.addEventListener('change', toggleDateFields);
                toggleDateFields(); // Initial call to set state
            }
        });
    </script>
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
                    "order": [], // Disable initial sorting
                    "columnDefs": [{
                            "orderable": false,
                            "targets": [0, 8]
                        } // Disable sorting on checkbox and actions columns
                    ]
                    // other options...
                });
            <?php endif; ?>
        });
    </script>

</body>

</html>