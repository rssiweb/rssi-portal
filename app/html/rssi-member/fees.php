<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");


if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];

    header("Location: index.php");
    exit;
}
if ($role != 'Admin' && $role != 'Offline Manager') {

    //header("Location: javascript:history.back()"); //redirect to the login page to secure the welcome page without login access.
    echo '<script type="text/javascript">';
    echo 'alert("Access Denied. You are not authorized to access this web page.");';
    echo 'window.location.href = "home.php";';
    echo '</script>';
}
?>
<?php
setlocale(LC_TIME, 'fr_FR.UTF-8');


@$id = $_POST['get_aid'];
@$status = $_POST['get_id'];
@$section = $_POST['get_category'];
@$stid = $_POST['get_stid'];
@$is_user = $_POST['is_user'];


$query = "SELECT fees.*, faculty.associatenumber, faculty.fullname, student.student_id, student.studentname, student.category, student.contact
          FROM fees
          LEFT JOIN rssimyaccount_members AS faculty ON fees.collectedby = faculty.associatenumber
          LEFT JOIN rssimyprofile_student AS student ON fees.studentid = student.student_id
          WHERE ";

if ($stid != null && $status == null && $section == null && $id == null) {
    $query .= "fees.studentid='$stid'";
} else {
    if ($section != null && $section != 'ALL') {
        $sections = implode("','", $section);
        $query .= "student.category IN ('$sections')";
    }

    if ($status != null && $status != 'ALL') {
        $month = date('m', strtotime($status));
        $query .= ($section != null && $section != 'ALL') ? " AND " : "";
        $query .= "EXTRACT(MONTH FROM TO_DATE('$status', 'Month')) = fees.month";
    }

    if ($id != null) {
        $query .= ($section != null || $status != null) ? " AND " : "";
        $query .= "feeyear = $id";
    }

    if (($section == 'ALL' || $section == null) && ($status == 'ALL' || $status == null) && $id == null) {
        $query .= "fees.month = '0'";
    }
}

$query .= " ORDER BY fees.id DESC";

$result = pg_query($con, $query);

if (!$result) {
    echo "An error occurred.\n";
    exit;
}

$totalapprovedamount_query = "SELECT SUM(fees) FROM fees";
$totaltransferredamount_query = "SELECT SUM(fees) FROM fees";

if ($section != null && $section != 'ALL') {
    $totalapprovedamount_query .= " LEFT JOIN rssimyprofile_student AS student ON fees.studentid = student.student_id WHERE student.category IN ('$sections')";
    $totaltransferredamount_query .= " LEFT JOIN rssimyprofile_student AS student ON fees.studentid = student.student_id WHERE student.category IN ('$sections')";
} else {
    $totalapprovedamount_query .= " LEFT JOIN rssimyprofile_student AS student ON fees.studentid = student.student_id";
    $totaltransferredamount_query .= " LEFT JOIN rssimyprofile_student AS student ON fees.studentid = student.student_id";
}

if ($status != null && $status != 'ALL') {
    $totalapprovedamount_query .= ($section != null && $section != 'ALL') ? " AND " : " WHERE ";
    $totalapprovedamount_query .= "EXTRACT(MONTH FROM TO_DATE('$status', 'Month')) = fees.month";

    $totaltransferredamount_query .= ($section != null && $section != 'ALL') ? " AND " : " WHERE ";
    $totaltransferredamount_query .= "EXTRACT(MONTH FROM TO_DATE('$status', 'Month')) = fees.month";
}

if ($id != null) {
    $totalapprovedamount_query .= ($section != null || $status != null) ? " AND " : " WHERE ";
    $totalapprovedamount_query .= "feeyear = $id";
    $totaltransferredamount_query .= ($section != null || $status != null) ? " AND " : " WHERE ";
    $totaltransferredamount_query .= "feeyear = $id";
}

$totaltransferredamount_query .= " AND pstatus='transferred'";

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
]
?>


<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Fees Details</title>

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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>

</head>

<!-- =========================
     NAVIGATION LINKS     
============================== -->

