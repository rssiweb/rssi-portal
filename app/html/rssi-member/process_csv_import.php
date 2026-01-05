<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}

validation();

if ($role !== 'Admin') {
    header("Location: index.php");
    exit;
}

header('Content-Type: application/json');

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

try {
    // Check if file was uploaded
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Please select a CSV file to upload');
    }

    // Validate file type
    $fileName = $_FILES['csv_file']['name'];
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    if ($fileExt !== 'csv') {
        throw new Exception('Please upload a valid CSV file (.csv extension)');
    }

    // Check file size (10MB max)
    $maxSize = 10 * 1024 * 1024;
    if ($_FILES['csv_file']['size'] > $maxSize) {
        throw new Exception('File size exceeds 10MB limit');
    }

    // Get import action
    $importAction = $_POST['import_action'] ?? 'preview';
    $defaultEffectiveFrom = $_POST['effective_from_csv'] ?? date('Y-m-01');
    $createdBy = pg_escape_string($con, $_SESSION['aid']);

    // Read entire file content
    $fileContent = file_get_contents($_FILES['csv_file']['tmp_name']);
    
    // Remove UTF-8 BOM if present
    $bom = pack('H*', 'EFBBBF');
    $fileContent = preg_replace("/^$bom/", '', $fileContent);
    
    // Save cleaned content to temp file
    $tempFile = tempnam(sys_get_temp_dir(), 'csv_');
    file_put_contents($tempFile, $fileContent);
    
    // Open CSV file
    $handle = fopen($tempFile, 'r');
    if ($handle === false) {
        throw new Exception('Unable to open CSV file');
    }

    // Read and parse the header
    $header = fgetcsv($handle);
    
    // Clean header - remove any whitespace and special characters
    $header = array_map(function($item) {
        // Remove BOM characters and trim
        $item = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $item);
        return trim($item);
    }, $header);
    
    // Debug: Log header
    error_log("CSV Header: " . implode(', ', $header));

    // Required columns (case-insensitive)
    $requiredColumns = ['student_id', 'category_name', 'amount'];
    $headerLower = array_map('strtolower', $header);
    
    // Check for required columns
    $missingColumns = [];
    foreach ($requiredColumns as $required) {
        if (!in_array(strtolower($required), $headerLower)) {
            $missingColumns[] = $required;
        }
    }
    
    if (!empty($missingColumns)) {
        throw new Exception("Missing required columns: " . implode(', ', $missingColumns) . 
                          ". Found columns: " . implode(', ', $header));
    }

    // Map header indices
    $headerMap = [];
    foreach ($header as $index => $column) {
        $headerMap[strtolower($column)] = $index;
    }

    $data = [];
    $errors = [];
    $rowNumber = 1; // Header is row 1

    // Process data rows
    while (($row = fgetcsv($handle)) !== false) {
        $rowNumber++;
        
        // Skip empty rows
        if ($row === null || count($row) === 0 || (count($row) === 1 && trim($row[0]) === '')) {
            continue;
        }
        
        // Ensure row has same number of columns as header
        while (count($row) < count($header)) {
            $row[] = '';
        }

        // Initialize record
        $record = [
            'row_number' => $rowNumber,
            'student_id' => '',
            'category_name' => '',
            'amount' => '',
            'effective_from' => '',
            'effective_until' => '',
            'student_name' => '',
            'class' => '',
            'category_id' => '',
            'status' => 'Pending',
            'message' => '',
            'is_valid' => true
        ];

        // Extract data based on header map
        $record['student_id'] = isset($headerMap['student_id']) && isset($row[$headerMap['student_id']]) 
            ? trim($row[$headerMap['student_id']]) 
            : '';
        
        $record['category_name'] = isset($headerMap['category_name']) && isset($row[$headerMap['category_name']]) 
            ? trim($row[$headerMap['category_name']]) 
            : '';
        
        $record['amount'] = isset($headerMap['amount']) && isset($row[$headerMap['amount']]) 
            ? trim($row[$headerMap['amount']]) 
            : '';
        
        $record['effective_from'] = isset($headerMap['effective_from']) && isset($row[$headerMap['effective_from']]) 
            ? trim($row[$headerMap['effective_from']]) 
            : $defaultEffectiveFrom;
        
        $record['effective_until'] = isset($headerMap['effective_until']) && isset($row[$headerMap['effective_until']]) 
            ? trim($row[$headerMap['effective_until']]) 
            : '';

        // Debug: Log row data
        error_log("Row $rowNumber: Student='{$record['student_id']}', Category='{$record['category_name']}', Amount='{$record['amount']}'");

        // Validation
        $validationErrors = [];

        // 1. Check required fields
        if (empty($record['student_id'])) {
            $validationErrors[] = 'Student ID is required';
        }
        
        if (empty($record['category_name'])) {
            $validationErrors[] = 'Category name is required';
        }
        
        if (empty($record['amount'])) {
            $validationErrors[] = 'Amount is required';
        }

        // 2. Validate student exists (only if student_id is provided)
        if (empty($validationErrors) && !empty($record['student_id'])) {
            $studentId = pg_escape_string($con, $record['student_id']);
            $studentCheck = pg_query($con, 
                "SELECT student_id, studentname, class 
                 FROM rssimyprofile_student 
                 WHERE student_id = '$studentId' AND filterstatus = 'Active'");
            
            if (!$studentCheck) {
                $validationErrors[] = 'Database error checking student: ' . pg_last_error($con);
            } elseif (pg_num_rows($studentCheck) == 0) {
                $validationErrors[] = "Student ID '{$record['student_id']}' not found or not active";
            } else {
                $student = pg_fetch_assoc($studentCheck);
                $record['student_name'] = $student['studentname'];
                $record['class'] = $student['class'];
            }
        }

        // 3. Validate category exists
        if (empty($validationErrors) && !empty($record['category_name'])) {
            $categoryName = pg_escape_string($con, $record['category_name']);
            $categoryCheck = pg_query($con, 
                "SELECT id, category_name 
                 FROM fee_categories 
                 WHERE category_name = '$categoryName' 
                 AND is_active = TRUE 
                 AND category_type = 'structured'");
            
            if (pg_num_rows($categoryCheck) == 0) {
                // Get available categories for error message
                $availableCategoriesResult = pg_query($con, 
                    "SELECT category_name 
                     FROM fee_categories 
                     WHERE is_active = TRUE 
                     AND category_type = 'structured' 
                     ORDER BY category_name");
                
                $availableCategories = [];
                if ($availableCategoriesResult) {
                    while ($cat = pg_fetch_assoc($availableCategoriesResult)) {
                        $availableCategories[] = $cat['category_name'];
                    }
                }
                
                $validationErrors[] = "Category '{$record['category_name']}' not found. " .
                                    "Available categories: " . implode(', ', $availableCategories);
            } else {
                $category = pg_fetch_assoc($categoryCheck);
                $record['category_id'] = $category['id'];
                $record['category_name'] = $category['category_name']; // Use exact case from DB
            }
        }

        // 4. Validate amount
        if (empty($validationErrors) && !empty($record['amount'])) {
            $amount = str_replace(',', '', $record['amount']);
            if (!is_numeric($amount)) {
                $validationErrors[] = "Amount '{$record['amount']}' is not a valid number";
            } else {
                $record['amount'] = number_format($amount, 2, '.', '');
            }
        }

        // 5. Validate dates
        if (empty($validationErrors)) {
            // Validate effective_from
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $record['effective_from'])) {
                $validationErrors[] = "Effective From '{$record['effective_from']}' must be in YYYY-MM-DD format";
            } else {
                // Check if date is valid
                $dateParts = explode('-', $record['effective_from']);
                if (!checkdate($dateParts[1], $dateParts[2], $dateParts[0])) {
                    $validationErrors[] = "Effective From '{$record['effective_from']}' is not a valid date";
                }
            }
            
            // Validate effective_until if provided
            if (!empty($record['effective_until'])) {
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $record['effective_until'])) {
                    $validationErrors[] = "Effective Until '{$record['effective_until']}' must be in YYYY-MM-DD format";
                } else {
                    $dateParts = explode('-', $record['effective_until']);
                    if (!checkdate($dateParts[1], $dateParts[2], $dateParts[0])) {
                        $validationErrors[] = "Effective Until '{$record['effective_until']}' is not a valid date";
                    } elseif ($record['effective_until'] < $record['effective_from']) {
                        $validationErrors[] = "Effective Until cannot be before Effective From";
                    }
                }
            }
        }

        // Update record status
        if (!empty($validationErrors)) {
            $record['is_valid'] = false;
            $record['status'] = 'Error';
            $record['message'] = implode('; ', $validationErrors);
            $errors[] = "Row $rowNumber: " . $record['message'];
        } else {
            $record['status'] = 'Valid';
            $record['message'] = 'Ready for import';
        }

        $data[] = $record;
    }

    fclose($handle);
    unlink($tempFile); // Clean up temp file

    // Check if any data was processed
    if (empty($data)) {
        throw new Exception('No data found in CSV file. Please check the file format.');
    }

    // Count valid and invalid records
    $validCount = count(array_filter($data, function($r) { return $r['is_valid']; }));
    $errorCount = count($data) - $validCount;

    // If import action is selected, insert into database
    if ($importAction === 'import') {
        pg_query($con, "BEGIN");

        try {
            $importedCount = 0;
            $duplicateCount = 0;
            $importErrors = [];

            foreach ($data as &$record) {
                if (!$record['is_valid']) {
                    continue; // Skip invalid records
                }

                $studentId = pg_escape_string($con, $record['student_id']);
                $categoryId = $record['category_id'];
                $amount = pg_escape_string($con, $record['amount']);
                $effectiveFrom = pg_escape_string($con, $record['effective_from']);
                $effectiveUntil = !empty($record['effective_until']) 
                    ? "'" . pg_escape_string($con, $record['effective_until']) . "'" 
                    : 'NULL';

                // Check for existing active fee
                $checkExisting = pg_query($con, 
                    "SELECT id FROM student_specific_fees 
                     WHERE student_id = '$studentId' 
                     AND category_id = $categoryId
                     AND amount = $amount
                     AND effective_from = '$effectiveFrom'
                     AND (effective_until IS NULL OR effective_until = $effectiveUntil)");
                
                if ($checkExisting && pg_num_rows($checkExisting) > 0) {
                    $record['status'] = 'Skipped';
                    $record['message'] = 'Duplicate fee already exists';
                    $duplicateCount++;
                    continue;
                }

                // End any previous effective period
                $endPreviousQuery = 
                    "UPDATE student_specific_fees 
                     SET effective_until = '$effectiveFrom'::date - INTERVAL '1 day'
                     WHERE student_id = '$studentId'
                     AND category_id = $categoryId
                     AND (effective_until IS NULL OR effective_until >= '$effectiveFrom')";
                
                pg_query($con, $endPreviousQuery);

                // Insert new fee assignment
                $query = 
                    "INSERT INTO student_specific_fees 
                     (student_id, category_id, amount, effective_from, effective_until, created_by, created_at)
                     VALUES ('$studentId', $categoryId, $amount, '$effectiveFrom', $effectiveUntil, '$createdBy', NOW())";

                if (pg_query($con, $query)) {
                    $importedCount++;
                    $record['status'] = 'Imported';
                    $record['message'] = 'Successfully imported';
                } else {
                    $errorMsg = pg_last_error($con);
                    $record['is_valid'] = false;
                    $record['status'] = 'Error';
                    $record['message'] = "Database error: " . $errorMsg;
                    $importErrors[] = "Row {$record['row_number']}: " . $errorMsg;
                }
            }

            if ($importedCount > 0) {
                pg_query($con, "COMMIT");
                
                $response = [
                    'success' => true,
                    'message' => "Successfully imported $importedCount records." . 
                                ($duplicateCount > 0 ? " $duplicateCount duplicates skipped." : "") . 
                                ($errorCount > 0 ? " $errorCount records had errors." : ""),
                    'data' => $data,
                    'summary' => [
                        'total_rows' => count($data),
                        'valid_rows' => $validCount,
                        'imported_count' => $importedCount,
                        'duplicate_count' => $duplicateCount,
                        'error_count' => $errorCount
                    ]
                ];
                
                if (!empty($errors)) {
                    $response['errors'] = array_slice($errors, 0, 10);
                }
                if (!empty($importErrors)) {
                    $response['import_errors'] = array_slice($importErrors, 0, 10);
                }
                
                echo json_encode($response);
            } else {
                pg_query($con, "ROLLBACK");
                throw new Exception('No records were imported. All rows had errors or were duplicates.');
            }
        } catch (Exception $e) {
            pg_query($con, "ROLLBACK");
            throw new Exception("Import failed: " . $e->getMessage());
        }
    } else {
        // Preview mode
        $response = [
            'success' => true,
            'message' => 'CSV validation completed.',
            'data' => $data,
            'summary' => [
                'total_rows' => count($data),
                'valid_rows' => $validCount,
                'error_rows' => $errorCount
            ]
        ];
        
        if (!empty($errors)) {
            $response['message'] .= " Found $errorCount errors.";
            $response['errors'] = array_slice($errors, 0, 10);
        }
        
        echo json_encode($response);
    }

} catch (Exception $e) {
    error_log("CSV Import Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>