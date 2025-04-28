<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$associate_number = $_POST['associate_number'] ?? '';
$pay_month = $_POST['pay_month'] ?? '';
$pay_year = $_POST['pay_year'] ?? '';

if (empty($associate_number)) {
    echo json_encode(['success' => false, 'message' => 'Associate number required']);
    exit;
}

// Find the active salary structure for this period
$effective_date = "$pay_year-$pay_month-01";
$query = "
    SELECT sc.id, sc.component_name, sc.monthly_amount, sc.is_deduction, sct.component_type
    FROM salary_components sc
    JOIN salary_structures ss ON sc.structure_id = ss.id
    JOIN salary_component_types sct ON sc.component_type_id = sct.id
    WHERE ss.associate_number = $1
    AND ss.effective_from <= $2
    AND (ss.effective_till IS NULL OR ss.effective_till >= $2)
    AND sc.monthly_amount IS NOT NULL
    AND sc.monthly_amount > 0
    ORDER BY ss.effective_from DESC, sc.display_order
";

$result = pg_query_params($con, $query, [$associate_number, $effective_date]);

if (!$result) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

$components = pg_fetch_all($result) ?: [];

// Format the response
$response = [
    'success' => true,
    'components' => array_map(function($component) {
        return [
            'component_name' => $component['component_name'],
            'monthly_amount' => $component['monthly_amount'],
            'is_deduction' => $component['is_deduction'] === 't',
            'component_type' => $component['component_type']
        ];
    }, $components)
];

header('Content-Type: application/json');
echo json_encode($response);
?>