<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");
include("../../util/email.php");
include("../../util/drive.php");

// Ensure only teachers/admins can access
if (!isLoggedIn("tid") && !isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}

validation();

// Prevent form resubmission
// if ($_SERVER['REQUEST_METHOD'] == 'POST') {
//     header("Location: " . $_SERVER['REQUEST_URI']);
//     exit();
// }

$is_admin = ($role === 'Admin' || $role === 'Offline Manager');
$teacher_id = $associatenumber; // Teacher's ID from session
$message = isset($_SESSION['message']) ? $_SESSION['message'] : '';
$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['message'], $_SESSION['error']);

// Pagination and filtering
$initial_limit = 10;
$load_more_limit = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = ($page == 1) ? $initial_limit : $initial_limit + (($page - 1) * $load_more_limit);

$search = isset($_GET['search']) ? pg_escape_string($con, $_GET['search']) : '';
$author = isset($_GET['author']) ? pg_escape_string($con, $_GET['author']) : '';
$publisher = isset($_GET['publisher']) ? pg_escape_string($con, $_GET['publisher']) : '';
$category = isset($_GET['search_category']) ? pg_escape_string($con, $_GET['search_category']) : '';

// Place order (teacher placing order for self or student)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['place_order'])) {
    $book_id = $_POST['book_id'];
    $student_id = $_POST['student_id'] ?? null;

    // Validate student ID if provided
    if ($student_id) {
        $student_check = pg_query_params($con, "SELECT student_id FROM rssimyprofile_student WHERE student_id = $1", [$student_id]);
        if (pg_num_rows($student_check) == 0) {
            $_SESSION['error'] = "Invalid student ID provided.";
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit();
        }
    }

    $target_user_id = $student_id ? $student_id : $teacher_id;
    $target_user_type = $student_id ? 'student' : 'teacher';

    // Check book availability
    $check_query = "SELECT available_copies FROM books WHERE book_id = $1 AND status = 'available'";
    $check_result = pg_query_params($con, $check_query, [$book_id]);

    if (pg_num_rows($check_result) > 0) {
        $book = pg_fetch_assoc($check_result);

        if ($book['available_copies'] > 0) {
            // Check for existing orders
            $existing_query = "SELECT COUNT(*) FROM book_orders 
                             WHERE user_id = $1 
                             AND book_id = $2 
                             AND status IN ('pending', 'issued')";
            $existing_count = pg_fetch_result(pg_query_params($con, $existing_query, [$target_user_id, $book_id]), 0, 0);

            if ($existing_count == 0) {
                $insert_query = "INSERT INTO book_orders (book_id, user_id, user_type, status, ordered_by) 
                               VALUES ($1, $2, $3, 'pending', $4)";
                $insert_result = pg_query_params($con, $insert_query, [
                    $book_id,
                    $target_user_id,
                    $target_user_type,
                    $teacher_id
                ]);

                if ($insert_result) {
                    // Decrement available copies
                    pg_query_params($con, "UPDATE books SET available_copies = available_copies - 1 WHERE book_id = $1", [$book_id]);

                    $query = "SELECT STRING_AGG(alt_email, ', ') AS centre_incharge_email,
                            STRING_AGG(fullname, ', ') AS centre_incharge_name 
                            FROM rssimyaccount_members 
                            WHERE position = 'Centre Incharge' 
                            AND filterstatus = 'Active'";

                    $result = pg_query($con, $query);
                    $row = pg_fetch_assoc($result);

                    $centre_incharge_email = $row['centre_incharge_email'];
                    $centre_incharge_name = $row['centre_incharge_name'];

                    if (!empty($centre_incharge_email)) {
                        // Split emails and names into arrays
                        $emails = explode(', ', $centre_incharge_email);
                        $names = explode(', ', $centre_incharge_name);

                        // Send individual emails
                        foreach ($emails as $index => $email) {
                            $name = $names[$index] ?? 'Centre Incharge';  // Fallback name if not matched
                            sendEmail("book_order_placed", [
                                "name" => $name,
                                "book_id" => $book_id,
                                "current_datetime" => date("d/m/Y g:i a"),
                            ], $email);
                        }
                    }

                    $_SESSION['message'] = "Order placed successfully!";
                    header("Location: " . $_SERVER['REQUEST_URI']);
                    exit();
                } else {
                    $_SESSION['error'] = "Error placing order: " . pg_last_error($con);
                    header("Location: " . $_SERVER['REQUEST_URI']);
                    exit();
                }
            } else {
                $_SESSION['error'] = "This user already has an active order for this book.";
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit();
            }
        } else {
            $_SESSION['error'] = "This book is currently unavailable.";
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit();
        }
    } else {
        $_SESSION['error'] = "Book not found or unavailable.";
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }
}