<body>

    <body>

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
                                        <form method="POST" action="export_function.php">
                                            <input type="hidden" value="fees" name="export_type" />
                                            <input type="hidden" value="<?php echo $id ?>" name="id" />
                                            <input type="hidden" value="<?php echo $status ?>" name="status" />
                                            <input type="hidden" value="<?php echo $section ?>" name="section" />
                                            <input type="hidden" value="<?php echo $sections ?>" name="sections" />
                                            <input type="hidden" value="<?php echo $stid ?>" name="stid" />

                                            <button type="submit" id="export" name="export" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Export CSV"><i class="bi bi-file-earmark-excel" style="font-size:large;"></i></button>
                                        </form>
                                    </div>
                                </div>
                                <form action="" method="POST">
                                    <div class="form-group" style="display: inline-block;">
                                        <div class="col2" style="display: inline-block;">

                                            <select name="get_aid" id="get_aid" class="form-select" style="width:max-content; display:inline-block" placeholder="Select policy year" required>
                                                <?php if ($id == null) { ?>
                                                    <option value="" hidden selected>Select year</option>
                                                <?php
                                                } else { ?>
                                                    <option hidden selected><?php echo $id ?></option>
                                                <?php }
                                                ?>
                                            </select>

                                            <select name="get_id" id="get_id" class="form-select" style="width:max-content; display:inline-block" placeholder="Select policy year">
                                                <?php if ($status == null) { ?>
                                                    <option value="" hidden selected>Select month</option>
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
                                                    <option value="" disabled selected hidden>Select Category</option>

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
                                        <input type="checkbox" name="is_user" id="is_user" value="1" <?php if (isset($_POST['is_user'])) echo "checked='checked'"; ?> />
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
                                <?php echo '
                            <div class="table-responsive">
                            <table class="table">
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
                            </thead>' ?>
                                <?php if ($resultArr != null) {
                                    echo '<tbody>';
                                    foreach ($resultArr as $array) {
                                        echo '<tr>
                            <td>' . $array['id'] . '</td>
                            <td>' . @date("d/m/Y", strtotime($array['date'])) . '</td>
                        <td>' . $array['studentid'] . '/' . strtok($array['studentname'], ' ') . '</td>
                        <td>' . $array['category'] . '</td>   
                        <td>' . date('F', mktime(0, 0, 0, $array['month'], 10)) . '-' . $array['feeyear'] . '</td>  
                        <td>' . $array['fees'] . '</td>
                        <td>' . $array['ptype'] . '</td>
                        <td>' . $array['fullname'] . '</td>
                        <td>
                        <form name="transfer_' . $array['id'] . '" action="#" method="POST" style="display: -webkit-inline-box;">
                        <input type="hidden" name="form-type" type="text" value="transfer">
                        <input type="hidden" name="pid" id="pid" type="text" value="' . $array['id'] . '">' ?>

                                        <?php if ($array['pstatus'] != 'transferred' && $role == 'Admin') { ?>

                                            <?php echo '<button type="submit" id="yes" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Transfer"><i class="bi bi-upload"></i></button>' ?>
                                        <?php } ?>
                                        <?php echo ' </form>


                        <form name="paydelete_' . $array['id'] . '" action="#" method="POST" style="display: -webkit-inline-box;">
                        <input type="hidden" name="form-type" type="text" value="paydelete">
                        <input type="hidden" name="pid" id="pid" type="text" value="' . $array['id'] . '">' ?>

                                        <?php if ($array['pstatus'] != 'transferred' && $role == 'Admin') { ?>

                                            <?php echo '&nbsp;&nbsp;&nbsp;<button type="submit" id="yes" onclick=validateForm() style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Delete"><i class="bi bi-x-lg"></i></button>' ?>
                                        <?php } ?>
                                    <?php echo ' </form></td></tr>';
                                    } ?>



                                <?php
                                } else if ($id == "" && $stid == "") {
                                    echo '<tr>
                                              <td colspan="5">Please select Filter value.</td>
                                          </tr>';
                                } else if (sizeof($resultArr) == 0 && $stid == "") {
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
                                } else if (sizeof($resultArr) == 0 && $stid != "") {
                                    echo '<tr>
                                              <td colspan="5">No record found for ' . $stid . '</td>
                                          </tr>';
                                } ?>

                                <?php echo '</tbody></table></div>'; ?>

                                <script>
                                    var data = <?php echo json_encode($resultArr) ?>;
                                    var aid = <?php echo '"' . $_SESSION['aid'] . '"' ?>;

                                    //var pid = document.getElementById("pid")
                                    //pid.value = mydata["pid"]

                                    const scriptURL = 'payment-api.php'

                                    data.forEach(item => {
                                        const form = document.forms['transfer_' + item.id]
                                        form.addEventListener('submit', e => {
                                            e.preventDefault()
                                            fetch(scriptURL, {
                                                    method: 'POST',
                                                    body: new FormData(document.forms['transfer_' + item.id])
                                                })
                                                .then(response => alert("Amount has been transferred.") +
                                                    location.reload())
                                                .catch(error => console.error('Error!', error.message))
                                        })

                                        console.log(item)
                                    })

                                    function validateForm() {
                                        if (confirm('Are you sure you want to delete this record? Once you click OK the record cannot be reverted.')) {
                                            data.forEach(item => {
                                                const form = document.forms['paydelete_' + item.id]
                                                form.addEventListener('submit', e => {
                                                    e.preventDefault()
                                                    fetch(scriptURL, {
                                                            method: 'POST',
                                                            body: new FormData(document.forms['paydelete_' + item.id])
                                                        })
                                                        .then(response => alert("Record has been deleted.") +
                                                            location.reload())
                                                        .catch(error => console.error('Error!', error.message))
                                                })

                                                console.log(item)
                                            })
                                        } else {
                                            alert("Record has NOT been deleted.");
                                            return false;
                                        }
                                    }
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

    </body>

</html>