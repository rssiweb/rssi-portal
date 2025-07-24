<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");
include("../../util/email.php");

header('Content-Type: application/json');

if (!isLoggedIn("aid")) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

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
        case 'add':
            handleAddOrder();
            break;
        case 'get_batch':
            handleGetBatch();
            break;
        case 'request_order':
            handleRequestOrder();
            break;
        case 'mark_ordered':
            handleMarkOrdered();
            break;
        case 'remove_item':
            handleRemoveItem();
            break;
        case 'update_order':
            handleUpdateOrder();
            break;
        case 'approve_item':
            handleApproveItem();
            break;
        case 'mark_delivered':
            handleMarkDelivered();
            break;
        case 'create_batch':
            handleCreateBatch();
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function handleCreateBatch() {
    global $con, $associatenumber;
    
    if (empty($_POST['batch_id']) || empty($_POST['created_by'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        return;
    }
    
    $batch_id = $_POST['batch_id'];
    $created_by = $_POST['created_by'];
    
    // Check if batch already exists
    $check = pg_query_params($con, 
        "SELECT 1 FROM id_card_batches WHERE batch_id = $1", 
        [$batch_id]
    );
    
    if (pg_num_rows($check) > 0) {
        echo json_encode(['success' => false, 'message' => 'Batch ID already exists']);
        return;
    }
    
    // Insert new batch
    $result = pg_query_params(
        $con,
        "INSERT INTO id_card_batches (
            batch_id, created_by, created_date, status
        ) VALUES (
            $1, $2, $3, 'Pending'
        )",
        [
            $batch_id,
            $created_by,
            date('Y-m-d H:i:s')
        ]
    );
    
    if (!$result) {
        echo json_encode([
            'success' => false, 
            'message' => 'Database error: ' . pg_last_error($con)
        ]);
        return;
    }
    
    echo json_encode([
        'success' => true,
        'batch_id' => $batch_id
    ]);
}

function handleAddOrder()
{
    global $con, $associatenumber;

    header('Content-Type: application/json');

    try {
        $required = ['batch_id', 'student_id', 'order_type'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }

        $student_id = $_POST['student_id'];
        $batch_id = $_POST['batch_id'];

        // Check if batch is pending
        $batchCheck = pg_query_params(
            $con,
            "SELECT 1 FROM id_card_batches WHERE batch_id = $1 AND status = 'Pending'",
            [$batch_id]
        );
        if (pg_num_rows($batchCheck) === 0) {
            echo json_encode([
                'success' => false,
                'isBatchError' => true,
                'message' => 'This batch is invalid or already processed'
            ]);
            return;
        }

        // Already in current batch
        $inCurrentBatch = pg_query_params(
            $con,
            "SELECT 1 FROM id_card_orders 
             WHERE batch_id = $1 AND student_id = $2 AND status = 'Pending'",
            [$batch_id, $student_id]
        );
        if (pg_num_rows($inCurrentBatch) > 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Already in current batch',
                'student_id' => $student_id
            ]);
            return;
        }

        // Pending order elsewhere
        $pendingOrder = pg_query_params(
            $con,
            "SELECT batch_id, status, order_date 
             FROM id_card_orders 
             WHERE student_id = $1 AND status IN ('Pending', 'Ordered') AND batch_id != $2
             ORDER BY order_date DESC LIMIT 1",
            [$student_id, $batch_id]
        );
        if (pg_num_rows($pendingOrder) > 0) {
            $info = pg_fetch_assoc($pendingOrder);
            echo json_encode([
                'success' => false,
                'message' => "Pending order exists in batch {$info['batch_id']} ({$info['status']}, {$info['order_date']})",
                'student_id' => $student_id
            ]);
            return;
        }

        // Fetch from student/member
        $student = pg_query_params(
            $con,
            "SELECT student_id FROM rssimyprofile_student WHERE student_id = $1 AND filterstatus = 'Active'",
            [$student_id]
        );
        if (pg_num_rows($student) === 0) {
            $member = pg_query_params(
                $con,
                "SELECT associatenumber FROM rssimyaccount_members WHERE associatenumber = $1",
                [$student_id]
            );
            if (pg_num_rows($member) === 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Student or associate not found or inactive',
                    'student_id' => $student_id
                ]);
                return;
            }
        }

        // Insert
        $historyRes = pg_query_params(
            $con,
            "SELECT COUNT(*) as count, MAX(order_date) as last_date 
             FROM id_card_orders WHERE student_id = $1 AND status = 'Delivered'",
            [$student_id]
        );
        $history = pg_fetch_assoc($historyRes);

        $insert = pg_query_params(
            $con,
            "INSERT INTO id_card_orders (
                batch_id, student_id, order_type, order_date, 
                order_placed_by, remarks, payment_status, academic_year
            ) VALUES (
                $1, $2, $3, $4, $5, $6, $7, $8
            ) RETURNING id",
            [
                $batch_id,
                $student_id,
                $_POST['order_type'],
                date('Y-m-d'),
                $associatenumber,
                $_POST['remarks'] ?? null,
                $_POST['payment_status'] ?? null,
                getAcademicYear()
            ]
        );

        if (!$insert) {
            throw new Exception('Database insert error');
        }

        echo json_encode([
            'success' => true,
            'student_id' => $student_id,
            'history' => [
                'times_issued' => $history['count'],
                'last_issued' => $history['last_date']
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage(),
            'student_id' => $_POST['student_id'] ?? 'unknown'
        ]);
    }
}

