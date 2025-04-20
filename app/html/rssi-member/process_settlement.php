<?php
require_once __DIR__ . "/../../bootstrap.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['create_settlement'])) {
    header("Location: settlement.php");
    exit;
}

// Validate input
$required = ['payment_ids', 'settlement_date', 'settled_by'];
foreach ($required as $field) {
    if (empty($_POST[$field])) {
        $_SESSION['error'] = "Please fill all required fields";
        header("Location: settlement.php");
        exit;
    }
}

// Get payment IDs
$paymentIds = explode(',', $_POST['payment_ids']);
$paymentIds = array_filter($paymentIds, 'is_numeric');
if (empty($paymentIds)) {
    $_SESSION['error'] = "No valid payments selected";
    header("Location: settlement.php");
    exit;
}

// Calculate totals
$totalsQuery = "SELECT SUM(amount) as total_amount,
                       SUM(CASE WHEN payment_type = 'cash' THEN amount ELSE 0 END) as cash_amount,
                       SUM(CASE WHEN payment_type = 'online' THEN amount ELSE 0 END) as online_amount
                FROM fee_payments
                WHERE id IN (" . implode(',', $paymentIds) . ")";
$totalsResult = pg_query($con, $totalsQuery);
$totals = pg_fetch_assoc($totalsResult);

// Begin transaction
pg_query($con, "BEGIN");

try {
    // Create settlement record
    $insertQuery = "INSERT INTO settlements (
                        settlement_date, total_amount, cash_amount, online_amount, 
                        settled_by, notes
                    ) VALUES (
                        '{$_POST['settlement_date']}', {$totals['total_amount']}, {$totals['cash_amount']}, 
                        {$totals['online_amount']}, '{$_POST['settled_by']}', " .
                        (!empty($_POST['notes']) ? "'".pg_escape_string($con, $_POST['notes'])."'" : 'NULL') . "
                    ) RETURNING id";
    
    $result = pg_query($con, $insertQuery);
    $settlementId = pg_fetch_result($result, 0, 0);
    
    if (!$settlementId) {
        throw new Exception("Failed to create settlement record");
    }
    
    // Update payments
    $updateQuery = "UPDATE fee_payments 
                    SET is_settled = TRUE, settlement_id = $settlementId
                    WHERE id IN (" . implode(',', $paymentIds) . ")";
    
    $updateResult = pg_query($con, $updateQuery);
    
    if (!$updateResult) {
        throw new Exception("Failed to update payment records");
    }
    
    // Commit transaction
    pg_query($con, "COMMIT");
    
    $_SESSION['success'] = "Settlement created successfully! Settlement ID: $settlementId";
} catch (Exception $e) {
    pg_query($con, "ROLLBACK");
    $_SESSION['error'] = "Error creating settlement: " . $e->getMessage();
}

header("Location: settlement.php");
exit;