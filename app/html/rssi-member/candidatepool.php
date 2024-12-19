<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();

// Calculate academic year for current period
if (date('m') == 1 || date('m') == 2 || date('m') == 3) { // Upto March
    $current_academic_year = (date('Y') - 1) . '-' . date('Y');
} else { // After March
    $current_academic_year = date('Y') . '-' . (date('Y') + 1);
}

// Handle filtering
$filter_application_number = isset($_POST['filter_application_number']) ? trim($_POST['filter_application_number']) : '';
$filter_status = isset($_POST['status']) ? $_POST['status'] : [];
@$lyear = isset($_POST['lyear']) ? $_POST['lyear'] : $current_academic_year;

// Start building the query
$query = "SELECT *, 
          CASE 
              WHEN EXTRACT(MONTH FROM timestamp) IN (1, 2, 3) THEN 
                  (EXTRACT(YEAR FROM timestamp) - 1) || '-' || EXTRACT(YEAR FROM timestamp)
              ELSE 
                  EXTRACT(YEAR FROM timestamp) || '-' || (EXTRACT(YEAR FROM timestamp) + 1)
          END AS academic_year
          FROM signup";

// Add filters based on user input
$conditions = [];
if (!empty($filter_application_number)) {
    $conditions[] = "application_number = '" . pg_escape_string($con, $filter_application_number) . "'";
}

if (!empty($filter_status)) {
    // Escape each status value using the connection
    $statuses = array_map(function ($status) use ($con) {
        return pg_escape_string($con, $status);
    }, $filter_status);
    $conditions[] = "application_status IN ('" . implode("', '", $statuses) . "')";
}

if (!empty($lyear)) {
    $conditions[] = "CASE 
                        WHEN EXTRACT(MONTH FROM timestamp) IN (1, 2, 3) THEN 
                            (EXTRACT(YEAR FROM timestamp) - 1) || '-' || EXTRACT(YEAR FROM timestamp)
                        ELSE 
                            EXTRACT(YEAR FROM timestamp) || '-' || (EXTRACT(YEAR FROM timestamp) + 1)
                    END = '" . pg_escape_string($con, $lyear) . "'";
}

// Append conditions to the query
if (!empty($conditions)) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}

$query .= " ORDER BY timestamp DESC";

// Execute the query
$result = pg_query($con, $query);

if (!$result) {
    echo "An error occurred.\n";
    exit;
}

// Fetch and process the results
$resultArr = pg_fetch_all($result);

?>

<!doctype html>
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
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Candidate Pool</title>

    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

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

<!-- =========================
     NAVIGATION LINKS     
