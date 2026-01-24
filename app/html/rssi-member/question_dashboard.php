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

// Handle status change with remarks
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status']) && isset($_POST['selected_ids'])) {
    $status = $_POST['status'];
    $remarks = $_POST['remarks'] ?? '';
    $selectedIds = $_POST['selected_ids'] ?? [];

    if (!empty($selectedIds)) {
        // Convert comma-separated string to array if needed
        $idsArray = is_array($selectedIds) ? $selectedIds : explode(',', $selectedIds);
        $ids = implode(",", array_map('intval', $idsArray));

        // Get current timestamp
        $changeTimestamp = date('Y-m-d H:i:s');
        $isActive = $status === 'active' ? 'TRUE' : 'FALSE';
        $oldStatus = $status === 'active' ? 'inactive' : 'active';
        $newStatus = $status;

        $updateQuery = "UPDATE test_questions 
                       SET is_active = $isActive, 
                           change_history = change_history || 
                           jsonb_build_array(
                             jsonb_build_object(
                               'changed_by', '$associatenumber',
                               'change_type', 'status_change',
                               'old_value', '$oldStatus',
                               'new_value', '$newStatus',
                               'change_timestamp', '$changeTimestamp',
                               'notes', " . ($remarks ? "'$remarks'" : "'Update by $associatenumber'") . "
                             )
                           )
                       WHERE id IN ($ids)";

        $updateResult = pg_query($con, $updateQuery);

        if ($updateResult) {
            echo "<script>
                    alert('Questions status updated successfully!');
                    if (window.history.replaceState) {
                        window.history.replaceState(null, null, window.location.href);
                    }
                    window.location.reload();
                  </script>";
        } else {
            echo "<script>
                    alert('Error updating questions status.');
                  </script>";
        }
    } else {
        echo "<script>
                alert('Please select at least one question.');
              </script>";
    }
    exit;
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
    $changeTimestamp = date('Y-m-d H:i:s');

    // First, get the current values to compare
    $currentQuery = "SELECT question_text, category_id, correct_option FROM test_questions WHERE id = $1";
    $currentResult = pg_query_params($con, $currentQuery, array($questionId));
    $currentData = pg_fetch_assoc($currentResult);

    $changes = [];
    if ($currentData['question_text'] !== $questionText) {
        $changes[] = "Question text updated";
    }
    if ($currentData['category_id'] != $categoryId) {
        $changes[] = "Category changed from {$currentData['category_id']} to $categoryId";
    }
    if ($currentData['correct_option'] !== $correctOption) {
        $changes[] = "Correct option changed from {$currentData['correct_option']} to $correctOption";
    }

    $changeNotes = !empty($changes) ? implode(', ', $changes) : 'No content changes detected';

    // Update the question text and history
    $updateQuery = "
        UPDATE test_questions
        SET question_text = $1, category_id = $2, correct_option = $3, created_at = $5, created_by = $6,
            change_history = change_history || 
            jsonb_build_array(
              jsonb_build_object(
                'changed_by', '$associatenumber',
                'change_type', 'content_edit',
                'old_value', 'Previous version',
                'new_value', 'Updated content',
                'change_timestamp', '$changeTimestamp',
                'notes', '$changeNotes'
              )
            )
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
                    window.history.replaceState(null, null, window.location.href);
                }
                window.location.reload();
              </script>";
    } else {
        echo "<script>
                alert('Error updating question.');
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

// Capture records per load filter
$recordsPerLoad = $_GET['records_per_load'] ?? '10';

// Build WHERE clause
$whereClauses = [];

// Category filter applies to all
if ($categoryFilter) {
    $whereClauses[] = "q.category_id = '$categoryFilter'";
}

// Question ID filter
if ($questionIdFilter) {
    $parts = explode(',', $questionIdFilter);
    $idParts = [];
    $keywordParts = [];

    foreach ($parts as $part) {
        $part = trim($part);
        if (is_numeric($part)) {
            $idParts[] = intval($part);
        } else {
            $keywordParts[] = pg_escape_string($con, $part);
        }
    }

    $idCondition = count($idParts) ? "q.id IN (" . implode(',', $idParts) . ")" : "";
    $keywordCondition = count($keywordParts) ?
        "(" . implode(" OR ", array_map(fn($k) => "q.question_text ILIKE '%$k%'", $keywordParts)) . ")"
        : "";

    $conditions = array_filter([$idCondition, $keywordCondition]);
    if (count($conditions)) {
        $whereClauses[] = "(" . implode(" OR ", $conditions) . ")";
    }
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

// Count total records
$countQuery = "SELECT COUNT(*) as total FROM test_questions q $whereSql";
$countResult = pg_query($con, $countQuery);
$totalRecords = pg_fetch_assoc($countResult)['total'];

// Final query with limit
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : $recordsPerLoad;
$query = "
    SELECT q.id, q.question_text, q.correct_option, q.created_at, q.created_by, q.is_active, c.id AS category_id, c.name AS category_name
    FROM test_questions q
    LEFT JOIN test_categories c ON q.category_id = c.id
    $whereSql
    ORDER BY q.created_at DESC
    LIMIT $limit
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

        .timeline {
            position: relative;
            padding-left: 30px;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 20px;
        }

        .timeline-marker {
            position: absolute;
            left: -30px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: #0d6efd;
        }

        .timeline-content {
            padding: 10px;
            border-left: 2px solid #dee2e6;
        }

        .timeline-item:last-child .timeline-content {
            border-left: 2px solid transparent;
        }

        .load-more-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }

        .records-info {
            font-weight: bold;
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

                <!-- Reports -->
                <div class="col-12">
                    <div class="card">

                        <div class="card-body">
                            <br>
                            <div class="container mt-5">
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
                                            <label for="questionIdFilter" class="form-label">Question IDs or Keywords (comma separated)</label>
                                            <input type="text" id="questionIdFilter" name="question_ids" class="form-control"
                                                value="<?= isset($_GET['question_ids']) ? htmlspecialchars($_GET['question_ids']) : '' ?>"
                                                placeholder="e.g., 1,5,7 or math, algebra, geometry">
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

                                        <!-- Records per load filter -->
                                        <div class="col-md-4">
                                            <label for="recordsPerLoad" class="form-label">Records per load</label>
                                            <select id="recordsPerLoad" name="records_per_load" class="form-select">
                                                <option value="10" <?= $recordsPerLoad == '10' ? 'selected' : '' ?>>10</option>
                                                <option value="20" <?= $recordsPerLoad == '20' ? 'selected' : '' ?>>20</option>
                                                <option value="50" <?= $recordsPerLoad == '50' ? 'selected' : '' ?>>50</option>
                                                <option value="100" <?= $recordsPerLoad == '100' ? 'selected' : '' ?>>100</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                                        <span class="ms-3 records-info">Total records: <?= $totalRecords ?></span>
                                    </div>
                                </form>
                                <!-- Bulk Actions -->
                                <?php if ($role === 'Admin'): ?>
                                    <div class="d-flex justify-content-end align-items-center bulk-actions">
                                        <button type="button" class="btn btn-primary" id="bulkActionButton">
                                            <i class="bi bi-pencil-square me-1"></i> Change Status (<span id="selectedCount">0</span>)
                                        </button>
                                    </div>
                                    <input type="hidden" name="selected_ids" id="selectedIds">
                                <?php endif; ?>

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
                                                                            <button class="dropdown-item view-history" data-question-id="<?= $row['id'] ?>">
                                                                                <i class="bi bi-clock-history me-2"></i> History
                                                                            </button>
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

                                <!-- Load More Section -->
                                <div class="load-more-container">
                                    <div class="records-info">
                                        Showing <?= pg_num_rows($result) ?> of <?= $totalRecords ?> records
                                    </div>
                                    <?php if (pg_num_rows($result) < $totalRecords): ?>
                                        <button type="button" class="btn btn-primary" id="loadMoreBtn"
                                            data-current-offset="<?= pg_num_rows($result) ?>"
                                            data-total-records="<?= $totalRecords ?>">
                                            Load More (<span id="recordsToLoad"><?= $recordsPerLoad ?></span>)
                                        </button>
                                    <?php endif; ?>
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
    <!-- History Modal -->
    <div class="modal fade" id="historyModal" tabindex="-1" aria-labelledby="historyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="historyModalLabel">Question History</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="historyContent">
                        <p class="text-center">Loading history...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Change Modal -->
    <div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="#" id="statusForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="statusModalLabel">Change Question Status</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="selected_ids" id="modalSelectedIds">
                        <div class="mb-3">
                            <label for="statusChange" class="form-label">Status</label>
                            <select id="statusChange" name="status" class="form-select" required>
                                <option value="">Select Status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="statusRemarks" class="form-label">Remarks</label>
                            <textarea class="form-control" id="statusRemarks" name="remarks" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>

    <script>
        // Global Set to store selected row IDs
        let selectedRows = new Set();

        // Helper functions - in global scope
        function updateRowSelection(checkbox) {
            const row = $(checkbox).closest('tr');
            const id = checkbox.value;
            if ($(checkbox).is(':checked')) {
                row.addClass('selected');
                selectedRows.add(id);
            } else {
                row.removeClass('selected');
                selectedRows.delete(id);
            }
        }

        // Store the original filter value from URL on page load
        let originalFilterValue = $('#questionIdFilter').val();

        function updateSelectionCount() {
            $('#selectedCount').text(selectedRows.size);
            $('#selectedIds').val(Array.from(selectedRows).join(','));

            // If rows are selected, show them in filter field
            // If no rows selected, show original URL filter value
            if (selectedRows.size > 0) {
                $('#questionIdFilter').val(Array.from(selectedRows).join(','));
            } else {
                $('#questionIdFilter').val(originalFilterValue);
            }
        }

        function loadQuestionHistory(questionId) {
            const historyContent = document.getElementById('historyContent');
            historyContent.innerHTML = '<p class="text-center">Loading history...</p>';

            fetch(`get_question_history.php?question_id=${questionId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.length === 0) {
                        historyContent.innerHTML = '<p class="text-center">No history available for this question.</p>';
                        return;
                    }
                    let html = '<div class="timeline">';
                    data.forEach(entry => {
                        html += `
                    <div class="timeline-item">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                            <h6 class="mb-1">${entry.change_type.replace('_', ' ').toUpperCase()}</h6>
                            <p class="mb-1 text-muted small">By ${entry.changed_by} on ${new Date(entry.change_timestamp).toLocaleString()}</p>
                            <p class="mb-1">${entry.notes}</p>
                            ${entry.old_value && entry.new_value ? 
                                `<p class="mb-0 small">Changed from <span class="text-danger">${entry.old_value}</span> to <span class="text-success">${entry.new_value}</span></p>` : ''}
                        </div>
                    </div>`;
                    });
                    html += '</div>';
                    historyContent.innerHTML = html;
                })
                .catch(error => {
                    historyContent.innerHTML = '<p class="text-center text-danger">Error loading history.</p>';
                    console.error('Error:', error);
                });
        }

        function initializeEventHandlers() {
            $('#table-id tbody').off(); // Clear previous handlers

            // Update Select All checkbox
            const allCheckboxes = $('.row-checkbox');
            const selectAll = $('#selectAll');
            if (selectAll.length) {
                selectAll.prop('checked', allCheckboxes.length === allCheckboxes.filter(':checked').length);
            }

            // Select All handler – ADD THIS
            $('#selectAll').on('change', function() {
                const isChecked = $(this).is(':checked');
                $('.row-checkbox').each(function() {
                    $(this).prop('checked', isChecked);
                    updateRowSelection(this);
                });
                updateSelectionCount();
            });

            // Checkbox change handler (delegated)
            $('#table-id tbody').off('change', '.row-checkbox').on('change', '.row-checkbox', function(e) {
                updateRowSelection(this);
                updateSelectionCount();
                e.stopPropagation(); // prevent row click from firing
            });

            // Row click handler (delegated for DataTables)
            $('#table-id tbody').off('click', 'tr').on('click', 'tr', function(e) {
                // Ignore clicks on inputs, buttons, dropdowns
                if ($(e.target).is('input, button, .dropdown-toggle, .dropdown-item')) return;

                const checkbox = $(this).find('.row-checkbox');
                if (checkbox.length) {
                    checkbox.prop('checked', !checkbox.prop('checked')); // toggle checkbox
                    updateRowSelection(checkbox[0]); // update selectedRows set
                    updateSelectionCount();
                }
            });

            // View history handler
            $('#table-id tbody').on('click', '.view-history', function(e) {
                e.stopPropagation();
                const questionId = $(this).data('question-id');
                loadQuestionHistory(questionId);
                const historyModal = new bootstrap.Modal(document.getElementById('historyModal'));
                historyModal.show();
            });

            // Edit modal handler
            $('#table-id tbody').on('click', '[data-bs-target="#editModal"]', function(e) {
                e.stopPropagation();
                const button = $(this);
                const id = button.data('id');
                const question = button.data('question');
                const category = button.data('category');
                const correct = button.data('correct');
                const options = button.data('options');

                $('#editQuestionId').val(id);
                $('#editQuestionText').val(question);
                $('#editCategory').val(category);
                $('#editCorrectOption').val(correct);

                const optionsContainer = $('#editOptionsContainer');
                optionsContainer.empty();

                options.forEach(function(option, index) {
                    const optionDiv = $('<div class="mb-3"></div>');
                    optionDiv.html(`
                <label for="editOption${index}" class="form-label">Option ${option.option_key}</label>
                <input type="text" class="form-control" id="editOption${index}" name="options[${option.option_key}]" value="${option.option_text}" required>
            `);
                    optionsContainer.append(optionDiv);
                });
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Restore selected rows from already checked checkboxes
            $('.row-checkbox:checked').each(function() {
                selectedRows.add(this.value);
            });
            updateSelectionCount();

            // Ignore date functionality
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
                toggleDateFields();
            }

            // Initialize event handlers
            initializeEventHandlers();
        });

        document.addEventListener('DOMContentLoaded', function() {
            const statusModal = new bootstrap.Modal(document.getElementById('statusModal'));
            const statusForm = document.getElementById('statusForm');
            const modalSelectedIds = document.getElementById('modalSelectedIds');
            const statusChangeBtn = document.getElementById('bulkActionButton');

            if (statusChangeBtn) {
                statusChangeBtn.addEventListener('click', function(e) {
                    // const selected = $('.row-checkbox:checked');
                    if (selectedRows.size === 0) {
                        e.preventDefault();
                        alert('Please select at least one question to change status.');
                        return false;
                    }
                    modalSelectedIds.value = Array.from(selectedRows).join(',');
                    document.getElementById('statusChange').value = '';
                    document.getElementById('statusRemarks').value = '';
                    statusModal.show();
                });
            }

            if (statusForm) {
                statusForm.addEventListener('submit', function(e) {
                    const selected = $('.row-checkbox:checked');
                    if (selected.length === 0) {
                        e.preventDefault();
                        alert('Please select at least one question.');
                        return;
                    }
                    const status = document.getElementById('statusChange').value;
                    if (!status) {
                        e.preventDefault();
                        alert('Please select a status.');
                        return;
                    }
                    const remarks = document.getElementById('statusRemarks').value;
                    if (!remarks.trim()) {
                        e.preventDefault();
                        alert('Please provide remarks for this status change.');
                        return;
                    }
                    if (!confirm(`Are you sure you want to change status for ${selected.length} question(s)?`)) {
                        e.preventDefault();
                    }
                });
            }
        });

        $(document).ready(function() {
            <?php if (!empty($result)) : ?>
                var table = $('#table-id').DataTable({
                    "order": [],
                    "columnDefs": [{
                        "orderable": false,
                        "targets": [0, 8]
                    }],
                    "createdRow": function(row, data, dataIndex) {
                        // data[1] is the question ID column (second column in your array)
                        $(row).attr('data-id', data[1]);
                    }
                });
            <?php endif; ?>

            $('#recordsPerLoad').change(function() {
                localStorage.setItem('recordsPerLoad', $(this).val());
                $('#recordsToLoad').text($(this).val());
            });

            var savedRecordsPerLoad = localStorage.getItem('recordsPerLoad');
            if (savedRecordsPerLoad) {
                $('#recordsPerLoad').val(savedRecordsPerLoad);
                $('#recordsToLoad').text(savedRecordsPerLoad);
            }

            $('#loadMoreBtn').click(function() {
                var currentOffset = $(this).data('current-offset');
                var recordsPerLoad = $('#recordsPerLoad').val();
                var totalRecords = $(this).data('total-records');

                var category = $('#categoryFilter').val();
                var status = $('#statusFilter').val();
                var dateFrom = $('#dateFromFilter').val();
                var dateTo = $('#dateToFilter').val();
                var ignoreDate = $('#ignoreDate').is(':checked') ? 1 : 0;

                var originalText = $(this).html();
                $(this).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...');

                $.ajax({
                    url: 'load_more_questions.php',
                    type: 'GET',
                    data: {
                        offset: currentOffset,
                        limit: recordsPerLoad,
                        category: category,
                        status: status,
                        date_from: dateFrom,
                        date_to: dateTo,
                        ignore_date: ignoreDate
                    },
                    success: function(response) {
                        var data = JSON.parse(response);
                        if (data.success && data.questions.length > 0) {
                            // Inside your AJAX success loop
                            data.questions.forEach(function(question) {
                                var optionsDisplay = '';
                                question.options.forEach(function(option) {
                                    optionsDisplay += option.option_key + ': ' + option.option_text + '<br>';
                                });

                                var isChecked = selectedRows.has(question.id) ? 'checked' : '';

                                // Determine if user can edit
                                var canEdit = <?php echo ($role === 'Admin') ? 'true' : 'false'; ?> || question.created_by === '<?php echo $associatenumber; ?>';
                                var editButton = '';
                                if (canEdit) {
                                    editButton = '<li><button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#editModal"' +
                                        ' data-id="' + question.id + '" data-question="' + question.question_text.replace(/"/g, '&quot;') + '"' +
                                        ' data-category="' + question.category_id + '" data-correct="' + question.correct_option + '"' +
                                        ' data-options=\'' + JSON.stringify(question.options) + '\'>' +
                                        '<i class="bi bi-pencil-square me-2"></i> Edit</button></li>';
                                }

                                // Admin only history button
                                var historyButton = '';
                                <?php if ($role === 'Admin'): ?>
                                    historyButton = '<li><button class="dropdown-item view-history" data-question-id="' + question.id + '">' +
                                        '<i class="bi bi-clock-history me-2"></i> History</button></li>';
                                <?php endif; ?>

                                var dropdown = '<div class="dropdown">' +
                                    '<button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">' +
                                    '<i class="bi bi-three-dots-vertical"></i></button>' +
                                    '<ul class="dropdown-menu">' + editButton + historyButton + '</ul></div>';

                                // Add row with proper data-id
                                var newRowNode = table.row.add([
                                    '<div class="form-check"><input class="form-check-input row-checkbox" type="checkbox" value="' + question.id + '" ' + isChecked + '></div>',
                                    question.id,
                                    question.question_text,
                                    question.category_name,
                                    question.correct_option,
                                    optionsDisplay,
                                    question.created_at_formatted + ' by ' + question.created_by,
                                    question.is_active === 't' ? 'Active' : 'Inactive',
                                    dropdown
                                ]).draw(false).node();

                                $(newRowNode).attr('data-id', question.id);
                            });

                            var newOffset = currentOffset + data.questions.length;
                            $('#loadMoreBtn').data('current-offset', newOffset);
                            $('.records-info').text('Showing ' + newOffset + ' of ' + totalRecords + ' records');

                            if (newOffset >= totalRecords) {
                                $('#loadMoreBtn').hide();
                            }

                            $('#loadMoreBtn').html(originalText);
                        } else {
                            alert('No more questions to load.');
                            $('#loadMoreBtn').hide();
                            $('#loadMoreBtn').html(originalText);
                        }
                    },
                    error: function() {
                        alert('Error loading more questions. Please try again.');
                        $('#loadMoreBtn').html(originalText);
                    }
                });
            });

            initializeEventHandlers();
        });
    </script>

</body>

</html>