<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");
include("../../util/drive.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();
?>
<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize input data
    $event_name = htmlspecialchars($_POST['event_name'], ENT_QUOTES, 'UTF-8');
    $event_description = htmlspecialchars($_POST['event_description'], ENT_QUOTES, 'UTF-8');
    $event_date = $_POST['event_date']; // Ensure the format matches your TIMESTAMP requirement
    $event_location = htmlspecialchars($_POST['event_location'], ENT_QUOTES, 'UTF-8');
    $created_by = 'JohnDoe'; // Replace this with dynamic user identification logic (e.g., session)

    // Initialize variables for the event image URL and upload status
    $event_image_url = null;

    // Handle file upload for the event image
    if (!empty($_FILES['event_image']['name'])) {
        $event_image = $_FILES['event_image'];
        $filename = $event_name . "_image_" . time(); // Unique filename
        $parent_folder_id = '1S6uLPt5G7hX4Iacgzx73gqdXsO-uKA4R'; // Replace with your Google Drive folder ID
        try {
            $event_image_url = uploadeToDrive($event_image, $parent_folder_id, $filename);
        } catch (Exception $e) {
            echo "<script>alert('Error uploading image: " . $e->getMessage() . "');</script>";
            exit;
        }
    }

    // SQL query for insertion
    $sql = "INSERT INTO events (event_name, event_description, event_date, event_location, event_image_url, created_by)
            VALUES ($1, $2, $3, $4, $5, $6)";

    // Use pg_query_params for parameterized query to prevent SQL injection
    $result = pg_query_params($con, $sql, array($event_name, $event_description, $event_date, $event_location, $event_image_url, $created_by));

    if ($result) {
        echo "<script>
                alert('Event successfully created.');
                document.getElementById('createEventForm').reset();
                document.getElementById('submitBtn').disabled = true; // Disable button to prevent resubmission
              </script>";
    } else {
        echo "<script>alert('Failed to create event.');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Event</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script>
        // Function to preview and resize the uploaded image to 800x400
        function previewImage(event) {
            const file = event.target.files[0];
            const reader = new FileReader();

            reader.onload = function() {
                const img = new Image();
                img.src = reader.result;

                img.onload = function() {
                    // Create a canvas to resize the image
                    const canvas = document.createElement('canvas');
                    const ctx = canvas.getContext('2d');

                    // Set canvas dimensions to 800x400
                    canvas.width = 800;
                    canvas.height = 400;

                    // Draw the image on the canvas, resizing it to fit 800x400
                    ctx.drawImage(img, 0, 0, 800, 400);

                    // Get the resized image data
                    const resizedImageUrl = canvas.toDataURL('image/jpeg');

                    // Show the resized image as a preview
                    const preview = document.getElementById('imagePreview');
                    preview.src = resizedImageUrl;
                    preview.style.display = 'block'; // Show the image preview

                    // Optionally, inform the user about the resizing
                    const infoText = document.getElementById('resizeInfo');
                    infoText.style.display = 'block'; // Show the resizing info
                };
            };

            reader.readAsDataURL(file);
        }
    </script>
</head>

<body>
    <div class="container mt-5">
        <h2>Create Event</h2>

        <?php if (isset($successMessage)): ?>
            <div class="alert alert-success"><?= $successMessage; ?></div>
        <?php elseif (isset($error)): ?>
            <div class="alert alert-danger"><?= $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="create_event.php" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="event_name" class="form-label">Event Name</label>
                <input type="text" class="form-control" id="event_name" name="event_name" required>
            </div>
            <div class="mb-3">
                <label for="event_description" class="form-label">Event Description</label>
                <textarea class="form-control" id="event_description" name="event_description" rows="3"></textarea>
            </div>
            <div class="mb-3">
                <label for="event_date" class="form-label">Event Date</label>
                <input type="datetime-local" class="form-control" id="event_date" name="event_date" required>
            </div>
            <div class="mb-3">
                <label for="event_location" class="form-label">Event Location</label>
                <select class="form-select" id="event_location" name="event_location" required>
                    <option>Lucknow</option>
                    <option>West Bengal</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="event_image" class="form-label">Event Image (will be resized to 800x400)</label>
                <input type="file" class="form-control" id="event_image" name="event_image" accept="image/*" onchange="previewImage(event)" required>
                <p id="resizeInfo" style="display:none; color: #ff6600; font-size: 0.9em;">Your image will be resized to 800x400 pixels for uploading.</p>
                <img id="imagePreview" src="#" alt="Image Preview" style="display:none; max-width: 100%; margin-top: 10px;">
            </div>
            <button type="submit" class="btn btn-primary">Create Event</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>