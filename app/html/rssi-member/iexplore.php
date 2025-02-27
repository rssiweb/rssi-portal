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
                                <div class="row">
                                    <div class="col" style="text-align: right;">
                                        <a href="my_learning.php" target="_self">My Learning History</a>
                                    </div>
                                </div>
                                <?php if ($role != 'Admin') { ?>
                                <?php } ?>
                                <?php
                                // Initialize filter variables
                                $courseid1 = isset($_GET['courseid1']) ? trim($_GET['courseid1']) : '';
                                $language1 = isset($_GET['language1']) ? $_GET['language1'] : '';
                                $type1 = isset($_GET['type1']) ? $_GET['type1'] : '';

                                // Define filter options
                                $languageOptions = array("English", "Hindi", "Bengali");
                                $typeOptions = array("Internal", "External");

                                // Function to generate options for dropdown fields
                                function generateOptions($options, $selectedValue)
                                {
                                    $html = '';
                                    foreach ($options as $option) {
                                        $selected = ($option == $selectedValue) ? 'selected' : '';
                                        $html .= "<option value=\"$option\" $selected>$option</option>";
                                    }
                                    return $html;
                                }
                                ?>

                                <form action="" method="GET">
                                    <div class="container">
                                        Customize your search by selecting any combination of filters to retrieve the data.
                                        <br><br>
                                        <div class="row d-flex align-items-center">
                                            <div class="col-md-3">
                                                <div class="mb-3">
                                                    <label for="courseid1" class="form-label">Course ID</label>
                                                    <input type="text" name="courseid1" class="form-control" id="courseid1" placeholder="Enter Course ID" value="<?php echo htmlspecialchars($courseid1); ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3">
                                                    <label for="language1" class="form-label">Language</label>
                                                    <select name="language1" class="form-select" id="language1">
                                                        <option <?php if ($language1 === '') echo 'selected'; ?> disabled>Select Language</option>
                                                        <?php echo generateOptions($languageOptions, $language1); ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3">
                                                    <label for="type1" class="form-label">Type</label>
                                                    <select name="type1" class="form-select" id="type1">
                                                        <option <?php if ($type1 === '') echo 'selected'; ?> disabled>Select Type</option>
                                                        <?php echo generateOptions($typeOptions, $type1); ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-3 d-flex justify-content-left">
                                                <button type="submit" name="search_by_id" class="btn btn-sm btn-primary">
                                                    <i class="bi bi-search"></i> Search
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </form>

                                <?php if ($resultArr1 != null) : ?>
                                    <div class="accordion" id="courseAccordion">
                                        <?php foreach ($resultArr1 as $array) : ?>
                                            <div class="accordion-item">
                                                <h2 class="accordion-header" id="heading-<?php echo $array['courseid']; ?>">
                                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?php echo $array['courseid']; ?>" aria-expanded="false" aria-controls="collapse-<?php echo $array['courseid']; ?>">
                                                        <strong>Course ID:</strong> <?php echo $array['courseid']; ?> &nbsp; | &nbsp; <strong>Name:</strong> <?php echo $array['coursename']; ?>
                                                    </button>
                                                </h2>
                                                <div id="collapse-<?php echo $array['courseid']; ?>" class="accordion-collapse collapse" aria-labelledby="heading-<?php echo $array['courseid']; ?>" data-bs-parent="#courseAccordion">
                                                    <div class="accordion-body">
                                                        <!-- Course Information -->
                                                        <div class="mb-4">
                                                            <h6 class="fw-bold mb-3">Course Details</h6>
                                                            <strong>Course ID:</strong> <?php echo $array['courseid']; ?> &nbsp; | &nbsp; <strong>Name:</strong> <?php echo $array['coursename']; ?>
                                                            <div class="row">
                                                                <div class="col-md-3"><strong>Language:</strong> <?php echo $array['language']; ?></div>
                                                                <div class="col-md-3"><strong>Type:</strong> <?php echo $array['type']; ?></div>
                                                                <div class="col-md-3"><strong>Mastery Score:</strong> <?php echo $array['passingmarks']; ?>%</div>
                                                                <div class="col-md-3"><strong>Validity:</strong> <?php echo $array['validity']; ?> years</div>
                                                            </div>
                                                        </div>

                                                        <!-- Study Materials -->
                                                        <div class="mb-4">
                                                            <h6 class="fw-bold mb-3">📚 Study Materials</h6>
                                                            <?php
                                                            // Fetch study materials for the current course
                                                            $studyMaterials = fetchStudyMaterials($array['courseid'], $con);
                                                            if (!empty($studyMaterials)) : ?>
                                                                <ul class="list-unstyled">
                                                                    <?php foreach ($studyMaterials as $material) : ?>
                                                                        <li class="mb-2">
                                                                            <a href="<?php echo $material['link']; ?>" target="_blank" class="text-decoration-none"><?php echo $material['material_name']; ?></a>
                                                                        </li>
                                                                    <?php endforeach; ?>
                                                                </ul>
                                                            <?php else : ?>
                                                                <p class="text-muted">No study materials available.</p>
                                                            <?php endif; ?>
                                                        </div>

                                                        <!-- Assessment Link -->
                                                        <div>
                                                            <h6 class="fw-bold mb-3">📝 Assessment</h6>
                                                            <a href="<?php echo $array['url']; ?>" target="_blank" class="btn btn-primary btn-sm">Launch Assessment</a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php elseif ($courseid1 == null && $language1 == null) : ?>
                                    <div class="text-center text-muted py-4">Please enter at least one value to get the WBT details.</div>
                                <?php else : ?>
                                    <div class="text-center text-muted py-4">No record found for <?php echo $courseid1 . ' ' . $language1 . ' ' . $type1; ?></div>
                                <?php endif; ?>

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
</body>

</html>