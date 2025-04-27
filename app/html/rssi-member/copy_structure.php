<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: salary_structure.php");
    exit;
}

$source_structure_id = $_POST['source_structure_id'];
$associate_number = $_POST['associate_number'];

// Fetch the source structure details
$structure_query = "SELECT structure_name, ctc_amount FROM salary_structures WHERE id = $1";
$structure_result = pg_query_params($con, $structure_query, [$source_structure_id]);
$source_structure = pg_fetch_assoc($structure_result);

if (!$source_structure) {
    $_SESSION['error_message'] = "Source salary structure not found";
    header("Location: salary_structure.php");
    exit;
}

// Fetch the components from the source structure with master component info
$query = "SELECT 
            sc.*, 
            mct.name as category_name,
            mct.component_type as category_type
          FROM salary_components sc
          JOIN salary_component_types mct ON sc.component_type_id = mct.id
          WHERE sc.structure_id = $1 
          ORDER BY sc.display_order, sc.id";
$result = pg_query_params($con, $query, [$source_structure_id]);
$components = pg_fetch_all($result) ?: [];

// Prepare components data for copying
$prepared_components = [];
foreach ($components as $component) {
    $prepared_components[] = [
        'master_id' => $component['master_component_id'],
        'category_id' => $component['component_type_id'],
        'category' => $component['category_name'],
        'name' => $component['component_name'],
        'monthly' => $component['monthly_amount'],
        'annual' => $component['annual_amount'],
        'is_deduction' => $component['category_type'] === 'Deduction',
        'order' => $component['display_order']
    ];
}

// Store all necessary data in session
$_SESSION['copy_source_data'] = [
    'components' => $prepared_components,
    'structure_name' => $_POST['new_structure_name'] ?: $source_structure['structure_name'] . ' (Copy)',
    'effective_from' => $_POST['new_effective_from'],
    'effective_till' => !empty($_POST['new_effective_till']) ? $_POST['new_effective_till'] : null,
    'ctc_amount' => $source_structure['ctc_amount']
];

header("Location: salary_structure.php?associate_number=" . urlencode($associate_number));
exit;
?>