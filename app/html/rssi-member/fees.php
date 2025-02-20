<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");


if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];

    header("Location: index.php");
    exit;
}
validation();
?>
<?php
setlocale(LC_TIME, 'fr_FR.UTF-8');

@$id = $_GET['get_aid'];
@$status = $_GET['get_id'];
@$section = $_GET['get_category'];
@$stid = $_GET['get_stid'];
@$is_user = $_GET['is_user'];

$query = "SELECT fees.*, faculty.associatenumber, faculty.fullname, student.student_id, student.studentname, student.category, student.contact
          FROM fees
          LEFT JOIN rssimyaccount_members AS faculty ON fees.collectedby = faculty.associatenumber
          LEFT JOIN rssimyprofile_student AS student ON fees.studentid = student.student_id
          WHERE 1 = 1 ";

if ($stid != null) {
    $query .= "AND fees.studentid = '$stid' ";
}

if ($section != null && $section != 'ALL') {
    $sections = implode("','", $section);
    $query .= "AND student.category IN ('$sections') ";
}

if ($status != null && $status != 'ALL') {
    $month = date('m', strtotime($status));
    $query .= "AND EXTRACT(MONTH FROM TO_DATE('$status', 'Month')) = fees.month ";
}

if ($id != null) {
    $query .= "AND fees.feeyear = $id ";
}
if ($id === null && $status === null && $section === null && $stid === null) {
    $query .= "AND fees.studentid = '' ";
}
$query .= "ORDER BY fees.id DESC";

$result = pg_query($con, $query);

if (!$result) {
    echo "An error occurred.\n";
    exit;
}

$totalapprovedamount_query = "SELECT COALESCE(SUM(fees), 0) 
                             FROM fees
                             WHERE 1=1 ";

$totaltransferredamount_query = "SELECT COALESCE(SUM(fees), 0) 
                                FROM fees
                                WHERE pstatus = 'transferred' ";

if ($stid != null) {
    $totalapprovedamount_query .= "AND fees.studentid = '$stid' ";
    $totaltransferredamount_query .= "AND fees.studentid = '$stid' ";
} else {
    if ($section != null && $section != 'ALL') {
        $totalapprovedamount_query .= "AND fees.studentid IN (SELECT student_id FROM rssimyprofile_student WHERE category IN ('$sections')) ";
        $totaltransferredamount_query .= "AND fees.studentid IN (SELECT student_id FROM rssimyprofile_student WHERE category IN ('$sections')) ";
    }

    if ($status != null && $status != 'ALL') {
        $month = date('m', strtotime($status));
        $totalapprovedamount_query .= "AND EXTRACT(MONTH FROM TO_DATE('$status', 'Month')) = fees.month ";
        $totaltransferredamount_query .= "AND EXTRACT(MONTH FROM TO_DATE('$status', 'Month')) = fees.month ";
    }

    if ($id != null) {
        $totalapprovedamount_query .= "AND fees.feeyear = $id ";
        $totaltransferredamount_query .= "AND fees.feeyear = $id ";
    }
    if ($id === null && $status === null && $section === null && $stid === null) {
        $totalapprovedamount_query .= "AND fees.studentid = '' ";
        $totaltransferredamount_query .= "AND fees.studentid = '' ";
    }
}

$totaltransferredamount_query .= " AND fees.pstatus = 'transferred'";

$totalapprovedamount = pg_query($con, $totalapprovedamount_query);
$totaltransferredamount = pg_query($con, $totaltransferredamount_query);

$resultArr = pg_fetch_all($result);
$resultArrr = pg_fetch_result($totalapprovedamount, 0, 0);
$resultArrrr = pg_fetch_result($totaltransferredamount, 0, 0);

$categories = [
    "LG2-A",
    "LG2-B",
    "LG2-C",
    "LG3",
    "LG4",
    "LG4S1",
    "LG4S2",
    "WLG3",
    "WLG4S1",
    'Undefined',
    "ALL"
];
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

    <title>Fees Details</title>

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
    <!-- Papa parse -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/PapaParse/5.3.0/papaparse.min.js"></script>
    <script type="importmap">
        {
        "imports": {
        "vue": "https://unpkg.com/vue@3/dist/vue.esm-browser.js"
        }
    }
    </script>

