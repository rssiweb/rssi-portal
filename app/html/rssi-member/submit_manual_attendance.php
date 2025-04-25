<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();

header('Content-Type: application/json');

// Process submitted data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get raw POST data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // Validate data
    if (json_last_error() !== JSON_ERROR_NONE || !isset($data['persons'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid data format']);
        exit;
    }

    $remarks = $data['remarks'] ?? '';
    $punch_in_time = $data['punch_in_time'] ?? '';
    $persons = $data['persons'];
    $current_date = date('Y-m-d');
    $ip_address = $_SERVER['REMOTE_ADDR'];
    // In your data processing section:
    $latitude = $data['latitude'] ?? null;
    $longitude = $data['longitude'] ?? null;

    if (empty($persons)) {
        echo json_encode(['success' => false, 'message' => 'No persons selected']);
        exit;
    }

    // Begin transaction
    pg_query($con, "BEGIN");

    try {
        $count = 0;
        foreach ($persons as $person) {
            // Determine if it's a student or associate
            $parts = explode('_', $person);
            if (count($parts) !== 2) continue;

            $type = $parts[0];
            $id = $parts[1];

            if ($type === 'student') {
                // Get student status
                $statusQuery = "SELECT filterstatus FROM rssimyprofile_student WHERE student_id = $1";
                $statusResult = pg_query_params($con, $statusQuery, [$id]);
                $status = pg_fetch_assoc($statusResult)['filterstatus'];
            } else if ($type === 'associate') {
                // Associate
                $status = 'Active';
            } else {
                continue;
            }

            // In your INSERT query:
            $insertQuery = "INSERT INTO attendance (
    user_id, punch_in, ip_address, gps_location, 
    recorded_by, date, status, remarks, is_manual
) VALUES (
    $1, $2, $3, " . ($latitude && $longitude ? "NULL" : "NULL") . ", 
    $4, $5, $6, $7, true
)";

            $insertParams = [
                $id,
                $punch_in_time,
                $ip_address,
                $associatenumber,
                $current_date,
                $status,
                $remarks
            ];

            if (pg_query_params($con, $insertQuery, $insertParams)) {
                $count++;
            }
        }

        pg_query($con, "COMMIT");
        echo json_encode([
            'success' => true,
            'count' => $count,
            'message' => 'Attendance recorded successfully'
        ]);
    } catch (Exception $e) {
        pg_query($con, "ROLLBACK");
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
