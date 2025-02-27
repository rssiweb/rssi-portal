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
    <title>iExplore</title>
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
    <!-- CSS Library Files -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
    <!-- JavaScript Library Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>iExplore</h1>
            <nav>
                <div class="row">
                    <div class="col" style="display: inline-block; width:50%;">
                        <?php if ($role == 'Admin') { ?>
                            Home / iExplore Management System
                        <?php } else { ?>
                            Home / iExplore Web-based training (WBT)
                        <?php } ?>
                    </div>
                </div>
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
                                    <div class="row">
                                        <div class="col" style="text-align: right;">
                                            <a href="iexplore_defaulters.php">iExplore Defaulters</a>
                                        </div>
                                    </div>
                                    <div class="container">
                                        <div class="row">
                                            <div class="col">
                                                <h3>Search Course</h3>
                                                <form action="" method="GET">
                                                    <div class="input-group mb-3">
                                                        <input type="text" name="courseid_search" class="form-control" placeholder="Enter Course ID" value="<?php echo @$courseid_search ?>">
                                                        <button type="submit" name="courseid_search_button" class="btn btn-primary">Search</button>
                                                    </div>
                                                </form>
                                            </div>
                                            <div class="col">
                                                <h3>Modify Course</h3>
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
                                                        <input type="url" name="url" class="form-control" placeholder="URL" value="<?php echo @$row['url']; ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <select name="validity" class="form-select" required>
                                                            <option disabled selected>Select Validity</option>
                                                            <?php
                                                            $validities = array("0.5", "1", "2", "3", "5", "Lifetime");
                                                            foreach ($validities as $validity) {
                                                                $selected = ($validity == @$row['validity']) ? "selected" : "";
                                                                echo "<option $selected>$validity</option>";
                                                            }
                                                            ?>
                                                        </select>
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
</body>

</html>