function handleGetBatch()
{
    global $con, $role;

    $batchId = $_GET['batch_id'] ?? null;
    $query = "
            SELECT o.*, 
                   COALESCE(s.studentname, m.fullname) AS studentname,
                   s.class,
                   COALESCE(s.photourl, m.photo) AS photourl,
                   u.fullname AS order_placed_by_name,
                   (SELECT COUNT(*) FROM id_card_orders 
                    WHERE student_id = o.student_id AND status = 'Delivered') AS times_issued,
                   (SELECT MAX(order_date) FROM id_card_orders 
                    WHERE student_id = o.student_id AND status = 'Delivered') AS last_issued
            FROM id_card_orders o
            LEFT JOIN rssimyprofile_student s ON o.student_id = s.student_id
            LEFT JOIN rssimyaccount_members m ON o.student_id = m.associatenumber
            JOIN rssimyaccount_members u ON o.order_placed_by = u.associatenumber
            WHERE o.status = 'Pending'
            ORDER BY o.id DESC
        ";
    $result = pg_query($con, $query);
    $data = pg_fetch_all($result) ?: [];
    echo json_encode([
        'success' => true,
        'data' => $data,
        'batch_id' => $batchId ?: ($data[0]['batch_id'] ?? null)
    ]);
}

function handleRequestOrder()
{
    global $associatenumber;
    $batchId = $_POST['batch_id'] ?? null;

    sendEmail("id_card_order_request", [
        "requester" => $associatenumber,
        "batchId" => $batchId,
        "now" => date("d/m/Y g:i a"),
    ], 'info@rssi.in');

    echo json_encode(['success' => true]);
}

