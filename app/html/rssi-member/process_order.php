<?php
require_once __DIR__ . "/../../bootstrap.php";

header('Content-Type: application/json');

try {
    $data = $_POST;

    // Validate input
    if (empty($data['cart']) || empty($data['beneficiaries'])) {
        throw new Exception('Invalid order data');
    }

    $cart = json_decode($data['cart'], true);
    $beneficiaries = json_decode($data['beneficiaries'], true);
    $associatenumber = $data['associatenumber'];
    $paymentMode = $data['paymentMode'];
    $transactionId = $data['transactionId'] ?? null;
    $remarks = $data['remarks'] ?? '';
    $totalAmount = $data['totalPoints'] ?? 0;

    $year = date('Y');
    $month = date('n');
    //$academicYear = ($month >= 4) ? "$year-" . ($year + 1) : ($year - 1) . "-$year";

    // Begin transaction
    pg_query($con, "BEGIN");

    // 1. First validate stock availability for all items
    $stockErrors = [];
    foreach ($cart as $item) {
        $stockCheckQuery = "SELECT 
        i.item_name,
        COALESCE(SUM(sa.quantity_received), 0) - COALESCE(SUM(so.quantity_distributed), 0) AS available_stock
    FROM stock_item i
    LEFT JOIN stock_add sa ON i.item_id = sa.item_id
    LEFT JOIN stock_out so ON i.item_id = so.item_distributed
    WHERE i.item_id = $1
    GROUP BY i.item_id, i.item_name";

        $stockCheckResult = pg_query_params($con, $stockCheckQuery, [$item['productId']]);
        $stockData = pg_fetch_assoc($stockCheckResult);

        if (!$stockData) {
            $stockErrors[] = "Could not verify stock for item ID: {$item['productId']}";
            continue;
        }

        $availableStock = (int)$stockData['available_stock'];
        $requestedQuantity = (int)$item['count'];
        $itemName = htmlspecialchars($stockData['item_name']);

        if ($requestedQuantity > $availableStock) {
            $stockErrors[] = "'{$itemName}' - Available: $availableStock, Ordered: $requestedQuantity";
        }
    }

    // If any stock errors, throw them all at once
    if (!empty($stockErrors)) {
        $errorMessage = "Insufficient stock for the following items:\n";
        $errorMessage .= implode("\n", $stockErrors);
        $errorMessage .= "\n\nPlease adjust quantities and try again.";
        throw new Exception($errorMessage);
    }

    foreach ($beneficiaries as $beneficiary) {
        // Insert stock_out for each item
        foreach ($cart as $item) {
            $stockOutQuery = "INSERT INTO stock_out (
            transaction_out_id, 
            date, 
            item_distributed, 
            unit, 
            description, 
            quantity_distributed, 
            distributed_to, 
            distributed_by, 
            timestamp
        ) VALUES (
            $6,
            CURRENT_DATE,
            $1,
            (SELECT unit_id FROM stock_add WHERE item_id = $1 LIMIT 1),
            $2,
            $3,
            $4,
            $5,
            NOW()
        )";

            $stockOutParams = [
                $item['productId'],
                $remarks,
                $item['count'],
                $beneficiary,
                $associatenumber,
                uniqid()
            ];

            $stockOutResult = pg_query_params($con, $stockOutQuery, $stockOutParams);
            if (!$stockOutResult) {
                throw new Exception('Failed to insert stock out record');
            }
        }

        // Insert payment record ONCE per beneficiary (outside item loop)
        $feePaymentQuery = "INSERT INTO fee_payments (
        student_id,
        amount,
        payment_type,
        transaction_id,
        collected_by,
        collection_date,
        notes,
        academic_year,
        month
    ) VALUES (
        $1,
        $2,
        $3,
        $4,
        $5,
        CURRENT_DATE,
        $6,
        $7,
        $8
    )";

        $feePaymentParams = [
            $beneficiary,
            $totalAmount,
            $paymentMode,
            $transactionId,
            $associatenumber,
            $remarks,
            $year,
            date('F', mktime(0, 0, 0, $month, 10))
        ];

        $feePaymentResult = pg_query_params($con, $feePaymentQuery, $feePaymentParams);
        if (!$feePaymentResult) {
            throw new Exception('Failed to insert fee payment record');
        }
    }

    pg_query($con, "COMMIT");


    echo json_encode([
        'status' => 'success',
        'message' => 'Order placed successfully!'
    ]);
} catch (Exception $e) {
    pg_query($con, "ROLLBACK");
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
