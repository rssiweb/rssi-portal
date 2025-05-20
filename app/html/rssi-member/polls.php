<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");
include("poll_functions.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_poll':
                $user = get_user_id();

                if (!$user) {
                    $_SESSION['error_message'] = "You must be logged in to create a poll.";
                    header("Location: polls.php");
                    exit;
                }

                // Check if user is Admin
                if ($role !== 'Admin') {
                    $_SESSION['error_message'] = "You do not have access privileges to create a poll.";
                    header("Location: polls.php");
                    exit;
                }

                $question = pg_escape_string($con, $_POST['question']);
                $expires_at = pg_escape_string($con, $_POST['expires_at']);
                $options = $_POST['options'];
                $is_multiple_choice = isset($_POST['is_multiple_choice']) ? true : false;

                $poll_id = create_poll($con, $question, $expires_at, $options, $user['id'], $is_multiple_choice);

                if ($poll_id) {
                    $_SESSION['success_message'] = "Poll created successfully!";
                    header("Location: polls.php?poll_id=$poll_id");
                } else {
                    $_SESSION['error_message'] = "Failed to create poll.";
                    header("Location: polls.php");
                }
                exit;


            case 'vote':
                $user = get_user_id();
                if (!$user) {
                    $_SESSION['error_message'] = "You must be logged in to vote.";
                    header("Location: polls.php");
                    exit;
                }

                $poll_id = $_POST['poll_id'];
                $option_ids = isset($_POST['option_id']) ? (is_array($_POST['option_id']) ? $_POST['option_id'] : [$_POST['option_id']]) : [];

                if (empty($option_ids)) {
                    $_SESSION['error_message'] = "Please select at least one option.";
                    header("Location: polls.php?poll_id=$poll_id");
                    exit;
                }

                if (is_poll_expired($con, $poll_id)) {
                    $_SESSION['error_message'] = "This poll has expired and no longer accepts votes.";
                    header("Location: polls.php?poll_id=$poll_id");
                    exit;
                }

                if (has_voted($con, $poll_id, $user['id'], $user['is_student'])) {
                    $_SESSION['error_message'] = "You have already voted in this poll.";
                    header("Location: polls.php?poll_id=$poll_id");
                    exit;
                }

                $success = true;
                foreach ($option_ids as $option_id) {
                    if (!record_vote($con, $poll_id, $option_id, $user['id'], $user['is_student'])) {
                        $success = false;
                        break;
                    }
                }

                if ($success) {
                    $_SESSION['success_message'] = "Your vote has been recorded!";
                } else {
                    $_SESSION['error_message'] = "Failed to record your vote.";
                }
                header("Location: polls.php?poll_id=$poll_id");
                exit;
        }
    }
}

// Get current view
$action = $_GET['action'] ?? '';
if ($action === 'create' && $role !== 'Admin') {
    echo "<script>
        alert('You do not have permission to create a poll.');
        window.location.href = 'polls.php';
    </script>";
    exit;
}
$poll_id = $_GET['poll_id'] ?? 0;

