<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$query = "SELECT 
            ct.id as type_id,
            ct.name as type_name,
            ct.component_type,
            mc.id as component_id,
            mc.name as component_name
          FROM salary_component_types ct
          JOIN salary_components_master mc ON ct.id = mc.component_type_id
          WHERE mc.is_active = TRUE
          ORDER BY ct.display_order, mc.name";

$result = pg_query($con, $query);
$options = ['Earning' => [], 'Deduction' => []];

while ($row = pg_fetch_assoc($result)) {
    $type = $row['component_type'];
    $options[$type][] = $row['component_name'];
}

header('Content-Type: application/json');
echo json_encode(['success' => true, 'options' => $options]);
?>