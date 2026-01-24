<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];

    header("Location: index.php");
    exit;
}
validation();

@$id = $_GET['get_aid'];

if ($id != null) {

    $result = pg_query($con, "select appraisee.fullname aname, appraisee.email aemail, manager.fullname mname, manager.email memail, manager1.fullname mname1, manager1.email memail1, reviewer.fullname rname, reviewer.email remail,*  from appraisee_response
    LEFT JOIN (SELECT associatenumber,fullname,email FROM rssimyaccount_members) appraisee ON appraisee.associatenumber = appraisee_response.appraisee_associatenumber
    LEFT JOIN (SELECT associatenumber,fullname,email FROM rssimyaccount_members) manager1 ON manager1.associatenumber = appraisee_response.manager1_associatenumber
    LEFT JOIN (SELECT associatenumber,fullname,email FROM rssimyaccount_members) manager ON manager.associatenumber = appraisee_response.manager_associatenumber
    LEFT JOIN (SELECT associatenumber,fullname,email FROM rssimyaccount_members) reviewer ON reviewer.associatenumber = appraisee_response.reviewer_associatenumber
    WHERE appraisalyear='$id' ORDER BY ipf_process_closed_on DESC, (ipf IS NULL) DESC, goalsheet_created_on DESC");
}

if ($id == null) {

    $result = pg_query($con, "select * from appraisee_response WHERE goalsheetid is null");
}

if (!$result) {
    echo "An error occurred.\n";
    exit;
}

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

    <title>Appraisal Workflow</title>

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
            <h1><?php echo getPageTitle(); ?></h1>
            <?php echo generateDynamicBreadcrumb(); ?>
        </div><!-- End Page Title -->

        <section class="section dashboard">
            <div class="row">

                <!-- Reports -->
                <div class="col-12">
                    <div class="card">

                        <div class="card-body">
                            <br>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <form action="" method="GET">
                                        <div class="form-group">
                                            <div class="col2" style="display: inline-block;">
                                                <select name="get_aid" id="get_aid" class="form-select" style="width:max-content; display:inline-block" placeholder="Select policy year" required>
                                                    <?php if ($id == null) { ?>
                                                        <option hidden selected>Select year</option>
                                                    <?php } else { ?>
                                                        <option hidden selected><?php echo $id ?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                            <div class="col2 left" style="display: inline-block;">
                                                <button type="submit" name="search_by_id" class="btn btn-primary btn-sm" style="outline: none;">
                                                    <i class="bi bi-search"></i>&nbsp;Search
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table" id="table-id">
                                    <thead>
                                        <tr>
                                            <th scope="col">Goal sheet ID</th>
                                            <th scope="col">Appraisee</th>
                                            <th scope="col">Manager1</th>
                                            <th scope="col">Manager</th>
                                            <th scope="col">Reviewer</th>
                                            <th scope="col">Appraisal Type</th>
                                            <th scope="col">Effective Start Date</th>
                                            <th scope="col">Effective End Date</th>
                                            <th scope="col">Initiated on</th>
                                            <th scope="col">IPF</th>
                                            <th scope="col">Status</th>
                                            <th scope="col">Appraisee responded on</th>
                                            <th scope="col">Closed on</th>
                                        </tr>
                                    </thead>
                                    <?php if ($resultArr != null) : ?>
                                        <tbody>
                                            <?php foreach ($resultArr as $array) :
                                                $roleBasedLink = '';
                                                if ($associatenumber == $array['appraisee_associatenumber']) {
                                                    $roleBasedLink = 'reviewer_response.php?goalsheetid=' . $array['goalsheetid'];
                                                } elseif ($associatenumber == $array['manager_associatenumber']) {
                                                    $roleBasedLink = 'manager_response.php?goalsheetid=' . $array['goalsheetid'];
                                                } elseif ($associatenumber == $array['reviewer_associatenumber']) {
                                                    $roleBasedLink = 'reviewer_response.php?goalsheetid=' . $array['goalsheetid'];
                                                }
                                            ?>
                                                <tr>
                                                    <td>
                                                        <?php if ($roleBasedLink != '') : ?>
                                                            <a href="<?= $roleBasedLink ?>"><?= $array['goalsheetid'] ?></a>
                                                        <?php else : ?>
                                                            <?= $array['goalsheetid'] ?>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= $array['aname'] . ' (' . $array['appraisee_associatenumber'] . ')' ?></td>
                                                    <td>
                                                        <?php
                                                        $managerInfo = $array['mname1'];
                                                        if (!empty($array['manager1_associatenumber'])) {
                                                            $managerInfo .= ' (' . $array['manager1_associatenumber'] . ')';
                                                        }
                                                        echo $managerInfo;
                                                        ?>
                                                    </td>
                                                    <td><?= $array['mname'] . ' (' . $array['manager_associatenumber'] . ')' ?></td>
                                                    <td><?= $array['rname'] . ' (' . $array['reviewer_associatenumber'] . ')' ?></td>
                                                    <td><?= $array['appraisaltype'] . '<br>' . $array['appraisalyear'] ?></td>
                                                    <td><?= isset($array['effective_start_date']) ? date("d/m/Y", strtotime($array['effective_start_date'])) : '' ?></td>
                                                    <td><?= isset($array['effective_end_date']) ? date("d/m/Y", strtotime($array['effective_end_date'])) : '' ?></td>
                                                    <td><?= date('d/m/y h:i:s a', strtotime($array['goalsheet_created_on'])) ?></td>
                                                    <td><?= $array['ipf'] ?></td>
                                                    <td>
                                                        <?php
                                                        $status = '';
                                                        if ($array['appraisee_response_complete'] == "" && $array['manager_evaluation_complete'] == "" && $array['reviewer_response_complete'] == "" && $array['ipf_process_closed_on'] == null) {
                                                            $status = 'Self-assessment';
                                                        } elseif ($array['appraisee_response_complete'] == "yes" && $array['manager_evaluation_complete'] == "" && $array['reviewer_response_complete'] == "" && $array['ipf_process_closed_on'] == null) {
                                                            $status = 'Manager assessment in progress';
                                                        } elseif ($array['appraisee_response_complete'] == "yes" && $array['manager_evaluation_complete'] == "yes" && $array['reviewer_response_complete'] == "" && $array['ipf_process_closed_on'] == null) {
                                                            $status = 'Reviewer assessment in progress';
                                                        } elseif ($array['appraisee_response_complete'] == "yes" && $array['manager_evaluation_complete'] == "yes" && $array['reviewer_response_complete'] == "yes" && $array['ipf_response'] == null) {
                                                            $status = 'IPF released';
                                                        } elseif ($array['ipf_response'] == 'accepted') {
                                                            $status = 'IPF Accepted';
                                                        } elseif ($array['ipf_response'] == 'rejected') {
                                                            $status = 'IPF Rejected';
                                                        } elseif (($array['appraisee_response_complete'] == "" || $array['appraisee_response_complete'] == "yes") && $array['manager_evaluation_complete'] == "" && $array['reviewer_response_complete'] == "" && $array['ipf_process_closed_on'] != null) {
                                                            $status = 'Incomplete';
                                                        }
                                                        echo $status;
                                                        ?>
                                                    </td>
                                                    <td><?= $array['ipf_response_on'] ? date('d/m/y h:i:s a', strtotime($array['ipf_response_on'])) : '' ?></td>
                                                    <td>
                                                        <?php if ($array['ipf_process_closed_by'] == null && $role == 'Admin') : ?>
                                                            <form name="ipfclose<?= $array['goalsheetid'] ?>" action="#" method="POST">
                                                                <input type="hidden" name="form-type" value="ipfclose">
                                                                <input type="hidden" name="ipfid" value="<?= $array['goalsheetid'] ?>">
                                                                <input type="hidden" name="ipf_process_closed_by" value="<?= $associatenumber ?>">
                                                                <button type="submit" id="yes" style="display:inline-flex; width:fit-content; word-wrap:break-word; outline:none; background:none; padding:0; border:none;" title="Closed">
                                                                    <i class="bi bi-box-arrow-up"></i>
                                                                </button>
                                                            </form>
                                                        <?php else : ?>
                                                            <?= date('d/m/y h:i:s a', strtotime($array['ipf_process_closed_on'])) ?>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    <?php elseif (@$id == null) : ?>
                                        <tr>
                                            <td colspan="6">Please select Filter value.</td>
                                        </tr>
                                    <?php else : ?>
                                        <tr>
                                            <td colspan="6">No record found for <?= @$id ?></td>
                                        </tr>
                                    <?php endif; ?>
                                </table>
                            </div>

                            <script>
                                var data = <?php echo json_encode($resultArr) ?>;
                                const scriptURL = 'payment-api.php';

                                data.forEach(item => {
                                    const form = document.forms['ipfclose' + item.goalsheetid]
                                    form.addEventListener('submit', e => {
                                        e.preventDefault();

                                        if (confirm('Are you sure you want to close the process?')) {
                                            fetch(scriptURL, {
                                                    method: 'POST',
                                                    // body: new FormData(document.forms['ipfclose' + item.goalsheetid])
                                                    body: new FormData(e.target)

                                                })
                                                .then(response => response.text())
                                                .then(result => {
                                                    if (result === 'success') {
                                                        alert("The process for form ID " + item.goalsheetid + " has been closed in the system.");
                                                        location.reload();
                                                    } else {
                                                        alert("Error closing the process for form ID " + item.goalsheetid + ". Please try again later or contact support.");
                                                    }
                                                })
                                                .catch(error => {
                                                    console.error('Error!', error.message);
                                                });
                                        } else {
                                            alert("Process close cancelled.");
                                        }
                                    });
                                    console.log(item);
                                });
                            </script>
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
    <script>
        <?php if (date('m') == 1 || date('m') == 2 || date('m') == 3) { ?>
            var currentYear = new Date().getFullYear() - 1;
        <?php } else { ?>
            var currentYear = new Date().getFullYear();
        <?php } ?>

        for (var i = 0; i < 5; i++) {
            var next = currentYear + 1;
            var year = currentYear + '-' + next;
            //next.toString().slice(-2) 
            $('#get_aid').append(new Option(year, year));
            currentYear--;
        }
    </script>
</body>

</html>