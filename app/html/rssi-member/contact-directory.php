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

// Get current user info
$current_user = $associatenumber;
$is_admin = ($role == 'Admin');
$is_centreIncharge = ($position == 'Centre Incharge' || $position == 'Senior Centre Incharge');

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_vendor'])) {
        // Add new vendor
        $vendor_name = pg_escape_string($con, $_POST['vendor_name']);
        $contact_person = pg_escape_string($con, $_POST['contact_person']);
        $contact_number = pg_escape_string($con, $_POST['contact_number']);
        $department = pg_escape_string($con, $_POST['department']);
        $notes = pg_escape_string($con, $_POST['notes']);

        if (empty($contact_number)) {
            echo "<script>alert('Error: Contact number cannot be empty!');</script>";
        } else {
            $query = "INSERT INTO vendor_directory 
                      (vendor_name, contact_person, contact_number, department, notes, added_by) 
                      VALUES ('$vendor_name', '$contact_person', '$contact_number', '$department', '$notes', '$current_user')";

            $result = pg_query($con, $query);
            if (!$result) {
                echo "<script>alert('Error: " . pg_last_error($con) . "');</script>";
            } else {
                echo "<script>alert('Success: Vendor added successfully!'); window.location.href = window.location.href;</script>";
                exit;
            }
        }
    } elseif (isset($_POST['toggle_active'])) {
        // Toggle active status
        $vendor_id = pg_escape_string($con, $_POST['vendor_id']);
        $current_status = pg_escape_string($con, $_POST['current_status']);
        $new_status = $current_status == 't' ? 'f' : 't';

        $query = "UPDATE vendor_directory SET is_active = '$new_status' WHERE vendor_id = '$vendor_id'";
        $result = pg_query($con, $query);
        if (!$result) {
            echo "<script>alert('Error: " . pg_last_error($con) . "');</script>";
        } else {
            echo "<script>alert('Success: Vendor status updated successfully!'); window.location.href = window.location.href;</script>";
            exit;
        }
    } elseif (isset($_POST['edit_vendor'])) {
        // Request vendor edit
        $vendor_id = pg_escape_string($con, $_POST['vendor_id']);
        $vendor_name = pg_escape_string($con, $_POST['vendor_name']);
        $contact_person = pg_escape_string($con, $_POST['contact_person']);
        $contact_number = pg_escape_string($con, $_POST['contact_number']);
        $department = pg_escape_string($con, $_POST['department']);
        $notes = pg_escape_string($con, $_POST['notes']);

        if (empty($contact_number)) {
            echo "<script>alert('Error: Contact number cannot be empty!');</script>";
        } else {
            // Prepare edited data as JSON
            $edited_data = json_encode([
                'vendor_name' => $vendor_name,
                'contact_person' => $contact_person,
                'contact_number' => $contact_number,
                'department' => $department,
                'notes' => $notes
            ]);

            // Set edit pending flag and store edited data
            $query = "UPDATE vendor_directory 
                      SET is_edit_pending = TRUE, 
                          edited_data = '$edited_data',
                          edited_by = '$current_user',
                          edit_request_date = CURRENT_TIMESTAMP
                      WHERE vendor_id = '$vendor_id'";

            $result = pg_query($con, $query);
            if (!$result) {
                echo "<script>alert('Error: " . pg_last_error($con) . "');</script>";
            } else {
                // Also add to approval history
                $query = "INSERT INTO vendor_edit_approvals 
                          (vendor_id, edited_data, requested_by, status)
                          VALUES ('$vendor_id', '$edited_data', '$current_user', 'Pending')";

                $result = pg_query($con, $query);
                echo "<script>alert('Success: Edit request submitted for admin approval!'); window.location.href = window.location.href;</script>";
                exit;
            }
        }
    } elseif (isset($_POST['approve_edit'])) {
        // Admin approves edit
        if (!$is_admin) {
            echo "<script>alert('Error: Only admins can approve edits!');</script>";
        } else {
            $vendor_id = pg_escape_string($con, $_POST['vendor_id']);
            $approval_id = pg_escape_string($con, $_POST['approval_id']);

            // Get the edited data with NULL check
            $query = "SELECT edited_data FROM vendor_directory WHERE vendor_id = '$vendor_id'";
            $result = pg_query($con, $query);
            $row = pg_fetch_assoc($result);

            // Initialize default empty array if edited_data is NULL
            $edited_data = [];
            if (!empty($row['edited_data'])) {
                $decoded_data = json_decode($row['edited_data'], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $edited_data = $decoded_data;
                }
            }

            // Apply the edits with proper escaping
            $query = "UPDATE vendor_directory 
                      SET vendor_name = '" . pg_escape_string($con, $edited_data['vendor_name'] ?? '') . "',
                          contact_person = '" . pg_escape_string($con, $edited_data['contact_person'] ?? '') . "',
                          contact_number = '" . pg_escape_string($con, $edited_data['contact_number'] ?? '') . "',
                          department = '" . pg_escape_string($con, $edited_data['department'] ?? '') . "',
                          notes = '" . pg_escape_string($con, $edited_data['notes'] ?? '') . "',
                          is_edit_pending = FALSE,
                          edited_data = NULL,
                          edited_by = NULL,
                          edit_request_date = NULL
                      WHERE vendor_id = '$vendor_id'";

            $result = pg_query($con, $query);
            if (!$result) {
                echo "<script>alert('Error: " . pg_last_error($con) . "');</script>";
            } else {
                // Update approval record
                $query = "UPDATE vendor_edit_approvals 
                          SET status = 'Approved',
                              approved_by = '$current_user',
                              approval_date = CURRENT_TIMESTAMP
                          WHERE approval_id = '$approval_id'";

                $result = pg_query($con, $query);
                echo "<script>alert('Success: Edit approved and applied successfully!'); window.location.href = window.location.href;</script>";
                exit;
            }
        }
    } elseif (isset($_POST['reject_edit'])) {
        // Admin rejects edit
        if (!$is_admin) {
            echo "<script>alert('Error: Only admins can reject edits!');</script>";
        } else {
            $vendor_id = pg_escape_string($con, $_POST['vendor_id']);
            $approval_id = pg_escape_string($con, $_POST['approval_id']);

            // Clear pending edit
            $query = "UPDATE vendor_directory 
                      SET is_edit_pending = FALSE,
                          edited_data = NULL,
                          edited_by = NULL,
                          edit_request_date = NULL
                      WHERE vendor_id = '$vendor_id'";

            $result = pg_query($con, $query);
            if (!$result) {
                echo "<script>alert('Error: " . pg_last_error($con) . "');</script>";
            } else {
                // Update approval record
                $query = "UPDATE vendor_edit_approvals 
                          SET status = 'Rejected',
                              approved_by = '$current_user',
                              approval_date = CURRENT_TIMESTAMP
                          WHERE approval_id = '$approval_id'";

                $result = pg_query($con, $query);
                echo "<script>alert('Success: Edit request rejected!'); window.location.href = window.location.href;</script>";
                exit;
            }
        }
    }
}

