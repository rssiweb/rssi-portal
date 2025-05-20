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
$poll_id = $_GET['poll_id'] ?? 0;

// Get user information
$user = get_user_id();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Poll System</title>
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Poll System</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Work</a></li>
                    <li class="breadcrumb-item active">Poll System</li>
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
                                    <div class="card mb-4">
                                        <div class="card-header bg-primary text-white">
                                            <h2>Create New Poll</h2>
                                        </div>
                                        <div class="card-body">
                                            <form action="polls.php" method="POST" class="mt-3">
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
                                                            <input type="text" class="form-control" name="options[]" required>
                                                            <button type="button" class="btn btn-danger remove-option">Remove</button>
                                                        </div>
                                                    </div>
                                                    <div class="mb-3 option-row">
                                                        <div class="input-group">
                                                            <input type="text" class="form-control" name="options[]" required>
                                                            <button type="button" class="btn btn-danger remove-option">Remove</button>
                                                        </div>
                                                    </div>
                                                </div>
                                                <button type="button" id="add-option" class="btn btn-secondary mb-3">Add Option</button>
                                                <button type="submit" class="btn btn-primary">Create Poll</button>
                                                <a href="polls.php" class="btn btn-outline-secondary">Cancel</a>
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

                                    <div class="card mb-4">
                                        <div class="card-header bg-info text-white">
                                            <h2><?= htmlspecialchars($poll['question']) ?></h2>
                                            <div class="text-white">
                                                Created: <?= date('M j, Y g:i A', strtotime($poll['created_at'])) ?> |
                                                Expires: <?= date('M j, Y g:i A', strtotime($poll['expires_at'])) ?>
                                                <?= $expired ? '<span class="badge bg-danger ms-2">Expired</span>' : '<span class="badge bg-success ms-2">Active</span>' ?>
                                                <?php if ($poll['is_multiple_choice'] == 't') { ?>
                                                    <span class="badge bg-warning ms-2">Multiple Choice</span>
                                                <?php } ?>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <?php if (!$has_voted && !$expired && $user): ?>
                                                <!-- Voting Form -->
                                                <form action="polls.php" method="POST" class="mt-3">
                                                    <input type="hidden" name="action" value="vote">
                                                    <input type="hidden" name="poll_id" value="<?= $poll_id ?>">
                                                    <div class="mb-3">
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
                                                    <button type="submit" class="btn btn-primary">Submit Vote</button>
                                                </form>
                                            <?php else: ?>
                                                <!-- Results Display -->
                                                <h4 class="mt-3">Results (<?= $total_votes ?> votes)</h4>
                                                <?php foreach ($results as $option): ?>
                                                    <div class="mb-3">
                                                        <div class="d-flex justify-content-between mb-1">
                                                            <span><?= htmlspecialchars($option['option_text']) ?></span>
                                                            <span><?= $option['vote_count'] ?> votes (<?= $total_votes > 0 ? round(($option['vote_count'] / $total_votes) * 100, 1) : 0 ?>%)</span>
                                                        </div>
                                                        <div class="progress" style="height: 20px;">
                                                            <div class="progress-bar" role="progressbar"
                                                                style="width: <?= $total_votes > 0 ? ($option['vote_count'] / $total_votes) * 100 : 0 ?>%"
                                                                aria-valuenow="<?= $option['vote_count'] ?>"
                                                                aria-valuemin="0"
                                                                aria-valuemax="<?= $total_votes ?>"></div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>

                                                <?php if ($has_voted): ?>
                                                    <div class="alert alert-success mt-3">You voted in this poll on <?= date('M j, Y g:i A', strtotime($poll['voted_at'])) ?>.</div>
                                                <?php elseif (!$expired && $user): ?>
                                                    <div class="alert alert-info mt-3">You haven't voted in this poll yet.</div>
                                                <?php endif; ?>
                                            <?php endif; ?>

                                            <a href="polls.php" class="btn btn-outline-secondary mt-3">Back to All Polls</a>
                                        </div>
                                    </div>

                                <?php else: ?>
                                    <!-- Main Polls Listing -->
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <h2>Current Polls</h2>
                                        <?php if ($role == 'Admin'): ?>
                                            <a href="polls.php?action=create" class="btn btn-primary">Create New Poll</a>
                                        <?php endif; ?>
                                    </div>

                                    <?php
                                    $polls = get_all_polls($con);

                                    if (empty($polls)): ?>
                                        <div class="alert alert-info">No polls available yet. Be the first to create one!</div>
                                    <?php else: ?>
                                        <?php foreach ($polls as $poll):
                                            $expired = is_poll_expired($con, $poll['poll_id']);
                                            $has_voted = $user ? has_voted($con, $poll['poll_id'], $user['id'], $user['is_student']) : false;
                                            $results = get_poll_results($con, $poll['poll_id']);
                                            $total_votes = array_sum(array_column($results, 'vote_count'));
                                        ?>
                                            <div class="card mb-4">
                                                <div class="card-header <?= $expired ? 'bg-secondary' : 'bg-primary' ?> text-white">
                                                    <h3><?= htmlspecialchars($poll['question']) ?></h3>
                                                    <div class="text-white">
                                                        Created by <?= htmlspecialchars($poll['creator_name']) ?> on <?= date('M j, Y g:i A', strtotime($poll['created_at'])) ?> |
                                                        Expires: <?= date('M j, Y g:i A', strtotime($poll['expires_at'])) ?>
                                                        <?= $expired ? '<span class="badge bg-danger ms-2">Expired</span>' : '<span class="badge bg-success ms-2">Active</span>' ?>
                                                        <?php if ($poll['is_multiple_choice'] == 't') { ?>
                                                            <span class="badge bg-warning ms-2">Multiple Choice</span>
                                                        <?php } ?>
                                                    </div>
                                                </div>
                                                <div class="card-body">
                                                    <?php if (!$has_voted && !$expired && $user): ?>
                                                        <!-- Voting Form -->
                                                        <form action="polls.php" method="POST" class="mt-3">
                                                            <input type="hidden" name="action" value="vote">
                                                            <input type="hidden" name="poll_id" value="<?= $poll['poll_id'] ?>">
                                                            <div class="mb-3">
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
                                                            <button type="submit" class="btn btn-primary">Submit Vote</button>
                                                            <a href="polls.php?poll_id=<?= $poll['poll_id'] ?>" class="btn btn-outline-secondary">View Details</a>
                                                        </form>
                                                    <?php else: ?>
                                                        <!-- Results Summary -->
                                                        <h5 class="mt-3">Results (<?= $total_votes ?> votes)</h5>
                                                        <?php foreach ($results as $option): ?>
                                                            <div class="mb-2">
                                                                <div class="d-flex justify-content-between">
                                                                    <span><?= htmlspecialchars($option['option_text']) ?></span>
                                                                    <span><?= $option['vote_count'] ?> votes (<?= $total_votes > 0 ? round(($option['vote_count'] / $total_votes) * 100, 1) : 0 ?>%)</span>
                                                                </div>
                                                                <div class="progress" style="height: 10px;">
                                                                    <div class="progress-bar" role="progressbar"
                                                                        style="width: <?= $total_votes > 0 ? ($option['vote_count'] / $total_votes) * 100 : 0 ?>%"
                                                                        aria-valuenow="<?= $option['vote_count'] ?>"
                                                                        aria-valuemin="0"
                                                                        aria-valuemax="<?= $total_votes ?>"></div>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>

                                                        <?php if ($has_voted): ?>
                                                            <div class="alert alert-success mt-3">You voted in this poll.</div>
                                                        <?php elseif (!$expired && $user): ?>
                                                            <div class="alert alert-info mt-3">You haven't voted in this poll yet.</div>
                                                        <?php endif; ?>

                                                        <a href="polls.php?poll_id=<?= $poll['poll_id'] ?>" class="btn btn-outline-secondary mt-2">View Details</a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div><!-- End Reports -->
            </div>
        </section>

    </main><!-- End #main -->

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>
    <script>
        // Add/remove poll options dynamically
        document.addEventListener('DOMContentLoaded', function() {
            const optionsContainer = document.getElementById('options-container');
            const addOptionBtn = document.getElementById('add-option');

            addOptionBtn.addEventListener('click', function() {
                const newOption = document.createElement('div');
                newOption.className = 'mb-3 option-row';
                newOption.innerHTML = `
                    <div class="input-group">
                        <input type="text" class="form-control" name="options[]" required>
                        <button type="button" class="btn btn-danger remove-option">Remove</button>
                    </div>
                `;
                optionsContainer.appendChild(newOption);
            });

            optionsContainer.addEventListener('click', function(e) {
                if (e.target.classList.contains('remove-option')) {
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