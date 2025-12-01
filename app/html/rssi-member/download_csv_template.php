<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=student_fees_import_template.csv');

// Create output stream WITHOUT BOM
$output = fopen('php://output', 'w');

// Write header row - NO BOM
$headers = [
    'student_id',       // Required: Student ID (must exist in rssimyprofile_student)
    'category_name',    // Required: Fee category name (must exist in fee_categories)
    'amount',           // Required: Positive number
    'effective_from',   // Required: Date in YYYY-MM-DD format
    'effective_until'   // Optional: Date in YYYY-MM-DD format (leave empty for indefinite)
];
fputcsv($output, $headers);

// Get current date in YYYY-MM-DD format
$currentDate = date('Y-m-01');
$nextYear = date('Y-m-d', strtotime('+1 year', strtotime($currentDate)));
$sixMonths = date('Y-m-d', strtotime('+6 months', strtotime($currentDate)));

// Write sample rows with correct date format
$sampleRows = [
    ['STU001', 'Tuition Fee', '15000.00', $currentDate, $nextYear],
    ['STU002', 'Library Fee', '2000.00', $currentDate, ''],
];

foreach ($sampleRows as $row) {
    fputcsv($output, $row);
}

// Add instructions section with CLEAR date format examples
fputcsv($output, []);
fputcsv($output, ['=== IMPORTANT INSTRUCTIONS ===']);
fputcsv($output, ['1. Required columns: student_id, category_name, amount']);
fputcsv($output, ['2. Replace all sample data with actual data from your system and delete all placeholder rows from the === IMPORTANT INSTRUCTIONS === section.']);
fputcsv($output, ['3. category_name must match exactly from the list below (case-sensitive)']);
fputcsv($output, ['4. amount must be positive. Excel may hide trailing zeros, so 1500 and 1500.00 are both valid.']);
fputcsv($output, ['5. DATE FORMAT MUST BE YYYY-MM-DD (e.g., 2024-12-01, NOT 01-12-2024). If Excel changes the format, prefix the date with a single quote (e.g., \'2024-12-01).']);
fputcsv($output, ['6. Leave effective_until empty for indefinite validity']);
fputcsv($output, []);
fputcsv($output, ['=== DATE FORMAT EXAMPLES ===']);
fputcsv($output, ['Correct: 2024-01-15 (January 15, 2024)']);
fputcsv($output, ['Correct: 2024-12-31 (December 31, 2024)']);
fputcsv($output, ['Incorrect: 15-01-2024 (will cause errors)']);
fputcsv($output, ['Incorrect: 01/15/2024 (will cause errors)']);
fputcsv($output, []);

// Add available categories section
fputcsv($output, ['=== AVAILABLE CATEGORIES (category_type = structured) ===']);
$categoriesQuery = "SELECT category_name FROM fee_categories WHERE is_active = TRUE AND category_type = 'structured' ORDER BY category_name";
$categoriesResult = pg_query($con, $categoriesQuery);

if ($categoriesResult && pg_num_rows($categoriesResult) > 0) {
    fputcsv($output, ['Category Name']);
    while ($category = pg_fetch_assoc($categoriesResult)) {
        fputcsv($output, [$category['category_name']]);
    }
} else {
    fputcsv($output, ['No categories found with category_type = structured']);
}

// Add student ID examples from the system
fputcsv($output, []);
fputcsv($output, ['=== EXAMPLE STUDENT IDs (Active students only) ===']);
$exampleStudentsQuery = "SELECT student_id FROM rssimyprofile_student WHERE filterstatus='Active' LIMIT 3";
$exampleStudentsResult = pg_query($con, $exampleStudentsQuery);

if ($exampleStudentsResult && pg_num_rows($exampleStudentsResult) > 0) {
    fputcsv($output, ['Student ID']);
    while ($student = pg_fetch_assoc($exampleStudentsResult)) {
        fputcsv($output, [$student['student_id']]);
    }
}

// Add data validation rules
fputcsv($output, []);
fputcsv($output, ['=== DATA VALIDATION RULES ===']);
fputcsv($output, ['1. student_id: Must exist in rssimyprofile_student table and be active']);
fputcsv($output, ['2. category_name: Must exist in fee_categories table, be active and category_type=structured']);
fputcsv($output, ['3. amount: Must be a positive number (e.g., 1000.50)']);
fputcsv($output, ['4. effective_from: Must be in YYYY-MM-DD format']);
fputcsv($output, ['5. effective_until: Optional, but must be in YYYY-MM-DD format if provided']);

fclose($output);
exit;