// Get filter status (default to active)
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'active';

// Build query based on filter
if ($filter_status == 'active') {
    $query = "SELECT vd.*, rm.fullname as added_by_name 
              FROM vendor_directory vd
              LEFT JOIN rssimyaccount_members rm ON vd.added_by = rm.associatenumber
              WHERE vd.is_active = TRUE
              ORDER BY vd.vendor_name";
} elseif ($filter_status == 'inactive') {
    $query = "SELECT vd.*, rm.fullname as added_by_name 
              FROM vendor_directory vd
              LEFT JOIN rssimyaccount_members rm ON vd.added_by = rm.associatenumber
              WHERE vd.is_active = FALSE
              ORDER BY vd.vendor_name";
} else {
    $query = "SELECT vd.*, rm.fullname as added_by_name 
              FROM vendor_directory vd
              LEFT JOIN rssimyaccount_members rm ON vd.added_by = rm.associatenumber
              ORDER BY vd.vendor_name";
}

$vendors = pg_query($con, $query);

// Get pending edit approvals (for admin)
$pending_approvals = null;
if ($is_admin) {
    $query = "SELECT vea.*, vd.vendor_name, rm.fullname as requested_by_name, vd.vendor_name as old_vendor_name, 
                     vd.contact_person as old_contact_person, vd.contact_number as old_contact_number,
                     vd.department as old_department, vd.notes as old_notes
              FROM vendor_edit_approvals vea
              JOIN vendor_directory vd ON vea.vendor_id = vd.vendor_id
              JOIN rssimyaccount_members rm ON vea.requested_by = rm.associatenumber
              WHERE vea.status = 'Pending'
              ORDER BY vea.request_date DESC";
    $pending_approvals = pg_query($con, $query);
}

