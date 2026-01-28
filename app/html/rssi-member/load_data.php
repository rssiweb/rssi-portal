<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");
include("../../util/drive.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}

// Get the requested view and academic year
$view = isset($_POST['view']) ? $_POST['view'] : 'stock_add';
$academicYear = isset($_POST['academic_year']) ? $_POST['academic_year'] : null;

// Function to check if a date falls within an academic year (April 1 to March 31)
function isInAcademicYear($date, $academicYear)
{
    if (empty($date) || empty($academicYear)) return false;

    list($startYear, $endYear) = explode('-', $academicYear);
    $startDate = new DateTime($startYear . '-04-01');
    $endDate = new DateTime($endYear . '-03-31 23:59:59');
    $checkDate = new DateTime($date);

    return ($checkDate >= $startDate && $checkDate <= $endDate);
}

// Query based on the view
switch ($view) {
    case 'stock_add':
        $query = "
            SELECT
                a.date_received,
                i.item_name,
                a.quantity_received,
                u.unit_name,
                a.source,
                a.description,
                a.timestamp,
                a.added_by
            FROM stock_item i
            JOIN stock_add a ON i.item_id = a.item_id
            JOIN stock_item_unit u ON u.unit_id = a.unit_id
            GROUP BY 
                a.date_received,
                a.source,
                a.description,
                a.timestamp,
                a.added_by,
                i.item_name,
                u.unit_name,
                a.quantity_received
            ORDER BY a.timestamp desc;
        ";
        $dateField = 'date_received';
        break;
    case 'stock_distribution':
        $query = "
            SELECT
                d.transaction_out_id AS Ref,
                d.date AS date_distribution,
                d.distributed_to,
                COALESCE(m.fullname, s.studentname, h.name) AS distributed_to_name,
                i.item_name,
                d.quantity_distributed,
                u.unit_name,
                d.description,
                d.timestamp,
                d.distributed_by
            FROM stock_item i
            JOIN stock_out d ON i.item_id = d.item_distributed
            JOIN stock_item_unit u ON u.unit_id = d.unit
            LEFT JOIN rssimyaccount_members m ON m.associatenumber = d.distributed_to
            LEFT JOIN rssimyprofile_student s ON s.student_id = d.distributed_to
            LEFT JOIN public_health_records h ON h.id::text = d.distributed_to
            GROUP BY 
                d.transaction_out_id,
                d.date,
                d.distributed_to,
                m.fullname, s.studentname, h.name,
                i.item_name,
                d.quantity_distributed,
                u.unit_name,
                d.description,
                d.timestamp,
                d.distributed_by
            ORDER BY d.timestamp desc;
        ";
        $dateField = 'date_distribution';
        break;
    case 'user_distribution':
        $query = "
            SELECT
                d.distributed_to,
                COALESCE(m.fullname, s.studentname, h.name) AS distributed_to_name,
                i.item_name,
                SUM(d.quantity_distributed) AS total_distributed_count,
                u.unit_name,
                MIN(d.date) AS first_distribution_date,
                MAX(d.date) AS last_distribution_date
            FROM stock_out d
            JOIN stock_item i ON i.item_id = d.item_distributed
            JOIN stock_item_unit u ON u.unit_id = d.unit
            LEFT JOIN rssimyaccount_members m ON m.associatenumber = d.distributed_to
            LEFT JOIN rssimyprofile_student s ON s.student_id = d.distributed_to
            LEFT JOIN public_health_records h ON h.id::text = d.distributed_to
        ";

        // Add WHERE clause for academic year filtering if selected
        if ($academicYear) {
            list($startYear, $endYear) = explode('-', $academicYear);
            $startDate = $startYear . '-04-01';
            $endDate = $endYear . '-03-31';
            $query .= " WHERE d.date BETWEEN '$startDate' AND '$endDate'";
        }

        $query .= "
            GROUP BY d.distributed_to, m.fullname, s.studentname, h.name, i.item_id, i.item_name, u.unit_id, u.unit_name
            ORDER BY d.distributed_to, i.item_id, u.unit_id;
        ";
        break;
    default:
        echo 'Invalid view.';
        exit;
}

$result = pg_query($con, $query);

if (!$result) {
    echo "Error: " . pg_last_error($con);
    exit;
}

$data = [];
while ($row = pg_fetch_assoc($result)) {
    // For user_distribution, we don't need additional PHP filtering since it's done in SQL
    if ($view !== 'user_distribution' && $academicYear && isset($dateField)) {
        if (isInAcademicYear($row[$dateField], $academicYear)) {
            $data[] = $row;
        }
    } else {
        $data[] = $row; // No additional filtering needed
    }
}

// Output the data in HTML table format
if (empty($data)) {
    echo '<div class="alert alert-info">No data found for the selected academic year.</div>';
} else {
    echo '<div class="table-responsive">
          <table class="table" id="table-id">';
    echo '<thead><tr>';
    foreach (array_keys($data[0]) as $key) {
        echo "<th>" . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . "</th>";
    }
    echo '</tr></thead><tbody>';

    foreach ($data as $row) {
        echo '<tr>';
        foreach ($row as $key => $value) {
            // Format the date fields
            if (in_array($key, ['date_received', 'timestamp', 'date_distribution', 'first_distribution_date', 'last_distribution_date'])) {
                if (!empty($value)) {
                    $date = new DateTime($value);
                    $formattedValue = (strpos($key, 'timestamp') !== false) ?
                        $date->format('d/m/Y h:i A') : $date->format('d/m/Y');
                } else {
                    $formattedValue = '';
                }
            } else {
                $formattedValue = $value ?? '';
            }
            echo "<td>" . htmlspecialchars($formattedValue, ENT_QUOTES, 'UTF-8') . "</td>";
        }
        echo '</tr>';
    }
    echo '</tbody></table></div>';
}


