<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

// Pagination setup
$records_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Search functionality
$search = isset($_GET['search']) ? pg_escape_string($con, $_GET['search']) : '';
$search_condition = $search ?
    "WHERE name ILIKE '%$search%' OR contact_number LIKE '%$search%' OR email ILIKE '%$search%'" : '';

// Get total records for pagination
$total_query = "SELECT COUNT(*) FROM public_health_records $search_condition";
$total_result = pg_query($con, $total_query);
$total_records = pg_fetch_result($total_result, 0, 0);
$total_pages = ceil($total_records / $records_per_page);

// Get records for current page
$query = "SELECT * FROM public_health_records $search_condition 
          ORDER BY created_at DESC 
          LIMIT $records_per_page OFFSET $offset";
$result = pg_query($con, $query);

// CSV Export functionality
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="beneficiaries_' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');

    // CSV header
    fputcsv($output, [
        'ID',
        'Name',
        'Mobile',
        'Email',
        'Date of Birth',
        'Gender',
        'Referral Source',
        'Registration Date'
    ]);

    // Get all records for export
    $export_query = "SELECT id, name, contact_number, email, date_of_birth, 
                    gender, referral_source, created_at 
                    FROM public_health_records $search_condition 
                    ORDER BY created_at DESC";
    $export_result = pg_query($con, $export_query);

    while ($row = pg_fetch_assoc($export_result)) {
        fputcsv($output, [
            $row['id'],
            $row['name'],
            $row['contact_number'],
            $row['email'],
            $row['date_of_birth'],
            $row['gender'],
            $row['referral_source'],
            $row['created_at']
        ]);
    }

    fclose($output);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beneficiary List</title>
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4a8bfc;
            --secondary-color: #f8f9fa;
            --accent-color: #e9f2ff;
        }

        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .card-header {
            background-color: var(--primary-color);
            padding: 1rem 1.5rem;
        }

        .table th {
            background-color: var(--accent-color);
            border-top: none;
        }

        .profile-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary-color);
        }

        .search-box {
            position: relative;
        }

        .search-box .form-control {
            padding-left: 40px;
            border-radius: 20px;
        }

        .search-box i {
            position: absolute;
            left: 15px;
            top: 10px;
            color: #6c757d;
        }

        .pagination .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .pagination .page-link {
            color: var(--primary-color);
        }

        .export-btn {
            border-radius: 20px;
        }
    </style>
</head>

<body>
    <div class="container py-4">
        <div class="card">
            <div class="card-header text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-users me-2"></i>Beneficiary List</h5>
                <a href="register_beneficiary.php" class="btn btn-light btn-sm" target="_blank">
                    <i class="fas fa-user-plus me-1"></i> New Registration
                </a>
            </div>

            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-8">
                        <form method="get" class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" name="search" class="form-control" placeholder="Search by name, mobile or email..."
                                value="<?= htmlspecialchars($search) ?>">
                        </form>
                    </div>
                    <div class="col-md-4 text-end">
                        <a href="?export=csv<?= $search ? '&search=' . urlencode($search) : '' ?>"
                            class="btn btn-outline-primary export-btn">
                            <i class="fas fa-file-export me-1"></i> Export to CSV
                        </a>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Profile</th>
                                <th>Name</th>
                                <th>Mobile</th>
                                <th>Email</th>
                                <th>Age</th>
                                <th>Gender</th>
                                <th>Registered On</th>
                                <!-- <th>Actions</th> -->
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (pg_num_rows($result) > 0): ?>
                                <?php while ($row = pg_fetch_assoc($result)): ?>
                                    <tr>
                                        <td><?= $row['id'] ?></td>
                                        <td>
                                            <?php if (!empty($row['profile_photo'])): ?>
                                                <?php
                                                // Extract file ID from Google Drive URL
                                                preg_match('/\/file\/d\/([a-zA-Z0-9_-]+)\//', $row['profile_photo'], $matches);
                                                $file_id = $matches[1] ?? null;

                                                if ($file_id):
                                                    $preview_url = "https://drive.google.com/file/d/$file_id/preview";
                                                ?>
                                                    <div class="drive-photo-preview"
                                                        data-toggle="tooltip"
                                                        title="Click to view full photo"
                                                        onclick="showPhotoModal('<?= $preview_url ?>')">
                                                        <iframe src="<?= $preview_url ?>"
                                                            width="40"
                                                            height="40"
                                                            frameborder="0"
                                                            style="border-radius: 50%; border: 2px solid #4a8bfc;"
                                                            allow="autoplay">
                                                        </iframe>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="profile-img bg-light text-center">
                                                        <i class="fas fa-user text-muted" style="line-height: 36px;"></i>
                                                    </div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <div class="profile-img bg-light text-center">
                                                    <i class="fas fa-user text-muted" style="line-height: 36px;"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($row['name']) ?></td>
                                        <td><?= htmlspecialchars($row['contact_number']) ?></td>
                                        <td><?= htmlspecialchars($row['email']) ?></td>
                                        <td>
                                            <?php
                                            $dob = new DateTime($row['date_of_birth']);
                                            $now = new DateTime();
                                            echo $now->diff($dob)->y;
                                            ?>
                                        </td>
                                        <td><?= htmlspecialchars($row['gender']) ?></td>
                                        <td><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                                        <!-- <td>
                                            <a href="view_beneficiary.php?id=<?= $row['id'] ?>"
                                                class="btn btn-sm btn-outline-primary" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit_beneficiary.php?id=<?= $row['id'] ?>"
                                                class="btn btn-sm btn-outline-secondary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </td> -->
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center py-4">
                                        <i class="fas fa-user-slash fa-2x mb-3" style="color: #6c757d;"></i>
                                        <h5>No beneficiaries found</h5>
                                        <?php if ($search): ?>
                                            <p>Try a different search term</p>
                                        <?php else: ?>
                                            <p>No beneficiaries have registered yet</p>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page - 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page + 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>