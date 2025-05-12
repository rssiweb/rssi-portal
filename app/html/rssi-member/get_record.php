<?php
require_once __DIR__ . "/../../bootstrap.php";

$type = $_GET['type'] ?? '';
$id = $_GET['id'] ?? 0;
$mode = $_GET['mode'] ?? 'view';

// Validate inputs
if (!in_array($type, ['health', 'period', 'pad']) || !is_numeric($id)) {
    die("Invalid request");
}

// Fetch record based on type
switch ($type) {
    case 'health':
        $record = pg_fetch_assoc(pg_query($con, 
            "SELECT * FROM student_health_records WHERE id = $id"));
        break;
    case 'period':
        $record = pg_fetch_assoc(pg_query($con, 
            "SELECT * FROM student_period_records WHERE id = $id"));
        break;
    case 'pad':
        $record = pg_fetch_assoc(pg_query($con, 
            "SELECT * FROM stock_out WHERE transaction_out_id = $id"));
        break;
}

if (!$record) {
    die("Record not found");
}

// Render appropriate HTML based on mode
if ($mode === 'view') {
    // View mode - display details
    echo '<div class="record-details">';
    foreach ($record as $field => $value) {
        if ($value === null) continue;
        echo '<div class="mb-3">';
        echo '<label class="form-label fw-bold">' . ucwords(str_replace('_', ' ', $field)) . '</label>';
        echo '<div>' . htmlspecialchars($value) . '</div>';
        echo '</div>';
    }
    echo '</div>';
} else {
    // Edit mode - display form
    echo '<div class="edit-form">';
    foreach ($record as $field => $value) {
        if ($field === 'id') continue;
        
        echo '<div class="mb-3">';
        echo '<label class="form-label">' . ucwords(str_replace('_', ' ', $field)) . '</label>';
        
        if (strpos($field, 'date') !== false) {
            echo '<input type="date" class="form-control" name="' . $field . '" value="' . htmlspecialchars($value) . '">';
        } elseif (strpos($field, 'notes') !== false || strpos($field, 'symptoms') !== false) {
            echo '<textarea class="form-control" name="' . $field . '">' . htmlspecialchars($value) . '</textarea>';
        } else {
            echo '<input type="text" class="form-control" name="' . $field . '" value="' . htmlspecialchars($value) . '">';
        }
        
        echo '</div>';
    }
    echo '</div>';
}
?>