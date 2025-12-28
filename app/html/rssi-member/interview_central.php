<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}

validation();

// Handle filtering
$filter_application_number = isset($_POST['filter_application_number']) ? trim($_POST['filter_application_number']) : '';
$filter_status = isset($_POST['status']) ? $_POST['status'] : [];

// Start building the query
$query = "SELECT * FROM signup 
          WHERE application_status IN ('Technical Interview Scheduled', 'Technical Interview Completed', 'HR Interview Scheduled', 'HR Interview Completed')";
          
// Add filters based on user input
$conditions = [];

if (!empty($filter_application_number)) {
    $conditions[] = "application_number = '" . pg_escape_string($con, $filter_application_number) . "'";
}

if (!empty($filter_status)) {
    $statuses = array_map(function ($status) use ($con) {
        return pg_escape_string($con, $status);
    }, $filter_status);
    $conditions[] = "application_status IN ('" . implode("', '", $statuses) . "')";
}

// Append conditions dynamically
if (!empty($conditions)) {
    $query .= " AND " . implode(" AND ", $conditions);
}

// Add the ORDER BY clause
$query .= " ORDER BY tech_interview_schedule DESC";

// Add a limit of 100 if no filters are applied
if (empty($filter_application_number) && empty($filter_status)) {
    $query .= " LIMIT 100";
}

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

    <title>Interview Central</title>

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
    <style>
        @media (min-width:767px) {
            .left {
                margin-left: 2%;
            }
        }
    </style>
    <!-- CSS Library Files -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
    <!-- JavaScript Library Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
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
            <h1>Interview Central</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">People Plus</a></li>
                    <li class="breadcrumb-item active">Interview Central</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section Interview Central">
            <div class="row">

                <!-- Reports -->
                <div class="col-12">
                    <div class="card">

                        <div class="card-body">
                            <br>
                            <div class="container">
                                <form method="POST" class="filter-form" style="display: flex;">
                                    <div class="form-group" style="margin-right: 10px;">
                                        <input type="text" id="filter_application_number" name="filter_application_number" class="form-control" placeholder="Application Number" value="<?php echo htmlspecialchars($filter_application_number); ?>" style="width: 200px;">
                                    </div>

                                    <div class="form-group" style="margin-right: 10px;">
                                        <select id="status" name="status[]" class="form-select" multiple>
                                            <option value="Technical Interview Scheduled" <?php echo in_array('Technical Interview Scheduled', $filter_status ?? []) ? 'selected' : ''; ?>>Technical Interview Scheduled</option>
                                            <option value="Technical Interview Completed" <?php echo in_array('Technical Interview Completed', $filter_status ?? []) ? 'selected' : ''; ?>>Technical Interview Completed</option>
                                            <option value="HR Interview Scheduled" <?php echo in_array('HR Interview Scheduled', $filter_status ?? []) ? 'selected' : ''; ?>>HR Interview Scheduled</option>
                                            <!-- <option value="Recommended" <?php echo in_array('Recommended', $filter_status ?? []) ? 'selected' : ''; ?>>Recommended</option>
                                            <option value="Not Recommended" <?php echo in_array('Not Recommended', $filter_status ?? []) ? 'selected' : ''; ?>>Not Recommended</option>
                                            <option value="On Hold" <?php echo in_array('On Hold', $filter_status ?? []) ? 'selected' : ''; ?>>On Hold</option>
                                            <option value="No-Show" <?php echo in_array('No-Show', $filter_status ?? []) ? 'selected' : ''; ?>>No-Show</option>
                                            <option value="Offer Extended" <?php echo in_array('Offer Extended', $filter_status ?? []) ? 'selected' : ''; ?>>Offer Extended</option>
                                            <option value="Offer Not Extended" <?php echo in_array('Offer Not Extended', $filter_status ?? []) ? 'selected' : ''; ?>>Offer Not Extended</option> -->
                                        </select>

                                    </div>

                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary btn-sm" style="outline: none;">
                                            <i class="bi bi-search"></i>&nbsp;Filter
                                        </button>
                                    </div>
                                </form>
                                <div class="table-responsive">
                                    <table class="table" id="table-id">
                                        <thead>
                                            <tr>
                                                <th scope="col">Application Number</th>
                                                <th scope="col">Applicant Name</th>
                                                <th scope="col">Technical Interview Scheduled On</th>
                                                <th scope="col">HR Interview Scheduled On</th>
                                                <th scope="col">Status</th>
                                                <th scope="col">Enter Evaluation</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            foreach ($resultArr as $array) {
                                                $interviewTimestamp = empty($array['tech_interview_schedule']) ? 'Not scheduled yet' : @date("d/m/Y g:i a", strtotime($array['tech_interview_schedule']));
                                                $hrTimestamp = empty($array['hr_interview_schedule']) ? 'Not scheduled yet' : @date("d/m/Y g:i a", strtotime($array['hr_interview_schedule']));
                                                $linkToShow = '';

                                                // Check if HR interview is scheduled
                                                if (!empty($array['hr_interview_schedule'])) {
                                                    $linkToShow = '<a href="hr_interview.php?applicationNumber_verify=' . $array['application_number'] . '">HR Interview</a>';
                                                }
                                                // Check if TR interview is scheduled and HR interview is not scheduled
                                                elseif (!empty($array['tech_interview_schedule']) && $array['application_status'] != 'No-Show') {
                                                    $linkToShow = '<a href="technical_interview.php?applicationNumber_verify=' . $array['application_number'] . '">Technical Interview</a>';
                                                }

                                                $interviewStatus = empty($array['application_status']) ? '' : $array['application_status'];
                                            ?>
                                                <tr>
                                                    <td><?php echo $array['application_number']; ?></td>
                                                    <td><?php echo $array['applicant_name']; ?></td>
                                                    <td><?php echo $interviewTimestamp; ?></td>
                                                    <td><?php echo $hrTimestamp; ?></td>
                                                    <td><?php echo $interviewStatus; ?></td>
                                                    <td><?php echo $linkToShow; ?></td>
                                                </tr>
                                            <?php
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
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
  <script src="../assets_new/js/text-refiner.js?v=1.1.0"></script>
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