// Add new book
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_book'])) {
    $title = pg_escape_string($con, $_POST['title']);
    $author = pg_escape_string($con, $_POST['author']);
    $isbn = pg_escape_string($con, $_POST['isbn']);
    $publisher = pg_escape_string($con, $_POST['publisher']);
    $publication_year = isset($_POST['publication_year']) && $_POST['publication_year'] !== '' ? $_POST['publication_year'] : null;
    $category = pg_escape_string($con, $_POST['category']);
    $total_copies = $_POST['total_copies'] ?? 1;
    $location = pg_escape_string($con, $_POST['location']);
    $description = pg_escape_string($con, $_POST['description']);
    $cover_image = $_FILES['cover_image'] ?? null;

    // Initialize file links to null
    $doclink_cover_image = null;

    // Handle caste document upload
    if (!empty($cover_image['name'])) {
        $filename_cover_image = "cover_image_" . "$title" . "_" . time();
        $parent_cover_image = '17OlTCBRZBLpv-pgmsbIPBpDZkL62HaBW';
        $doclink_cover_image = uploadeToDrive($cover_image, $parent_cover_image, $filename_cover_image);
    }

    $query = "INSERT INTO books (title, author, isbn, publisher, publication_year, category, total_copies, available_copies, location, description, cover_image) 
              VALUES ($1, $2, $3, $4, $5, $6, $7, $7, $8, $9, $10) RETURNING book_id";

    $result = pg_query_params($con, $query, [
        $title,
        $author,
        $isbn,
        $publisher,
        $publication_year,
        $category,
        $total_copies,
        $location,
        $description,
        $doclink_cover_image
    ]);

    if ($result) {
        $book_id = pg_fetch_result($result, 0, 0);
        $_SESSION['message'] = "Book added successfully!";
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    } else {
        $_SESSION['error'] = "Error adding book: " . pg_last_error($con);
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }
}

// Update book
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_book'])) {
    $book_id = $_POST['book_id'];
    $title = pg_escape_string($con, $_POST['title']);
    $author = pg_escape_string($con, $_POST['author']);
    $isbn = pg_escape_string($con, $_POST['isbn']);
    $publisher = pg_escape_string($con, $_POST['publisher']);
    $publication_year = isset($_POST['publication_year']) && $_POST['publication_year'] !== '' ? $_POST['publication_year'] : null;
    $category = pg_escape_string($con, $_POST['category']);
    $total_copies = $_POST['total_copies'] ?? 1;
    $location = pg_escape_string($con, $_POST['location']);
    $description = pg_escape_string($con, $_POST['description']);
    // Get uploaded file from $_FILES
    $cover_image = $_FILES['cover_image'] ?? null;
    $status = $_POST['status'] ?? 'available';

    // Initialize file links to null
    $doclink_cover_image = null;

    // Handle caste document upload
    if (!empty($cover_image['name'])) {
        $filename_cover_image = "cover_image_" . "$book_id" . "_" . time();
        $parent_cover_image = '17OlTCBRZBLpv-pgmsbIPBpDZkL62HaBW';
        $doclink_cover_image = uploadeToDrive($cover_image, $parent_cover_image, $filename_cover_image);
    }


    // Calculate available copies
    $current = pg_fetch_assoc(pg_query_params($con, "SELECT total_copies, available_copies FROM books WHERE book_id = $1", [$book_id]));
    $diff = $total_copies - $current['total_copies'];
    $available_copies = $current['available_copies'] + $diff;

    $query = "UPDATE books SET 
              title = $1, 
              author = $2, 
              isbn = $3, 
              publisher = $4, 
              publication_year = $5, 
              category = $6, 
              total_copies = $7, 
              available_copies = $8, 
              location = $9, 
              description = $10,
              status = $11,
              cover_image = $12
              WHERE book_id = $13";

    $result = pg_query_params($con, $query, [
        $title,
        $author,
        $isbn,
        $publisher,
        $publication_year,
        $category,
        $total_copies,
        $available_copies,
        $location,
        $description,
        $status,
        $doclink_cover_image,
        $book_id
    ]);

    if ($result) {
        $_SESSION['message'] = "Book updated successfully!";
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    } else {
        $_SESSION['error'] = "Error updating book: " . pg_last_error($con);
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }
}

// Get filter values for dropdowns
$authors = pg_query($con, "SELECT DISTINCT author FROM books ORDER BY author");
$publishers = pg_query($con, "SELECT DISTINCT publisher FROM books WHERE publisher IS NOT NULL ORDER BY publisher");
$categories = pg_query($con, "SELECT DISTINCT category FROM books WHERE category IS NOT NULL ORDER BY category");

// Build base query for books
$query = "SELECT * FROM books WHERE 1=1";
$count_query = "SELECT COUNT(*) FROM books WHERE 1=1";

// Add filters to query
if (!empty($search)) {
    $query .= " AND (title ILIKE '%$search%' OR description ILIKE '%$search%')";
    $count_query .= " AND (title ILIKE '%$search%' OR description ILIKE '%$search%')";
}
if (!empty($author)) {
    $query .= " AND author = '$author'";
    $count_query .= " AND author = '$author'";
}
if (!empty($publisher)) {
    $query .= " AND publisher = '$publisher'";
    $count_query .= " AND publisher = '$publisher'";
}
if (!empty($category)) {
    $query .= " AND category = '$category'";
    $count_query .= " AND category = '$category'";
}

