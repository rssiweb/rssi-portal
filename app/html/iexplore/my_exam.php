<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util_iexplore.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();
?>
<?php

// Handle filters
$exam_name_filter = $_GET['exam_name'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// Build query with filters
$query = "SELECT 
            uexam.id AS attempt_id, 
            exam.id AS exam_id, 
            exam.name AS exam_name, 
            uexam.score, 
            TO_CHAR(uexam.created_at, 'DD-MM-YYYY HH24:MI:SS') AS exam_date
          FROM test_user_exams uexam
          JOIN test_exams exam ON uexam.exam_id = exam.id
          WHERE uexam.user_id = $1";

$conditions = [];
$params = [$id];
$paramIndex = 2;

if (!empty($exam_name_filter)) {
    $conditions[] = "exam.name ILIKE $" . $paramIndex++;
    $params[] = '%' . $exam_name_filter . '%';
}

if (!empty($start_date)) {
    $conditions[] = "uexam.created_at >= $" . $paramIndex++;
    $params[] = $start_date;
}

if (!empty($end_date)) {
    $conditions[] = "uexam.created_at <= $" . $paramIndex++;
    $params[] = $end_date;
}

if ($conditions) {
    $query .= " AND " . implode(" AND ", $conditions);
}

$query .= " ORDER BY uexam.created_at DESC";

$result = pg_query_params($con, $query, $params);

if (!$result) {
    echo "Error in fetching exam data.";
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Exams</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-4">
        <h2>My Exams</h2>

        <!-- Filter Form -->
        <form method="get" class="mb-4">
            <div class="row">
                <div class="col-md-3">
                    <input type="text" name="exam_name" class="form-control" placeholder="Search Exam Name" value="<?= htmlspecialchars($exam_name_filter) ?>">
                </div>
                <div class="col-md-3">
                    <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($start_date) ?>">
                </div>
                <div class="col-md-3">
                    <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($end_date) ?>">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                </div>
            </div>
        </form>

        <!-- Exam Records Table -->
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>Attempt ID</th>
                    <th>Exam ID</th>
                    <th>Exam Name</th>
                    <th>Date Taken</th>
                    <th>Score</th>
                    <th>Analysis</th>
                </tr>
            </thead>
            <tbody>
                <?php if (pg_num_rows($result) > 0): ?>
                    <?php while ($row = pg_fetch_assoc($result)): ?>
                        <tr>
                            <td><?= $row['attempt_id'] ?></td>
                            <td><?= $row['exam_id'] ?></td>
                            <td><?= $row['exam_name'] ?></td>
                            <td><?= $row['exam_date'] ?></td>
                            <td><?= $row['score'] ?></td>
                            <td>
                                <a href="exam_analysis.php?user_exam_id=<?= $row['attempt_id'] ?>" class="btn btn-info btn-sm">View Analysis</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center">No exams found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>

</html>