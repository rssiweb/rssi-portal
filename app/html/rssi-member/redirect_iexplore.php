<?php
require_once __DIR__ . "/../../bootstrap.php";

// Check if $_SESSION['aid'] is set
if (!isset($_SESSION['aid'])) {
    // If not set, show JavaScript alert and redirect to index.php
    echo "<script>alert('Unauthorized access!'); window.location.href = 'index.php';</script>";
    exit(); // Stop further execution
}

$user_check = $_SESSION['aid'];

// Step 1: Retrieve name and phone from rssimyaccount_members table
$rssi_user_query = pg_query($con, "SELECT fullname, phone FROM rssimyaccount_members WHERE email='$user_check'");
$rssi_user = pg_fetch_assoc($rssi_user_query);

if ($rssi_user) {
    // Data is available in rssimyaccount_members
    $name = $rssi_user['fullname']; // Get the name
    $phone = $rssi_user['phone']; // Get the phone number

    // Step 2: Insert the user into test_users table if they don't already exist
    $insert_query = pg_query_params(
        $con,
        "INSERT INTO test_users (id, name, email, contact, user_type, created_at)
         SELECT $1, $2, $3::VARCHAR, $4, $5, $6
         WHERE NOT EXISTS (
             SELECT 1 FROM test_users WHERE email = $3::VARCHAR
         )",
        [
            str_pad(mt_rand(0, 999999999999), 12, '0', STR_PAD_LEFT), // Generate a new user ID
            $name,
            $user_check,
            $phone,
            'rssi-member',
            date('Y-m-d H:i:s')
        ]
    );

    if (!$insert_query) {
        // Log the error instead of dying (optional)
        error_log("Error inserting user into test_users table: " . pg_last_error($con));
    }

    // Step 3: Update user_type to 'rssi-member' if the user already exists and their user_type is not 'rssi-member'
    $update_query = pg_query_params(
        $con,
        "UPDATE test_users
         SET user_type = $1
         WHERE email = $2::VARCHAR AND user_type != $1",
        [
            'rssi-member',
            $user_check
        ]
    );

    if (!$update_query) {
        // Log the error instead of dying (optional)
        error_log("Error updating user_type in test_users table: " . pg_last_error($con));
    }
} else {
    // Data is not available in rssimyaccount_members
    echo "User data not found in rssimyaccount_members. Skipping insertion in test_users table.";
}

session_write_close(); // Close iexplore-session
// Step 4: Start iexplore-session
session_name('iexplore-session');
session_start();

// Step 5: Copy data to iexplore-session
$_SESSION['eid'] = $user_check; // Copy aid
$_SESSION['user_type'] = 'rssi-member'; // Copy user_type

// Step 6: Close iexplore-session
session_write_close(); // Close iexplore-session
session_name('rssi-member-session');
session_start();

// Capture all query parameters from the URL
$params = $_GET;
?>
<!doctype html>
<html lang="en">

<head>
    <script>
        window.onload = (event) => {
            console.log("page is fully loaded");

            // Function to get query parameters from the current URL
            function getQueryParams() {
                const params = {};
                const queryString = window.location.search.substring(1);
                const pairs = queryString.split('&');

                pairs.forEach(pair => {
                    const [key, value] = pair.split('=');
                    if (key) {
                        params[decodeURIComponent(key)] = decodeURIComponent(value || '');
                    }
                });

                return params;
            }

            // Get the parameters from the current URL
            const params = getQueryParams();

            // Construct the base URL
            let url = '/iexplore/' + (params.path || 'home') + '.php';

            // Add additional parameters (excluding 'path')
            const queryParams = [];
            for (const key in params) {
                if (key !== 'path' && params[key]) {
                    queryParams.push(`${encodeURIComponent(key)}=${encodeURIComponent(params[key])}`);
                }
            }

            // Append query parameters to the URL
            if (queryParams.length > 0) {
                url += '?' + queryParams.join('&');
            }

            // Redirect to the constructed URL
            window.location = url;
        };
    </script>
</head>

<body>
</body>

</html>