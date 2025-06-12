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

    // Begin transaction
    pg_query($con, "BEGIN");

    // First, get all product details in one query to minimize database calls
    $productIds = array_column($cart, 'productId');
    $productIdsString = implode(',', array_map('intval', $productIds));

    // Updated query to join with stock_item_price table
    $productQuery = "SELECT 
        i.item_id as product_id,
        i.item_name,
        p.price_per_unit as price,
        u.unit_name,
        p.unit_quantity,
        COALESCE(SUM(sa.quantity_received), 0) - COALESCE(SUM(so.quantity_distributed), 0) AS available_stock
    FROM stock_item i
    JOIN stock_item_price p ON i.item_id = p.item_id
    JOIN stock_item_unit u ON p.unit_id = u.unit_id
    LEFT JOIN stock_add sa ON i.item_id = sa.item_id
    LEFT JOIN stock_out so ON i.item_id = so.item_distributed
    WHERE i.item_id IN ($productIdsString)
    AND CURRENT_DATE BETWEEN p.effective_start_date AND COALESCE(p.effective_end_date, CURRENT_DATE)
    GROUP BY i.item_id, i.item_name, p.price_per_unit, u.unit_name, p.unit_quantity";

    $productResult = pg_query($con, $productQuery);
    if (!$productResult) {
        throw new Exception('Failed to fetch product details: ' . pg_last_error($con));
    }

    $products = [];
    while ($row = pg_fetch_assoc($productResult)) {
        $products[$row['product_id']] = $row;
    }

    // Validate stock availability and prepare complete cart items
    $stockErrors = [];
    $completeCart = [];

    foreach ($cart as $item) {
        if (!isset($products[$item['productId']])) {
            $stockErrors[] = "Product ID {$item['productId']} not found or no active price";
            continue;
        }

        $product = $products[$item['productId']];
        $availableStock = (int)$product['available_stock'];
        $requestedQuantity = (int)$item['count'];

        if ($requestedQuantity > $availableStock) {
            $stockErrors[] = "'{$product['item_name']}' - Available: $availableStock, Ordered: $requestedQuantity";
        }

        // Build complete cart item with all required fields
        $completeCart[] = [
            'productId' => $item['productId'],
            'count' => $item['count'],
            'price' => $product['price'],
            'unit_name' => $product['unit_name'],
            'unit_quantity' => $product['unit_quantity'],
            'item_name' => $product['item_name']
        ];
    }

    // If any stock errors, throw them all at once
    if (!empty($stockErrors)) {
        $errorMessage = "Insufficient stock or missing price for the following items:\n";
        $errorMessage .= implode("\n", $stockErrors);
        $errorMessage .= "\n\nPlease adjust quantities and try again.";
        throw new Exception($errorMessage);
    }

    foreach ($beneficiaries as $beneficiary) {
        $paymentId = null;

        // Process payment first if it's online or cash
        if ($paymentMode == 'online' || $paymentMode == 'cash') {
            // Insert payment record
            $feePaymentQuery = "INSERT INTO fee_payments (
                student_id,
                amount,
                payment_type,
                transaction_id,
                collected_by,
                collection_date,
                notes,
                academic_year,
                month,
                source,
                category_id
            ) VALUES (
                $1,
                $2,
                $3,
                $4,
                $5,
                CURRENT_DATE,
                $6,
                $7,
                $8,
                'eMart',
                10
            ) RETURNING id";

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
                throw new Exception('Failed to insert fee payment record: ' . pg_last_error($con));
            }

            $paymentData = pg_fetch_assoc($feePaymentResult);
            $paymentId = $paymentData['id'];
        }

        // Insert the order record with payment_id and beneficiary
        $orderNumber = uniqid();
        $orderQuery = "INSERT INTO emart_orders (
            order_number,
            associatenumber,
            total_amount,
            payment_mode,
            transaction_id,
            remarks,
            payment_id,
            beneficiary
        ) VALUES ($1, $2, $3, $4, $5, $6, $7, $8) RETURNING order_id";

        $orderParams = [
            $orderNumber,
            $associatenumber,
            $totalAmount,
            $paymentMode,
            $transactionId,
            $remarks,
            $paymentId,
            $beneficiary
        ];

        $orderResult = pg_query_params($con, $orderQuery, $orderParams);
        if (!$orderResult) {
            throw new Exception('Failed to create order: ' . pg_last_error($con));
        }

        $orderData = pg_fetch_assoc($orderResult);
        $orderId = $orderData['order_id'];

        // Insert order items
        foreach ($completeCart as $item) {
            $orderItemQuery = "INSERT INTO emart_order_items (
                order_id,
                product_id,
                quantity,
                unit_price,
                unit_name,
                unit_quantity
            ) VALUES ($1, $2, $3, $4, $5, $6)";

            $orderItemParams = [
                $orderId,
                $item['productId'],
                $item['count'],
                $item['price'],
                $item['unit_name'],
                $item['unit_quantity']
            ];

            $orderItemResult = pg_query_params($con, $orderItemQuery, $orderItemParams);
            if (!$orderItemResult) {
                throw new Exception('Failed to insert order item: ' . pg_last_error($con));
            }
        }

        // Insert stock_out for each item
        foreach ($completeCart as $item) {
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
                (SELECT unit_id FROM stock_item_price WHERE item_id = $1 LIMIT 1),
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
                throw new Exception('Failed to insert stock out record: ' . pg_last_error($con));
            }
        }
    }

    pg_query($con, "COMMIT");

    echo json_encode([
        'status' => 'success',
        'message' => 'Order placed successfully!',
        'order_id' => $orderId
    ]);
} catch (Exception $e) {
    pg_query($con, "ROLLBACK");
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
