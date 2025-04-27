<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_structure'])) {
        $associate_number = $_POST['associate_number'];
        $structure_name = $_POST['structure_name'];
        $ctc_amount = $_POST['ctc_amount'];
        $effective_from = $_POST['effective_from'];
        $effective_till = !empty($_POST['effective_till']) ? $_POST['effective_till'] : null;

        // Reindex and filter components
        $components = [];
        foreach ($_POST['components'] as $comp) {
            if (!empty($comp['name']) && !empty($comp['annual']) && is_numeric($comp['annual'])) {
                $components[] = [
                    'master_id' => !empty($comp['master_id']) ? $comp['master_id'] : null, // Handle empty master_id
                    'category_id' => $comp['category_id'],
                    'category' => $comp['category'],
                    'name' => $comp['name'],
                    'monthly' => isset($comp['monthly']) && $comp['monthly'] !== '' && is_numeric($comp['monthly']) ? $comp['monthly'] : null,
                    'annual' => $comp['annual'],
                    'is_deduction' => $comp['is_deduction'] ?? false,
                    'order' => $comp['order']
                ];
            }
        }

        // Validate we have at least one valid component
        if (empty($components)) {
            $_SESSION['error_message'] = "At least one valid salary component is required";
        } else {
            pg_query($con, "BEGIN");
            $transaction_success = true;

            try {
                // Insert structure header
                $query = "INSERT INTO salary_structures 
                          (associate_number, structure_name, ctc_amount, effective_from, effective_till, created_by)
                          VALUES ($1, $2, $3, $4, $5, $6) RETURNING id";
                $result = pg_query_params($con, $query, [
                    $associate_number,
                    $structure_name,
                    $ctc_amount,
                    $effective_from,
                    $effective_till,
                    $associatenumber
                ]);

                if (!$result) {
                    throw new Exception(pg_last_error($con));
                }

                $structure_id = pg_fetch_result($result, 0, 0);

                // Insert components
                foreach ($components as $component) {
                    $query = "INSERT INTO salary_components 
                              (structure_id, master_component_id, component_type_id, category, component_name, 
                              monthly_amount, annual_amount, is_deduction, display_order)
                              VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9)";
                    $result = pg_query_params($con, $query, [
                        $structure_id,
                        $component['master_id'],
                        $component['category_id'],
                        $component['category'],
                        $component['name'],
                        $component['monthly'],
                        $component['annual'],
                        $component['is_deduction'] ? 't' : 'f',
                        isset($component['order']) && is_numeric($component['order']) ? (int)$component['order'] : 0
                    ]);

                    if (!$result) {
                        throw new Exception(pg_last_error($con));
                    }
                }

                pg_query($con, "COMMIT");
                $_SESSION['success_message'] = "Salary structure saved successfully!";
            } catch (Exception $e) {
                pg_query($con, "ROLLBACK");
                $_SESSION['error_message'] = "Error saving salary structure: " . $e->getMessage();
                $transaction_success = false;
            }

            if ($transaction_success) {
                header("Location: salary_structure.php?associate_number=" . urlencode($associate_number));
                exit;
            }
        }
    } elseif (isset($_POST['update_end_date'])) {
        // Handle end date update
        $structure_id = $_POST['structure_id'];
        $effective_till = $_POST['effective_till'];

        $query = "UPDATE salary_structures SET effective_till = $1 WHERE id = $2";
        $result = pg_query_params($con, $query, [$effective_till, $structure_id]);

        if ($result) {
            $_SESSION['success_message'] = "Effective end date updated successfully!";
        } else {
            $_SESSION['error_message'] = "Error updating end date: " . pg_last_error($con);
        }

        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit;
    }
}

// Fetch component types and master components
$component_types = pg_query($con, "SELECT * FROM salary_component_types WHERE is_active = TRUE ORDER BY display_order");
$component_types = pg_fetch_all($component_types) ?: [];

