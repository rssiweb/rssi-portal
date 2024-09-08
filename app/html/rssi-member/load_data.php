<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");
include("../../util/drive.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

// Get the requested view
$view = isset($_POST['view']) ? $_POST['view'] : 'stock_add';

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
        break;
    case 'stock_distribution':
        $query = "
        SELECT
            d.transaction_out_id AS Ref,
            d.date AS date_distribution,
            d.distributed_to,
            COALESCE(m.fullname, s.studentname) AS distributed_to_name,
            i.item_name,
            d.quantity_distributed,
            u.unit_name,
            d.timestamp,
            d.distributed_by
        FROM stock_item i
        JOIN stock_out d ON i.item_id = d.item_distributed
        JOIN stock_item_unit u ON u.unit_id = d.unit
        LEFT JOIN rssimyaccount_members m ON m.associatenumber = d.distributed_to
        LEFT JOIN rssimyprofile_student s ON s.student_id = d.distributed_to
        GROUP BY 
            d.transaction_out_id,
            d.date,
            d.distributed_to,
            m.fullname, s.studentname,
            i.item_name,
            d.quantity_distributed,
            u.unit_name,
            d.timestamp,
            d.distributed_by
        ORDER BY d.timestamp desc;
    ";
        break;
    case 'user_distribution':
        $query = "
    SELECT
        d.distributed_to,
        COALESCE(m.fullname, s.studentname) AS distributed_to_name,
        i.item_name,
        COALESCE(SUM(d.quantity_distributed), 0) AS total_distributed_count,
        u.unit_name
    FROM stock_out d
    JOIN stock_item i ON i.item_id = d.item_distributed
    JOIN stock_item_unit u ON u.unit_id = d.unit
    LEFT JOIN rssimyaccount_members m ON m.associatenumber = d.distributed_to
    LEFT JOIN rssimyprofile_student s ON s.student_id = d.distributed_to
    GROUP BY d.distributed_to, m.fullname, s.studentname, i.item_id, i.item_name, u.unit_id, u.unit_name
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
    $data[] = $row;
}

// Output the data in HTML table format
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
        if ($key == 'date_received' || $key == 'timestamp' || $key == 'date_distribution') {
            // Check if value is not null or empty
            if (!empty($value)) {
                $date = new DateTime($value);
                // Format dates in dd/mm/yyyy and timestamps in dd/mm/yyyy hh:mm AM/PM
                $formattedValue = ($key == 'timestamp') ? $date->format('d/m/Y h:i A') : $date->format('d/m/Y');
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


pg_close($con);
