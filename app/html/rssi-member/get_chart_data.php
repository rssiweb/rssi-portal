<?php
require_once __DIR__ . "/../../bootstrap.php";

header('Content-Type: application/json');

try {
    $selectedAcademicYear = isset($_GET['academic_year']) ? $_GET['academic_year'] : '';

    if (empty($selectedAcademicYear)) {
        $currentYear = date('Y');
        $currentMonth = date('n');
        $selectedAcademicYear = ($currentMonth >= 4) ? $currentYear . '-' . ($currentYear + 1) : ($currentYear - 1) . '-' . $currentYear;
    }

    list($startYear, $endYear) = explode('-', $selectedAcademicYear);

    $query = "SELECT 
                TO_CHAR(record_date, 'Mon') AS month,
                EXTRACT(MONTH FROM record_date) AS month_num,
                AVG(height_cm) AS avg_height,
                AVG(weight_kg) AS avg_weight
              FROM student_health_records
              WHERE record_date BETWEEN '$startYear-04-01' AND '$endYear-03-31'
              GROUP BY month, month_num
              ORDER BY month_num";

    $result = pg_query($con, $query);

    $months = [];
    $avg_height = [];
    $avg_weight = [];

    while ($row = pg_fetch_assoc($result)) {
        $months[] = $row['month'];
        $avg_height[] = round($row['avg_height'], 1);
        $avg_weight[] = round($row['avg_weight'], 1);
    }

    echo json_encode([
        'months' => $months,
        'avg_height' => $avg_height,
        'avg_weight' => $avg_weight
    ]);
} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