// Function to generate initials from name
function generateInitials($name)
{
    $words = explode(' ', $name);
    $initials = '';
    foreach ($words as $word) {
        $initials .= strtoupper(substr($word, 0, 1));
    }
    return substr($initials, 0, 2);
}

// Function to generate random color based on name
function generateColor($name)
{
    $colors = ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40'];
    $hash = crc32($name);
    return $colors[abs($hash) % count($colors)];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Directory</title>
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --success-color: #4cc9f0;
            --warning-color: #f8961e;
            --danger-color: #f72585;
            --light-color: #f8f9fa;
            --dark-color: #212529;
        }

        /* body {
            background-color: #f5f7fb;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        } */

        .container {
            max-width: 1400px;
        }

        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: none;
            margin-bottom: 25px;
        }

        .card-header {
            background-color: var(--primary-color);
            color: white;
            border-radius: 10px 10px 0 0 !important;
            padding: 15px 20px;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }

        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 16px;
            margin-right: 10px;
        }

        .initials-avatar {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            font-weight: bold;
            margin-right: 10px;
        }

        .user-info {
            display: flex;
            align-items: center;
        }

        .masked {
            filter: blur(4px);
            user-select: none;
            transition: filter 0.3s ease;
        }

        .masked:hover {
            filter: none;
        }

        .edit-pending {
            background-color: rgba(255, 193, 7, 0.1);
            position: relative;
        }

        .edit-pending::after {
            content: "Pending Approval";
            position: absolute;
            top: 5px;
            right: 5px;
            background-color: var(--warning-color);
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 12px;
        }

        .badge-pending {
            background-color: var(--warning-color);
        }

        .badge-active {
            background-color: var(--success-color);
        }

        .badge-inactive {
            background-color: var(--danger-color);
        }

        .status-filter {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .status-filter .btn {
            border-radius: 20px;
            padding: 5px 15px;
        }

        .status-filter .btn.active {
            background-color: var(--primary-color);
            color: white;
        }

        .table th {
            border-top: none;
            font-weight: 600;
            color: var(--dark-color);
        }

        .table td {
            vertical-align: middle;
        }

        .action-buttons .btn {
            margin-right: 5px;
            margin-bottom: 5px;
        }

        .changes-highlight {
            background-color: #e2f0fd;
            padding: 2px 5px;
            border-radius: 3px;
            font-weight: bold;
        }

        .old-value {
            text-decoration: line-through;
            color: #6c757d;
        }

        .new-value {
            color: var(--success-color);
            font-weight: bold;
        }

        .changes-container {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 10px;
            margin-top: 10px;
        }

        .change-item {
            margin-bottom: 5px;
        }

        .search-container {
            margin-bottom: 20px;
        }

        .no-results {
            text-align: center;
            padding: 30px;
            color: #6c757d;
        }

        .floating-action-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            cursor: pointer;
            border: none;
        }

        .floating-action-btn:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
        }
    </style>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Contact Directory</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Alliance Portal</a></li>
                    <li class="breadcrumb-item active">Contact Directory</li>
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
                            <div class="container mt-4">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <!-- <h2 class="mb-0"><i class="fas fa-address-book me-2"></i>Contact Directory</h2> -->
                                    <div class="status-filter ms-auto">
                                        <a href="?status=active" class="btn btn-sm <?php echo $filter_status == 'active' ? 'active btn-primary' : 'btn-outline-primary'; ?>">
                                            <i class="fas fa-check-circle me-1"></i> Active
                                        </a>
                                        <a href="?status=inactive" class="btn btn-sm <?php echo $filter_status == 'inactive' ? 'active btn-primary' : 'btn-outline-primary'; ?>">
                                            <i class="fas fa-times-circle me-1"></i> Inactive
                                        </a>
                                        <a href="?status=all" class="btn btn-sm <?php echo $filter_status == 'all' ? 'active btn-primary' : 'btn-outline-primary'; ?>">
                                            <i class="fas fa-list me-1"></i> All
                                        </a>
                                    </div>
                                </div>

                                <!-- Search Box -->
                                <div class="search-container">
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                                        <input type="text" id="searchInput" class="form-control" placeholder="Search vendors...">
                                        <button class="btn btn-outline-secondary" type="button" id="clearSearch">Clear</button>
                                    </div>
                                </div>

                                <!-- Pending Approvals (Admin only) -->
                                <?php if ($is_admin && $pending_approvals && pg_num_rows($pending_approvals) > 0): ?>
                                    <div class="card mb-4 border-warning">
                                        <div class="card-header bg-warning text-white">
                                            <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Pending Edit Approvals</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-hover">
                                                    <thead>
                                                        <tr>
                                                            <th>Vendor</th>
                                                            <th>Requested By</th>
                                                            <th>Request Date</th>
                                                            <th>Changes</th>
                                                            <th>Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php while ($row = pg_fetch_assoc($pending_approvals)):
                                                            $changes = json_decode($row['edited_data'], true);
                                                            $initials = generateInitials($row['requested_by_name']);
                                                            $color = generateColor($row['requested_by_name']);
                                                        ?>
                                                            <tr>
                                                                <td>
                                                                    <div class="user-info">
                                                                        <div class="avatar" style="background-color: <?php echo generateColor($row['vendor_name']); ?>">
                                                                            <?php echo generateInitials($row['vendor_name']); ?>
                                                                        </div>
                                                                        <?php echo htmlspecialchars($row['vendor_name']); ?>
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <div class="user-info">
                                                                        <div class="avatar" style="background-color: <?php echo $color; ?>">
                                                                            <?php echo $initials; ?>
                                                                        </div>
                                                                        <?php echo htmlspecialchars($row['requested_by_name']); ?>
                                                                    </div>
                                                                </td>
                                                                <td><?php echo date('M d, Y H:i', strtotime($row['request_date'])); ?></td>
                                                                <td>
                                                                    <div class="changes-container">
                                                                        <?php foreach ($changes as $key => $value):
                                                                            $old_value = $row['old_' . $key];
                                                                            if ($old_value != $value): ?>
                                                                                <div class="change-item">
                                                                                    <strong><?php echo ucfirst(str_replace('_', ' ', $key)); ?>:</strong>
                                                                                    <span class="old-value"><?php echo htmlspecialchars($old_value); ?></span> →
                                                                                    <span class="new-value"><?php echo htmlspecialchars($value); ?></span>
                                                                                </div>
                                                                            <?php endif; ?>
                                                                        <?php endforeach; ?>
                                                                    </div>
                                                                </td>
                                                                <td class="action-buttons">
                                                                    <form method="POST" style="display:inline;">
                                                                        <input type="hidden" name="vendor_id" value="<?php echo $row['vendor_id']; ?>">
                                                                        <input type="hidden" name="approval_id" value="<?php echo $row['approval_id']; ?>">
                                                                        <button type="submit" name="approve_edit" class="btn btn-success btn-sm">
                                                                            <i class="fas fa-check me-1"></i> Approve
                                                                        </button>
                                                                    </form>
                                                                    <form method="POST" style="display:inline;">
                                                                        <input type="hidden" name="vendor_id" value="<?php echo $row['vendor_id']; ?>">
                                                                        <input type="hidden" name="approval_id" value="<?php echo $row['approval_id']; ?>">
                                                                        <button type="submit" name="reject_edit" class="btn btn-danger btn-sm">
                                                                            <i class="fas fa-times me-1"></i> Reject
                                                                        </button>
                                                                    </form>
                                                                </td>
                                                            </tr>
                                                        <?php endwhile; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Vendor List -->
                                <div class="card">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0">
                                            <i class="fas <?php echo $filter_status == 'active' ? 'fa-check-circle' : ($filter_status == 'inactive' ? 'fa-times-circle' : 'fa-list'); ?> me-2"></i>
                                            <?php echo ucfirst($filter_status); ?> Vendors
                                            <span class="badge bg-primary ms-2"><?php echo pg_num_rows($vendors); ?></span>
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <?php if (pg_num_rows($vendors) > 0): ?>
                                            <div class="table-responsive">
                                                <table class="table table-hover" id="vendorTable">
                                                    <thead>
                                                        <tr>
                                                            <th>Vendor</th>
                                                            <th>Contact</th>
                                                            <th>Department</th>
                                                            <th>Added By</th>
                                                            <th>Status</th>
                                                            <th>Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php while ($row = pg_fetch_assoc($vendors)):
                                                            $is_pending = $row['is_edit_pending'] == 't';
                                                            $is_owner = ($row['added_by'] == $current_user);
                                                            $initials = generateInitials($row['vendor_name']);
                                                            $color = generateColor($row['vendor_name']);
                                                        ?>
                                                            <tr class="<?php echo $is_pending ? 'edit-pending' : ''; ?>">
                                                                <td>
                                                                    <div class="user-info">
                                                                        <div class="avatar" style="background-color: <?php echo $color; ?>">
                                                                            <?php echo $initials; ?>
                                                                        </div>
                                                                        <div>
                                                                            <strong><?php echo htmlspecialchars($row['vendor_name']); ?></strong>
                                                                            <?php if (!empty($row['notes'])): ?>
                                                                                <div class="text-muted small"><?php echo htmlspecialchars($row['notes']); ?></div>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <div>
                                                                        <strong><?php echo htmlspecialchars($row['contact_person']); ?></strong>
                                                                        <div>
                                                                            <?php if ($is_admin || $is_centreIncharge || $is_owner): ?>
                                                                                <a href="tel:<?php echo htmlspecialchars($row['contact_number']); ?>" class="text-decoration-none">
                                                                                    <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($row['contact_number']); ?>
                                                                                </a>
                                                                            <?php else: ?>
                                                                                <span class="masked" title="Contact admin for full access">
                                                                                    <i class="fas fa-phone me-1"></i><?php echo substr($row['contact_number'], 0, 3) . '****' . substr($row['contact_number'], -3); ?>
                                                                                </span>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                    </div>
                                                                </td>
                                                                <td><?php echo htmlspecialchars($row['department']); ?></td>
                                                                <td>
                                                                    <div class="user-info">
                                                                        <div class="initials-avatar" style="background-color: <?php echo generateColor($row['added_by_name']); ?>">
                                                                            <?php echo generateInitials($row['added_by_name']); ?>
                                                                        </div>
                                                                        <?php echo htmlspecialchars($row['added_by_name']); ?>
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <span class="badge rounded-pill <?php echo $row['is_active'] == 't' ? 'badge-active' : 'badge-inactive'; ?>">
                                                                        <?php echo $row['is_active'] == 't' ? 'Active' : 'Inactive'; ?>
                                                                    </span>
                                                                    <?php if ($is_pending): ?>
                                                                        <span class="badge rounded-pill badge-pending ms-1">Pending</span>
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td class="action-buttons">
                                                                    <?php if ($is_owner || $is_admin): ?>
                                                                        <button class="btn btn-sm btn-primary edit-vendor"
                                                                            data-vendor-id="<?php echo $row['vendor_id']; ?>"
                                                                            data-vendor-name="<?php echo htmlspecialchars($row['vendor_name']); ?>"
                                                                            data-contact-person="<?php echo htmlspecialchars($row['contact_person']); ?>"
                                                                            data-contact-number="<?php echo htmlspecialchars($row['contact_number']); ?>"
                                                                            data-department="<?php echo htmlspecialchars($row['department']); ?>"
                                                                            data-notes="<?php echo htmlspecialchars($row['notes']); ?>">
                                                                            <i class="fas fa-edit me-1"></i> Edit
                                                                        </button>
                                                                        <form method="POST" style="display:inline;">
                                                                            <input type="hidden" name="vendor_id" value="<?php echo $row['vendor_id']; ?>">
                                                                            <input type="hidden" name="current_status" value="<?php echo $row['is_active']; ?>">
                                                                            <button type="submit" name="toggle_active" class="btn btn-sm <?php echo $row['is_active'] == 't' ? 'btn-warning' : 'btn-success'; ?>">
                                                                                <i class="fas <?php echo $row['is_active'] == 't' ? 'fa-eye-slash' : 'fa-eye'; ?> me-1"></i>
                                                                                <?php echo $row['is_active'] == 't' ? 'Deactivate' : 'Activate'; ?>
                                                                            </button>
                                                                        </form>
                                                                    <?php endif; ?>
                                                                </td>
                                                            </tr>
                                                        <?php endwhile; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php else: ?>
                                            <div class="no-results">
                                                <i class="fas fa-info-circle fa-3x mb-3 text-muted"></i>
                                                <h5>No vendors found</h5>
                                                <p class="text-muted">There are no <?php echo $filter_status; ?> vendors in the directory.</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div><!-- End Reports -->
            </div>
        </section>

    </main><!-- End #main -->

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
    <!-- Add Vendor Modal -->
    <div class="modal fade" id="addVendorModal" tabindex="-1" aria-labelledby="addVendorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addVendorModalLabel"><i class="fas fa-plus-circle me-2"></i>Add New Vendor</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="addVendorForm">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="vendor_name" class="form-label">Vendor Name*</label>
                                <input type="text" class="form-control" id="vendor_name" name="vendor_name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="contact_person" class="form-label">Contact Person</label>
                                <input type="text" class="form-control" id="contact_person" name="contact_person">
                            </div>
                            <div class="col-md-6">
                                <label for="contact_number" class="form-label">Contact Number*</label>
                                <input type="text" class="form-control" id="contact_number" name="contact_number" required>
                            </div>
                            <div class="col-md-6">
                                <label for="department" class="form-label">Department</label>
                                <input type="text" class="form-control" id="department" name="department">
                            </div>
                            <div class="col-12">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times me-1"></i> Cancel</button>
                        <button type="submit" name="add_vendor" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save Vendor</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Vendor Modal -->
    <div class="modal fade" id="editVendorModal" tabindex="-1" aria-labelledby="editVendorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="editVendorModalLabel"><i class="fas fa-edit me-2"></i>Edit Vendor</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="editVendorForm">
                    <div class="modal-body">
                        <input type="hidden" name="vendor_id" id="edit_vendor_id">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="edit_vendor_name" class="form-label">Vendor Name*</label>
                                <input type="text" class="form-control" id="edit_vendor_name" name="vendor_name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_contact_person" class="form-label">Contact Person</label>
                                <input type="text" class="form-control" id="edit_contact_person" name="contact_person">
                            </div>
                            <div class="col-md-6">
                                <label for="edit_contact_number" class="form-label">Contact Number*</label>
                                <input type="text" class="form-control" id="edit_contact_number" name="contact_number" required>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_department" class="form-label">Department</label>
                                <input type="text" class="form-control" id="edit_department" name="department">
                            </div>
                            <div class="col-12">
                                <label for="edit_notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="edit_notes" name="notes" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times me-1"></i> Close</button>
                        <button type="submit" name="edit_vendor" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Floating Action Button -->
    <button class="floating-action-btn" data-bs-toggle="modal" data-bs-target="#addVendorModal">
        <i class="fas fa-plus fa-lg"></i>
    </button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Template Main JS File -->
      <script src="../assets_new/js/main.js"></script>
  
    <script>
        // Handle edit button clicks
        document.querySelectorAll('.edit-vendor').forEach(button => {
            button.addEventListener('click', function() {
                const vendorId = this.getAttribute('data-vendor-id');
                const vendorName = this.getAttribute('data-vendor-name');
                const contactPerson = this.getAttribute('data-contact-person');
                const contactNumber = this.getAttribute('data-contact-number');
                const department = this.getAttribute('data-department');
                const notes = this.getAttribute('data-notes');

                document.getElementById('edit_vendor_id').value = vendorId;
                document.getElementById('edit_vendor_name').value = vendorName;
                document.getElementById('edit_contact_person').value = contactPerson;
                document.getElementById('edit_contact_number').value = contactNumber;
                document.getElementById('edit_department').value = department;
                document.getElementById('edit_notes').value = notes;

                var modal = new bootstrap.Modal(document.getElementById('editVendorModal'));
                modal.show();
            });
        });

        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchText = this.value.toLowerCase();
            const rows = document.querySelectorAll('#vendorTable tbody tr');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchText) ? '' : 'none';
            });
        });

        // Clear search
        document.getElementById('clearSearch').addEventListener('click', function() {
            document.getElementById('searchInput').value = '';
            const rows = document.querySelectorAll('#vendorTable tbody tr');
            rows.forEach(row => row.style.display = '');
        });

        // Prevent form resubmission on page reload
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }

        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
</body>

</html>