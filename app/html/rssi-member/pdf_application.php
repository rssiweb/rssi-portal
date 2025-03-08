<?php
// Start output buffering to prevent output being sent before PDF
ob_start();

// Include necessary libraries and files at the top
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

// Include the FPDF and FPDI libraries
require_once('../fpdf186/fpdf.php');
require_once('../FPDI-2.6.2/src/autoload.php'); // This autoloads FPDI classes

use setasign\Fpdi\Fpdi; // Correct namespace for FPDI

// Check if user is logged in
if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}

validation();

date_default_timezone_set('Asia/Kolkata');
$date = date('Y-m-d H:i:s');

// Default values for certificate and project names
$certificate_title = isset($_POST['certificate_title']) ? $_POST['certificate_title'] : null;
$project_name = isset($_POST['project_name']) ? $_POST['project_name'] : null;

// Get the associate number from the GET request and sanitize it
$student_id = $_GET['student_id'];

// Combined query to fetch both the member and appraisal details based on associatenumber
$query = "
SELECT 
   * from rssimyprofile_student
    WHERE 
        student_id = '$student_id'"; // Use associatenumber for the condition

// Execute the query
$result = pg_query($con, $query);

// Fetch all rows
$resultArr = pg_fetch_all($result);

// Check if query was successful
if (!$result) {
    echo "An error occurred.\n";
    exit;
}

?>

<?php if ($resultArr != null) { ?>
    <?php foreach ($resultArr as $array) { ?>
        <?php
        // Create an instance of FPDI
        $pdf = new Fpdi();

        // Set the source file (certificate template PDF)
        $pageCount = $pdf->setSourceFile('../pdf_template/application.pdf');

        // Import the first page of the template
        $templateId = $pdf->importPage(1);

        // Add a page in landscape mode with custom size (11 x 8.5 inches)
        $pdf->addPage('P', 'A4'); // 'P' for portrait mode, 'A4' for standard A4 size

        // Use the imported template
        $pdf->useTemplate($templateId);

        // Set font for text that will be added to the certificate
        $pdf->AddFont('cambria', '', 'Cambria.php'); //Regular
        $pdf->AddFont('oldenglishtextmt', '', 'oldenglishtextmt.php'); //Regular
        $pdf->SetFont('Times', 'B', 12); // Set Times New Roman in Bold

        // Set the text color (optional)
        $pdf->SetTextColor(0, 0, 0); // Red color

        // Add dynamic data to the certificate


        // Output the PDF to the browser for download
        $randomNumber = mt_rand(100000000000, 999999999999); // Generates a 12-digit random number
        $pdf->SetXY(155, 48); // Adjust position for the name
        $pdf->Cell(0, 0, $randomNumber, 0, 1, 'L'); // Dynamic full name from database
        $pdf->SetXY(155, -38); // Adjust position for the name
        $pdf->Cell(0, 0, $randomNumber, 0, 1, 'L'); // Dynamic full name from database

        $pdf->SetFont('Times', '', 9);
        // Full Name (from the database)
        $pdf->SetXY(135, 60); // Adjust position for the name
        $pdf->Cell(0, 0, $array['student_id'] . '/' . $array['studentname'], 0, 1, 'L'); // Dynamic full name from database
        $pdf->SetXY(135, 65); // 10 for X (left margin), -30 for Y (relative to the bottom of the page)
        $pdf->Cell(0, 0, 'Class- '.$array['class'], 0, 1, 'L');
        $pdf->SetXY(135, 70); // 10 for X (left margin), -30 for Y (relative to the bottom of the page)
        $pdf->Cell(0, 0, 'Issued by '.$fullname.' on '.date("d/m/Y H:i:s"), 0, 1, 'L');
        $pdf->Output($randomNumber . '.pdf', 'D');

        // Delete the temporary QR code file after use
        unlink($tempQrFile);
        ?>
    <?php } ?>
<?php } else { ?>
    <p class="no-print">Please enter Associate ID.</p>
<?php }

// End output buffering and clean it
ob_end_flush();
?>