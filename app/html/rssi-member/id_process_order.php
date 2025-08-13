<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

function getAcademicYear($date = null)
{
    $date = new DateTime($date ?: 'now');
    $year = $date->format('Y');
    $month = $date->format('m');
    return ($month >= 4) ? $year . '-' . ($year + 1) : ($year - 1) . '-' . $year;
}

try {
    switch ($action) {
        case 'create_batch':
            handleCreateBatch();
            break;
        case 'get_open_batches':
            handleGetOpenBatches();
            break;
        case 'get_batch_details':
            handleGetBatchDetails();
            break;
        case 'add':
            handleAddToBatch();
            break;
        case 'remove_item':
            handleRemoveItem();
            break;
        case 'place_orders':
            handlePlaceOrders();
            break;
        case 'update_order':
            handleUpdateOrder();
            break;
        case 'get_order_history':
            getOrderHistory();
            break;
        case 'export_batch':
            handleExportBatch();
            break;
        case 'get_order_details':
            handleGetOrderDetails();
            break;
        case 'mark_batch_delivered':
            markBatchDelivered();
            break;

        case 'mark_order_delivered':
            markOrderDelivered();
            break;
        case 'get_batch_status':
            getBatchStatus();
            break;
        case 'get_order_details_history':
            getOrderDetails();
            break;
        case 'revert_to_pending':
            revertToPending();
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function handleCreateBatch()
{
    global $con, $associatenumber;

    // Validate required fields
    if (empty($_POST['created_by'])) {
        throw new Exception('Missing required fields');
    }

    // Set parameters
    $batch_name = $_POST['batch_name'] ?? null;
    $batch_type = $_POST['batch_type'] ?? 'Public';
    $created_by = $_POST['created_by'];
    $batch_id = $_POST['batch_id'] ?? 'ID-' . date('Ymd-His');

    // Validate batch type
    if (!in_array($batch_type, ['Public', 'Restricted'])) {
        throw new Exception('Invalid batch type');
    }

    // Insert new batch
    $result = pg_query_params(
        $con,
        "INSERT INTO id_card_batches (
            batch_id, batch_name, batch_type, created_by, 
            created_date, status
        ) VALUES (
            $1, $2, $3, $4, $5, 'Pending'
        ) RETURNING batch_id",
        [
            $batch_id,
            $batch_name,
            $batch_type,
            $created_by,
            date('Y-m-d H:i:s')
        ]
    );

    if (!$result) {
        throw new Exception('Database error: ' . pg_last_error($con));
    }

    $batch = pg_fetch_assoc($result);

    echo json_encode([
        'success' => true,
        'batch_id' => $batch['batch_id'],
        'batch_name' => $batch_name,
        'batch_type' => $batch_type
    ]);
}

function handleGetOpenBatches()
{
    global $con, $associatenumber, $role;

    $isAdmin = $role === 'Admin';
    $params = [];

    // Base query
    $query = "SELECT 
                b.*,
                u.fullname as created_by_name,
                (SELECT COUNT(*) FROM id_card_orders WHERE batch_id = b.batch_id) as item_count
              FROM id_card_batches b
              JOIN rssimyaccount_members u ON b.created_by = u.associatenumber
              WHERE b.status = 'Pending'";

    // For non-admins, only show public batches or their own restricted batches
    if (!$isAdmin) {
        $query .= " AND (b.batch_type = 'Public' OR b.created_by = $1)";
        $params[] = $associatenumber;
    }

    $query .= " ORDER BY b.created_date DESC";

    $result = pg_query_params($con, $query, $params);

    if (!$result) {
        throw new Exception('Database error: ' . pg_last_error($con));
    }

    $batches = [];
    while ($row = pg_fetch_assoc($result)) {
        $batches[] = $row;
    }

    echo json_encode([
        'success' => true,
        'data' => $batches,
        'count' => count($batches)
    ]);
}

function handleGetBatchDetails()
{
    global $con;

    if (empty($_GET['batch_id'])) {
        throw new Exception('Batch ID is required');
    }

    $batch_id = $_GET['batch_id'];

    // Get batch info
    $batchResult = pg_query_params(
        $con,
        "SELECT b.*, u.fullname as created_by_name
         FROM id_card_batches b
         JOIN rssimyaccount_members u ON b.created_by = u.associatenumber
         WHERE b.batch_id = $1",
        [$batch_id]
    );

    if (!$batchResult || pg_num_rows($batchResult) === 0) {
        throw new Exception('Batch not found');
    }

    $batch = pg_fetch_assoc($batchResult);

    // Get batch items
    $itemsResult = pg_query_params(
        $con,
        "SELECT 
            o.*,
            COALESCE(s.studentname, m.fullname) AS studentname, s.class, COALESCE(s.photourl, m.photo) AS photourl,
            u.fullname as order_placed_by_name,COALESCE(s.filterstatus, m.filterstatus) AS filterstatus,
            (SELECT COUNT(*) FROM id_card_orders 
                    WHERE student_id = o.student_id AND status = 'Delivered') AS times_issued,
                   (SELECT MAX(order_date) FROM id_card_orders 
                    WHERE student_id = o.student_id AND status = 'Delivered') AS last_issued
         FROM id_card_orders o
         LEFT JOIN rssimyprofile_student s ON o.student_id = s.student_id
         LEFT JOIN rssimyaccount_members m ON o.student_id = m.associatenumber
         JOIN rssimyaccount_members u ON o.order_placed_by = u.associatenumber
         WHERE o.batch_id = $1
         ORDER BY o.order_date DESC",
        [$batch_id]
    );

    if (!$itemsResult) {
        throw new Exception('Failed to load batch items');
    }

    $items = [];
    while ($row = pg_fetch_assoc($itemsResult)) {
        $items[] = $row;
    }

    echo json_encode([
        'success' => true,
        'batch' => $batch,
        'items' => $items
    ]);
}

function handleAddToBatch()
{
    global $con, $associatenumber;

    $required = ['batch_id', 'student_id', 'order_type'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // For reissue orders, payment status is required
    if ($_POST['order_type'] === 'Reissue' && empty($_POST['payment_status'])) {
        throw new Exception('Payment status is required for Reissue orders');
    }

    $params = [
        $_POST['batch_id'],
        $_POST['student_id'],
        $_POST['order_type'],
        $associatenumber,
        date('Y-m-d H:i:s'),
        $_POST['payment_status'] ?? null,
        $_POST['remarks'] ?? null,
        getAcademicYear()
    ];

    // Check if student already exists in this batch
    $check = pg_query_params(
        $con,
        "SELECT 1 FROM id_card_orders 
         WHERE batch_id = $1 AND student_id = $2",
        [$params[0], $params[1]]
    );

    if (pg_num_rows($check) > 0) {
        throw new Exception('This student already exists in the batch');
    }

    // Insert order
    $result = pg_query_params(
        $con,
        "INSERT INTO id_card_orders (
            batch_id, student_id, order_type, order_placed_by,
            order_date, payment_status, remarks, status, academic_year
        ) VALUES (
            $1, $2, $3, $4, $5, $6, $7, 'Pending', $8
        ) RETURNING id",
        $params
    );

    if (!$result) {
        throw new Exception('Database error: ' . pg_last_error($con));
    }

    echo json_encode([
        'success' => true,
        'student_id' => $params[1]
    ]);
}

function handleRemoveItem()
{
    global $con;

    if (empty($_POST['id'])) {
        throw new Exception('Order ID is required');
    }

    $result = pg_query_params(
        $con,
        "DELETE FROM id_card_orders WHERE id = $1 RETURNING batch_id",
        [$_POST['id']]
    );

    if (!$result || pg_num_rows($result) === 0) {
        throw new Exception('Failed to remove item or item not found');
    }

    $batch_id = pg_fetch_assoc($result)['batch_id'];

    echo json_encode([
        'success' => true,
        'batch_id' => $batch_id
    ]);
}

function handlePlaceOrders()
{
    global $con, $associatenumber;

    header('Content-Type: application/json'); // Ensure JSON response

    try {
        // Validate required fields
        $required = ['batch_ids', 'vendor_name'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }

        // Process batch IDs
        $batch_ids = $_POST['batch_ids'];
        if (is_string($batch_ids)) {
            $batch_ids = json_decode($batch_ids, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON format for batch IDs');
            }
        }

        if (!is_array($batch_ids) || empty($batch_ids)) {
            throw new Exception('Invalid batch IDs format');
        }

        // Convert to PostgreSQL text array format
        $pg_array = '{' . implode(',', array_map(function ($id) use ($con) {
            return '"' . pg_escape_string($con, $id) . '"';
        }, $batch_ids)) . '}';

        // Begin transaction
        pg_query($con, "BEGIN");

        // Update batches
        $batchUpdate = pg_query_params(
            $con,
            "UPDATE id_card_batches 
             SET status = 'Ordered',
                 ordered_date = $1,
                 vendor_name = $2,
                 admin_remarks = $3
             WHERE batch_id = ANY($4::text[]) AND status = 'Pending'
             RETURNING batch_id",
            [
                date('Y-m-d H:i:s'),
                $_POST['vendor_name'],
                $_POST['admin_remarks'] ?? null,
                $pg_array
            ]
        );

        if (!$batchUpdate) {
            throw new Exception('Failed to update batches: ' . pg_last_error($con));
        }

        // Update orders
        $orderUpdate = pg_query_params(
            $con,
            "UPDATE id_card_orders 
             SET status = 'Ordered',
                 order_placed_date = $2,
                 processed_by = $3
             WHERE batch_id = ANY($1::text[]) AND status = 'Pending'",
            [
                $pg_array,
                date('Y-m-d H:i:s'),
                $associatenumber
            ]
        );

        if (!$orderUpdate) {
            throw new Exception('Failed to update orders: ' . pg_last_error($con));
        }

        pg_query($con, "COMMIT");

        // After successful commit
        $updatedBatch = pg_fetch_assoc($batchUpdate); // Get the single batch record

        echo json_encode([
            'success' => true,
            'batch_id' => $updatedBatch['batch_id'], // Single batch ID
            'message' => 'Order placed successfully for Batch: ' . $updatedBatch['batch_id']
        ]);
        exit;
    } catch (Exception $e) {
        pg_query($con, "ROLLBACK");
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        exit;
    }
}

function handleUpdateOrder()
{
    global $con;

    if (empty($_POST['id'])) {
        throw new Exception('Order ID is required');
    }

    $fields = [];
    $params = [];
    $paramCount = 1;

    // Add all possible fields that can be updated
    $updatableFields = ['order_type', 'payment_status', 'remarks'];

    foreach ($updatableFields as $field) {
        if (isset($_POST[$field])) {
            $fields[] = "$field = $" . $paramCount++;
            $params[] = $_POST[$field];
        }
    }

    if (empty($fields)) {
        throw new Exception('No fields to update');
    }

    $params[] = $_POST['id'];

    $query = "UPDATE id_card_orders SET " . implode(', ', $fields) . " WHERE id = $" . $paramCount;

    $result = pg_query_params($con, $query, $params);

    if (!$result) {
        throw new Exception('Database error: ' . pg_last_error($con));
    }

    echo json_encode([
        'success' => true,
        'updated' => pg_affected_rows($result)
    ]);
}

function handleExportBatch()
{
    global $con;

    if (empty($_GET['batch_id'])) {
        throw new Exception('Batch ID is required');
    }

    $batch_id = $_GET['batch_id'];

    // Get batch details
    $batchResult = pg_query_params(
        $con,
        "SELECT * FROM id_card_batches WHERE batch_id = $1",
        [$batch_id]
    );

    if (!$batchResult || pg_num_rows($batchResult) === 0) {
        throw new Exception('Batch not found');
    }

    $batch = pg_fetch_assoc($batchResult);

    // Get orders
    $ordersResult = pg_query_params(
        $con,
        "SELECT 
            o.*,
            s.studentname, s.class, s.fathername, s.mothername, s.photourl,
            u.fullname as order_placed_by_name
         FROM id_card_orders o
         JOIN rssimyprofile_student s ON o.student_id = s.student_id
         JOIN rssimyaccount_members u ON o.order_placed_by = u.associatenumber
         WHERE o.batch_id = $1
         ORDER BY s.class, s.studentname",
        [$batch_id]
    );

    if (!$ordersResult) {
        throw new Exception('Failed to fetch orders');
    }

    // Prepare CSV output
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=id_card_batch_' . $batch_id . '.csv');

    $output = fopen('php://output', 'w');

    // Write CSV headers
    fputcsv($output, [
        'Batch ID',
        'Batch Name',
        'Batch Type',
        'Status',
        'Student ID',
        'Student Name',
        'Class',
        'Order Type',
        'Payment Status',
        'Remarks',
        'Requested By',
        'Order Date'
    ]);

    // Write data rows
    while ($row = pg_fetch_assoc($ordersResult)) {
        fputcsv($output, [
            $batch['batch_id'],
            $batch['batch_name'],
            $batch['batch_type'],
            $batch['status'],
            $row['student_id'],
            $row['studentname'],
            $row['class'],
            $row['order_type'],
            $row['payment_status'],
            $row['remarks'],
            $row['order_placed_by_name'],
            $row['order_date']
        ]);
    }

    fclose($output);
    exit;
}
function handleGetOrderDetails()
{
    global $con;

    if (empty($_GET['id'])) {
        throw new Exception('Order ID is required');
    }

    $result = pg_query_params(
        $con,
        "SELECT o.id, o.order_type, o.payment_status, o.remarks, 
                COALESCE(s.studentname, m.fullname) AS studentname, COALESCE(s.student_id, m.associatenumber) AS student_id
         FROM id_card_orders o
         LEFT JOIN rssimyprofile_student s ON o.student_id = s.student_id
         LEFT JOIN rssimyaccount_members m ON o.student_id = m.associatenumber
         WHERE o.id = $1",
        [$_GET['id']]
    );

    if (!$result || pg_num_rows($result) === 0) {
        throw new Exception('Order not found');
    }

    echo json_encode([
        'success' => true,
        'data' => pg_fetch_assoc($result)
    ]);
}
function getOrderHistory()
{
    global $con;

    // Set JSON header first
    header('Content-Type: application/json');

    try {
        $params = [
            'from_date' => $_GET['from_date'] ?? null,
            'to_date' => $_GET['to_date'] ?? null,
            'status' => $_GET['status'] ?? null
        ];

        $query = "SELECT 
                    o.id, o.batch_id, o.student_id, o.order_type, 
                    o.status, o.payment_status, o.order_date,
                    o.order_placed_by, o.remarks,
                    COALESCE(s.studentname, m.fullname) AS studentname, s.class, COALESCE(s.photourl, m.photo) AS photourl,
                    u.fullname as order_placed_by_name,
                    b.vendor_name, b.admin_remarks
                  FROM id_card_orders o
                  LEFT JOIN rssimyprofile_student s ON o.student_id = s.student_id
                  LEFT JOIN rssimyaccount_members m ON o.student_id = m.associatenumber
                  JOIN rssimyaccount_members u ON o.order_placed_by = u.associatenumber
                  LEFT JOIN id_card_batches b ON o.batch_id = b.batch_id
                  WHERE 1=1";

        $conditions = [];
        $queryParams = [];
        $paramCount = 1;

        // Date range filter
        if ($params['from_date'] && $params['to_date']) {
            $conditions[] = "o.order_date BETWEEN $" . $paramCount++ . " AND $" . $paramCount++;
            $queryParams[] = $params['from_date'];
            $queryParams[] = $params['to_date'];
        }

        // Status filter
        if ($params['status']) {
            $conditions[] = "o.status = $" . $paramCount++;
            $queryParams[] = $params['status'];
        }

        if (!empty($conditions)) {
            $query .= " AND " . implode(" AND ", $conditions);
        }

        $query .= " ORDER BY o.order_date DESC, s.class, s.studentname";

        $result = pg_query_params($con, $query, $queryParams);

        if (!$result) {
            throw new Exception('Database error: ' . pg_last_error($con));
        }

        $data = [];
        while ($row = pg_fetch_assoc($result)) {
            $data[] = $row;
        }

        // Only clean buffer if there is one
        if (ob_get_length() > 0) {
            ob_clean();
        }

        echo json_encode([
            'success' => true,
            'data' => $data
        ]);
        exit;
    } catch (Exception $e) {
        // Only clean buffer if there is one
        if (ob_get_length() > 0) {
            ob_clean();
        }

        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        exit;
    }
}

// Add these new functions
function markBatchDelivered()
{
    global $con, $associatenumber;

    header('Content-Type: application/json');

    try {
        $required = ['batch_id'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }

        $batch_id = $_POST['batch_id'];
        $remarks = $_POST['remarks'] ?? null;

        // Begin transaction
        pg_query($con, "BEGIN");

        // 1. Update the batch status
        $batchUpdate = pg_query_params(
            $con,
            "UPDATE id_card_batches 
             SET status = 'Delivered'
             WHERE batch_id = $1 AND status = 'Ordered'
             RETURNING batch_id",
            [$batch_id]
        );

        if (!$batchUpdate || pg_num_rows($batchUpdate) === 0) {
            throw new Exception('Batch not found or not in Ordered status');
        }

        // 2. Update all orders in this batch
        $ordersUpdate = pg_query_params(
            $con,
            "UPDATE id_card_orders
             SET status = 'Delivered',
                 delivered_date = $1,
                 delivered_by = $2,
                 delivered_remarks = $3
             WHERE batch_id = $4 AND status = 'Ordered'",
            [date('Y-m-d H:i:s'), $associatenumber, $remarks, $batch_id]
        );

        if (!$ordersUpdate) {
            throw new Exception('Failed to update orders: ' . pg_last_error($con));
        }

        pg_query($con, "COMMIT");

        echo json_encode([
            'success' => true,
            'message' => 'Batch marked as delivered successfully'
        ]);
    } catch (Exception $e) {
        pg_query($con, "ROLLBACK");
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function markOrderDelivered()
{
    global $con, $associatenumber;

    header('Content-Type: application/json');

    try {
        $required = ['order_id'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }

        $order_id = $_POST['order_id'];
        $remarks = $_POST['remarks'] ?? null;

        // Begin transaction
        pg_query($con, "BEGIN");

        // 1. Update the single order
        $orderUpdate = pg_query_params(
            $con,
            "UPDATE id_card_orders
             SET status = 'Delivered',
                 delivered_date = $1,
                 delivered_by = $2,
                 delivered_remarks = $3
             WHERE id = $4 AND status = 'Ordered'
             RETURNING batch_id",
            [date('Y-m-d H:i:s'), $associatenumber, $remarks, $order_id]
        );

        if (!$orderUpdate || pg_num_rows($orderUpdate) === 0) {
            throw new Exception('Order not found or not in Ordered status');
        }

        $row = pg_fetch_assoc($orderUpdate);
        $batch_id = $row['batch_id'];

        // 2. Check if all orders in batch are now delivered
        $check = pg_query_params(
            $con,
            "SELECT COUNT(*) as pending_count 
             FROM id_card_orders 
             WHERE batch_id = $1 AND status != 'Delivered'",
            [$batch_id]
        );

        $pending = pg_fetch_assoc($check)['pending_count'];

        // 3. If no pending orders, update batch status
        if ($pending == 0) {
            pg_query_params(
                $con,
                "UPDATE id_card_batches
                 SET status = 'Delivered'
                 WHERE batch_id = $1 AND status = 'Ordered'",
                [$batch_id]
            );
        }

        pg_query($con, "COMMIT");

        echo json_encode([
            'success' => true,
            'message' => 'Order marked as delivered successfully'
        ]);
    } catch (Exception $e) {
        pg_query($con, "ROLLBACK");
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}
// Add this new function
function getBatchStatus()
{
    global $con;

    header('Content-Type: application/json');

    try {
        if (empty($_GET['batch_id'])) {
            throw new Exception("Missing batch_id parameter");
        }

        $batch_id = $_GET['batch_id'];

        // Check ordered items
        $ordered = pg_query_params(
            $con,
            "SELECT COUNT(*) as count FROM id_card_orders 
             WHERE batch_id = $1 AND status = 'Ordered'",
            [$batch_id]
        );
        $hasOrdered = pg_fetch_assoc($ordered)['count'] > 0;

        // Check if all delivered
        $delivered = pg_query_params(
            $con,
            "SELECT COUNT(*) as count FROM id_card_orders 
             WHERE batch_id = $1 AND status != 'Delivered'",
            [$batch_id]
        );
        $allDelivered = pg_fetch_assoc($delivered)['count'] == 0;

        echo json_encode([
            'success' => true,
            'hasOrdered' => $hasOrdered,
            'allDelivered' => $allDelivered
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}
// Add these new functions
function getOrderDetails()
{
    global $con;

    header('Content-Type: application/json');

    try {
        if (empty($_GET['id'])) {
            throw new Exception("Missing order ID");
        }

        $order_id = $_GET['id'];

        $query = "SELECT 
                    o.*,
                    COALESCE(s.studentname, m.fullname) AS studentname, COALESCE(s.student_id, m.associatenumber) AS student_id,
                    u.fullname as order_placed_by_name,
                    du.fullname as delivered_by_name,
                    up.fullname as updated_by_name,
                    b.vendor_name,
                    b.ordered_date
                  FROM id_card_orders o
                  LEFT JOIN rssimyprofile_student s ON o.student_id = s.student_id
                  LEFT JOIN rssimyaccount_members m ON o.student_id = m.associatenumber
                  LEFT JOIN rssimyaccount_members u ON o.order_placed_by = u.associatenumber
                  LEFT JOIN rssimyaccount_members du ON o.delivered_by = du.associatenumber
                  LEFT JOIN rssimyaccount_members up ON o.delivered_by = up.associatenumber
                  LEFT JOIN id_card_batches b ON o.batch_id = b.batch_id
                  WHERE o.id = $1";

        $result = pg_query_params($con, $query, [$order_id]);

        if (!$result || pg_num_rows($result) === 0) {
            throw new Exception('Order not found');
        }

        $order = pg_fetch_assoc($result);

        echo json_encode([
            'success' => true,
            'data' => $order
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function revertToPending()
{
    global $con, $associatenumber;

    header('Content-Type: application/json');

    try {
        $required = ['order_id'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }

        $order_id = $_POST['order_id'];
        $remarks = $_POST['remarks'] ?? null;

        // Begin transaction
        pg_query($con, "BEGIN");

        // 1. Update the order status
        $orderUpdate = pg_query_params(
            $con,
            "UPDATE id_card_orders
             SET status = 'Ordered',
                 pending_remarks = $1,
                 updated_by = $2,
                 updated_at = $3
             WHERE id = $4 AND status = 'Delivered'
             RETURNING batch_id",
            [$remarks, $associatenumber, date('Y-m-d H:i:s'), $order_id]
        );

        if (!$orderUpdate || pg_num_rows($orderUpdate) === 0) {
            throw new Exception('Order not found or not in Delivered status');
        }

        $row = pg_fetch_assoc($orderUpdate);
        $batch_id = $row['batch_id'];

        // 2. Check if batch needs to be updated
        $check = pg_query_params(
            $con,
            "SELECT COUNT(*) as delivered_count 
             FROM id_card_orders 
             WHERE batch_id = $1 AND status = 'Delivered'",
            [$batch_id]
        );

        $delivered = pg_fetch_assoc($check)['delivered_count'];

        // 3. If no delivered orders left, update batch status
        if ($delivered == 0) {
            pg_query_params(
                $con,
                "UPDATE id_card_batches
                 SET status = 'Ordered'
                 WHERE batch_id = $1 AND status = 'Delivered'",
                [$batch_id]
            );
        }

        pg_query($con, "COMMIT");

        echo json_encode([
            'success' => true,
            'message' => 'Order reverted to pending successfully'
        ]);
    } catch (Exception $e) {
        pg_query($con, "ROLLBACK");
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}
