<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $beneficiary_id = $_POST['beneficiary_id'] ?? '';
    $appointment_for = $_POST['appointment_for'] ?? '';
    $appointment_date = $_POST['appointment_date'] ?? '';
    $appointment_time = $_POST['appointment_time'] ?? '';
    $remarks = $_POST['remarks'] ?? '';

    // Validate inputs
    if (empty($beneficiary_id) || empty($appointment_for) || empty($appointment_date) || empty($appointment_time)) {
        echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
        exit;
    }

    // Insert the appointment
    $result = pg_query_params(
        $con,
        "INSERT INTO appointments (
            beneficiary_id, 
            appointment_for, 
            appointment_date, 
            appointment_time,
            remarks, 
            created_by, 
            updated_by
        ) VALUES ($1, $2, $3, $4, $5, $6, $6) RETURNING id",
        [
            $beneficiary_id,
            $appointment_for,
            $appointment_date,
            $appointment_time,
            $remarks,
            $associatenumber
        ]
    );

    if ($result && pg_num_rows($result) > 0) {
        $appointment_id = pg_fetch_result($result, 0, 'id');
        echo json_encode(['success' => true, 'message' => 'Appointment created successfully!', 'id' => $appointment_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create appointment: ' . pg_last_error($con)]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
