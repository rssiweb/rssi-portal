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
$scode = $_GET['scode'];

// Combined query to fetch both the member and appraisal details based on associatenumber
$query = "
    SELECT 
        m.*, 
        apprisal.max_goalsheet_created_on, 
        apprisal.ipf 
    FROM 
        rssimyaccount_members m
    LEFT JOIN (
        SELECT 
            MAX(goalsheet_created_on) AS max_goalsheet_created_on, 
            appraisee_associatenumber, 
            ipf 
        FROM 
            appraisee_response 
        GROUP BY 
            appraisee_associatenumber, ipf
    ) apprisal ON apprisal.appraisee_associatenumber = m.associatenumber
    WHERE 
        m.scode = '$scode'"; // Use associatenumber for the condition

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
        // Example input dates
        $doj = $array["doj"]; // Date of Joining
        $effectiveFrom = $array["effectivedate"]; // Effective End Date, could be null

        // Parse dates
        $dojDate = new DateTime($doj);
        $currentDate = new DateTime(); // Current date
        $endDate = $effectiveFrom ? new DateTime($effectiveFrom) : $currentDate; // Use effective date if set, otherwise use today

        // Check if DOJ is in the future
        if ($dojDate > $currentDate) {
            // If the DOJ is in the future, display a message
            echo "Not yet commenced";
        } else {
            // Calculate the difference
            $interval = $dojDate->diff($endDate);

            // Extract years, months, and days
            $years = $interval->y;
            $months = $interval->m;
            $days = $interval->d;

            // Determine the format to display
            if ($years > 0) {
                $experience = number_format($years + ($months / 12), 2) . " year(s)";
            } elseif ($months > 0) {
                $experience = number_format($months + ($days / 30), 2) . " month(s)";
            } else {
                $experience = number_format($days, 2) . " day(s)";
            }
        }
        ?>
        <?php
        // Assuming you have fetched data into $array from your database
        $rating = $array['ipf'] / 5 * 100;

        // Calculate grade based on the rating
        if ($rating == 0) {
            $grade = 'E';
        } elseif ($rating >= 20 && $rating < 40) {
            $grade = 'D';
        } elseif ($rating >= 40 && $rating < 50) {
            $grade = 'C2';
        } elseif ($rating >= 50 && $rating < 60) {
            $grade = 'C1';
        } elseif ($rating >= 60 && $rating < 70) {
            $grade = 'B2';
        } elseif ($rating >= 70 && $rating < 80) {
            $grade = 'B1';
        } elseif ($rating >= 80 && $rating < 90) {
            $grade = 'A2';
        } elseif ($rating >= 90) {
            $grade = 'A1';
        }
        ?>
        <?php
        // Create an instance of FPDI
        $pdf = new Fpdi();

        // Set the source file (certificate template PDF)
        $pageCount = $pdf->setSourceFile('../pdf_template/community_service.pdf');

        // Import the first page of the template
        $templateId = $pdf->importPage(1);

        // Add a page in landscape mode with custom size (11 x 8.5 inches)
        $pdf->addPage('L', array(279.4, 215.9)); // Page size: 11 x 8.5 inches in landscape

        // Use the imported template
        $pdf->useTemplate($templateId);

        // Set font for text that will be added to the certificate
        $pdf->AddFont('cambria', '', 'Cambria.php'); //Regular
        $pdf->AddFont('oldenglishtextmt', '', 'oldenglishtextmt.php'); //Regular
        $pdf->SetFont('oldenglishtextmt', '', 26);

        // Set the text color (optional)
        $pdf->SetTextColor(127, 127, 127);  // Black color for text

        // Add dynamic data to the certificate
        // Full Name (from the database)
        $pdf->SetXY(15, 70); // Adjust position for the name
        $pdf->Cell(0, 0, $array['fullname'], 0, 1, 'C'); // Dynamic full name from database

        // Change the text color (optional)
        $pdf->SetTextColor(0, 0, 0);  // Black color for text
        // Change font size for Grade
        $pdf->SetFont('cambria', '', 14); // Set font size for grade
        // Internship Duration (example, use a dynamic value if available)
        $pdf->SetXY(15, 85); // Position for internship duration
        $pdf->Cell(0, 0, 'has successfully completed ' . $experience . ' internship with ' . $grade . ' Grade on', 0, 1, 'C'); // Center aligned text

        // Change font size for Grade
        $pdf->SetFont('cambria', '', 12); // Set font size for grade

        // Grade (with font size 25)
        $pdf->SetXY(15, 158); // Position for grade
        $pdf->Cell(0, 0, $fullname, 0, 1, 'C'); // Center aligned text

        // Project Name (example, use a dynamic value if available)
        $pdf->SetXY(15, 163); // Position for project name
        $pdf->Cell(0, 0, $position, 0, 1, 'C'); // Center aligned text

        // Project Name (example, use a dynamic value if available)
        $pdf->SetXY(15, 170); // Position for project name
        $pdf->Cell(0, 0, date("d/m/Y", strtotime(date('Y-m-d H:i:s'))), 0, 1, 'C'); // Center aligned text

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
        $pdf->Image($tempQrFile, 215, 120, 45, 45); // Adjust position and size (in mm)

        // Output the PDF to the browser for download
        $pdf->Output('D', 'certificate_with_qrcode.pdf');

        // Delete the temporary QR code file after use
        unlink($tempQrFile);

        // // Date (use current date dynamically)
        // $pdf->SetXY(60, 210); // Position for date
        // $pdf->Cell(0, 0, date('d/m/Y'), 0, 1, 'C'); // Current date dynamically

        // Output the PDF to the browser for download
        $pdf->Output('D', 'certificate_with_values_landscape.pdf');  // 'D' means download
        ?>
    <?php } ?>
<?php } else { ?>
    <p class="no-print">Please enter Associate ID.</p>
<?php }

// End output buffering and clean it
ob_end_flush();
?>