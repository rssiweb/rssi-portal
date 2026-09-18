<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

header('Content-Type: application/json');

if (!isLoggedIn("aid")) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

// -----------------------------------------------------------------------------
// Mode detection
//
//  - "units_for_item"  : POST/GET item_id            → returns the units that
//                        item belongs to (used by the Add/Edit Price form to
//                        auto-fill / lock the Unit field).
//
//  - "validate"        : POST unit_id + item_ids[]   → returns which of the
//                        given items do NOT belong to the chosen unit
//                        (used by the Filter Prices form).
// -----------------------------------------------------------------------------
$item_id_single = 0;
if (isset($_POST['item_id']))      $item_id_single = intval($_POST['item_id']);
elseif (isset($_GET['item_id']))   $item_id_single = intval($_GET['item_id']);

$unit_id = 0;
if (isset($_POST['unit_id']))      $unit_id = intval($_POST['unit_id']);
elseif (isset($_GET['unit_id']))   $unit_id = intval($_GET['unit_id']);

$item_ids_raw = $_POST['item_ids'] ?? $_GET['item_ids'] ?? [];
if (!is_array($item_ids_raw)) {
    $item_ids_raw = [$item_ids_raw];
}
$item_ids = array_values(array_filter(array_map('intval', $item_ids_raw), fn($v) => $v > 0));

// ---------- Mode 1: units for a single item ----------
if ($item_id_single > 0 && empty($item_ids) && $unit_id === 0) {
    $sql = "
        SELECT DISTINCT u.unit_id, u.unit_name
        FROM stock_add a
        JOIN stock_item_unit u ON u.unit_id = a.unit_id
        WHERE a.item_id = \$1
        ORDER BY u.unit_name
    ";
    $res = pg_query_params($con, $sql, [$item_id_single]);

    if ($res === false) {
        echo json_encode([
            'units' => [],
            'error' => 'Query failed: ' . pg_last_error($con),
        ]);
        exit;
    }

    $units = [];
    while ($row = pg_fetch_assoc($res)) {
        $units[] = [
            'id'   => (int)$row['unit_id'],
            'name' => $row['unit_name'],
        ];
    }

    echo json_encode(['units' => $units]);
    exit;
}

// ---------- Mode 2: validate item(s) against a unit ----------
if ($unit_id > 0 && !empty($item_ids)) {
    // Unit display name
    $unit_name = '';
    $u_res = pg_query_params($con, "SELECT unit_name FROM stock_item_unit WHERE unit_id = \$1", [$unit_id]);
    if ($u_res && ($u = pg_fetch_assoc($u_res))) {
        $unit_name = $u['unit_name'];
    }

    $in_list = implode(',', $item_ids); // safe: all ints

    $belongs_sql = "
        SELECT DISTINCT a.item_id
        FROM stock_add a
        WHERE a.unit_id = \$1
          AND a.item_id IN ($in_list)
    ";
    $belongs_res = pg_query_params($con, $belongs_sql, [$unit_id]);

    if ($belongs_res === false) {
        echo json_encode([
            'unit_name'  => $unit_name,
            'mismatched' => [],
            'error'      => 'Validation query failed: ' . pg_last_error($con),
        ]);
        exit;
    }

    $belongs_ids = [];
    while ($row = pg_fetch_assoc($belongs_res)) {
        $belongs_ids[(int)$row['item_id']] = true;
    }

    $mismatched_ids = array_values(array_filter($item_ids, fn($id) => !isset($belongs_ids[$id])));

    if (empty($mismatched_ids)) {
        echo json_encode([
            'unit_name'  => $unit_name,
            'mismatched' => [],
        ]);
        exit;
    }

    $mm_list = implode(',', $mismatched_ids);
    $name_res = pg_query($con, "SELECT item_id, item_name FROM stock_item WHERE item_id IN ($mm_list) ORDER BY item_name");

    $mismatched = [];
    if ($name_res) {
        while ($row = pg_fetch_assoc($name_res)) {
            $mismatched[] = [
                'id'   => (int)$row['item_id'],
                'name' => $row['item_name'],
            ];
        }
    }

    echo json_encode([
        'unit_name'  => $unit_name,
        'mismatched' => $mismatched,
    ]);
    exit;
}

// ---------- Neither mode matched ----------
echo json_encode(['error' => 'Invalid request parameters']);
