<?php
require_once __DIR__ . "/../../bootstrap.php"; // Assuming this establishes the $con connection
include("../../util/login_util.php");

// Check if user is logged in
if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}
validation();
?>

<?php
// Assuming $con is a valid PostgreSQL connection established by pg_connect

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $exception_date = pg_escape_string($con, $_POST['exception_date']);  // Prevent SQL injection
    $is_workday = isset($_POST['is_workday']) ? true : false;

    // Insert or update the exception
    $query = "INSERT INTO workday_exceptions (exception_date, is_workday)
              VALUES ('$exception_date', '$is_workday')
              ON CONFLICT (exception_date) 
              DO UPDATE SET is_workday = '$is_workday'";

    // Execute the query
    pg_query($con, $query);
}

// Fetch existing exceptions
$query = "SELECT * FROM workday_exceptions";
$result = pg_query($con, $query);
?>

<!-- Form to add or modify exceptions -->
<form method="POST">
    <label for="exception_date">Select Date:</label>
    <input type="date" id="exception_date" name="exception_date" required>
    <label for="is_workday">Is Workday:</label>
    <input type="checkbox" id="is_workday" name="is_workday">
    <button type="submit">Add/Modify Exception</button>
</form>

<h3>Current Exceptions:</h3>
<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>Workday</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = pg_fetch_assoc($result)): ?>
            <tr>
                <td><?php echo $row['exception_date']; ?></td>
                <td><?php echo $row['is_workday'] ? 'Yes' : 'No'; ?></td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>
