<?php
require_once __DIR__ . "/../../bootstrap.php";

header('Content-Type: application/json');

try {
    $data = $_POST;

    if (empty($data['cart']) || empty($data['beneficiaries'])) {
        throw new Exception('Invalid order data');
    }

    $cart            = json_decode($data['cart'], true);
    $beneficiaries   = json_decode($data['beneficiaries'], true);
    $associatenumber = $data['associatenumber'];
    $paymentMode     = $data['paymentMode'];
    $transactionId   = $data['transactionId'] ?? null;
    $remarks         = $data['remarks'] ?? '';
    $totalAmount     = $data['totalPoints'] ?? 0;

    $year  = date('Y');
    $month = date('n');

    pg_query($con, "BEGIN");

    // -------- 1. Fetch product metadata (price, fixed flag, cashflow flag, stock, mapping) --------
    $productIds       = array_column($cart, 'productId');
    $productIdsString = implode(',', array_map('intval', $productIds));

    $productQuery = "SELECT 
        i.item_id AS product_id,
        i.item_name,
        i.is_cashflow,
        i.cashflow_category_id,
        p.price_per_unit AS price,
        p.is_fixed_price,
        COALESCE(u.unit_name, 'Unit') AS unit_name,
        COALESCE(p.unit_quantity, 1) AS unit_quantity,
        COALESCE(SUM(sa.quantity_received), 0) - COALESCE(SUM(so.quantity_distributed), 0) AS available_stock
    FROM stock_item i
    JOIN stock_item_price p ON i.item_id = p.item_id
    LEFT JOIN stock_item_unit u ON p.unit_id = u.unit_id
    LEFT JOIN stock_add sa ON i.item_id = sa.item_id
    LEFT JOIN stock_out so ON i.item_id = so.item_distributed
    WHERE i.item_id IN ($productIdsString)
      AND CURRENT_DATE BETWEEN p.effective_start_date AND COALESCE(p.effective_end_date, CURRENT_DATE)
    GROUP BY i.item_id, i.item_name, i.is_cashflow, i.cashflow_category_id, p.price_per_unit, p.is_fixed_price, u.unit_name, p.unit_quantity";

    $productResult = pg_query($con, $productQuery);
    if (!$productResult) {
        throw new Exception('Failed to fetch product details: ' . pg_last_error($con));
    }

    $products = [];
    while ($row = pg_fetch_assoc($productResult)) {
        $products[$row['product_id']] = $row;
    }

    // -------- 2. Validate stock + build complete cart --------
    $stockErrors  = [];
    $completeCart = [];

    foreach ($cart as $item) {
        if (!isset($products[$item['productId']])) {
            $stockErrors[] = "Product ID {$item['productId']} not found or no active price";
            continue;
        }

        $product           = $products[$item['productId']];
        $availableStock    = (int)$product['available_stock'];
        $requestedQuantity = (int)$item['count'];

        if ($requestedQuantity > $availableStock) {
            $stockErrors[] = "'{$product['item_name']}' - Available: $availableStock, Ordered: $requestedQuantity";
        }

        $isFixedPrice = $product['is_fixed_price'] == 't' || $product['is_fixed_price'] == '1' || $product['is_fixed_price'] === true;
        $isCashflow   = $product['is_cashflow']    == 't' || $product['is_cashflow']    == '1' || $product['is_cashflow']    === true;

        // Cashflow mapping is required when the product is a cashflow item
        $cashflowMappingId = null;
        if ($isCashflow) {
            if (empty($product['cashflow_category_id'])) {
                $stockErrors[] = "'{$product['item_name']}' is marked as cashflow but has no cashflow mapping configured.";
            } else {
                $cashflowMappingId = (int)$product['cashflow_category_id'];
            }
        }

        $basePrice      = (float)$product['price'];
        $customPrice    = null;
        $discountPct    = 0;
        $finalUnitPrice = $basePrice;

        if (!$isFixedPrice) {
            if (isset($item['customPrice']) && $item['customPrice'] > 0) {
                $customPrice    = (float)$item['customPrice'];
                $discountPct    = isset($item['discount']) ? (float)$item['discount'] : 0;
                $finalUnitPrice = $customPrice * (1 - $discountPct / 100);
            } else {
                $customPrice    = $basePrice;
                $finalUnitPrice = $basePrice;
            }
        } else {
            $finalUnitPrice = $basePrice;
            $customPrice    = null;
            $discountPct    = 0;
        }

        $completeCart[] = [
            'productId'            => $item['productId'],
            'count'                => $item['count'],
            'price'                => $finalUnitPrice,
            'base_price'           => $basePrice,
            'custom_price'         => $customPrice,
            'discount_percent'     => $discountPct,
            'is_fixed_price'       => $isFixedPrice,
            'is_cashflow'          => $isCashflow,
            'cashflow_category_id' => $cashflowMappingId,
            'unit_name'            => $product['unit_name'],
            'unit_quantity'        => $product['unit_quantity'],
            'item_name'            => $product['item_name']
        ];
    }

    if (!empty($stockErrors)) {
        $errorMessage  = "Insufficient stock or missing price for the following items:\n";
        $errorMessage .= implode("\n", $stockErrors);
        $errorMessage .= "\n\nPlease adjust quantities and try again.";
        throw new Exception($errorMessage);
    }

    // -------- 3. Split items into cashflow / non-cashflow buckets --------
    $cashflowItems    = [];
    $nonCashflowItems = [];

    foreach ($completeCart as $item) {
        if ($item['is_cashflow']) {
            $cashflowItems[] = $item;
        } else {
            $nonCashflowItems[] = $item;
        }
    }

    // Totals per bucket
    $cashflowTotal = 0;
    foreach ($cashflowItems as $item) {
        $cashflowTotal += $item['price'] * $item['count'];
    }

    $nonCashflowTotal = 0;
    foreach ($nonCashflowItems as $item) {
        $nonCashflowTotal += $item['price'] * $item['count'];
    }

    $totalAmount = $cashflowTotal + $nonCashflowTotal;

    // -------- 4. Per beneficiary processing --------
    foreach ($beneficiaries as $beneficiary) {

        // --- 4a. fee_payments (only for non-cashflow items) ---
        $paymentId = null;

        if (($paymentMode == 'online' || $paymentMode == 'cash') && $nonCashflowTotal > 0) {
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
                category_id
            ) VALUES (
                $1, $2, $3, $4, $5, CURRENT_DATE, $6, $7, $8, 10
            ) RETURNING id";

            $feePaymentParams = [
                $beneficiary,
                $nonCashflowTotal,
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
            $paymentId   = $paymentData['id'];
        }

        // --- 4b. cashflow_transactions (one row per cashflow item) ---
        if (($paymentMode == 'online' || $paymentMode == 'cash') && !empty($cashflowItems)) {

            foreach ($cashflowItems as $item) {

                $itemAmount = $item['price'] * $item['count'];

                // Simple note: "eMart Purchase - <item name> | Buyer: <beneficiary id>"
                $noteText = 'eMart Purchase - ' . $item['item_name']
                    . ' | Buyer: ' . $beneficiary;

                $cfQuery = "INSERT INTO cashflow_transactions (
                    transaction_date,
                    type,
                    category_id,
                    amount,
                    notes,
                    created_by,
                    created_at
                ) VALUES (
                    CURRENT_DATE,
                    'earning',
                    $1,
                    $2,
                    $3,
                    $4,
                    NOW()
                )";

                $cfParams = [
                    $item['cashflow_category_id'],
                    $itemAmount,
                    $noteText,
                    $associatenumber
                ];

                $cfResult = pg_query_params($con, $cfQuery, $cfParams);
                if (!$cfResult) {
                    throw new Exception('Failed to insert cashflow transaction: ' . pg_last_error($con));
                }
            }
        }

        // --- 4c. emart_orders ---
        $orderNumber = uniqid();
        $orderQuery  = "INSERT INTO emart_orders (
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
        $orderId   = $orderData['order_id'];

        // --- 4d. emart_order_items ---
        foreach ($completeCart as $item) {
            $isFixedPriceBool = $item['is_fixed_price'] ? 'true' : 'false';
            $isCashflowBool   = $item['is_cashflow']    ? 'true' : 'false';

            $orderItemQuery = "INSERT INTO emart_order_items (
                order_id,
                product_id,
                quantity,
                unit_price,
                unit_name,
                unit_quantity,
                base_price,
                custom_price,
                discount_percent,
                is_fixed_price,
                is_cashflow
            ) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11)";

            $orderItemParams = [
                $orderId,
                $item['productId'],
                $item['count'],
                $item['price'],
                $item['unit_name'],
                $item['unit_quantity'],
                $item['base_price'],
                $item['custom_price'],
                $item['discount_percent'],
                $isFixedPriceBool,
                $isCashflowBool
            ];

            $orderItemResult = pg_query_params($con, $orderItemQuery, $orderItemParams);
            if (!$orderItemResult) {
                throw new Exception('Failed to insert order item: ' . pg_last_error($con));
            }
        }

        // --- 4e. stock_out (runs for every item) ---
        foreach ($completeCart as $item) {
            $stockOutQuery = "INSERT INTO stock_out (
                date, 
                item_distributed, 
                unit, 
                description, 
                quantity_distributed, 
                distributed_to, 
                distributed_by, 
                timestamp
            ) VALUES (
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
                $associatenumber
            ];

            $stockOutResult = pg_query_params($con, $stockOutQuery, $stockOutParams);
            if (!$stockOutResult) {
                throw new Exception('Failed to insert stock out record: ' . pg_last_error($con));
            }
        }
    }

    pg_query($con, "COMMIT");

    echo json_encode([
        'status'   => 'success',
        'message'  => 'Order placed successfully!',
        'order_id' => $orderId
    ]);
} catch (Exception $e) {
    pg_query($con, "ROLLBACK");
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage()
    ]);
}
