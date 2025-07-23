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
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function handleAddOrder()
{
    global $con, $associatenumber;

    $required = ['batch_id', 'student_id', 'order_type'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // Try to fetch from student table
    $student = pg_query_params(
        $con,
        "SELECT student_id, studentname, class, photourl FROM rssimyprofile_student 
     WHERE student_id = $1 AND filterstatus = 'Active'",
        [$_POST['student_id']]
    );

    if (pg_num_rows($student) === 0) {
        // If not found in student table, try members table
        $member = pg_query_params(
            $con,
            "SELECT associatenumber AS student_id, fullname AS studentname, NULL AS class, photo AS photourl 
         FROM rssimyaccount_members 
         WHERE associatenumber = $1",
            [$_POST['student_id']]
        );

        if (pg_num_rows($member) === 0) {
            throw new Exception('Student/Associate not found or inactive');
        } else {
            $student = $member; // Override $student with $member for downstream use
        }
    }

    $exists = pg_query_params(
        $con,
        "SELECT 1 FROM id_card_orders 
         WHERE batch_id = $1 AND student_id = $2 AND status = 'Pending'",
        [$_POST['batch_id'], $_POST['student_id']]
    );
    if (pg_num_rows($exists) > 0) {
        throw new Exception('Student already exists in current batch');
    }

    $history = pg_query_params(
        $con,
        "SELECT COUNT(*) as count, MAX(order_date) as last_date 
         FROM id_card_orders 
         WHERE student_id = $1 AND status = 'Delivered'",
        [$_POST['student_id']]
    );
    $history = pg_fetch_assoc($history);

    $result = pg_query_params(
        $con,
        "INSERT INTO id_card_orders (
            batch_id, student_id, order_type, order_date, 
            order_placed_by, remarks, payment_status, academic_year
         ) VALUES (
            $1, $2, $3, $4, $5, $6, $7, $8
         ) RETURNING id",
        [
            $_POST['batch_id'],
            $_POST['student_id'],
            $_POST['order_type'],
            date('Y-m-d'),
            $associatenumber,
            $_POST['remarks'] ?? null,
            $_POST['payment_status'] ?? null,
            getAcademicYear()
        ]
    );

    if (!$result) {
        throw new Exception('Database error: ' . pg_last_error($con));
    }

    echo json_encode([
        'success' => true,
        'history' => [
            'last_issued' => $history['last_date'],
            'times_issued' => $history['count']
        ]
    ]);
}

function handleGetBatch()
{
    global $con, $role;

    $batchId = $_GET['batch_id'] ?? null;

    if ($role === 'Admin' && !$batchId) {
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
            ORDER BY o.order_date DESC
        ";
        $result = pg_query($con, $query);
    } else {
        $query = "
            SELECT o.*, 
                   COALESCE(s.studentname, m.fullname) AS studentname,
                   s.class,
                   COALESCE(s.photourl, m.photo) AS photourl,
                   (SELECT COUNT(*) FROM id_card_orders 
                    WHERE student_id = o.student_id AND status = 'Delivered') AS times_issued,
                   (SELECT MAX(order_date) FROM id_card_orders 
                    WHERE student_id = o.student_id AND status = 'Delivered') AS last_issued
            FROM id_card_orders o
            LEFT JOIN rssimyprofile_student s ON o.student_id = s.student_id
            LEFT JOIN rssimyaccount_members m ON o.student_id = m.associatenumber
            WHERE o.batch_id = $1
            ORDER BY o.order_date DESC
        ";
        $result = pg_query_params($con, $query, [$batchId]);
    }

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
    global $con, $role, $associatenumber; // Added $associatenumber here

    if ($role !== 'Admin') {
        throw new Exception('Unauthorized');
    }

    $required = ['batch_id', 'vendor_name'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    $result = pg_query_params(
        $con,
        "UPDATE id_card_orders SET 
            status = 'Ordered',
            vendor_name = $1,
            order_placed_date = CURRENT_DATE,
            processed_by = $2,
            admin_remarks = $3
         WHERE batch_id = $4 AND status = 'Pending'",
        [
            $_POST['vendor_name'],
            $associatenumber,
            $_POST['remarks'] ?? null,
            $_POST['batch_id']
        ]
    );

    if (!$result) {
        throw new Exception('Database error: ' . pg_last_error($con));
    }

    echo json_encode(['success' => true]);
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