// Get user information
$user = get_user_id();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Opinion Poll Archives</title>
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">
    <style>
        /* Scoped poll styles with ID selector */
        #poll-system-container {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif !important;
        }

        #poll-system-container .poll-item {
            padding: 20px 0;
            border-bottom: 1px solid #e0e0e0;
        }

        #poll-system-container .poll-item:last-child {
            border-bottom: none;
        }

        #poll-system-container .poll-question {
            font-size: 1.1rem;
            font-weight: 500;
            margin-bottom: 12px;
            color: #333;
        }

        #poll-system-container .poll-meta {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        #poll-system-container .status-badge,
        #poll-system-container .type-badge {
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
        }

        #poll-system-container .status-badge.active {
            background-color: #e8f5e9;
            color: #2e7d32;
        }

        #poll-system-container .status-badge.expired {
            background-color: #ffebee;
            color: #c62828;
        }

        #poll-system-container .type-badge {
            background-color: #e3f2fd;
            color: #1565c0;
        }

        #poll-system-container .poll-results {
            margin-top: 16px;
        }

        #poll-system-container .poll-options {
            margin: 16px 0;
        }

        #poll-system-container .results-header {
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 12px;
            color: #444;
        }

        #poll-system-container .poll-option {
            margin-bottom: 10px;
        }

        #poll-system-container .option-text {
            font-size: 0.9rem;
            margin-bottom: 4px;
            color: #555;
        }

        #poll-system-container .progress {
            height: 6px;
            background-color: #f0f0f0;
            border-radius: 3px;
            overflow: hidden;
        }

        #poll-system-container .progress-bar {
            height: 100%;
            background-color: #1976d2;
        }

        #poll-system-container .action-buttons {
            margin-top: 20px;
        }

        /* For the form checkboxes/radios - scoped */
        #poll-system-container .form-check-input {
            margin-top: 0.25rem;
        }

        #poll-system-container .form-check-label {
            margin-left: 0.5rem;
        }

        /* Create poll form styles */
        #poll-system-container .create-poll-form {
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 20px;
            background: white;
        }
    </style>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Opinion Poll Archives</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Work</a></li>
                    <li class="breadcrumb-item active">Opinion Poll Archives</li>
                </ol>
            </nav>
        </div>

        <section class="section dashboard">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div id="poll-system-container" class="container mt-3">
                                <?php if (isset($_SESSION['success_message'])): ?>
                                    <div class="alert alert-success"><?= $_SESSION['success_message'] ?></div>
                                    <?php unset($_SESSION['success_message']); ?>
                                <?php endif; ?>

                                <?php if (isset($_SESSION['error_message'])): ?>
                                    <div class="alert alert-danger"><?= $_SESSION['error_message'] ?></div>
                                    <?php unset($_SESSION['error_message']); ?>
                                <?php endif; ?>

                                <?php if ($action == 'create'): ?>
                                    <!-- Create Poll Form -->
                                    <div class="create-poll-form">
                                        <div class="card-header">
                                            <h3>Create New Poll</h3>
                                        </div>
                                        <div class="card-body">
                                            <form action="polls.php" method="POST">
                                                <input type="hidden" name="action" value="create_poll">
                                                <div class="mb-3">
                                                    <label for="question" class="form-label">Question</label>
                                                    <input type="text" class="form-control" id="question" name="question" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="expires_at" class="form-label">Expiry Date/Time</label>
                                                    <input type="datetime-local" class="form-control" id="expires_at" name="expires_at" required>
                                                </div>
                                                <div class="mb-3 form-check">
                                                    <input type="checkbox" class="form-check-input" id="is_multiple_choice" name="is_multiple_choice">
                                                    <label class="form-check-label" for="is_multiple_choice">Allow multiple choice voting</label>
                                                </div>
                                                <div id="options-container">
                                                    <div class="mb-3 option-row">
                                                        <div class="input-group">
                                                            <input type="text" class="form-control" name="options[]" placeholder="Option text" required>
                                                            <button type="button" class="btn btn-outline-danger remove-option">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div class="mb-3 option-row">
                                                        <div class="input-group">
                                                            <input type="text" class="form-control" name="options[]" placeholder="Option text" required>
                                                            <button type="button" class="btn btn-outline-danger remove-option">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="d-flex gap-2">
                                                    <button type="button" id="add-option" class="btn btn-outline-secondary">
                                                        <i class="bi bi-plus-circle"></i> Add Option
                                                    </button>
                                                    <button type="submit" class="btn btn-primary">Create Poll</button>
                                                    <a href="polls.php" class="btn btn-outline-secondary">Cancel</a>
                                                </div>
                                            </form>
                                        </div>
                                    </div>

                                <?php elseif ($poll_id): ?>
                                    <!-- Poll Detail View -->
                                    <?php
                                    $poll = get_poll($con, $poll_id);
                                    if (!$poll) {
                                        echo '<div class="alert alert-danger">Poll not found.</div>';
                                        exit;
                                    }

                                    $has_voted = $user ? has_voted($con, $poll_id, $user['id'], $user['is_student']) : false;
                                    $expired = is_poll_expired($con, $poll_id);
                                    $results = get_poll_results($con, $poll_id);
                                    $total_votes = array_sum(array_column($results, 'vote_count'));
                                    ?>

                                    <div class="poll-item">
                                        <small class="text-muted d-block mb-2">
                                            <?= date('M j, Y', strtotime($poll['created_at'])) ?>
                                        </small>

                                        <p class="poll-question"><?= htmlspecialchars($poll['question']) ?></p>

                                        <div class="poll-meta">
                                            <span class="creator">Created by <?= htmlspecialchars($poll['creator_name']) ?></span>
                                            <span class="status-badge <?= $expired ? 'expired' : 'active' ?>">
                                                <?= $expired ? 'Closed' : 'Active' ?>
                                            </span>
                                            <?php if ($poll['is_multiple_choice'] == 't'): ?>
                                                <span class="type-badge">Multiple Choice</span>
                                            <?php endif; ?>
                                        </div>

                                        <?php if (!$has_voted && !$expired && $user): ?>
                                            <!-- Voting Form -->
                                            <form action="polls.php" method="POST">
                                                <input type="hidden" name="action" value="vote">
                                                <input type="hidden" name="poll_id" value="<?= $poll_id ?>">
                                                <div class="poll-options">
                                                    <?php foreach ($results as $option): ?>
                                                        <div class="form-check mb-3">
                                                            <input class="form-check-input"
                                                                type="<?= $poll['is_multiple_choice'] == 't' ? 'checkbox' : 'radio' ?>"
                                                                name="<?= $poll['is_multiple_choice'] == 't' ? 'option_id[]' : 'option_id' ?>"
                                                                id="option_<?= $option['option_id'] ?>"
                                                                value="<?= $option['option_id'] ?>"
                                                                <?= $poll['is_multiple_choice'] != 't' ? 'required' : '' ?>>
                                                            <label class="form-check-label" for="option_<?= $option['option_id'] ?>">
                                                                <?= htmlspecialchars($option['option_text']) ?>
                                                            </label>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                                <div class="action-buttons">
                                                    <button type="submit" class="btn btn-primary">Submit Vote</button>
                                                </div>
                                            </form>
                                        <?php else: ?>
                                            <!-- Results Display -->
                                            <?php if ($has_voted): ?>
                                                <div class="alert alert-success">
                                                    <i class="bi bi-check-circle"></i> You already voted in this poll on <?= date('M j, Y g:i A', strtotime($poll['voted_at'])) ?>
                                                </div>
                                            <?php elseif ($user): ?>
                                                <div class="alert alert-info">
                                                    <i class="bi bi-info-circle"></i> You haven't voted in this poll yet.
                                                </div>
                                            <?php endif; ?>

                                            <div class="poll-results">
                                                <div class="results-header">Results (<?= $total_votes ?> votes)</div>

                                                <?php foreach ($results as $option): ?>
                                                    <div class="poll-option">
                                                        <div class="option-text"><?= htmlspecialchars($option['option_text']) ?> (<?= $option['vote_count'] ?> votes, <?= $total_votes > 0 ? round(($option['vote_count'] / $total_votes) * 100, 1) : 0 ?>%)</div>
                                                        <div class="progress">
                                                            <div class="progress-bar" style="width: <?= $total_votes > 0 ? ($option['vote_count'] / $total_votes) * 100 : 0 ?>%"></div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php
                                        $current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
                                        $poll = get_poll($con, $poll_id);
                                        // ... rest of your poll detail code ...
                                        ?>
                                        <div class="action-buttons mt-3">
                                            <a href="polls.php?page=<?= $current_page ?>" class="btn btn-outline-secondary">Back to All Polls (Page <?= $current_page ?>)</a>
                                        </div>
                                    </div>

                                <?php else: ?>
                                    <!-- Main Polls Listing -->
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <div></div> <!-- Empty left side to preserve spacing -->
                                        <?php if ($role == 'Admin'): ?>
                                            <a href="polls.php?action=create" class="btn btn-outline-primary">
                                                <i class="bi bi-plus-circle"></i> Create New Poll
                                            </a>
                                        <?php endif; ?>
                                    </div>

                                    <?php
                                    // Get current page from URL, default to 1
                                    $current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
                                    $per_page = 3;

                                    // Get current user info
                                    $user = get_user_id();
                                    $user_id = $user ? $user['id'] : null;
                                    $is_student = $user ? $user['is_student'] : false;

                                    // Get paginated polls
                                    $polls_data = get_paginated_polls($con, $current_page, $per_page, $user_id, $is_student);
                                    $polls = $polls_data['polls'];
                                    $total_pages = $polls_data['total_pages'];

                                    if (empty($polls)): ?>
                                        <div class="alert alert-info">No polls available yet.</div>
                                    <?php else: ?>
                                        <div class="poll-list">
                                            <?php foreach ($polls as $poll):
                                                $expired = is_poll_expired($con, $poll['poll_id']);
                                                $has_voted = isset($poll['has_voted']) ? (bool)$poll['has_voted'] : false;
                                                $results = get_poll_results($con, $poll['poll_id']);
                                                $total_votes = array_sum(array_column($results, 'vote_count'));
                                            ?>
                                                <div class="poll-item">
                                                    <small class="text-muted d-block mb-2">
                                                        <?= date('M j, Y', strtotime($poll['created_at'])) ?>
                                                    </small>

                                                    <p class="poll-question"><?= htmlspecialchars($poll['question']) ?></p>

                                                    <div class="poll-meta">
                                                        <span class="creator">Created by <?= htmlspecialchars($poll['creator_name']) ?></span>
                                                        <span class="status-badge <?= $expired ? 'expired' : 'active' ?>">
                                                            <?= $expired ? 'Closed' : 'Active' ?>
                                                        </span>
                                                        <?php if ($poll['is_multiple_choice'] == 't'): ?>
                                                            <span class="type-badge">Multiple Choice</span>
                                                        <?php endif; ?>
                                                    </div>

                                                    <?php if (!$has_voted && !$expired && $user): ?>
                                                        <!-- Voting Form in Listing -->
                                                        <form action="polls.php" method="POST">
                                                            <input type="hidden" name="action" value="vote">
                                                            <input type="hidden" name="poll_id" value="<?= $poll['poll_id'] ?>">
                                                            <div class="poll-options">
                                                                <?php foreach ($results as $option): ?>
                                                                    <div class="form-check mb-2">
                                                                        <input class="form-check-input"
                                                                            type="<?= $poll['is_multiple_choice'] == 't' ? 'checkbox' : 'radio' ?>"
                                                                            name="<?= $poll['is_multiple_choice'] == 't' ? 'option_id[]' : 'option_id' ?>"
                                                                            id="option_<?= $option['option_id'] ?>"
                                                                            value="<?= $option['option_id'] ?>"
                                                                            <?= $poll['is_multiple_choice'] != 't' ? 'required' : '' ?>>
                                                                        <label class="form-check-label" for="option_<?= $option['option_id'] ?>">
                                                                            <?= htmlspecialchars($option['option_text']) ?>
                                                                        </label>
                                                                    </div>
                                                                <?php endforeach; ?>
                                                            </div>
                                                            <div class="action-buttons">
                                                                <button type="submit" class="btn btn-primary">Submit Vote</button>
                                                            </div>
                                                        </form>
                                                    <?php else: ?>
                                                        <!-- Results Display in Listing -->

                                                        <div class="poll-results">
                                                            <div class="results-header">Results (<?= $total_votes ?> votes)</div>

                                                            <?php foreach (array_slice($results, 0, 3) as $option): ?>
                                                                <div class="poll-option">
                                                                    <div class="option-text">
                                                                        <?= htmlspecialchars($option['option_text']) ?>
                                                                        (<?= $total_votes > 0 ? round(($option['vote_count'] / $total_votes) * 100, 1) : 0 ?>%)
                                                                    </div>
                                                                    <div class="progress">
                                                                        <div class="progress-bar" style="width: <?= $total_votes > 0 ? ($option['vote_count'] / $total_votes) * 100 : 0 ?>%"></div>
                                                                    </div>

                                                                </div>
                                                            <?php endforeach; ?>

                                                            <?php if (count($results) > 3): ?>
                                                                <div class="text-center mt-2">
                                                                    <small>+<?= count($results) - 3 ?> more options</small>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>

                                                        <div class="action-buttons">
                                                            <!-- In your poll listing where you have the "View Details" button -->
                                                            <a href="polls.php?poll_id=<?= $poll['poll_id'] ?>&page=<?= $current_page ?>" class="btn btn-outline-secondary">View Details</a>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <!-- Pagination Controls -->
                                        <nav aria-label="Poll pagination">
                                            <ul class="pagination justify-content-center mt-4">
                                                <!-- Previous Button -->
                                                <li class="page-item <?= $current_page == 1 ? 'disabled' : '' ?>">
                                                    <a class="page-link" href="polls.php?page=<?= $current_page - 1 ?>" aria-label="Previous">
                                                        <span aria-hidden="true">&laquo; Previous</span>
                                                    </a>
                                                </li>

                                                <!-- Page Numbers -->
                                                <?php
                                                // Show up to 5 page numbers around current page
                                                $start_page = max(1, $current_page - 2);
                                                $end_page = min($total_pages, $current_page + 2);

                                                // Adjust if we're at the beginning or end
                                                if ($current_page <= 3) {
                                                    $end_page = min(5, $total_pages);
                                                }
                                                if ($current_page >= $total_pages - 2) {
                                                    $start_page = max(1, $total_pages - 4);
                                                }

                                                // Always show first page if not in range
                                                if ($start_page > 1) {
                                                    echo '<li class="page-item"><a class="page-link" href="polls.php?page=1">1</a></li>';
                                                    if ($start_page > 2) {
                                                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                                    }
                                                }

                                                for ($i = $start_page; $i <= $end_page; $i++): ?>
                                                    <li class="page-item <?= $i == $current_page ? 'active' : '' ?>">
                                                        <a class="page-link" href="polls.php?page=<?= $i ?>"><?= $i ?></a>
                                                    </li>
                                                <?php endfor;

                                                // Always show last page if not in range
                                                if ($end_page < $total_pages) {
                                                    if ($end_page < $total_pages - 1) {
                                                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                                    }
                                                    echo '<li class="page-item"><a class="page-link" href="polls.php?page=' . $total_pages . '">' . $total_pages . '</a></li>';
                                                }
                                                ?>

                                                <!-- Next Button -->
                                                <li class="page-item <?= $current_page == $total_pages ? 'disabled' : '' ?>">
                                                    <a class="page-link" href="polls.php?page=<?= $current_page + 1 ?>" aria-label="Next">
                                                        <span aria-hidden="true">Next &raquo;</span>
                                                    </a>
                                                </li>
                                            </ul>
                                        </nav>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const optionsContainer = document.getElementById('options-container');
            const addOptionBtn = document.getElementById('add-option');

            addOptionBtn.addEventListener('click', function() {
                const newOption = document.createElement('div');
                newOption.className = 'mb-3 option-row';
                newOption.innerHTML = `
                    <div class="input-group">
                        <input type="text" class="form-control" name="options[]" placeholder="Option text" required>
                        <button type="button" class="btn btn-outline-danger remove-option">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                `;
                optionsContainer.appendChild(newOption);
            });

            optionsContainer.addEventListener('click', function(e) {
                if (e.target.classList.contains('remove-option') || e.target.closest('.remove-option')) {
                    if (document.querySelectorAll('.option-row').length > 1) {
                        e.target.closest('.option-row').remove();
                    } else {
                        alert('A poll must have at least one option.');
                    }
                }
            });
        });
    </script>
</body>

</html>