// In your PHP code where you fetch master components:
$master_components = pg_query($con, "SELECT mc.*, ct.name as type_name, ct.component_type 
                                   FROM salary_components_master mc
                                   JOIN salary_component_types ct ON mc.component_type_id = ct.id
                                   WHERE mc.is_active = TRUE
                                   ORDER BY ct.display_order, mc.name");
$master_components = pg_fetch_all($master_components) ?: [];

// Group master components by type
$grouped_master_components = [];
foreach ($master_components as $component) {
    $grouped_master_components[$component['type_name']][] = $component;
}

// Fetch existing structures with pagination
$existing_structures = [];
$total_pages = 1;
$current_page = 1;

if (isset($_GET['associate_number'])) {
    $associate_number = $_GET['associate_number'];
    $current_page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $per_page = 10;
    $offset = ($current_page - 1) * $per_page;

    $query = "SELECT ss.*, COUNT(*) OVER() as total_count 
              FROM salary_structures ss
              WHERE ss.associate_number = $1
              ORDER BY ss.effective_from DESC
              LIMIT $2 OFFSET $3";
    $result = pg_query_params($con, $query, [$associate_number, $per_page, $offset]);
    $existing_structures = pg_fetch_all($result) ?: [];

    if (!empty($existing_structures)) {
        $total_count = $existing_structures[0]['total_count'];
        $total_pages = ceil($total_count / $per_page);
    }
}

// Check if we're copying from an existing structure
$copy_source_data = $_SESSION['copy_source_data'] ?? null;
$copied_components = [];

if ($copy_source_data) {
    $copied_components = $copy_source_data['components'];
    $_SESSION['new_structure_name'] = $copy_source_data['structure_name'];
    $_SESSION['new_effective_from'] = $copy_source_data['effective_from'];
    $_SESSION['new_effective_till'] = $copy_source_data['effective_till'];

    // Clear the session data after use
    unset($_SESSION['copy_source_data']);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salary Structure Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
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

        .deduction-row {
            background-color: #fff3f3;
        }

        .pagination .page-item.active .page-link {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }

        .component-selector {
            cursor: pointer;
            transition: all 0.2s;
        }

        .component-selector:hover {
            background-color: #f8f9fa;
        }

        .badge-earning {
            background-color: #198754;
        }

        .badge-deduction {
            background-color: #dc3545;
        }
    </style>
</head>

<body>
    <div class="container-fluid py-4">

        <div class="row mb-4">
            <div class="col">
                <h1 class="h3 fw-bold">Salary Structure Management</h1>
            </div>
        </div>

        <!-- Search Form -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h2 class="h5 mb-0">Search Associate</h2>
            </div>
            <div class="card-body">
                <form method="get" class="row g-3">
                    <div class="col-md-8">
                        <label for="associate_number" class="form-label">Associate Number</label>
                        <select class="form-select select2" id="associate_number" name="associate_number" required>
                            <option value="">Select Associate</option>
                            <?php if (isset($associate_number)): ?>
                                <option value="<?= htmlspecialchars($associate_number) ?>" selected>
                                    <?= htmlspecialchars($associate_number) ?>
                                </option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search me-1"></i> Search
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <?php if (isset($associate_number)): ?>
            <!-- Existing Structures -->
            <div class="card mb-4">
                <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                    <h2 class="h5 mb-0">Existing Salary Structures</h2>
                    <div>
                        <button class="btn btn-sm btn-light" data-bs-toggle="collapse" data-bs-target="#structuresCollapse">
                            <i class="bi bi-chevron-down"></i> Toggle
                        </button>
                    </div>
                </div>
                <div class="card-body collapse show" id="structuresCollapse">
                    <?php if (empty($existing_structures)): ?>
                        <div class="alert alert-info mb-0">No salary structures found.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Structure Name</th>
                                        <th>Effective From</th>
                                        <th>Effective Till</th>
                                        <th>CTC Amount</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($existing_structures as $structure): ?>
                                        <?php
                                        $current_date = date('Y-m-d');
                                        $effective_from = $structure['effective_from'];
                                        $effective_till = $structure['effective_till'];

                                        $is_active = ($current_date >= $effective_from) &&
                                            ($effective_till === null || $current_date <= $effective_till);
                                        ?>
                                        <tr>
                                            <td><?= htmlspecialchars($structure['structure_name']) ?></td>
                                            <td><?= date('d-M-Y', strtotime($structure['effective_from'])) ?></td>
                                            <td><?= $structure['effective_till'] ? date('d-M-Y', strtotime($structure['effective_till'])) : 'Present' ?></td>
                                            <td>₹ <?= number_format($structure['ctc_amount'], 2) ?></td>
                                            <td>
                                                <span class="badge bg-<?= $is_active ? 'success' : 'secondary' ?>">
                                                    <?= $is_active ? 'Active' : 'Inactive' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <a href="view_structure.php?id=<?= $structure['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <button class="btn btn-sm btn-outline-warning update-end-date-btn"
                                                        data-id="<?= $structure['id'] ?>"
                                                        data-associate="<?= $structure['associate_number'] ?>">
                                                        <i class="bi bi-calendar"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-success copy-structure-btn"
                                                        data-id="<?= $structure['id'] ?>"
                                                        data-associate="<?= $structure['associate_number'] ?>">
                                                        <i class="bi bi-files"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Page navigation">
                                <ul class="pagination justify-content-center">
                                    <?php if ($current_page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?associate_number=<?= $associate_number ?>&page=<?= $current_page - 1 ?>" aria-label="Previous">
                                                <span aria-hidden="true">&laquo;</span>
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?= $i == $current_page ? 'active' : '' ?>">
                                            <a class="page-link" href="?associate_number=<?= $associate_number ?>&page=<?= $i ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($current_page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?associate_number=<?= $associate_number ?>&page=<?= $current_page + 1 ?>" aria-label="Next">
                                                <span aria-hidden="true">&raquo;</span>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Create New Salary Structure -->
            <div class="card">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <h2 class="h5 mb-0">Create New Salary Structure</h2>
                    <button class="btn btn-sm btn-light" data-bs-toggle="collapse" data-bs-target="#componentLibrary">
                        <i class="bi bi-collection"></i> Component Library
                    </button>
                </div>
                <div class="card-body">
                    <!-- Component Library (Collapsible) -->
                    <div class="collapse mb-4" id="componentLibrary">
                        <div class="card card-body mb-4">
                            <h5 class="mb-3">Component Library</h5>
                            <div class="row">
                                <?php foreach ($grouped_master_components as $type_name => $components): ?>
                                    <div class="col-md-4 mb-3">
                                        <div class="card">
                                            <div class="card-header bg-light">
                                                <h6 class="mb-0"><?= htmlspecialchars($type_name) ?></h6>
                                            </div>
                                            <div class="card-body p-0">
                                                <ul class="list-group list-group-flush">
                                                    <?php foreach ($components as $component): ?>
                                                        <li class="list-group-item component-selector"
                                                            data-master-id="<?= $component['id'] ?>"
                                                            data-category-id="<?= $component['component_type_id'] ?>"
                                                            data-category="<?= htmlspecialchars($type_name) ?>"
                                                            data-name="<?= htmlspecialchars($component['name']) ?>"
                                                            data-is-deduction="<?= $component['component_type'] === 'Deduction' ? '1' : '0' ?>">
                                                            <div class="d-flex justify-content-between align-items-center">
                                                                <span><?= htmlspecialchars($component['name']) ?></span>
                                                                <span class="badge <?= $component['component_type'] === 'Deduction' ? 'badge-deduction' : 'badge-earning' ?>">
                                                                    <?= $component['component_type'] ?>
                                                                </span>
                                                            </div>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <form method="post" id="salaryStructureForm">
                        <input type="hidden" name="associate_number" value="<?= htmlspecialchars($associate_number) ?>">

                        <div class="row mb-4">
                            <div class="col-md-4">
                                <label for="structure_name" class="form-label">Structure Name</label>
                                <input type="text" class="form-control" id="structure_name" name="structure_name"
                                    value="<?= htmlspecialchars($_SESSION['new_structure_name'] ?? '') ?>" required>
                                <?php unset($_SESSION['new_structure_name']); ?>
                            </div>
                            <div class="col-md-4">
                                <label for="effective_from" class="form-label">Effective From</label>
                                <input type="date" class="form-control" id="effective_from" name="effective_from"
                                    value="<?= htmlspecialchars($_SESSION['new_effective_from'] ?? date('Y-m-d')) ?>" required>
                                <?php unset($_SESSION['new_effective_from']); ?>
                            </div>
                            <div class="col-md-4">
                                <label for="effective_till" class="form-label">Effective Till (optional)</label>
                                <input type="date" class="form-control" id="effective_till" name="effective_till"
                                    value="<?= htmlspecialchars($_SESSION['new_effective_till'] ?? '') ?>">
                                <?php unset($_SESSION['new_effective_till']); ?>
                                <div class="form-text">Leave blank for current structure</div>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-4">
                                <label for="ctc_amount" class="form-label">Cost to Company (CTC)</label>
                                <div class="input-group">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" step="0.01" class="form-control" id="ctc_amount" name="ctc_amount" readonly>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="earnings_total" class="form-label">Total Earnings</label>
                                <div class="input-group">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" step="0.01" class="form-control" id="earnings_total" readonly>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="deductions_total" class="form-label">Total Deductions</label>
                                <div class="input-group">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" step="0.01" class="form-control" id="deductions_total" readonly>
                                </div>
                            </div>
                        </div>

                        <h5 class="mb-3">Salary Components</h5>
                        <div id="componentsContainer">
                            <!-- Components will be added here dynamically -->
                            <?php if (!empty($copied_components)): ?>
                                <?php foreach ($copied_components as $index => $component): ?>
                                    <div class="component-row row g-3 mb-2 <?= $component['is_deduction'] ? 'deduction-row' : '' ?>">
                                        <input type="hidden" name="components[<?= $index ?>][master_id]" value="<?= $component['master_id'] ?? '' ?>">
                                        <input type="hidden" name="components[<?= $index ?>][category_id]" value="<?= $component['category_id'] ?>">
                                        <input type="hidden" name="components[<?= $index ?>][category]" value="<?= htmlspecialchars($component['category']) ?>">
                                        <input type="hidden" name="components[<?= $index ?>][is_deduction]" value="<?= $component['is_deduction'] ? '1' : '0' ?>">
                                        <input type="hidden" name="components[<?= $index ?>][order]" value="<?= $component['order'] ?>">
                                        <div class="col-md-5">
                                            <input type="text" class="form-control" name="components[<?= $index ?>][name]"
                                                value="<?= htmlspecialchars($component['name']) ?>" required>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="input-group">
                                                <span class="input-group-text">₹</span>
                                                <input type="number" step="0.01" class="form-control monthly-input"
                                                    name="components[<?= $index ?>][monthly]"
                                                    value="<?= htmlspecialchars($component['monthly'] ?? '') ?>"
                                                    placeholder="Monthly">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="input-group">
                                                <span class="input-group-text">₹</span>
                                                <input type="number" step="0.01" class="form-control annual-input"
                                                    name="components[<?= $index ?>][annual]"
                                                    value="<?= htmlspecialchars($component['annual']) ?>"
                                                    placeholder="Annual" required>
                                            </div>
                                        </div>
                                        <div class="col-md-1">
                                            <button type="button" class="btn btn-outline-danger remove-component">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-between mt-4">
                            <button type="button" id="addCustomComponent" class="btn btn-outline-primary">
                                <i class="bi bi-plus-circle"></i> Add Custom Component
                            </button>
                            <div>
                                <button type="submit" name="save_structure" class="btn btn-primary">
                                    <i class="bi bi-save me-1"></i> Save Structure
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Component Template -->
    <div id="componentTemplate" class="component-row row g-3 mb-2" style="display: none;">
        <input type="hidden" name="components[0][master_id]" value="">
        <input type="hidden" name="components[0][category_id]" value="">
        <input type="hidden" name="components[0][category]" value="">
        <input type="hidden" name="components[0][is_deduction]" value="0">
        <input type="hidden" name="components[0][order]" value="0">
        <div class="col-md-5">
            <input type="text" class="form-control" name="components[0][name]" placeholder="Component Name" required>
        </div>
        <div class="col-md-3">
            <div class="input-group">
                <span class="input-group-text">₹</span>
                <input type="number" step="0.01" class="form-control monthly-input"
                    name="components[0][monthly]" placeholder="Monthly">
            </div>
        </div>
        <div class="col-md-3">
            <div class="input-group">
                <span class="input-group-text">₹</span>
                <input type="number" step="0.01" class="form-control annual-input"
                    name="components[0][annual]" placeholder="Annual" required>
            </div>
        </div>
        <div class="col-md-1">
            <button type="button" class="btn btn-outline-danger remove-component">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    </div>

    <!-- Category Selection Modal -->
    <div class="modal fade" id="categorySelectionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Select Component Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="list-group">
                        <?php foreach ($component_types as $type): ?>
                            <a href="#" class="list-group-item list-group-item-action category-selector"
                                data-id="<?= $type['id'] ?>"
                                data-name="<?= htmlspecialchars($type['name']) ?>"
                                data-type="<?= htmlspecialchars($type['component_type']) ?>">
                                <?= htmlspecialchars($type['name']) ?>
                                <span class="badge <?= $type['component_type'] === 'Deduction' ? 'bg-danger' : 'bg-success' ?> float-end">
                                    <?= htmlspecialchars($type['component_type']) ?>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Update End Date Modal -->
    <div class="modal fade" id="updateEndDateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Effective End Date</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="structure_id" id="update_structure_id">
                        <input type="hidden" name="associate_number" id="update_associate_number">
                        <input type="hidden" name="update_end_date" value="1">

                        <div class="mb-3">
                            <label for="new_effective_till" class="form-label">New Effective End Date</label>
                            <input type="date" class="form-control" id="new_effective_till" name="effective_till" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Date</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Copy Structure Modal -->
    <div class="modal fade" id="copyStructureModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Copy Salary Structure</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="copy_structure.php">
                    <div class="modal-body">
                        <input type="hidden" name="source_structure_id" id="copy_structure_id">
                        <input type="hidden" name="associate_number" id="copy_associate_number">

                        <div class="mb-3">
                            <label for="new_structure_name" class="form-label">New Structure Name</label>
                            <input type="text" class="form-control" id="new_structure_name" name="new_structure_name" required>
                        </div>

                        <div class="mb-3">
                            <label for="new_effective_from" class="form-label">Effective From</label>
                            <input type="date" class="form-control" id="new_effective_from" name="new_effective_from" required>
                        </div>

                        <div class="mb-3">
                            <label for="new_effective_till" class="form-label">Effective Till (optional)</label>
                            <input type="date" class="form-control" id="new_effective_till" name="new_effective_till">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Copy Structure</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize Select2
            $('.select2').select2({
                ajax: {
                    url: 'fetch_associates.php',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data.results
                        };
                    },
                    cache: true
                },
                minimumInputLength: 1
            });

            // Global component counter
            let componentCounter = <?= !empty($copied_components) ? count($copied_components) : 0 ?>;

            // Replace the existing calculateAnnualFromMonthly function with this:
            function calculateAnnualFromMonthly(monthlyInput) {
                const monthly = parseFloat(monthlyInput.val()) || 0;
                const annualInput = monthlyInput.closest('.component-row').find('.annual-input');
                const currentAnnual = parseFloat(annualInput.val()) || 0;

                if (monthly > 0) {
                    annualInput.val((monthly * 12).toFixed(2));
                } else if (monthlyInput.val() === '') {
                    // If monthly is cleared, keep the existing annual value
                    return;
                } else {
                    // If monthly is set to 0, set annual to 0
                    annualInput.val('0.00');
                }
                calculateTotals();
            }

            function calculateMonthlyFromAnnual(annualInput) {
                const annual = parseFloat(annualInput.val()) || 0;
                const monthlyInput = annualInput.closest('.component-row').find('.monthly-input');
                monthlyInput.val((annual / 12).toFixed(2));
            }

            function calculateTotals() {
                let totalEarnings = 0;
                let totalDeductions = 0;

                $('.component-row').each(function() {
                    const annualValue = parseFloat($(this).find('.annual-input').val()) || 0;
                    const isDeduction = $(this).find('input[name$="[is_deduction]"]').val() === '1';

                    if (isDeduction) {
                        // Add to deductions only
                        totalDeductions += annualValue;
                    } else {
                        // Add to earnings and CTC
                        totalEarnings += annualValue;
                    }
                });

                // CTC should be sum of earnings only (no deductions)
                $('#ctc_amount').val(totalEarnings.toFixed(2));
                $('#earnings_total').val(totalEarnings.toFixed(2));
                $('#deductions_total').val(totalDeductions.toFixed(2));
            }

            // Replace the existing component-selector click handler with this:
            $(document).on('click', '.component-selector', function() {
                console.log('Component clicked:', {
                    name: $(this).data('name'),
                    is_deduction: $(this).data('is-deduction'),
                    component_type: $(this).find('.badge').text()
                });

                const masterId = $(this).data('master-id');
                const categoryId = $(this).data('category-id');
                const category = $(this).data('category');
                const name = $(this).data('name');
                const isDeduction = $(this).attr('data-is-deduction') === '1';

                console.log('Parsed isDeduction:', isDeduction);

                addComponent(masterId, categoryId, category, name, isDeduction);
            });

            // Add custom component
            $('#addCustomComponent').click(function() {
                $('#categorySelectionModal').modal('show');
            });

            // Handle category selection
            $(document).on('click', '.category-selector', function(e) {
                e.preventDefault();
                $('#categorySelectionModal').modal('hide');

                const categoryId = $(this).data('id');
                const category = $(this).data('name');
                const isDeduction = $(this).data('type') === 'Deduction';

                addComponent('', categoryId, category, '', isDeduction);
            });

            // Modified addComponent function to handle all cases properly
            function addComponent(masterId, categoryId, category, name, isDeduction, monthly, annual) {
                const template = $('#componentTemplate').clone();
                template.attr('id', '').removeClass('d-none').show();

                // Add deduction class if needed
                if (isDeduction) {
                    template.addClass('deduction-row');
                }

                const currentIndex = componentCounter++;
                template.html(template.html().replace(/\[0\]/g, `[${currentIndex}]`));

                // Set component data
                template.find('input[name$="[master_id]"]').val(masterId || '');
                template.find('input[name$="[category_id]"]').val(categoryId || '');
                template.find('input[name$="[category]"]').val(category);
                template.find('input[name$="[is_deduction]"]').val(isDeduction ? '1' : '0');
                template.find('input[name$="[name]"]').val(name);
                template.find('input[name$="[order]"]').val(currentIndex);

                // Set initial values if provided
                if (typeof monthly !== 'undefined' && monthly !== null) {
                    template.find('.monthly-input').val(monthly);
                }
                if (typeof annual !== 'undefined' && annual !== null) {
                    template.find('.annual-input').val(annual);
                }

                // Add class for deduction rows
                if (isDeduction) {
                    template.addClass('deduction-row');
                }

                // Modified calculation handlers to prevent annual amount from becoming zero
                template.find('.monthly-input').on('input', function() {
                    const monthlyValue = parseFloat($(this).val()) || 0;
                    const annualInput = $(this).closest('.component-row').find('.annual-input');
                    const currentAnnual = parseFloat(annualInput.val()) || 0;

                    if (monthlyValue > 0) {
                        annualInput.val((monthlyValue * 12).toFixed(2));
                    } else if (currentAnnual > 0) {
                        // Keep the existing annual value if monthly is cleared
                        return;
                    }
                    calculateTotals();
                });

                template.find('.annual-input').on('input', function() {
                    const annualValue = parseFloat($(this).val()) || 0;
                    const monthlyInput = $(this).closest('.component-row').find('.monthly-input');

                    if (annualValue > 0) {
                        monthlyInput.val((annualValue / 12).toFixed(2));
                    }
                    calculateTotals();
                });

                // Allow both fields to be editable
                template.find('.monthly-input, .annual-input').on('focus', function() {
                    $(this).select();
                });

                $('#componentsContainer').append(template);
                calculateTotals();
            }

            // Initialize calculations if components exist
            if (componentCounter > 0) {
                $('.component-row').each(function() {
                    const annualInput = $(this).find('.annual-input');
                    const monthlyInput = $(this).find('.monthly-input');

                    // Initialize monthly from annual if annual exists but monthly doesn't
                    if (annualInput.val() && !monthlyInput.val()) {
                        const annualValue = parseFloat(annualInput.val());
                        if (annualValue > 0) {
                            monthlyInput.val((annualValue / 12).toFixed(2));
                        }
                    }

                    // Reattach event handlers with protection
                    $(this).find('.monthly-input').off('input').on('input', function() {
                        const monthlyValue = parseFloat($(this).val()) || 0;
                        const annualInput = $(this).closest('.component-row').find('.annual-input');
                        const currentAnnual = parseFloat(annualInput.val()) || 0;

                        if (monthlyValue > 0) {
                            annualInput.val((monthlyValue * 12).toFixed(2));
                        } else if (currentAnnual > 0) {
                            // Keep the existing annual value if monthly is cleared
                            return;
                        }
                        calculateTotals();
                    });

                    $(this).find('.annual-input').off('input').on('input', function() {
                        const annualValue = parseFloat($(this).val()) || 0;
                        const monthlyInput = $(this).closest('.component-row').find('.monthly-input');

                        if (annualValue > 0) {
                            monthlyInput.val((annualValue / 12).toFixed(2));
                        }
                        calculateTotals();
                    });
                });
                calculateTotals();
            }

            // Copy Structure button handler - Modified to populate all fields
            $('.copy-structure-btn').click(function() {
                const structureId = $(this).data('id');
                const associateNumber = $(this).data('associate');
                const structureName = $(this).closest('tr').find('td:first').text().trim();
                const effectiveFrom = $(this).closest('tr').find('td:nth-child(2)').text().trim();
                const effectiveTill = $(this).closest('tr').find('td:nth-child(3)').text().trim();

                // Convert date formats if needed (assuming dates are in dd-Mmm-YYYY format)
                function convertDate(inputDate) {
                    if (!inputDate || inputDate.toLowerCase() === 'present') return '';
                    const months = {
                        'Jan': '01',
                        'Feb': '02',
                        'Mar': '03',
                        'Apr': '04',
                        'May': '05',
                        'Jun': '06',
                        'Jul': '07',
                        'Aug': '08',
                        'Sep': '09',
                        'Oct': '10',
                        'Nov': '11',
                        'Dec': '12'
                    };
                    const parts = inputDate.split('-');
                    if (parts.length === 3) {
                        return `${parts[2]}-${months[parts[1]]}-${parts[0].padStart(2, '0')}`;
                    }
                    return '';
                }

                $('#copy_structure_id').val(structureId);
                $('#copy_associate_number').val(associateNumber);
                $('#new_structure_name').val(structureName + ' (Copy)');
                $('#new_effective_from').val(convertDate(effectiveFrom) || new Date().toISOString().split('T')[0]);
                $('#new_effective_till').val(convertDate(effectiveTill));

                $('#copyStructureModal').modal('show');
            });

            // Initialize calculations if components exist
            if (componentCounter > 0) {
                $('.component-row').each(function() {
                    const annualInput = $(this).find('.annual-input');
                    const monthlyInput = $(this).find('.monthly-input');

                    // Initialize monthly from annual if annual exists but monthly doesn't
                    if (annualInput.val() && !monthlyInput.val()) {
                        calculateMonthlyFromAnnual(annualInput);
                    }

                    // Reattach event handlers
                    $(this).find('.monthly-input').off('input').on('input', function() {
                        calculateAnnualFromMonthly($(this));
                    });

                    $(this).find('.annual-input').off('input').on('input', function() {
                        calculateMonthlyFromAnnual($(this));
                        calculateTotals();
                    });
                });
                calculateTotals();
            }

            // Remove component
            $(document).on('click', '.remove-component', function() {
                $(this).closest('.component-row').remove();
                calculateTotals();
            });
        });

        // Add this to your $(document).ready() function
        $('.update-end-date-btn').click(function() {
            const structureId = $(this).data('id');
            const associateNumber = $(this).data('associate');

            // Set the values in the modal
            $('#update_structure_id').val(structureId);
            $('#update_associate_number').val(associateNumber);
            $('#new_effective_till').val(''); // Clear any previous value

            // Show the modal
            $('#updateEndDateModal').modal('show');
        });
    </script>
</body>

</html>