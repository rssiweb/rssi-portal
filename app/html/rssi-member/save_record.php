<?php
require_once __DIR__ . "/../../bootstrap.php";

header('Content-Type: application/json');

$type = $_GET['type'] ?? '';
$id = $_GET['id'] ?? 0;
$method = $_SERVER['REQUEST_METHOD'];

// Validate inputs
if (!in_array($type, ['health', 'period', 'pad'])) {
    http_response_code(400);
    die(json_encode(['error' => 'Invalid request']));
}

// Determine table and ID field based on type
switch ($type) {
    case 'health':
        $table = 'student_health_records';
        $idField = 'id';
        break;
    case 'period':
        $table = 'student_period_records';
        $idField = 'id';
        break;
    case 'pad':
        $table = 'stock_out';
        $idField = 'transaction_out_id';
        break;
    default:
        http_response_code(400);
        die(json_encode(['error' => 'Invalid record type']));
}

try {
    // Handle POST request (update)
    if ($method === 'POST') {
        $updates = [];
        $values = [];
        $paramCount = 1;

        foreach ($_POST as $field => $value) {
            // Skip the 'tab' field as we don't need to store it
            if ($field === 'tab' || $field === 'academic_year') {
                continue;
            }

            // Validate field names to prevent SQL injection
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $field)) {
                continue;
            }

            $updates[] = "$field = $" . $paramCount++;
            $values[] = $value;
        }

        if (empty($updates)) {
            http_response_code(400);
            die(json_encode(['error' => 'No valid fields to update']));
        }

        // Add ID as the last parameter
        $values[] = $id;

        $query = "UPDATE $table SET " . implode(', ', $updates) . " WHERE $idField = $" . $paramCount;
        $result = pg_query_params($con, $query, $values);

        if ($result) {
            http_response_code(200);
            echo json_encode(['success' => 'Record updated successfully']);
        } else {
            throw new Exception(pg_last_error($con));
        }
        exit;
    }

    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
