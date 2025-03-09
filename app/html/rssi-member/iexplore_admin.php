<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
}

validation();
date_default_timezone_set('Asia/Kolkata');

// Retrieve course ID from form input
@$courseid_search = $_GET['courseid_search'];

// Query database for course information based on ID
$result = pg_query($con, "SELECT * FROM wbt WHERE courseid = '$courseid_search'");

if (!$result) {
    echo "An error occurred.\n";
    exit;
}

$resultArr = pg_fetch_all($result);

if ($resultArr) {
    foreach ($resultArr as $row) {
    }
}

// Fetch study materials for the course
$studyMaterials = [];
if (!empty($courseid_search)) {
    $studyMaterialsQuery = pg_query($con, "SELECT material_name, link FROM wbt_study_materials WHERE courseid = '$courseid_search'");
    if ($studyMaterialsQuery) {
        $studyMaterials = pg_fetch_all($studyMaterialsQuery);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=AW-11316670180"></script>
    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }
        gtag('js', new Date());
        gtag('config', 'AW-11316670180');
    </script>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Modify Course</title>
    <meta content="" name="description">
    <meta content="" name="keywords">
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">
    <!-- Glow Cookies -->
    <script src="https://cdn.jsdelivr.net/gh/manucaralmo/GlowCookies@3.0.1/src/glowCookies.min.js"></script>
    <script>
        glowCookies.start('en', {
            analytics: 'G-S25QWTFJ2S',
            policyLink: 'https://www.rssi.in/disclaimer'
        });
    </script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
    <!-- JavaScript Library Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>

</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>
    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Modify Course</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">iExplore Learner</a></li>
                    <li class="breadcrumb-item active">Modify Course</li>
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
                            <div class="container">
                                <?php if ($role == 'Admin') { ?>
                                    <?php if (@$courseid != null && @$cmdtuples == 0) { ?>
                                        <div class="alert alert-danger alert-dismissible text-center" role="alert">
                                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                            <i class="bi bi-exclamation-triangle"></i>
                                            <span>ERROR: Oops, something wasn't right.</span>
                                        </div>
                                    <?php } else if (@$cmdtuples == 1) { ?>
                                        <div class="alert alert-success alert-dismissible text-center" role="alert">
                                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                            <i class="bi bi-check2-circle"></i>
                                            <span>Database has been updated successfully for course id <?php echo @$courseid ?>.</span>
                                        </div>
                                    <?php } ?>
                                    <div class="container">
                                        <div class="row">
                                            <div class="col-6">
                                                <h3>Search Course</h3>
                                                <!-- Course Selection Form -->
                                                <form id="courseSearchForm" action="" method="GET" class="mb-5">
                                                    <div class="mb-3">
                                                        <select id="courseid_search" name="courseid_search" class="form-select" required>
                                                            <option value="">Search for a Course</option>
                                                        </select>
                                                        <div class="form-text">Start typing the course name or any part of it. Matching results will appear as you type.</div>
                                                    </div>
                                                    <button type="submit" name="courseid_search_button" id="courseSearchButton" class="btn btn-primary">
                                                        Get Details
                                                    </button>
                                                </form>

                                                <form autocomplete="off" name="wbt" id="wbt" method="POST">
                                                    <input type="hidden" name="form-type" value="wbt">
                                                    <div class="mb-3">
                                                        <input type="text" name="courseid" class="form-control" placeholder="Course ID" value="<?php echo @$row['courseid']; ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <input type="text" name="coursename" class="form-control" placeholder="Course Name" value="<?php echo @$row['coursename']; ?>" required>
                                                    </div>
                                                    <div class="row mb-3">
                                                        <div class="col">
                                                            <select name="language" class="form-select" required>
                                                                <option disabled selected>Select Language</option>
                                                                <?php
                                                                $languages = array("English", "Hindi", "Bengali");
                                                                foreach ($languages as $language) {
                                                                    $selected = ($language == @$row['language']) ? "selected" : "";
                                                                    echo "<option $selected>$language</option>";
                                                                }
                                                                ?>
                                                            </select>
                                                        </div>
                                                        <div class="col">
                                                            <select name="type" class="form-select" required>
                                                                <option disabled selected>Select Type</option>
                                                                <?php
                                                                $types = array("Internal", "External");
                                                                foreach ($types as $type) {
                                                                    $selected = ($type == @$row['type']) ? "selected" : "";
                                                                    echo "<option $selected>$type</option>";
                                                                }
                                                                ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="mb-3">
                                                        <input type="number" name="passingmarks" max="100" min="0" class="form-control" placeholder="Mastery Score" value="<?php echo @$row['passingmarks']; ?>">
                                                    </div>
                                                    <div class="mb-3">
                                                        <input type="text" name="url" class="form-control" placeholder="URL" value="<?php echo @$row['url']; ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <select name="validity" class="form-select" required>
                                                            <option disabled selected>Select Validity</option>
                                                            <?php
                                                            $validities = array("0.5", "1", "2", "3", "5", "100");
                                                            foreach ($validities as $validity) {
                                                                $selected = ($validity == @$row['validity']) ? "selected" : "";
                                                                echo "<option $selected>$validity</option>";
                                                            }
                                                            ?>
                                                        </select>
                                                        <div class="form-text">Choose the validity period in years. 0.5 represents 6 months.</div>
                                                    </div>
                                                    <input type="hidden" name="issuedby" class="form-control" value="<?php echo $fullname ?>" required readonly>

                                                    <!-- Study Materials Section -->
                                                    <div class="mb-3">
                                                        <h5>Study Materials</h5>
                                                        <div id="study-materials-container">
                                                            <?php if (!empty($studyMaterials)) : ?>
                                                                <?php foreach ($studyMaterials as $material) : ?>
                                                                    <div class="study-material-item mb-2">
                                                                        <div class="row">
                                                                            <div class="col">
                                                                                <input type="text" name="material_name[]" class="form-control" placeholder="Material Name" value="<?php echo $material['material_name']; ?>" required>
                                                                            </div>
                                                                            <div class="col">
                                                                                <input type="url" name="material_link[]" class="form-control" placeholder="Material Link" value="<?php echo $material['link']; ?>" required>
                                                                            </div>
                                                                            <div class="col-auto">
                                                                                <button type="button" class="btn btn-danger btn-sm remove-material">Remove</button>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                <?php endforeach; ?>
                                                            <?php else : ?>
                                                                <div class="study-material-item mb-2">
                                                                    <div class="row">
                                                                        <div class="col">
                                                                            <input type="text" name="material_name[]" class="form-control" placeholder="Material Name" required>
                                                                        </div>
                                                                        <div class="col">
                                                                            <input type="url" name="material_link[]" class="form-control" placeholder="Material Link" required>
                                                                        </div>
                                                                        <div class="col-auto">
                                                                            <button type="button" class="btn btn-danger btn-sm remove-material">Remove</button>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                        <button type="button" id="add-material" class="btn btn-success btn-sm">Add Material</button>
                                                    </div>

                                                    <div class="form-check mb-3">
                                                        <input type="checkbox" class="form-check-input" id="mandatory_course" name="mandatory_course" value='1'
                                                            <?php echo (@$row['is_mandatory'] == 't') ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="mandatory_course">Mark as Mandatory Course</label>
                                                    </div>

                                                    <div class="form-check mb-3">
                                                        <input type="checkbox" class="form-check-input" id="addNewCheckbox">
                                                        <label class="form-check-label" for="addNewCheckbox">Add New Course</label>
                                                    </div>

                                                    <div class="mb-3">
                                                        <button type="submit" id="submit2" class="btn btn-warning">Update</button>
                                                        <button type="submit" id="submit3" class="btn btn-danger" style="display: none;">Add New</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div><!-- End Reports -->
        </section>
    </main><!-- End #main -->

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>
    <script>
        // JavaScript to handle dynamic addition and removal of study material fields
        document.getElementById('add-material').addEventListener('click', function() {
            const container = document.getElementById('study-materials-container');
            const newItem = document.createElement('div');
            newItem.classList.add('study-material-item', 'mb-2');
            newItem.innerHTML = `
            <div class="row">
                <div class="col">
                    <input type="text" name="material_name[]" class="form-control" placeholder="Material Name" required>
                </div>
                <div class="col">
                    <input type="url" name="material_link[]" class="form-control" placeholder="Material Link" required>
                </div>
                <div class="col-auto">
                    <button type="button" class="btn btn-danger btn-sm remove-material">Remove</button>
                </div>
            </div>
        `;
            container.appendChild(newItem);
        });

        // Event delegation for remove buttons
        document.getElementById('study-materials-container').addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-material')) {
                e.target.closest('.study-material-item').remove();
            }
        });

        // Handle form submission for Update and Add New buttons
        const form = document.getElementById('wbt');
        const submit2Button = document.getElementById('submit2');
        const submit3Button = document.getElementById('submit3');
        const addNewCheckbox = document.getElementById('addNewCheckbox');

        if (submit2Button && submit3Button && addNewCheckbox) {
            // Set initial button visibility
            updateButtonVisibility();

            // Add event listener to checkbox
            addNewCheckbox.addEventListener('change', updateButtonVisibility);

            // Handle form action based on button clicked
            submit2Button.addEventListener('click', function() {
                form.action = 'wbt_update.php';
            });

            submit3Button.addEventListener('click', function() {
                form.action = 'wbt_add.php';
            });
        }

        function updateButtonVisibility() {
            if (addNewCheckbox.checked) {
                // If "Add New Course" is checked, show "Add New" button and hide "Update" button
                submit2Button.style.display = 'none';
                submit3Button.style.display = 'inline-block';
            } else {
                // If "Add New Course" is unchecked, show "Update" button and hide "Add New" button
                submit2Button.style.display = 'inline-block';
                submit3Button.style.display = 'none';
            }
        }
    </script>
    <!-- Initialize Select2 with AJAX -->
    <script>
        $(document).ready(function() {
            $('#courseid_search').select2({
                placeholder: "Enter Course Name",
                allowClear: true,
                ajax: {
                    url: 'fetch_courses.php', // Backend script to fetch course data
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term
                        }; // Pass the search term
                    },
                    processResults: function(data) {
                        return {
                            results: data
                        };
                    }
                }
            });
        });
    </script>
    <!-- JavaScript to Handle Button Disable and Text Change -->
    <script>
        document.getElementById('courseSearchForm').addEventListener('submit', function() {
            let searchButton = document.getElementById('courseSearchButton');
            searchButton.innerHTML = 'Fetching Data...'; // Change button text
            searchButton.disabled = true; // Disable button
        });
    </script>
</body>

</html>