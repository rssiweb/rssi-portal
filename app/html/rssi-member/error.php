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
        }
        .container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
            display: inline-block;
        }
        h1 {
            color: #ff5733;
        }
        p {
            color: #333;
        }
    </style>
    <script>
        // Redirect to the home page after 5 seconds
        setTimeout(function() {
            window.location.href = "home.php";
        }, 5000);
    </script>
</head>
<body>
    <div class="container">
        <h1>🚧 Under Maintenance 🚧</h1>
        <p>We are currently performing maintenance. Please check back soon.</p>
        <p>You will be redirected to the homepage in a few seconds...</p>
    </div>
</body>
</html>
');
