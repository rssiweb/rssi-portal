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
<?php
$data = [];

// Determine the associate number to use for the query
$associateNumber = $user_check;

// Define the query template
$query = "
WITH LatestAttempts AS (
    SELECT 
        ws.associatenumber,
        ws.courseid,
        MAX(ws.timestamp) AS latest_timestamp
    FROM 
        wbt_status ws
    JOIN 
        wbt w ON ws.courseid = w.courseid
    WHERE 
        w.is_mandatory = TRUE
    GROUP BY 
        ws.associatenumber, ws.courseid
)
SELECT 
    ws.associatenumber,
    ws.timestamp AS completed_on,
    w.courseid,
    w.coursename,
    ROUND(ws.f_score * 100, 2) AS score_percentage,
    CASE 
        WHEN ROUND(ws.f_score * 100, 2) >= w.passingmarks THEN 'Completed'
        ELSE 'Incomplete'
    END AS status,
    CASE 
        WHEN ROUND(ws.f_score * 100, 2) >= w.passingmarks THEN 
            TO_CHAR(ws.timestamp + (w.validity || ' years')::INTERVAL, 'YYYY-MM-DD HH24:MI:SS')
        ELSE NULL
    END AS valid_upto,
    CASE 
        WHEN ROUND(ws.f_score * 100, 2) >= w.passingmarks THEN
            CASE 
                WHEN ws.timestamp + (w.validity || ' years')::INTERVAL > NOW() THEN 'Active'
                ELSE 'Expired'
            END
        ELSE NULL
    END AS additional_status
FROM 
    wbt_status ws
JOIN 
    LatestAttempts la ON ws.associatenumber = la.associatenumber 
                      AND ws.courseid = la.courseid 
                      AND ws.timestamp = la.latest_timestamp
JOIN 
    wbt w ON ws.courseid = w.courseid
WHERE 
    ws.associatenumber = $1"; // The WHERE clause will be dynamically adjusted based on role

// Prepare the statement
$stmt = pg_prepare($con, "fetch_data", $query);

// Execute the query with the appropriate associate number
$result = pg_execute($con, "fetch_data", [$associateNumber]);

