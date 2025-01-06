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
    $created_by = $associatenumber; // Replace this with dynamic user identification logic (e.g., session)

    // Initialize variables for the event image URL and upload status
    $event_image_url = null;

    // Handle file upload for the event image
    if (!empty($_FILES['event_image']['name'])) {
        $event_image = $_FILES['event_image'];

        // Check if file size is less than 300 KB
        if ($event_image['size'] > 300 * 1024) {
            echo "<script>alert('Image size should not exceed 300 KB.');</script>";
            exit;
        }

        // Resize image to 800x400 (client-side)
        // This requires JavaScript and client-side logic (covered below in HTML)

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
                if (window.history.replaceState) {
                        // Update the URL without causing a page reload or resubmission
                        window.history.replaceState(null, null, window.location.href);
                    }
                    window.location.reload(); // Trigger a page reload to reflect changes
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">
    <style>
        #imagePreview {
            display: none;
            max-width: 100%;
            margin-top: 10px;
        }

        .required-field span {
            color: red;
        }
    </style>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Create Event</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item active"><a href="#">Create Event</a></li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section dashboard">
            <div class="row">

                <!-- Reports -->
                <div class="col-12">
                    <div class="card">

                        <div class="card-body">
                            <br>
                            <div class="container mt-5">
                                <!-- <h2>Create Event</h2> -->

                                <?php if (isset($successMessage)): ?>
                                    <div class="alert alert-success"><?= $successMessage; ?></div>
                                <?php elseif (isset($error)): ?>
                                    <div class="alert alert-danger"><?= $error; ?></div>
                                <?php endif; ?>

                                <form method="POST" action="create_event.php" enctype="multipart/form-data">
                                    <div class="mb-3">
                                        <label for="event_name" class="form-label required-field">Event Name</label>
                                        <input type="text" class="form-control" id="event_name" name="event_name" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="event_description" class="form-label">Event Description</label>
                                        <textarea class="form-control" id="event_description" name="event_description" rows="3"></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label for="event_date" class="form-label required-field">Event Date</label>
                                        <input type="datetime-local" class="form-control" id="event_date" name="event_date" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="event_location" class="form-label required-field">Event Location</label>
                                        <select class="form-select" id="event_location" name="event_location" required>
                                            <option>Lucknow</option>
                                            <option>West Bengal</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="event_image" class="form-label required-field">Event Image</label>
                                        <input type="file" class="form-control" id="event_image" name="event_image" accept="image/*">
                                        <img id="imagePreview" src="#" alt="Image Preview" style="max-width: 800px; height: 400px;">
                                    </div>
                                    <button type="submit" class="btn btn-primary">Create Event</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div><!-- End Reports -->
            </div>
        </section>

    </main><!-- End #main -->

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {
            // Loop through all input, select, and textarea fields with the 'required' attribute
            $('form').find('input[required], select[required], textarea[required]').each(function() {
                var label = $(this).prev('label'); // Get the label preceding the input, select, or textarea

                // Check if the field is required and the asterisk isn't already added
                if ($(this).prop('required') && !label.find('span').length) {
                    label.append(' <span style="color: red">*</span>'); // Append red asterisk
                }
            });

            // Image preview and validation
            $('#event_image').on('change', function(event) {
                const file = event.target.files[0];

                // Check if a file was selected
                if (file) {
                    // Validate image size (300 KB limit)
                    if (file.size > 300 * 1024) {
                        alert("Image size should not exceed 300 KB.");
                        $('#event_image').val(''); // Reset file input
                        $('#imagePreview').hide(); // Hide preview
                        return;
                    }

                    // Create image object for preview
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const img = new Image();
                        img.src = e.target.result;

                        img.onload = function() {
                            // Check if the image is 800x400
                            if (img.width !== 800 || img.height !== 400) {
                                alert("Image should be resized to 800x400.");
                                $('#event_image').val(''); // Reset file input
                                $('#imagePreview').hide(); // Hide preview
                                return;
                            }
                            // Show image preview
                            $('#imagePreview').attr('src', e.target.result).show();
                        };
                    };
                    reader.readAsDataURL(file);
                }
            });
        });
    </script>
</body>

</html>