// Complete queries with ordering and pagination
$query .= " ORDER BY title LIMIT $limit";
$books_result = pg_query($con, $query);
$total_books = pg_fetch_result(pg_query($con, $count_query), 0, 0);
$has_more = ($total_books > $limit);

// Get statistics for dashboard
$stats = [
    'total_books' => pg_fetch_result(pg_query($con, "SELECT COUNT(*) FROM books"), 0, 0),
    'available_books' => pg_fetch_result(pg_query($con, "SELECT COUNT(*) FROM books WHERE available_copies > 0"), 0, 0),
    'pending_orders' => pg_fetch_result(pg_query($con, "SELECT COUNT(*) FROM book_orders WHERE status = 'pending'"), 0, 0),
    'issued_books' => pg_fetch_result(pg_query($con, "SELECT COUNT(*) FROM book_orders WHERE status = 'issued'"), 0, 0)
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management System</title>
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.0.0/css/all.min.css">
    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">
    <style>
        .book-card {
            transition: all 0.3s ease;
            border-radius: 10px;
            overflow: hidden;
            height: 100%;
        }

        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .stat-card {
            border-left: 4px solid #0d6efd;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .filter-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .badge-availability {
            font-size: 0.8rem;
            padding: 5px 10px;
            border-radius: 50px;
        }

        .load-more-btn {
            transition: all 0.3s ease;
        }

        .load-more-btn:hover {
            letter-spacing: 1px;
        }

        .book-cover {
            height: 200px;
            object-fit: contain;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
        }

        .book-cover img {
            max-height: 100%;
            max-width: 100%;
        }

        .unavailable-book {
            opacity: 0.7;
            position: relative;
        }

        /* .unavailable-book::after {
            content: "Not Available";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-15deg);
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 5px 15px;
            border-radius: 5px;
            font-weight: bold;
            font-size: 1.2rem;
        } */

        .pagination-controls {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }

        #new_category,
        #add_category_btn,
        #category_feedback {
            display: none;
        }

        #dashboard .card-body {
            flex: 1 1 auto;
            padding: var(--bs-card-spacer-y) var(--bs-card-spacer-x);
            color: var(--bs-card-color);
        }
    </style>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Library Dashboard</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Information Resource Center</a></li>
                    <li class="breadcrumb-item active">Library Dashboard</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section dashboard">
            <div class="row">

                <!-- Reports -->
                <div class="col-12">
                    <div class="card">

                        <div class="card-body" id="dashboard">
                            <br>
                            <div class="container-fluid">
                                <div class="row">
                                    <main class="col-md-12 px-md-4 py-3">
                                        <!-- Dashboard Header -->
                                        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4 border-bottom">
                                            <h1 class="h2">At a Glance</h1>
                                            <?php if ($is_admin): ?>
                                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBookModal">
                                                    <i class="bi bi-plus-circle"></i> Add Book
                                                </button>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Statistics Cards -->
                                        <div class="row mb-4">
                                            <div class="col-md-3">
                                                <div class="card stat-card h-100">
                                                    <div class="card-body">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <div>
                                                                <h6 class="text-muted mb-2">Total Books</h6>
                                                                <h3 class="mb-0"><?= number_format($stats['total_books']) ?></h3>
                                                            </div>
                                                            <div class="bg-primary bg-opacity-10 p-3 rounded">
                                                                <i class="fas fa-book text-primary"></i>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="card stat-card h-100">
                                                    <div class="card-body">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <div>
                                                                <h6 class="text-muted mb-2">Available</h6>
                                                                <h3 class="mb-0"><?= number_format($stats['available_books']) ?></h3>
                                                            </div>
                                                            <div class="bg-success bg-opacity-10 p-3 rounded">
                                                                <i class="fas fa-check-circle text-success"></i>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="card stat-card h-100">
                                                    <div class="card-body">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <div>
                                                                <h6 class="text-muted mb-2">Pending Orders</h6>
                                                                <h3 class="mb-0"><?= number_format($stats['pending_orders']) ?></h3>
                                                            </div>
                                                            <div class="bg-warning bg-opacity-10 p-3 rounded">
                                                                <i class="fas fa-clock text-warning"></i>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="card stat-card h-100">
                                                    <div class="card-body">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <div>
                                                                <h6 class="text-muted mb-2">Books Issued</h6>
                                                                <h3 class="mb-0"><?= number_format($stats['issued_books']) ?></h3>
                                                            </div>
                                                            <div class="bg-info bg-opacity-10 p-3 rounded">
                                                                <i class="fas fa-hand-holding text-info"></i>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <?php if ($message): ?>
                                            <div class="alert alert-success alert-dismissible fade show"><?= $message ?>
                                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($error): ?>
                                            <div class="alert alert-danger alert-dismissible fade show"><?= $error ?>
                                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Filter Section -->
                                        <div class="filter-section mb-4">
                                            <form method="GET" class="row g-3">
                                                <div class="col-md-4">
                                                    <label for="search" class="form-label">Search</label>
                                                    <div class="input-group">
                                                        <input type="text" class="form-control" id="search" name="search" placeholder="Title or description..." value="<?= htmlspecialchars($search) ?>">
                                                        <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                                                    </div>
                                                </div>
                                                <div class="col-md-2">
                                                    <label for="author" class="form-label">Author</label>
                                                    <select class="form-select" id="author" name="author">
                                                        <option value="">All Authors</option>
                                                        <?php while ($row = pg_fetch_assoc($authors)): ?>
                                                            <option value="<?= htmlspecialchars($row['author']) ?>" <?= $author == $row['author'] ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($row['author']) ?>
                                                            </option>
                                                        <?php endwhile; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-2">
                                                    <label for="publisher" class="form-label">Publisher</label>
                                                    <select class="form-select" id="publisher" name="publisher">
                                                        <option value="">All Publishers</option>
                                                        <?php while ($row = pg_fetch_assoc($publishers)): ?>
                                                            <option value="<?= htmlspecialchars($row['publisher']) ?>" <?= $publisher == $row['publisher'] ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($row['publisher']) ?>
                                                            </option>
                                                        <?php endwhile; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-2">
                                                    <label for="search_category" class="form-label">Category</label>
                                                    <select class="form-select" id="search_category" name="search_category">
                                                        <option value="">All Categories</option>
                                                        <?php while ($row = pg_fetch_assoc($categories)): ?>
                                                            <option value="<?= htmlspecialchars($row['category']) ?>" <?= $category == $row['category'] ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($row['category']) ?>
                                                            </option>
                                                        <?php endwhile; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-2 d-flex align-items-end">
                                                    <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                                                </div>
                                            </form>
                                        </div>

                                        <!-- Books Listing -->
                                        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4 mb-4">
                                            <?php while ($book = pg_fetch_assoc($books_result)): ?>
                                                <div class="col">
                                                    <div class="card book-card h-100 <?= ($book['available_copies'] <= 0 || $book['status'] == 'unavailable') ? 'unavailable-book' : '' ?>">
                                                        <div class="book-cover">
                                                            <?php if (!empty($book['cover_image'])): ?>
                                                                <!-- <img src="<?= htmlspecialchars($book['cover_image']) ?>" alt="<?= htmlspecialchars($book['title']) ?>"> -->
                                                                <?php
                                                                if (!empty($book['cover_image'])) {
                                                                    // Extract photo ID from the Google Drive link using a regular expression
                                                                    $pattern = '/\/d\/([a-zA-Z0-9_-]+)/';
                                                                    if (preg_match($pattern, $book['cover_image'], $matches)) {
                                                                        $photoID = $matches[1]; // Extracted file ID
                                                                        $previewUrl = "https://drive.google.com/file/d/{$photoID}/preview";
                                                                        echo '<iframe src="' . $previewUrl . '" width="150" height="200" frameborder="0" allow="autoplay" sandbox="allow-scripts allow-same-origin"></iframe>';
                                                                    } else {
                                                                        // If no valid photo ID is found
                                                                        echo "Invalid Google Drive photo URL.";
                                                                    }
                                                                }
                                                                ?>
                                                            <?php else: ?>
                                                                <div class="text-center p-3">
                                                                    <i class="bi bi-book" style="font-size: 3rem;"></i>
                                                                    <p class="mt-2 mb-0">No Cover Available</p>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="card-body">
                                                            <h5 class="card-title"><?= htmlspecialchars($book['title']) ?></h5>
                                                            <h6 class="card-subtitle mb-2 text-muted"><?= htmlspecialchars($book['author']) ?></h6>
                                                            <div class="d-flex justify-content-between mb-2">
                                                                <small class="text-muted">Publisher: <?= htmlspecialchars($book['publisher'] ?? 'N/A') ?></small>
                                                                <small class="text-muted">Year: <?= $book['publication_year'] ?? 'N/A' ?></small>
                                                            </div>
                                                            <p class="card-text text-truncate-3"><?= htmlspecialchars($book['description']) ?></p>
                                                            <div class="d-flex justify-content-between align-items-center mt-3">
                                                                <small class="text-muted">Copies: <?= $book['available_copies'] ?>/<?= $book['total_copies'] ?></small>
                                                                <small class="text-muted"><?= htmlspecialchars($book['category'] ?? 'N/A') ?></small>
                                                            </div>
                                                        </div>
                                                        <div class="card-footer bg-white border-0">
                                                            <?php if ($book['available_copies'] > 0 && $book['status'] != 'unavailable'): ?>
                                                                <button class="btn btn-sm btn-primary w-100" data-bs-toggle="modal" data-bs-target="#orderModal<?= $book['book_id'] ?>">
                                                                    <i class="bi bi-cart-plus"></i> Place Order
                                                                </button>
                                                            <?php else: ?>
                                                                <button class="btn btn-sm btn-secondary w-100" disabled>
                                                                    <i class="bi bi-cart-x"></i> Not Available
                                                                </button>
                                                            <?php endif; ?>

                                                            <?php if ($is_admin): ?>
                                                                <button class="btn btn-sm btn-outline-primary w-100 mt-2" data-bs-toggle="modal" data-bs-target="#editBookModal<?= $book['book_id'] ?>">
                                                                    <i class="bi bi-pencil"></i> Edit
                                                                </button>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Order Modal for each book -->
                                                <div class="modal fade" id="orderModal<?= $book['book_id'] ?>" tabindex="-1" aria-labelledby="orderModalLabel" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="orderModalLabel">Place Order</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <form method="POST">
                                                                <input type="hidden" name="book_id" value="<?= $book['book_id'] ?>">
                                                                <div class="modal-body">
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Book: <strong><?= htmlspecialchars($book['title']) ?></strong></label>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label for="student_id_<?= $book['book_id'] ?>" class="form-label">Student ID (optional)</label>
                                                                        <input type="text" class="form-control" id="student_id_<?= $book['book_id'] ?>" name="student_id"
                                                                            placeholder="Leave blank to order for yourself">
                                                                        <small class="text-muted">Only required if ordering for a student</small>
                                                                    </div>
                                                                    <div class="alert alert-info">
                                                                        <i class="bi bi-info-circle"></i> You are ordering as: <strong><?= $fullname ?></strong>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                    <button type="submit" name="place_order" class="btn btn-primary">Confirm Order</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Edit Book Modal (for admin) -->
                                                <?php if ($is_admin): ?>
                                                    <div class="modal fade" id="editBookModal<?= $book['book_id'] ?>" tabindex="-1" aria-labelledby="editBookModalLabel" aria-hidden="true">
                                                        <div class="modal-dialog modal-lg">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title" id="editBookModalLabel">Edit Book</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <form method="POST" action="#" enctype="multipart/form-data">
                                                                    <input type="hidden" name="book_id" value="<?= $book['book_id'] ?>">
                                                                    <div class="modal-body">
                                                                        <div class="row">
                                                                            <div class="col-md-6 mb-3">
                                                                                <label for="edit_title_<?= $book['book_id'] ?>" class="form-label">Title *</label>
                                                                                <input type="text" class="form-control" id="edit_title_<?= $book['book_id'] ?>" name="title" value="<?= htmlspecialchars($book['title']) ?>" required>
                                                                            </div>
                                                                            <div class="col-md-6 mb-3">
                                                                                <label for="edit_author_<?= $book['book_id'] ?>" class="form-label">Author *</label>
                                                                                <input type="text" class="form-control" id="edit_author_<?= $book['book_id'] ?>" name="author" value="<?= htmlspecialchars($book['author']) ?>" required>
                                                                            </div>
                                                                        </div>
                                                                        <div class="row">
                                                                            <div class="col-md-6 mb-3">
                                                                                <label for="edit_isbn_<?= $book['book_id'] ?>" class="form-label">ISBN</label>
                                                                                <input type="text" class="form-control" id="edit_isbn_<?= $book['book_id'] ?>" name="isbn" value="<?= htmlspecialchars($book['isbn']) ?>">
                                                                            </div>
                                                                            <div class="col-md-6 mb-3">
                                                                                <label for="edit_publisher_<?= $book['book_id'] ?>" class="form-label">Publisher</label>
                                                                                <input type="text" class="form-control" id="edit_publisher_<?= $book['book_id'] ?>" name="publisher" value="<?= htmlspecialchars($book['publisher']) ?>">
                                                                            </div>
                                                                        </div>
                                                                        <div class="row">
                                                                            <div class="col-md-4 mb-3">
                                                                                <label for="edit_publication_year_<?= $book['book_id'] ?>" class="form-label">Publication Year</label>
                                                                                <input type="number" class="form-control" id="edit_publication_year_<?= $book['book_id'] ?>" name="publication_year"
                                                                                    min="1800" max="<?= date('Y') ?>" value="<?= $book['publication_year'] ?>">
                                                                            </div>
                                                                            <div class="col-md-4 mb-3">
                                                                                <label for="edit_category_<?= $book['book_id'] ?>" class="form-label">Category</label>

                                                                                <select class="form-select category-select" id="edit_category_<?= $book['book_id'] ?>" name="category" required>
                                                                                    <option value="">Select a category</option>
                                                                                    <?php
                                                                                    $categories_query = pg_query($con, "SELECT DISTINCT category FROM books WHERE category IS NOT NULL AND category != '' ORDER BY category");
                                                                                    while ($cat = pg_fetch_assoc($categories_query)) {
                                                                                        $selected = ($book['category'] == $cat['category']) ? 'selected' : '';
                                                                                        echo '<option value="' . htmlspecialchars($cat['category']) . '" ' . $selected . '>' . htmlspecialchars($cat['category']) . '</option>';
                                                                                    }
                                                                                    ?>
                                                                                    <option value="__new__">+ Add New Category</option>
                                                                                </select>

                                                                                <div class="mt-2 new-category-group" style="display: none;">
                                                                                    <input type="text" class="form-control new-category-input" placeholder="Enter new category">
                                                                                    <div class="invalid-feedback new-category-feedback">Please enter a category name</div>
                                                                                    <button type="button" class="btn btn-outline-secondary mt-2 add-category-btn">Add Category</button>
                                                                                </div>
                                                                            </div>

                                                                            <div class="col-md-4 mb-3">
                                                                                <label for="edit_location_<?= $book['book_id'] ?>" class="form-label">Location</label>
                                                                                <input type="text" class="form-control" id="edit_location_<?= $book['book_id'] ?>" name="location" value="<?= htmlspecialchars($book['location']) ?>">
                                                                            </div>
                                                                        </div>
                                                                        <div class="row">
                                                                            <div class="col-md-6 mb-3">
                                                                                <label for="edit_total_copies_<?= $book['book_id'] ?>" class="form-label">Total Copies *</label>
                                                                                <input type="number" class="form-control" id="edit_total_copies_<?= $book['book_id'] ?>" name="total_copies" min="1" value="<?= $book['total_copies'] ?>" required>
                                                                            </div>
                                                                            <div class="col-md-6 mb-3">
                                                                                <label for="edit_status_<?= $book['book_id'] ?>" class="form-label">Status</label>
                                                                                <select class="form-select" id="edit_status_<?= $book['book_id'] ?>" name="status">
                                                                                    <option value="available" <?= $book['status'] == 'available' ? 'selected' : '' ?>>Available</option>
                                                                                    <option value="unavailable" <?= $book['status'] == 'unavailable' ? 'selected' : '' ?>>Unavailable</option>
                                                                                </select>
                                                                            </div>
                                                                        </div>
                                                                        <!-- <div class="mb-3">
                                                                            <label for="edit_cover_image_<?= $book['book_id'] ?>" class="form-label">Cover Image URL</label>
                                                                            <input type="text" class="form-control" id="edit_cover_image_<?= $book['book_id'] ?>" name="cover_image" value="<?= $book['cover_image'] ?>">
                                                                        </div> -->
                                                                        <div class="mb-3">
                                                                            <label for="edit_cover_image_<?= $book['book_id'] ?>" class="form-label">Upload New Cover Image</label>
                                                                            <input type="file" class="form-control" id="edit_cover_image_<?= $book['book_id'] ?>" name="cover_image" accept="image/*" onchange="compressImageBeforeUpload(this)">

                                                                            <?php if (!empty($book['cover_image'])): ?>
                                                                                <div class="mt-2">
                                                                                    <small>Current Image: <a href="<?= htmlspecialchars($book['cover_image']) ?>" target="_blank">View Cover Image</a></small>
                                                                                </div>
                                                                            <?php endif; ?>
                                                                        </div>

                                                                        <div class="mb-3">
                                                                            <label for="edit_description_<?= $book['book_id'] ?>" class="form-label">Description</label>
                                                                            <textarea class="form-control" id="edit_description_<?= $book['book_id'] ?>" name="description" rows="4"><?= htmlspecialchars($book['description']) ?></textarea>
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                        <button type="submit" name="update_book" class="btn btn-primary">Update Book</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endwhile; ?>
                                        </div>
                                    </main>
                                </div>
                            </div>
                        </div>
                    </div>
                </div><!-- End Reports -->
            </div>
        </section>

    </main><!-- End #main -->

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
    <!-- Add Book Modal (for admin) -->
    <?php if ($is_admin): ?>
        <div class="modal fade" id="addBookModal" tabindex="-1" aria-labelledby="addBookModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addBookModalLabel">Add New Book</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST" action="#" id="addBookForm" enctype="multipart/form-data">
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="title" class="form-label">Title *</label>
                                    <input type="text" class="form-control" id="title" name="title" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="author" class="form-label">Author *</label>
                                    <input type="text" class="form-control" id="author" name="author" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="isbn" class="form-label">ISBN</label>
                                    <input type="text" class="form-control" id="isbn" name="isbn">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="publisher" class="form-label">Publisher</label>
                                    <input type="text" class="form-control" id="publisher" name="publisher">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="publication_year" class="form-label">Publication Year</label>
                                    <input type="number" class="form-control" id="publication_year" name="publication_year"
                                        min="1800" max="<?= date('Y') ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="category" class="form-label">Category</label>
                                    <div class="input-group">
                                        <select class="form-select" id="category" name="category" required>
                                            <option value="">Select a category</option>
                                            <?php
                                            $categories_query = pg_query($con, "SELECT DISTINCT category FROM books WHERE category IS NOT NULL AND category != '' ORDER BY category");
                                            while ($cat = pg_fetch_assoc($categories_query)) {
                                                $selected = (!empty($_POST['category']) && $_POST['category'] == $cat['category']) ? 'selected' : '';
                                                echo '<option value="' . htmlspecialchars($cat['category']) . '" ' . $selected . '>' . htmlspecialchars($cat['category']) . '</option>';
                                            }
                                            ?>
                                            <option value="__new__">+ Add New Category</option>
                                        </select>
                                        <!-- New Category Input -->
                                        <div class="mb-3">
                                            <input type="text" id="new_category" class="form-control" placeholder="Enter new category">
                                            <div id="category_feedback" class="invalid-feedback"></div>
                                        </div>

                                        <!-- Add Category Button -->
                                        <div class="mb-3">
                                            <button type="button" id="add_category_btn" class="btn btn-outline-secondary">Add Category</button>
                                        </div>
                                    </div>
                                    <div id="category_feedback" class="invalid-feedback">Please select or create a category</div>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="location" class="form-label">Location</label>
                                    <input type="text" class="form-control" id="location" name="location">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="total_copies" class="form-label">Total Copies *</label>
                                    <input type="number" class="form-control" id="total_copies" name="total_copies" min="1" value="1" required>
                                </div>
                                <!-- <div class="col-md-6 mb-3">
                                    <label for="cover_image" class="form-label">Cover Image URL</label>
                                    <input type="text" class="form-control" id="cover_image" name="cover_image">
                                </div> -->
                                <div class="col-md-6 mb-3">
                                    <label for="cover_image" class="form-label">Upload Cover Image</label>
                                    <input type="file" class="form-control" id="cover_image" name="cover_image" accept="image/*" onchange="compressImageBeforeUpload(this)">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="4"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" name="add_book" class="btn btn-primary">Add Book</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assets_new/js/image-compressor-100kb.js"></script>
    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>
    <script>
        // Enhance filter dropdowns with search functionality
        document.querySelectorAll('select.form-select').forEach(select => {
            select.addEventListener('focus', function() {
                this.size = 5;
            });
            select.addEventListener('blur', function() {
                this.size = 1;
            });
            select.addEventListener('change', function() {
                this.size = 1;
                this.blur();
            });
        });
    </script>
    <!-- Custom JS -->
    <script>
        $(document).ready(function() {
            $('#category').on('change', function() {
                if ($(this).val() === '__new__') {
                    $(this).hide();
                    $('#new_category').val('').show().focus();
                    $('#add_category_btn').show();
                }
            });

            $('#add_category_btn').on('click', function() {
                var newCat = $('#new_category').val().trim();
                if (!newCat) {
                    $('#new_category').addClass('is-invalid');
                    $('#category_feedback').text('Please enter a category name').show();
                    return;
                }

                // Add new category
                $('#category')
                    .append($('<option>', {
                        value: newCat,
                        text: newCat
                    }))
                    .val(newCat)
                    .show();

                // Reset new category input
                $('#new_category').val('').hide().removeClass('is-invalid');
                $('#add_category_btn').hide();
                $('#category_feedback').hide();
            });

            $('#addBookForm').on('submit', function(e) {
                if ($('#category').val() === '__new__') {
                    var inputVal = $('#new_category').val().trim();
                    if (!inputVal) {
                        e.preventDefault();
                        $('#new_category').addClass('is-invalid').show().focus();
                        $('#category_feedback').text('Please enter a new category name').show();
                        $('#add_category_btn').show();
                        $('#category').hide();
                    }
                }
            });
        });
    </script>
    <script>
        $(document).ready(function() {
            // Handle category change
            $(document).on('change', '.category-select', function() {
                const $wrapper = $(this).closest('.col-md-4');
                if ($(this).val() === '__new__') {
                    $(this).hide();
                    $wrapper.find('.new-category-group').show();
                    $wrapper.find('.new-category-input').val('').focus();
                }
            });

            // Add new category
            $(document).on('click', '.add-category-btn', function() {
                const $wrapper = $(this).closest('.col-md-4');
                const newCategory = $wrapper.find('.new-category-input').val().trim();

                if (!newCategory) {
                    $wrapper.find('.new-category-input').addClass('is-invalid');
                    $wrapper.find('.new-category-feedback').show();
                    return;
                }

                const $select = $wrapper.find('.category-select');
                $select
                    .append($('<option>', {
                        value: newCategory,
                        text: newCategory
                    }))
                    .val(newCategory)
                    .show();

                $wrapper.find('.new-category-input').val('').removeClass('is-invalid');
                $wrapper.find('.new-category-feedback').hide();
                $wrapper.find('.new-category-group').hide();
            });
        });
    </script>

    <!-- Add this above your load more button -->
    <div class="text-center mb-2">
        <small class="text-muted">
            Showing <?= min($initial_limit + (($page - 1) * $load_more_limit), $total_books) ?> of <?= $total_books ?> books
        </small>
    </div>

    <!-- Your existing load more button -->
    <div class="d-flex justify-content-center mb-5">
        <?php if ($has_more): ?>
            <button class="btn btn-primary load-more-btn px-4 py-2" id="loadMoreButton">
                <i class="bi bi-arrow-down-circle"></i> Load More Books
            </button>
        <?php elseif ($total_books > $initial_limit): ?>
            <div class="alert alert-info text-center py-2">
                <i class="bi bi-info-circle"></i> That's all for now! Check back later for new ones.
            </div>
        <?php elseif ($total_books == 0): ?>
            <div class="alert alert-warning text-center py-2">
                <i class="bi bi-exclamation-triangle"></i> No books found matching your criteria
            </div>
        <?php endif; ?>
    </div>

    <script>
        $(document).ready(function() {
            // Initialize with the current page from URL
            let targetPage = <?= $page ?>;
            let currentLoadedPage = 1; // Tracks what we've actually loaded
            let isLoading = false;
            const initialLimit = <?= $initial_limit ?>;
            const loadMoreLimit = <?= $load_more_limit ?>;
            const totalBooks = <?= $total_books ?>;
            const $loadMoreBtn = $('#loadMoreButton');
            const $showingCount = $('.text-center.mb-2 small');

            // Function to update the showing count
            function updateShowingCount() {
                const totalShown = initialLimit + ((currentLoadedPage - 1) * loadMoreLimit);
                $showingCount.text(`Showing ${Math.min(totalShown, totalBooks)} of ${totalBooks} books`);
            }

            // Initialize count display
            updateShowingCount();

            // Function to load a specific page of books
            function loadPage(pageNumber) {
                if (isLoading) return Promise.reject();

                isLoading = true;
                const $btn = $loadMoreBtn;
                const wasButtonVisible = $btn.is(':visible');

                // Show loading state if button is visible
                if (wasButtonVisible) {
                    $btn.prop('disabled', true);
                    $btn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...');
                }

                return new Promise((resolve, reject) => {
                    $.ajax({
                        url: 'load_books.php',
                        type: 'GET',
                        data: {
                            page: pageNumber,
                            search: $('#search').val(),
                            author: $('#author').val(),
                            publisher: $('#publisher').val(),
                            search_category: $('#search_category').val()
                        },
                        success: function(data) {
                            if (data.trim()) {
                                $('.row-cols-1').append(data);
                                currentLoadedPage = pageNumber;
                                updateShowingCount();
                                resolve();
                            } else {
                                reject();
                            }
                        },
                        error: function() {
                            reject();
                        },
                        complete: function() {
                            isLoading = false;
                            if (wasButtonVisible) {
                                $btn.prop('disabled', false);
                                $btn.html('<i class="bi bi-arrow-down-circle"></i> Load More Books');
                            }
                        }
                    });
                });
            }

            // Load More button click handler
            $(document).on('click', '.load-more-btn', function(e) {
                e.preventDefault();
                const nextPage = currentLoadedPage + 1;

                loadPage(nextPage).then(() => {
                    // Update URL to reflect the new highest loaded page
                    const params = new URLSearchParams(window.location.search);
                    params.set('page', nextPage);
                    history.replaceState(null, '', '?' + params.toString());

                    // Check if we've reached the end
                    const totalLoaded = initialLimit + ((nextPage - 1) * loadMoreLimit);
                    if (totalLoaded >= totalBooks) {
                        $loadMoreBtn.replaceWith('<div class="alert alert-info text-center py-2">' +
                            '<i class="bi bi-info-circle"></i> That\'s all for now! Check back later for new ones.' +
                            '</div>');
                    }
                }).catch(() => {
                    alert('Error loading more books. Please try again.');
                });
            });

            // On initial load, if targetPage > 1, load all pages up to targetPage
            if (targetPage > 1) {
                // Show loading state immediately
                $loadMoreBtn.prop('disabled', true);
                $loadMoreBtn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...');

                // Load pages sequentially
                (async function() {
                    for (let p = 2; p <= targetPage; p++) {
                        try {
                            await loadPage(p);
                        } catch {
                            alert('Error loading books. Please refresh the page.');
                            break;
                        }
                    }

                    // Restore button state
                    const totalLoaded = initialLimit + ((targetPage - 1) * loadMoreLimit);
                    if (totalLoaded >= totalBooks) {
                        $loadMoreBtn.replaceWith('<div class="alert alert-info text-center py-2">' +
                            '<i class="bi bi-info-circle"></i> That\'s all for now! Check back later for new ones.' +
                            '</div>');
                    } else {
                        $loadMoreBtn.prop('disabled', false);
                        $loadMoreBtn.html('<i class="bi bi-arrow-down-circle"></i> Load More Books');
                    }

                    // Set URL to the target page (not changing during loading)
                    const params = new URLSearchParams(window.location.search);
                    params.set('page', targetPage);
                    history.replaceState(null, '', '?' + params.toString());
                })();
            }
        });
    </script>
</body>

</html>