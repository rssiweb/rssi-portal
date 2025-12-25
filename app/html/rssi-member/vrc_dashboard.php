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

// Ensure a valid database connection
if (!$con) {
    echo "Error: Unable to connect to the database.";
    exit;
}

// Fetch data from the database
$query = "SELECT unique_id, application_number, name, video,timestamp,ip_address FROM vrc order by timestamp desc";
$result = pg_query($con, $query);

if (!$result) {
    echo "Error: " . pg_last_error($con);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VRC Dashboard</title>
    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-5">
        <h2 class="mb-4">Virtual Response Center - Dashboard</h2>

        <?php if (pg_num_rows($result) > 0): ?>
            <table class="table table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>Unique ID</th>
                        <th>Submitted on</th>
                        <th>IP Address</th>
                        <th>Application Number</th>
                        <th>Name</th>
                        <th>Video</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = pg_fetch_assoc($result)): ?>
                        <?php
                        // Prepare video source as a base64-encoded data URL
                        $videoSrc = "data:video/webm;base64," . base64_encode(base64_decode($row['video']));
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['unique_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['timestamp']); ?></td>
                            <td><?php echo htmlspecialchars($row['ip_address']); ?></td>
                            <td><?php echo htmlspecialchars($row['application_number']); ?></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td>
                                <video width="320" height="240" controls>
                                    <source src="<?php echo $videoSrc; ?>" type="video/webm">
                                    Your browser does not support the video tag.
                                </video>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-info">No interviews found.</div>
        <?php endif; ?>

    </div>

    <?php
    // Close the database connection
    pg_close($con);
    ?>
</body>

</html>