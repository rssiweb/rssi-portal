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
    'effective_from',   // Optional: Date in YYYY-MM-DD (defaults to provided date)
    'effective_until'   // Optional: Date in YYYY-MM-DD
];
fputcsv($output, $headers);

// Get fee categories where is_listed = FALSE
$categoriesQuery = "SELECT category_name FROM fee_categories WHERE is_active = TRUE AND category_type = 'structured' ORDER BY category_name";
$categoriesResult = pg_query($con, $categoriesQuery);

// Write sample rows with dummy student IDs
$sampleRows = [
    ['STU001', 'Tuition Fee', '15000.00', date('Y-m-01'), date('Y-m-d', strtotime('+1 year'))],
    ['STU002', 'Library Fee', '2000.00', date('Y-m-01'), ''],
    ['STU003', 'Transportation Fee', '5000.00', date('Y-m-01'), date('Y-m-d', strtotime('+6 months'))],
    ['STU004', 'Exam Fee', '3000.00', date('Y-m-01'), date('Y-m-d', strtotime('+1 year'))],
    ['STU005', 'Sports Fee', '1000.00', date('Y-m-01'), '']
];

foreach ($sampleRows as $row) {
    fputcsv($output, $row);
}

// Add instructions section
fputcsv($output, []);
fputcsv($output, ['=== IMPORTANT INSTRUCTIONS ===']);
fputcsv($output, ['1. Required columns: student_id, category_name, amount']);
fputcsv($output, ['2. Replace sample student IDs with actual student IDs from your system']);
fputcsv($output, ['3. category_name must match exactly from the list below (case-sensitive)']);
fputcsv($output, ['4. amount must be a positive number (e.g., 1500.00)']);
fputcsv($output, ['5. Dates must be in YYYY-MM-DD format']);
fputcsv($output, ['6. Leave effective_until empty for indefinite validity']);
fputcsv($output, []);

// Add available categories section
fputcsv($output, ['=== AVAILABLE CATEGORIES (category_type = structured) ===']);
if ($categoriesResult && pg_num_rows($categoriesResult) > 0) {
    fputcsv($output, ['Category Name']);
    while ($category = pg_fetch_assoc($categoriesResult)) {
        fputcsv($output, [$category['category_name']]);
    }
} else {
    fputcsv($output, ['No categories found with category_type = structured']);
}

// Add student ID examples from the system (just for reference, not actual data)
fputcsv($output, []);
fputcsv($output, ['=== EXAMPLE STUDENT IDs (from your system) ===']);
$exampleStudentsQuery = "SELECT student_id FROM rssimyprofile_student where filterstatus='Active' LIMIT 5";
$exampleStudentsResult = pg_query($con, $exampleStudentsQuery);
if ($exampleStudentsResult && pg_num_rows($exampleStudentsResult) > 0) {
    fputcsv($output, ['Sample Student ID']);
    while ($student = pg_fetch_assoc($exampleStudentsResult)) {
        fputcsv($output, [$student['student_id']]);
    }
}

fclose($output);
exit;
?>