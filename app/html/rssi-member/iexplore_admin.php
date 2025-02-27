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

// Retrieve student ID from form input
@$courseid_search = $_GET['courseid_search'];

// Query database for student information based on ID
$result = pg_query($con, "SELECT * FROM wbt WHERE courseid = '$courseid_search'");

// Check if the query was executed successfully
if (!$result) {
    echo "An error occurred.\n";
    exit;
}

// Fetch all rows as an associative array
$resultArr = pg_fetch_all($result);

// Check if any rows were found
if ($resultArr) {
    // Loop through each row in the result array
    foreach ($resultArr as $row) {
    }
}

$courseid1 = isset($_GET['courseid1']) ? trim($_GET['courseid1']) : null;
$language1 = isset($_GET['language1']) ? $_GET['language1'] : 'ALL';
$type1 = isset($_GET['type1']) ? $_GET['type1'] : 'ALL';

$query = "SELECT * FROM wbt WHERE 1=1";

if ($courseid1 != null) {
    $query .= " AND courseid = '$courseid1'";
}

if ($language1 != 'ALL') {
    $query .= " AND language = '$language1'";
}

if ($type1 != 'ALL') {
    $query .= " AND type = '$type1'";
}

$query .= " ORDER BY courseid";

$result1 = pg_query($con, $query);

if (!$result1) {
    echo "An error occurred.\n";
    exit;
}

$resultArr1 = pg_fetch_all($result1);

// Function to fetch study materials for a course
function fetchStudyMaterials($courseid, $con)
{
    $query = "SELECT material_name, link FROM wbt_study_materials WHERE courseid = '$courseid'";
    $result = pg_query($con, $query);
    if (!$result) {
        return []; // Return empty array if no materials found
    }
    return pg_fetch_all($result);
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

    <script src="https://cdn.jsdelivr.net/gh/manucaralmo/GlowCookies@3.0.1/src/glowCookies.min.js"></script>
    <!-- Glow Cookies v3.0.1 -->
    <script>
        glowCookies.start('en', {
            analytics: 'G-S25QWTFJ2S',
            //facebookPixel: '',
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
                                                    <div class="form-check mb-3">
                                                        <input type="checkbox" class="form-check-input" id="disableFieldsCheckbox">
                                                        <label class="form-check-label" for="disableFieldsCheckbox">Modify course</label>
                                                    </div>
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
                                                    <div class="mb-3">
                                                        <button type="submit" id="submit2" class="btn btn-warning">Update</button>
                                                        <button type="submit" id="submit3" class="btn btn-danger">Add New</button>
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
        var form = document.getElementById('wbt');
        var submit2Button = document.getElementById('submit2');
        var submit3Button = document.getElementById('submit3');
        var disableCheckbox = document.getElementById('disableFieldsCheckbox');

        // Initial check on page load
        updateButtonVisibility();
        // Add event listeners to the submit buttons
        submit2Button.addEventListener('click', function() {
            form.action = 'wbt_update.php';
        });

        submit3Button.addEventListener('click', function() {
            form.action = 'wbt_add.php';
        });
        // Add an event listener to the checkbox
        disableCheckbox.addEventListener('change', function() {
            updateButtonVisibility();
        });

        function updateButtonVisibility() {
            // If the checkbox is checked, show Update and Add New buttons, and hide Submit button
            if (disableCheckbox.checked) {
                submit2Button.style.display = 'inline-block';
                submit3Button.style.display = 'none';
            } else {
                // If the checkbox is unchecked, show Submit button, and hide Update and Add New buttons
                submit2Button.style.display = 'none';
                submit3Button.style.display = 'inline-block';
            }
        }
    </script>
</body>

</html>