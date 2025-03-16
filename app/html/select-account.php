<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Association Type</title>
    <link href="../img/favicon.ico" rel="icon">
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .container {
            background: #fff;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            width: 100%;
            text-align: center;
        }

        .container h2 {
            margin-bottom: 1.5rem;
            font-size: 1.75rem;
            color: #333;
        }

        .form-select {
            margin-bottom: 1.5rem;
        }

        .btn-primary {
            width: 100%;
            padding: 0.75rem;
            font-size: 1rem;
            position: relative;
        }

        .btn-primary:disabled {
            opacity: 0.7;
        }

        .loader {
            display: none;
            margin-left: 10px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>Help us redirect you to your account</h2>
        <p class="text-muted mb-4">Select your association type with Rina Shiksha Sahayak Foundation</p>
        <select class="form-select" id="accountType">
            <option value="" disabled selected>Select your association type</option>
            <option value="rssi-member/index.php">Associate</option>
            <option value="tap/index.php">Applicant</option>
            <option value="iexplore/home.php">iExplore</option>
            <option value="rssi-student/index.php">Student</option>
        </select>
        <button class="btn btn-primary" id="goButton">
            <span id="buttonText">Go</span>
            <span class="loader" id="loader">Redirecting...</span>
        </button>
    </div>

    <!-- Bootstrap 5.3 JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
    <script>
        // Function to reset the button and form to their initial state
        function resetForm() {
            const button = document.getElementById("goButton");
            const buttonText = document.getElementById("buttonText");
            const loader = document.getElementById("loader");

            button.disabled = false; // Enable the button
            buttonText.style.display = "inline-block"; // Show the "Go" text
            loader.style.display = "none"; // Hide the "Redirecting..." loader
            document.getElementById("accountType").selectedIndex = 0; // Reset the dropdown to the default option
        }

        // Add event listener to the "Go" button
        document.getElementById("goButton").addEventListener("click", function() {
            const selectedValue = document.getElementById("accountType").value;
            if (selectedValue) {
                // Disable the button and show the loader
                const button = document.getElementById("goButton");
                const buttonText = document.getElementById("buttonText");
                const loader = document.getElementById("loader");

                button.disabled = true; // Disable the button
                buttonText.style.display = "none"; // Hide the "Go" text
                loader.style.display = "inline-block"; // Show the "Redirecting..." loader

                // Redirect to the selected URL after a short delay (for visual effect)
                setTimeout(() => {
                    window.location.href = selectedValue;
                }, 1000); // 1-second delay before redirection
            } else {
                alert("Please select an association type."); // Show alert if no option is selected
            }
        });

        // Reset the form when the page is shown (including when navigating back)
        window.addEventListener("pageshow", resetForm);
    </script>
</body>

</html>