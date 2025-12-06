<?php
require_once __DIR__ . "/../../bootstrap.php";

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

$response = ['success' => false, 'message' => ''];

try {
    // Validate required fields
    $requiredFields = ['id', 'full_name', 'company_name', 'email', 'phone', 'company_address'];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Field '$field' is required");
        }
    }
    
    // Get form data
    $id = $_POST['id'];
    $full_name = $_POST['full_name'];
    $company_name = $_POST['company_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $aadhar_number = $_POST['aadhar_number'] ?? null;
    $company_address = $_POST['company_address'];
    $is_active = $_POST['is_active'] === 'true' ? true : false;
    $is_verified = $_POST['is_verified'] === 'true' ? true : false;
    $notes = $_POST['notes'] ?? null;
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }
    
    // Validate phone
    if (!preg_match('/^[0-9]{10}$/', $phone)) {
        throw new Exception('Phone number must be 10 digits');
    }
    
    // Validate Aadhar if provided
    if ($aadhar_number && !preg_match('/^[0-9]{12}$/', $aadhar_number)) {
        throw new Exception('Aadhar number must be 12 digits');
    }
    
    // Check if email already exists (excluding current recruiter)
    $checkEmailQuery = "SELECT id FROM recruiters WHERE email = $1 AND id != $2";
    $checkEmailResult = pg_query_params($con, $checkEmailQuery, [$email, $id]);
    
    if (pg_num_rows($checkEmailResult) > 0) {
        throw new Exception('Email already exists for another recruiter');
    }
    
    // Update recruiter
    $updateQuery = "UPDATE recruiters SET 
                    full_name = $1,
                    company_name = $2,
                    email = $3,
                    phone = $4,
                    aadhar_number = $5,
                    company_address = $6,
                    is_active = $7,
                    is_verified = $8,
                    notes = $9,
                    updated_at = NOW()
                    WHERE id = $10";
    
    $params = [
        $full_name,
        $company_name,
        $email,
        $phone,
        $aadhar_number,
        $company_address,
        $is_active ? 'true' : 'false',
        $is_verified ? 'true' : 'false',
        $notes,
        $id
    ];
    
    $result = pg_query_params($con, $updateQuery, $params);
    
    if (!$result) {
        throw new Exception('Database update failed: ' . pg_last_error($con));
    }
    
    $response['success'] = true;
    $response['message'] = 'Recruiter updated successfully';
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);