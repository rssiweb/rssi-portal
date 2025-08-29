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
$certificate_no = $_GET['certificate_no'];

// Combined query to fetch both the member and appraisal details based on associatenumber
$query = "
SELECT 
    c.*, 
    m.fullname, 
    m.scode
FROM 
    certificate c
JOIN 
    rssimyaccount_members m
ON 
    c.awarded_to_id = m.associatenumber
    WHERE 
        c.certificate_no = '$certificate_no'"; // Use associatenumber for the condition

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
        // Ensure 'template_code' exists and is not empty
        if (!isset($array['template_code']) || empty($array['template_code'])) {
            die("Error: Template code is missing.");
        }

        // Get the template code
        $template_code = trim($array['template_code']); // Trim to remove any whitespace

        // Construct the template file path dynamically
        $template_file = "../pdf_template/{$template_code}.pdf";

        // Debugging: Print to verify
        if (!file_exists($template_file)) {
            die("Error: Template file '{$template_file}' not found.");
        }

        // Set the source file (certificate template PDF)
        $pageCount = $pdf->setSourceFile($template_file);

        // Import the first page of the template
        $templateId = $pdf->importPage(1);

        // Add a page in landscape mode with custom size (11 x 8.5 inches)
        $pdf->addPage('L', array(279.4, 215.9)); // Page size: 11 x 8.5 inches in landscape

        // Use the imported template
        $pdf->useTemplate($templateId);

        // Set font for text that will be added to the certificate
        $pdf->AddFont('cambria', '', 'Cambria.php'); //Regular
        $pdf->AddFont('oldenglishtextmt', '', 'oldenglishtextmt.php'); //Regular
        $pdf->SetFont('oldenglishtextmt', '', 30);

        // Set the text color (optional)
        $pdf->SetTextColor(127, 127, 127);  // Black color for text

        // Add dynamic data to the certificate
        // Full Name (from the database)
        $pdf->SetXY(15, 100); // Adjust position for the name
        $pdf->Cell(0, 0, $array['fullname'], 0, 1, 'C'); // Dynamic full name from database

        // Change the text color (optional)
        $pdf->SetTextColor(0, 0, 0); // Black color for text

        // Change font and size for the comment
        $pdf->SetFont('Arial', '', 14);

        // Full Name (from the database)
        $pdf->SetXY(12, 75); // Adjust position for the name
        $pdf->Cell(0, 0, '(' . $array['badge_name'] . ')', 0, 1, 'C'); // Dynamic full name from database

        // Change font and size for the comment
        $pdf->SetFont('Arial', '', 8);
        // Position the certificate number at the left bottom corner
        $pdf->SetXY(20, -30); // 10 for X (left margin), -30 for Y (relative to the bottom of the page)

        // Print the certificate number
        $pdf->Cell(0, 0, 'Certificate no: ' . $array['certificate_no'], 0, 0, 'L'); // Align text to the left

        $pdf->SetXY(20, -26); // 10 for X (left margin), -30 for Y (relative to the bottom of the page)
        $pdf->Cell(0, 0, 'Issued on: ' . date("d/m/Y", strtotime($array['issuedon'])), 0, 1, 'L');

        // Change font and size for the comment
        $pdf->SetFont('Arial', '', 10);

        // Define cell dimensions and text wrapping properties
        $cellWidth = 175; // Width of the cell
        $lineHeight = 5; // Line height
        // Decode HTML entities and replace typographic apostrophes with standard ones
        $wrappedText = html_entity_decode($array['comment'], ENT_QUOTES, 'UTF-8');

        // Replace typographic apostrophes with standard apostrophes
        $wrappedText = str_replace('’', "'", $wrappedText);

        // Set the initial position for the cell (X and Y coordinates)
        $pdf->SetXY(50, 110); // Adjust the X and Y position as required

        // Render the wrapped text using MultiCell
        $pdf->MultiCell($cellWidth, $lineHeight, $wrappedText, 0, 'C');

        // Optional: Reset position for subsequent content
        $newY = $pdf->GetY(); // Get the new Y position after text rendering
        $pdf->SetY($newY + 5); // Adjust Y to avoid overlapping with next elements

        // Generate the QR code URL dynamically
        $a = 'https://login.rssi.in/rssi-member/getdetails.php?scode=';
        $b = $array['scode'];  // Assuming $array['scode'] is fetched from the database
        $url = $a . $b;
        $url = urlencode($url);

        // QR code image URL (the API to generate QR code)
        $qrCodeUrl = "https://qrcode.tec-it.com/API/QRCode?data=" . $url;

        // Temporary filename for QR code image
        $tempQrFile = tempnam(sys_get_temp_dir(), 'qr_') . '.png';

        // Download the QR code image and save it temporarily
        file_put_contents($tempQrFile, file_get_contents($qrCodeUrl));

        // Set the position for the QR code (e.g., bottom right of the page)
        $pdf->Image($tempQrFile, 20, 145, 35, 35); // Adjust position and size (in mm)

        // Output the PDF to the browser for download
        $pdf->Output($array['certificate_no'] . '.pdf', 'D');

        // Delete the temporary QR code file after use
        unlink($tempQrFile);

        // Output the PDF to the browser for download
        $pdf->Output('D', $array['certificate_no'] . '.pdf');  // 'D' means download
        ?>
    <?php } ?>
<?php } else { ?>
    <p class="no-print">Please enter Associate ID.</p>
<?php }

// End output buffering and clean it
ob_end_flush();
?>