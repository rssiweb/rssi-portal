<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}
validation();
?>
<?php
// SQL query
$sql = "SELECT s.family_id, s.contact, s.parent_name, sd.student_name, sd.age, sd.gender, sd.grade, s.timestamp, s.surveyor_id, s.address, rm.fullname, s.earning_source, s.other_earning_source_input, sd.already_going_school, sd.school_type, sd.already_coaching, sd.coaching_name
        FROM survey_data s 
        LEFT JOIN student_data sd ON s.family_id = sd.family_id
        JOIN rssimyaccount_members rm ON s.surveyor_id = rm.associatenumber";

$result = pg_query($con, $sql);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Table</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
</head>

<body>
    <div class="container">
        <div class="row">
            <div class="col">
                <?php
                // Display data
                if ($result) {
                    echo '<div class="table-responsive">';
                    echo '<table class="table table-striped">';
                    echo '<thead>';
                    echo '<tr>';
                    echo '<th>#</th>'; // Serial number column
                    echo '<th>Family ID</th>';
                    echo '<th>Address</th>';
                    echo '<th>Contact</th>';
                    echo '<th>Parent Name</th>';
                    echo '<th>Student Name</th>';
                    echo '<th>Age</th>';
                    echo '<th>Gender</th>';
                    echo '<th>Grade</th>';
                    echo '<th>Timestamp</th>';
                    echo '<th>Misc</th>';
                    echo '<th>Surveyor Name</th>';
                    echo '</tr>';
                    echo '</thead>';
                    echo '<tbody>';

                    $serialNumber = 1; // Initialize serial number
                    while ($row = pg_fetch_assoc($result)) {
                        echo '<tr>';
                        echo '<td>' . $serialNumber++ . '</td>'; // Display and increment serial number
                        echo '<td>' . $row["family_id"] . '</td>';
                        // Shorten the address
                        $shortAddress = strlen($row["address"]) > 30 ? substr($row["address"], 0, 30) . "..." : $row["address"];
                        // Display the shortened address with "more" link
                        echo '<td><span class="short-address">' . $shortAddress;
                        // Display the full address (hidden by default)
                        echo '<span class="full-address" style="display: none;">' . $row["address"] . '</span>';
                        echo ' <a href="#" class="more-link">more</a></span></td>';
                        echo '<td>' . $row["contact"] . '</td>';
                        echo '<td>' . $row["parent_name"] . '</td>';
                        echo '<td>' . $row["student_name"] . '</td>';
                        echo '<td>' . $row["age"] . '</td>';
                        echo '<td>' . $row["gender"] . '</td>';
                        echo '<td>' . $row["grade"] . '</td>';
                        echo '<td>' . date('d/m/Y h:i A', strtotime($row["timestamp"])) . '</td>';
                        // Button to trigger modal for "Misc" data
                        echo '<td><a href="#" class="misc-link" data-bs-toggle="modal" data-bs-target="#miscModal' . $row["family_id"] . '">View Details</a></td>';
                        echo '<td>' . $row["fullname"] . '</td>';
                        echo '</tr>';

                        // Modal for "Misc" data
                        echo '<div class="modal fade" id="miscModal' . $row["family_id"] . '" tabindex="-1" aria-labelledby="miscModalLabel' . $row["family_id"] . '" aria-hidden="true">';
                        echo '<div class="modal-dialog">';
                        echo '<div class="modal-content">';
                        echo '<div class="modal-header">';
                        echo '<h5 class="modal-title" id="miscModalLabel' . $row["family_id"] . '">Miscellaneous Data</h5>';
                        echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
                        echo '</div>';
                        echo '<div class="modal-body">';
                        // Display "Misc" data
                        echo '<p>Student Name: ' . $row["student_name"] . '&nbsp;(' . $row["family_id"] . ')</p>';
                        echo '<p>Family Earning Source: ';
                        if ($row["earning_source"] == "other") {
                            echo $row["other_earning_source_input"];
                        } else {
                            echo $row["earning_source"];
                        }
                        echo '</p>';
                        echo '<p>Already Going to School: ' . $row["already_going_school"] . '</p>';
                        echo '<p>School Type: ' . $row["school_type"] . '</p>';
                        echo '<p>Already Coaching: ' . $row["already_coaching"] . '</p>';
                        echo '<p>Coaching Name: ' . $row["coaching_name"] . '</p>';
                        echo '</div>';
                        echo '<div class="modal-footer">';
                        echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
                        echo '</div>';
                        echo '</div>';
                        echo '</div>';
                        echo '</div>';
                    }

                    echo '</tbody>';
                    echo '</table>';
                    echo '</div>';
                } else {
                    echo "Error executing query: " . pg_last_error($con);
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS (optional) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
        $(document).ready(function() {
            // Toggle full address visibility on "more" link click
            $('.more-link').click(function(e) {
                e.preventDefault();
                var shortAddress = $(this).siblings('.short-address');
                var fullAddress = $(this).siblings('.full-address');
                if (fullAddress.is(':visible')) {
                    // If full address is visible, toggle to show short address
                    shortAddress.show();
                    fullAddress.hide();
                    $(this).text('more');
                } else {
                    // If short address is visible, toggle to show full address
                    shortAddress.hide();
                    fullAddress.show();
                    $(this).text('less');
                }
            });
        });
    </script>

</body>

</html>