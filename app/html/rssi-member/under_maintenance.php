<?php
require_once __DIR__ . "/../../bootstrap.php"; // Include the database connection

// Get the original page name from the query parameter
$originalPage = isset($_GET['page']) ? $_GET['page'] : null;

// Fetch the end_date from the active_maintenance table
$endDate = null;
if ($originalPage) {
    $query = "SELECT end_date FROM active_maintenance WHERE page_name = $1;";
    $result = pg_query_params($con, $query, [$originalPage]);

    if ($result && pg_num_rows($result) > 0) {
        $row = pg_fetch_assoc($result);
        $endDate = $row['end_date'];
    }
}

// Format the end_date if it exists
$formattedEndDate = '';
if ($endDate) {
    $date = new DateTime($endDate);
    $date->setTimezone(new DateTimeZone('Asia/Kolkata')); // Convert to IST
    $formattedEndDate = $date->format('d/m/Y h:i A') . ' IST';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Under Maintenance</title>
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <style>
        body {
            /* font-family: Arial, sans-serif; */
            text-align: center;
            /* padding: 50px; */
            background-color: #f4f4f4;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            max-width: 800px;
            width: 100%;
        }

        .content {
            flex: 1;
            text-align: left;
        }

        h1 {
            color: #ff5733;
            margin-bottom: 20px;
        }

        p {
            color: #333;
            margin-bottom: 20px;
        }

        #countdown {
            font-size: 24px;
            font-weight: bold;
            color: #ff5733;
        }

        .vector-image {
            width: 50%;
            margin-left: 40px;
        }
    </style>
    <script>
        var timeLeft = 10; // Set countdown time in seconds

        function updateTimer() {
            document.getElementById("countdown").textContent = timeLeft;
            if (timeLeft <= 0) {
                window.location.href = "home.php"; // Redirect to home page
            } else {
                timeLeft--;
                setTimeout(updateTimer, 1000);
            }
        }

        window.onload = updateTimer; // Start countdown on page load
    </script>
</head>

<body>
    <div class="container">
        <div class="content">
            <h1>Under Maintenance</h1>
            <p>It’s not you, it’s us! We’re working hard to improve your experience.</p>
            <?php if ($formattedEndDate): ?>
                <p>We expect to be back by <strong><?php echo $formattedEndDate; ?></strong>.</p>
            <?php endif; ?>
            <p>Redirecting to homepage in <span id="countdown">10</span> seconds...</p>
        </div>
        <img src="../img/6029646.jpg" alt="Maintenance" class="vector-image">
    </div>
</body>

</html>