function handleMarkOrdered()
{
    global $con, $role, $associatenumber;

    if ($role !== 'Admin') {
        throw new Exception('Unauthorized');
    }

    $required = ['batch_id', 'vendor_name'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // Start transaction
    pg_query($con, "BEGIN");

    try {
        // 1. Update the batch record
        $updateBatch = pg_query_params(
            $con,
            "UPDATE id_card_batches SET 
                status = 'Ordered',
                vendor_name = $1,
                ordered_date = CURRENT_TIMESTAMP,
                admin_remarks = $2
             WHERE batch_id = $3 AND status = 'Pending'",
            [
                $_POST['vendor_name'],
                $_POST['remarks'] ?? null,
                $_POST['batch_id']
            ]
        );

        if (pg_affected_rows($updateBatch) === 0) {
            throw new Exception('Batch not found or already processed');
        }

        // 2. Update all orders in the batch
        $updateOrders = pg_query_params(
            $con,
            "UPDATE id_card_orders SET 
                status = 'Ordered',
                processed_by = $1,
                order_placed_date = CURRENT_DATE
             WHERE batch_id = $2 AND status = 'Pending'",
            [
                $associatenumber,
                $_POST['batch_id']
            ]
        );

        // Commit transaction
        pg_query($con, "COMMIT");

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        pg_query($con, "ROLLBACK");
        throw $e;
    }
}
function handleRemoveItem()
{
    global $con;

    if (empty($_POST['id'])) {
        throw new Exception('Missing order ID');
    }

    $result = pg_query_params(
        $con,
        "DELETE FROM id_card_orders 
         WHERE id = $1 AND status = 'Pending' 
         RETURNING batch_id",
        [$_POST['id']]
    );

    if (pg_num_rows($result) === 0) {
        throw new Exception('Order not found or cannot be removed');
    }

    echo json_encode([
        'success' => true,
        'batch_id' => pg_fetch_assoc($result)['batch_id']
    ]);
}

function handleUpdateOrder()
{
    global $con;

    $required = ['id'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    $order = pg_query_params(
        $con,
        "SELECT 1 FROM id_card_orders WHERE id = $1 AND status = 'Pending'",
        [$_POST['id']]
    );
    if (pg_num_rows($order) === 0) {
        throw new Exception('Order not found or not editable');
    }

    $result = pg_query_params(
        $con,
        "UPDATE id_card_orders SET 
            payment_status = $1,
            remarks = $2
         WHERE id = $3",
        [
            $_POST['payment_status'] ?? null,
            $_POST['remarks'] ?? null,
            $_POST['id']
        ]
    );

    if (!$result) {
        throw new Exception('Database error: ' . pg_last_error($con));
    }

    echo json_encode(['success' => true]);
}

function handleApproveItem()
{
    global $con, $role, $_SESSION;

    if ($role !== 'Admin') {
        throw new Exception('Unauthorized');
    }

    if (empty($_POST['id'])) {
        throw new Exception('Missing order ID');
    }

    $result = pg_query_params(
        $con,
        "UPDATE id_card_orders SET 
            status = 'Approved',
            processed_by = $1,
            processed_date = CURRENT_DATE
         WHERE id = $2 AND status = 'Pending'",
        [
            $_SESSION['associatenumber'],
            $_POST['id']
        ]
    );

    if (pg_affected_rows($result) === 0) {
        throw new Exception('Order not found or already processed');
    }

    echo json_encode(['success' => true]);
}
function handleMarkDelivered()
{
    global $con, $role, $associatenumber;

    if ($role !== 'Admin') {
        throw new Exception('Unauthorized');
    }

    if (empty($_POST['batch_id'])) {
        throw new Exception('Missing batch ID');
    }

    // Verify batch exists and is in Ordered status
    $batch = pg_query_params(
        $con,
        "SELECT 1 FROM id_card_orders 
         WHERE batch_id = $1 AND status = 'Ordered' LIMIT 1",
        [$_POST['batch_id']]
    );
    if (pg_num_rows($batch) === 0) {
        throw new Exception('Batch not found or not in Ordered status');
    }

    // Update all orders in the batch to Delivered status
    $result = pg_query_params(
        $con,
        "UPDATE id_card_orders SET 
            status = 'Delivered',
            delivered_date = CURRENT_DATE,
            delivered_by = $1
         WHERE batch_id = $2 AND status = 'Ordered'",
        [
            $associatenumber,
            $_POST['batch_id']
        ]
    );

    if (!$result) {
        throw new Exception('Database error: ' . pg_last_error($con));
    }

    echo json_encode(['success' => true]);
}