// Fetch the results if the query was successful
if ($result) {
    while ($row = pg_fetch_assoc($result)) {
        $data[] = $row;
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
    <style>
        .tag {
            display: inline-block;
            font-size: 12px;
            font-weight: bold;
            padding: 3px 8px;
            border-radius: 10px 0 0 10px;
            position: relative;
            margin-left: 10px;
            /* Adds space between name and tag */
        }

        .tag::after {
            content: "";
            position: absolute;
            top: 0;
            right: -6px;
            width: 0;
            height: 0;
            border-top: 12px solid transparent;
            border-bottom: 12px solid transparent;
            border-left: 6px solid;
            /* Arrow color will inherit from .tag */
        }

        .tag.internal {
            background-color: #818ECE;
            /* Green for Internal */
            color: white;
        }

        .tag.internal::after {
            border-left-color: #818ECE;
        }

        .tag.external {
            background-color: #D77BB8;
            /* Grey for External */
            color: white;
        }

        .tag.external::after {
            border-left-color: #D77BB8;
        }

        .accordion-button {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }

        .tag-container {
            /* margin-left: auto; */
            /* Pushes tag to the right */
        }
    </style>
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
                                                        <span><strong>Course ID:</strong> <?php echo $array['courseid']; ?> &nbsp; | &nbsp; <strong>Name:</strong> <?php echo $array['coursename']; ?></span>
                                                        <span class="tag-container">
                                                            <div class="tag <?php echo ($array['type'] === 'Internal') ? 'internal' : 'external'; ?>">
                                                                <?php echo htmlspecialchars($array['type']); ?>
                                                            </div>
                                                        </span>
                                                    </button>
                                                </h2>
                                                <div id="collapse-<?php echo $array['courseid']; ?>" class="accordion-collapse collapse" aria-labelledby="heading-<?php echo $array['courseid']; ?>" data-bs-parent="#courseAccordion">
                                                    <div class="accordion-body">
                                                        <div class="container mt-3">
                                                            <div class="card border-0 shadow-sm p-3">
                                                                <div class="row align-items-center">
                                                                    <!-- Course Title -->
                                                                    <div class="col-md-12 fw-bold fs-5 mb-2">
                                                                        <?= htmlspecialchars($array['coursename']) ?>
                                                                    </div>

                                                                    <!-- Course Details -->
                                                                    <div class="col-md-2 text-center">
                                                                        <span class="d-block"><i class="bi bi-globe"></i> Language</span>
                                                                        <strong><?= htmlspecialchars($array['language']) ?></strong>
                                                                    </div>
                                                                    <div class="col-md-2 text-center">
                                                                        <span class="d-block"><i class="bi bi-mortarboard"></i> Mastery Score</span>
                                                                        <strong><?= htmlspecialchars($array['passingmarks']) ?></strong>
                                                                    </div>

                                                                    <!-- Fetch User Attempt Data -->
                                                                    <?php
                                                                    $courseId = $array['courseid'];
                                                                    $userAttempt = null;

                                                                    foreach ($data as $row) {
                                                                        if ($row['courseid'] === $courseId) {
                                                                            $userAttempt = $row;
                                                                            break;
                                                                        }
                                                                    }
                                                                    ?>

                                                                    <!-- Score -->
                                                                    <div class="col-md-2 text-center">
                                                                        <span class="d-block"><i class="bi bi-trophy"></i> Score</span>
                                                                        <?php if ($userAttempt): ?>
                                                                            <strong class="<?= $userAttempt['status'] === 'Completed' ? 'text-success' : 'text-danger' ?>">
                                                                                <?= htmlspecialchars($userAttempt['score_percentage']) ?>%
                                                                            </strong>
                                                                        <?php else: ?>
                                                                            <strong class="text-danger">Not Attempted</strong>
                                                                        <?php endif; ?>
                                                                    </div>

                                                                    <!-- Status -->
                                                                    <div class="col-md-2 text-center">
                                                                        <span class="d-block"><i class="bi bi-circle"></i> Status</span>
                                                                        <?php if ($userAttempt): ?>
                                                                            <?php if ($userAttempt['status'] === 'Incomplete'): ?>
                                                                                <strong class="text-warning">Incomplete</strong>
                                                                            <?php elseif ($userAttempt['additional_status'] === 'Active'): ?>
                                                                                <strong class="text-success">Active</strong>
                                                                            <?php elseif ($userAttempt['additional_status'] === 'Expired'): ?>
                                                                                <strong class="text-danger">Expired</strong>
                                                                            <?php endif; ?>
                                                                        <?php else: ?>
                                                                            <strong class="text-warning">Incomplete</strong>
                                                                        <?php endif; ?>
                                                                    </div>

                                                                    <!-- Validity Date (Only if completed) -->
                                                                    <div class="col-md-2 text-center">
                                                                        <?php if ($userAttempt && $userAttempt['status'] === 'Completed'): ?>
                                                                            <span class="d-block"><i class="bi bi-calendar"></i> Valid Until</span>
                                                                            <strong><?= date("d/m/Y", strtotime($userAttempt['valid_upto'])) ?></strong>
                                                                        <?php endif; ?>
                                                                    </div>

                                                                    <!-- Action Buttons -->
                                                                    <!-- <div class="col-md-2 text-center">
                                                                        <a href="#" class="text-danger">View More</a>
                                                                    </div> -->
                                                                    <div class="col-md-2 text-center">
                                                                        <a href="<?= htmlspecialchars($array['url']) ?>" class="btn btn-danger px-4">Launch</a>
                                                                        <!-- Attempt History Button -->
                                                                        <div>
                                                                            <!-- Button to open modal -->
                                                                            <a href="#" class="text-danger small" data-bs-toggle="modal" data-bs-target="#attemptHistoryModal-<?= $array['courseid']; ?>">Attempt History</a>
                                                                        </div>


                                                                        <!-- Bootstrap Modal for Attempt History -->
                                                                        <div class="modal fade" id="attemptHistoryModal-<?= $array['courseid']; ?>" tabindex="-1" aria-labelledby="attemptHistoryModalLabel" aria-hidden="true">
                                                                            <div class="modal-dialog modal-lg">
                                                                                <div class="modal-content">
                                                                                    <div class="modal-header">
                                                                                        <h5 class="modal-title" id="attemptHistoryModalLabel">Last 10 Attempts for Course ID: <?= htmlspecialchars($array['courseid']) ?></h5>
                                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                                    </div>
                                                                                    <div class="modal-body">
                                                                                        <?php
                                                                                        // Fetch last 10 attempts for the user and course
                                                                                        $courseId = $array['courseid'];
                                                                                        $attemptsQuery = "SELECT timestamp, ROUND(f_score * 100, 2) AS score_percentage FROM wbt_status 
                                                                                        WHERE associatenumber = $1 AND courseid = $2 
                                                                                        ORDER BY timestamp DESC 
                                                                                        LIMIT 10";

                                                                                        $attemptsStmt = pg_prepare($con, "fetch_attempts_$courseId", $attemptsQuery);
                                                                                        $attemptsResult = pg_execute($con, "fetch_attempts_$courseId", [$user_check, $courseId]);

                                                                                        if ($attemptsResult && pg_num_rows($attemptsResult) > 0) :
                                                                                        ?>
                                                                                            <table class="table table-bordered">
                                                                                                <thead class="table-dark">
                                                                                                    <tr>
                                                                                                        <th>#</th>
                                                                                                        <th>Date & Time</th>
                                                                                                        <th>Score (%)</th>
                                                                                                    </tr>
                                                                                                </thead>
                                                                                                <tbody>
                                                                                                    <?php
                                                                                                    $count = 1;
                                                                                                    while ($attemptRow = pg_fetch_assoc($attemptsResult)) :
                                                                                                    ?>
                                                                                                        <tr>
                                                                                                            <td><?= $count++; ?></td>
                                                                                                            <td><?= date("d/m/Y H:i:s", strtotime($attemptRow['timestamp'])); ?></td>
                                                                                                            <td><?= htmlspecialchars($attemptRow['score_percentage']) ?>%</td>
                                                                                                        </tr>
                                                                                                    <?php endwhile; ?>
                                                                                                </tbody>
                                                                                            </table>
                                                                                        <?php else : ?>
                                                                                            <p class="text-muted text-center">No attempt history available.</p>
                                                                                        <?php endif; ?>
                                                                                    </div>
                                                                                    <div class="modal-footer">
                                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>

                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <!-- Study Materials -->
                                                        <div class="mb-4">

                                                            <div class="card shadow-sm">
                                                                <div class="card-body">
                                                                    <p class="fw-bold mb-3">Study Materials</p>
                                                                    <?php
                                                                    // Fetch study materials for the current course
                                                                    $studyMaterials = fetchStudyMaterials($array['courseid'], $con);
                                                                    if (!empty($studyMaterials)) : ?>
                                                                        <ul class="list-group list-group-flush">
                                                                            <?php foreach ($studyMaterials as $material) : ?>
                                                                                <li class="list-group-item">
                                                                                    <a href="<?php echo $material['link']; ?>" target="_blank" class="text-decoration-none">
                                                                                        <?php echo $material['material_name']; ?>
                                                                                    </a>
                                                                                </li>
                                                                            <?php endforeach; ?>
                                                                        </ul>
                                                                    <?php else : ?>
                                                                        <p class="text-muted mb-0">No study materials available.</p>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div> <!-- End accordion-body -->
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