</head>

<!-- =========================
     NAVIGATION LINKS     
============================== -->

<body>
    <?php include 'inactive_session_expire_check.php'; ?>

    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Fees Details</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Work</a></li>
                    <li class="breadcrumb-item"><a href="student.php">Student Database</a></li>
                    <li class="breadcrumb-item active">Fees Details</li>
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

                            <div class="row">
                                <div class="col">
                                    Record count: <?php echo sizeof($resultArr) ?><br>
                                    Total collected amount:
                                    <p class="badge bg-secondary"><?php echo ($resultArrr - $resultArrrr) ?></p>
                                    + <p class="badge bg-success"><?php echo ($resultArrrr) ?></p>
                                    = <p class="badge bg-info"><?php echo ($resultArrr) ?></p>
                                </div>
                                <div class="col text-end">
                                    <form method="POST" action="export_function.php" style="display:inline">
                                        <input type="hidden" value="fees" name="export_type" />
                                        <input type="hidden" value="<?php echo $id ?>" name="id" />
                                        <input type="hidden" value="<?php echo $status ?>" name="status" />
                                        <input type="hidden" value="<?php echo $section ?>" name="section" />
                                        <input type="hidden" value="<?php echo $sections ?>" name="sections" />
                                        <input type="hidden" value="<?php echo $stid ?>" name="stid" />

                                        <button type="submit" id="export" name="export" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Export CSV"><i class="bi bi-file-earmark-excel" style="font-size:large;"></i></button>
                                    </form>
                                    <span style="display:inline">|</span>
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#popup">Import file</a>
                                </div>
                            </div>
                            <form action="" method="GET">
                                <div class="form-group" style="display: inline-block;">
                                    <div class="col2" style="display: inline-block;">

                                        <select name="get_aid" id="get_aid" class="form-select" style="width:max-content; display:inline-block" placeholder="Select policy year" required>
                                            <?php if ($id == null) { ?>
                                                <option hidden selected>Select year</option>
                                            <?php
                                            } else { ?>
                                                <option hidden selected><?php echo $id ?></option>
                                            <?php }
                                            ?>
                                        </select>

                                        <select name="get_id" id="get_id" class="form-select" style="width:max-content; display:inline-block" placeholder="Select policy year">
                                            <?php if ($status == null) { ?>
                                                <option hidden selected>Select month</option>
                                            <?php
                                            } else { ?>
                                                <option hidden selected><?php echo $status ?></option>
                                            <?php }
                                            ?>
                                            <option>January</option>
                                            <option>February</option>
                                            <option>March</option>
                                            <option>April</option>
                                            <option>May</option>
                                            <option>June</option>
                                            <option>July</option>
                                            <option>August</option>
                                            <option>September</option>
                                            <option>October</option>
                                            <option>November</option>
                                            <option>December</option>
                                            <option>ALL</option>
                                        </select>

                                        <select name="get_category[]" id="get_category" class="form-control" style="width:max-content;display:inline-block" multiple>
                                            <?php if ($section == null) { ?>
                                                <option disabled selected hidden>Select Category</option>

                                                <?php foreach ($categories as $cat) { ?>
                                                    <option><?php echo $cat ?></option>
                                                <?php } ?>

                                                <?php
                                            } else {

                                                foreach ($categories as $cat) { ?>
                                                    <option <?php if (in_array($cat, $section)) {
                                                                echo "selected";
                                                            } ?>><?php echo $cat ?></option>
                                            <?php }
                                            }
                                            ?>

                                        </select>
                                        <input name="get_stid" id="get_stid" class="form-control" style="width:max-content; display:inline-block" placeholder="Student ID" value="<?php echo $stid ?>" required>
                                    </div>
                                </div>
                                <div class="col2 left" style="display: inline-block;">
                                    <button type="submit" name="search_by_id" class="btn btn-success btn-sm" style="outline: none;">
                                        <i class="bi bi-search"></i>&nbsp;Search</button>
                                </div>
                                <div id="filter-checks">
                                    <input type="checkbox" name="is_user" id="is_user" value="1" <?php if (isset($_GET['is_user'])) echo "checked='checked'"; ?> />
                                    <label for="is_user" style="font-weight: 400;">Search by Student ID</label>
                                </div>
                            </form>
                            <br>
                            <script>
                                if ($('#is_user').not(':checked').length > 0) {

                                    document.getElementById("get_aid").disabled = false;
                                    document.getElementById("get_id").disabled = false;
                                    document.getElementById("get_category").disabled = false;
                                    document.getElementById("get_stid").disabled = true;

                                } else {

                                    document.getElementById("get_aid").disabled = true;
                                    document.getElementById("get_id").disabled = true;
                                    document.getElementById("get_category").disabled = true;
                                    document.getElementById("get_stid").disabled = false;

                                }

                                const checkbox = document.getElementById('is_user');

                                checkbox.addEventListener('change', (event) => {
                                    if (event.target.checked) {
                                        document.getElementById("get_aid").disabled = true;
                                        document.getElementById("get_id").disabled = true;
                                        document.getElementById("get_category").disabled = true;
                                        document.getElementById("get_stid").disabled = false;
                                    } else {
                                        document.getElementById("get_aid").disabled = false;
                                        document.getElementById("get_id").disabled = false;
                                        document.getElementById("get_category").disabled = false;
                                        document.getElementById("get_stid").disabled = true;
                                    }
                                })
                            </script>
                            <script>
                                var currentYear = new Date().getFullYear();
                                for (var i = 0; i < 5; i++) {
                                    var year = currentYear;
                                    //next.toString().slice(-2)
                                    $('#get_aid').append(new Option(year));
                                    currentYear--;
                                }
                            </script>
                            <?php if ($role == 'Admin') { ?>
                                <div class="text-end">
                                    <form name="transfer_all" action="payment-api.php" method="POST" style="display: -webkit-inline-box;">
                                        <input type="hidden" name="form-type" type="text" value="transfer_all">
                                        <input type="hidden" name="pid" id="pid" readonly>
                                        <button type="submit" class="btn btn-primary" id="transferButton" disabled>Bulk
                                            Transfer (<span id="selectedCount">0</span>)</button>
                                    </form>
                                </div>
                            <?php } ?>

                            <div class="table-responsive">
                                <table class="table" id="table-id">
                                    <thead>
                                        <tr>
                                            <th scope="col">Ref.</th>
                                            <th scope="col">Fees collection date</th>
                                            <th scope="col">ID/F Name</th>
                                            <th scope="col">Category</th>
                                            <th scope="col">Month</th>
                                            <th scope="col">Amount (&#8377;)</th>
                                            <th scope="col">Type</th>
                                            <th scope="col">Collected by</th>
                                            <th scope="col"></th>
                                        </tr>
                                    </thead>
                                    <?php if ($resultArr != null) : ?>
                                        <tbody>
                                            <?php foreach ($resultArr as $array) : ?>
                                                <tr>
                                                    <td>
                                                        <?php if ($array['pstatus'] != 'transferred' && $role == 'Admin') : ?>
                                                            <input class="form-check-input" type="checkbox" id="myCheckbox<?= $array['id'] ?>" name="checkbox[]" value="<?= $array['id'] ?>">
                                                        <?php endif; ?>
                                                        <label class="form-check-label" for="myCheckbox<?= $array['id'] ?>"><?= $array['id'] ?></label>
                                                    </td>
                                                    <td><?= @date("d/m/Y", strtotime($array['date'])) ?></td>
                                                    <td><?= $array['studentid'] . '/' . @strtok($array['studentname'], ' ') ?>
                                                    </td>
                                                    <td><?= $array['category'] ?></td>
                                                    <td><?= date('F', mktime(0, 0, 0, $array['month'], 10)) . '-' . $array['feeyear'] ?>
                                                    </td>
                                                    <td><?= $array['fees'] ?></td>
                                                    <td><?= $array['ptype'] ?></td>
                                                    <td><?= $array['fullname'] ?></td>
                                                    <td>
                                                        <?php if ($array['pstatus'] != 'transferred' && $role == 'Admin') : ?>
                                                            <form name="transfer_<?= $array['id'] ?>" action="#" method="POST" style="display: -webkit-inline-box;">
                                                                <input type="hidden" name="form-type" type="text" value="transfer">
                                                                <input type="hidden" name="pid" id="pid" type="text" value="<?= $array['id'] ?>">
                                                                <button type="submit" id="yes" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Transfer"><i class="bi bi-upload"></i></button>
                                                            </form>
                                                        <?php endif; ?>
                                                        <?php if ($array['pstatus'] != 'transferred' && $role == 'Admin') : ?>
                                                            <form name="paydelete_<?= $array['id'] ?>" action="#" method="POST" style="display: -webkit-inline-box;">
                                                                <input type="hidden" name="form-type" type="text" value="paydelete">
                                                                <input type="hidden" name="pid" id="pid" type="text" value="<?= $array['id'] ?>">
                                                                &nbsp;&nbsp;&nbsp;<button type="submit" id="yes" onclick="validateForm()" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Delete"><i class="bi bi-x-lg"></i></button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    <?php elseif ($id == "" && $stid == "") : ?>
                                        <tbody>
                                            <tr>
                                                <td colspan="9">Please select Filter value.</td>
                                            </tr>
                                        </tbody>
                                    <?php elseif (sizeof($resultArr) == 0 && $stid == "") : ?>
                                        <tbody>
                                            <?php
                                            $filterValues = [];

                                            if (!empty($id)) {
                                                if (is_array($id)) {
                                                    $filterValues[] = implode(", ", array_filter($id));
                                                } else {
                                                    $filterValues[] = $id;
                                                }
                                            }

                                            if (!empty($status)) {
                                                if (is_array($status)) {
                                                    $filterValues[] = implode(", ", array_filter($status));
                                                } else {
                                                    $filterValues[] = $status;
                                                }
                                            }

                                            if (!empty($section)) {
                                                if (is_array($section)) {
                                                    $filterValues[] = implode(", ", array_filter($section));
                                                } else {
                                                    $filterValues[] = $section;
                                                }
                                            }

                                            $filterString = implode(", ", $filterValues);

                                            echo '<tr>
                                                     <td colspan="5">No record found for ' . $filterString . '</td>
                                                 </tr>';
                                            ?>
                                        </tbody>
                                    <?php elseif (sizeof($resultArr) == 0 && $stid != "") : ?>
                                        <tbody>
                                            <tr>
                                                <td colspan="5">No record found for <?= $stid ?></td>
                                            </tr>
                                        </tbody>
                                    <?php endif; ?>
                                </table>
                            </div>
                        </div>
                    </div>
                </div><!-- End Reports -->
            </div>
        </section>

    </main><!-- End #main -->
    <!-- Popup -->
    <div class="modal fade" id="popup" tabindex="-1" aria-labelledby="popupLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-xl">
            <div class="modal-content" id="fees-import-app">
                <!-- Header -->
                <div class="modal-header">
                    <h5 class="modal-title" id="popupLabel">Import file</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <!-- Body -->
                <div class="modal-body" v-if="!loading">
                    <!-- <div class="container mt-5"> -->
                    <div>

                        <div class="mb-3">
                            <label for="fileInput" class="form-label">Choose a File</label>
                            <input ref="fileInput" @change="onFileSelected" type="file" class="form-control" id="fileInput" name="fileInput" required>
                        </div>
                        <div>
                            <table class="table table-striped" v-if="rows.length > 0" v-cloak>
                                <thead>
                                    <tr>
                                        <th scope="col" v-for="header in headers" :key="header">{{ header }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="row in rows" :key="row.id">
                                        <td v-for="header in headers" :key="header">{{ row[header] }}</td>
                                    </tr>
                                </tbody>
                            </table>
                            <div class="alert alert-danger" v-if="error" v-cloak>{{error}}</div>
                            <div class="alert alert-success" v-if="message" v-cloak>{{message}}</div>
                        </div>
                    </div>
                </div>
                <div class="modal-body" v-if="loading">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p id="loadingMessage">Submission in progress.
                            Please do not close or reload this page.</p>
                    </div>
                </div>
                <!-- Footer -->
                <div class="modal-footer">
                    <button type="submit" class="btn" @click="upload" :disabled="rows.length == 0" :class="{ 'btn-outlined': rows.length == 0, 'btn-primary': rows.length > 0 }">Insert</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
    <!-- Bootstrap Modal -->
    <div class="modal fade" id="myModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p id="loadingMessage">Submission in progress.
                            Please do not close or reload this page.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        const pidInput = document.getElementById('pid');
        const transferButton = document.getElementById('transferButton');

        pidInput.addEventListener('input', () => {
            const pidValue = pidInput.value;
            if (pidValue) {
                transferButton.disabled = false;
            } else {
                transferButton.disabled = true;
            }
        });

        $(document).ready(function() {
            $('input[name="checkbox[]"]').change(function() {
                var selectedValues = [];
                $('input[name="checkbox[]"]:checked').each(function() {
                    selectedValues.push($(this).val());
                });
                $('#pid').val(selectedValues.join(', '));
                $('#selectedCount').text(selectedValues.length); // Update selected count
                pidInput.dispatchEvent(new Event('input')); // Trigger the input event
            });

            $('form[name="transfer_all"]').submit(function(event) {
                event.preventDefault(); // Prevent the default form submission
                const formData = $(this).serialize(); // Serialize form data
                const scriptURL = 'payment-api.php';
                // Show the Bootstrap modal
                const myModal = new bootstrap.Modal(document.getElementById("myModal"), {
                    backdrop: 'static',
                    keyboard: false
                });
                myModal.show();

                $.ajax({
                    type: 'POST',
                    url: scriptURL,
                    data: formData,
                    success: function(response) {
                        myModal.hide();
                        if (response === 'success') {
                            alert('Bulk transfer has been completed.');
                            location.reload();
                        } else {
                            alert('Error occurred during bulk transfer.');
                        }
                    },
                    error: function() {
                        myModal.hide();
                        alert('Error occurred during the AJAX request.');
                    }
                });
            });
        });
    </script>

    <script>
        var data = <?php echo json_encode($resultArr) ?>;
        var aid = <?php echo json_encode($_SESSION['aid']) ?>; // Enclose session data in json_encode

        const scriptURL = 'payment-api.php';

        data.forEach(item => {
            const form = document.querySelector(`form[name='transfer_${item.id}']`);
            form.addEventListener('submit', e => {
                e.preventDefault();
                fetch(scriptURL, {
                        method: 'POST',
                        body: new FormData(form)
                    })
                    .then(response => response.text())
                    .then(data => {
                        alert("Amount has been transferred.");
                        location.reload();
                    })
                    .catch(error => console.error('Error!', error.message));
            });
        });

        function validateForm() {
            if (confirm(
                    'Are you sure you want to delete this record? Once you click OK, the record cannot be reverted.')) {
                data.forEach(item => {
                    const form = document.querySelector(`form[name='paydelete_${item.id}']`);
                    form.addEventListener('submit', e => {
                        e.preventDefault();
                        fetch(scriptURL, {
                                method: 'POST',
                                body: new FormData(form)
                            })
                            .then(response => response.text())
                            .then(data => {
                                alert("Record has been deleted.");
                                location.reload();
                            })
                            .catch(error => console.error('Error!', error.message));
                    });
                });
            } else {
                alert("Record has NOT been deleted.");
                return false;
            }
        }
    </script>


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
                    paging: false,
                    "order": [] // Disable initial sorting
                    // other options...
                });
            <?php endif; ?>
        });
    </script>
    <script type="module" src="/js/fees_import_app.js"></script>
</body>

</html>