============================== -->

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Candidate Pool</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">People Plus</a></li>
                    <li class="breadcrumb-item active">Candidate Pool</li>
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
                                <form method="POST" class="filter-form d-flex flex-wrap" style="gap: 10px;">
                                    <div class="form-group">
                                        <input type="text" id="filter_application_number" name="filter_application_number" class="form-control" placeholder="Application Number" value="<?php echo htmlspecialchars($filter_application_number); ?>" style="max-width: 200px;">
                                    </div>
                                    <div class="form-group">
                                        <select name="lyear" id="lyear" class="form-select" required>
                                            <?php if ($lyear == null) { ?>
                                                <option value="" disabled selected hidden>Academic Year</option>
                                            <?php } else { ?>
                                                <option hidden selected><?php echo $lyear ?></option>
                                            <?php } ?>
                                            <!-- Add options dynamically if needed -->
                                            <?php
                                            // Dynamically generate the academic year options
                                            $currentYear = date('Y');
                                            for ($i = 0; $i < 5; $i++) {
                                                $startYear = $currentYear - $i;
                                                $endYear = $startYear + 1;
                                                $value = "$startYear-$endYear";
                                                echo "<option value='$value'>$value</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <select id="status" name="status[]" class="form-select" multiple>
                                            <option value="Application Submitted" <?php echo in_array('Application Submitted', $filter_status ?? []) ? 'selected' : ''; ?>>Application Submitted</option>
                                            <option value="Application Re-Submitted" <?php echo in_array('Application Re-Submitted', $filter_status ?? []) ? 'selected' : ''; ?>>Application Re-Submitted</option>
                                            <option value="Identity verification document submitted" <?php echo in_array('Identity verification document submitted', $filter_status ?? []) ? 'selected' : ''; ?>>Identity verification document submitted</option>
                                            <option value="Photo Verification Completed" <?php echo in_array('Photo Verification Completed', $filter_status ?? []) ? 'selected' : ''; ?>>Photo Verification Completed</option>
                                            <option value="Photo Verification Failed" <?php echo in_array('Photo Verification Failed', $filter_status ?? []) ? 'selected' : ''; ?>>Photo Verification Failed</option>
                                            <option value="Identity Verification Completed" <?php echo in_array('Identity Verification Completed', $filter_status ?? []) ? 'selected' : ''; ?>>Identity Verification Completed</option>
                                            <option value="Identity Verification Failed" <?php echo in_array('Identity Verification Failed', $filter_status ?? []) ? 'selected' : ''; ?>>Identity Verification Failed</option>
                                            <option value="Technical Interview Scheduled" <?php echo in_array('Technical Interview Scheduled', $filter_status ?? []) ? 'selected' : ''; ?>>Technical Interview Scheduled</option>
                                            <option value="Technical Interview Completed" <?php echo in_array('Technical Interview Completed', $filter_status ?? []) ? 'selected' : ''; ?>>Technical Interview Completed</option>
                                            <option value="HR Interview Scheduled" <?php echo in_array('HR Interview Scheduled', $filter_status ?? []) ? 'selected' : ''; ?>>HR Interview Scheduled</option>
                                            <option value="Recommended" <?php echo in_array('Recommended', $filter_status ?? []) ? 'selected' : ''; ?>>Recommended</option>
                                            <option value="Not Recommended" <?php echo in_array('Not Recommended', $filter_status ?? []) ? 'selected' : ''; ?>>Not Recommended</option>
                                            <option value="On Hold" <?php echo in_array('On Hold', $filter_status ?? []) ? 'selected' : ''; ?>>On Hold</option>
                                            <option value="No-Show" <?php echo in_array('No-Show', $filter_status ?? []) ? 'selected' : ''; ?>>No-Show</option>
                                            <option value="Offer Extended" <?php echo in_array('Offer Extended', $filter_status ?? []) ? 'selected' : ''; ?>>Offer Extended</option>
                                            <option value="Offer Not Extended" <?php echo in_array('Offer Not Extended', $filter_status ?? []) ? 'selected' : ''; ?>>Offer Not Extended</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary btn-sm">
                                            <i class="bi bi-search"></i>&nbsp;Filter
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <div class="table-responsive">
                                <table class="table" id="table-id">
                                    <thead>
                                        <tr>
                                            <th scope="col">Applied on</th>
                                            <th scope="col">Application Number</th>
                                            <th scope="col">Applicant Name</th>
                                            <th scope="col">Association</th>
                                            <th scope="col">Post</th>
                                            <th scope="col">Subject Preference</th>
                                            <th scope="col">Identity Verification</th>
                                            <th scope="col">Technical Interview Scheduled On</th>
                                            <th scope="col">HR Interview Scheduled On</th>
                                            <th scope="col">Status</th>
                                            <th scope="col">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Iterate through the fetched candidate information
                                        foreach ($resultArr as $array) {
                                            $interviewTimestamp = empty($array['tech_interview_schedule']) ? 'Not scheduled yet' : @date("d/m/Y g:i a", strtotime($array['tech_interview_schedule']));
                                            $hrTimestamp = empty($array['hr_interview_schedule']) ? 'Not scheduled yet' : @date("d/m/Y g:i a", strtotime($array['hr_interview_schedule']));
                                            $linkToShow = '';

                                            $interviewStatus = empty($array['application_status']) ? '' : $array['application_status'];
                                        ?>
                                            <tr>
                                                <td><?php echo !empty($array['timestamp']) ? @date("d/m/Y g:i a", strtotime($array['timestamp'])) : ''; ?></td>
                                                <td><?php echo $array['application_number']; ?></td>
                                                <td><?php echo $array['applicant_name']; ?></td>
                                                <td><?php echo $array['association']; ?></td>
                                                <td><?php echo $array['post_select']; ?></td>
                                                <td><?php echo $array['subject1']; ?>,<?php echo $array['subject2']; ?>,<?php echo $array['subject3']; ?></td>
                                                <td><?php echo $array['identifier']; ?></td>
                                                <td><?php echo $interviewTimestamp; ?></td>
                                                <td><?php echo $hrTimestamp; ?></td>
                                                <td><?php echo $interviewStatus; ?></td>
                                                <td><a href='candidate_profile.php?application_number=<?php echo $array['application_number']; ?>' target="_blank">Profile</a></td>
                                            </tr>
                                        <?php
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div><!-- End Reports -->
                </div>
            </div>
        </section>
    </main><!-- End #main -->

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>
    <script>
        $(document).ready(function() {
            // Check if resultArr is empty
            <?php if (!empty($resultArr)) : ?>
                // Initialize DataTables only if resultArr is not empty
                $('#table-id').DataTable({
                    // paging: false,
                    "order": [] // Disable initial sorting
                    // other options...
                });
            <?php endif; ?>
        });
    </script>
</body>

</html>