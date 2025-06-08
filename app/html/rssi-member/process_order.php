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
