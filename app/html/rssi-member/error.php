<?php
// Exit immediately and prevent further execution
exit('
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Under Maintenance</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            padding: 50px;
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
            width: 200px;
            margin-left: 40px;
        }
    </style>
    <script>
        var timeLeft = 5; // Set countdown time in seconds

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
            <h1>🚧 Under Maintenance 🚧</h1>
            <p>It’s not you, it’s us! We’re working hard to improve your experience. Please check back soon.</p>
            <p>Redirecting to homepage in <span id="countdown">5</span> seconds...</p>
        </div>
        <img src="../img/maintenance.jpg" alt="Maintenance" class="vector-image">
    </div>
</body>
</html>
');
