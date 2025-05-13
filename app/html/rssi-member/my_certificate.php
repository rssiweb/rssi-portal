<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");
include("../../util/drive.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}

validation();

date_default_timezone_set('Asia/Kolkata');
include("../../util/email.php");
?>
<?php if ($role == 'Admin') {

    if ($_POST) {
        @$certificate_no = 'RSC' . time();
        @$awarded_to_id = strtoupper($_POST['awarded_to_id'] ?? "na");
        @$badge_name = $_POST['badge_name'];
        @$comment = htmlspecialchars($_POST['comment'], ENT_QUOTES, 'UTF-8');
        @$template_code = $_POST['template_code'];
        @$certificate_url = $_POST['certificate_url'];
        @$nominatedby = $_POST['nominatedby'];;

        @$awarded_to_name = $_POST['out_name'];
        @$out_phone = $_POST['out_phone'];
        @$out_email = $_POST['out_email'];
        @$out_scode = $_POST['out_scode'];
        @$out_flag = $_POST['is_users'] ?? 0;
        @$uploadedFile = $_FILES['certificate_url'];
        $pdf_certificate = isset($_POST['pdf_certificate']) ? true : null;

        @$now = date('Y-m-d H:i:s');

        if ($certificate_no != "") {
            // send uploaded file to drive
            // get the drive link
            if (empty($_FILES['certificate_url']['name'])) {
                $doclink = null;
            } else {
                if ($badge_name == 'Offer Letter') {
                    $parent = '1ax2QbjgC3yjJK3ezbrS9ZtOllRlUHOR8';
                    $filename = $awarded_to_id . "_" . $badge_name . "_" . time();
                } else if ($badge_name == 'Joining Letter') {
                    $parent = '1ax2QbjgC3yjJK3ezbrS9ZtOllRlUHOR8';
                    $filename = $awarded_to_id . "_" . $badge_name . "_" . time();
                } else {
                    $parent = '1Qsogy6nZHd5MgnPHcKyiYmnhefkNjGln';
                    $filename = $certificate_no . "_" . $badge_name . "_" . $awarded_to_id;
                }
                $doclink = uploadeToDrive($uploadedFile, $parent, $filename);
            }

            // Define all possible fields and their corresponding variables
            $data = [
                "certificate_no" => $certificate_no,
                "issuedon" => $now,
                "awarded_to_id" => $awarded_to_id,
                "badge_name" => $badge_name,
                "comment" => $comment,
                "certificate_url" => $doclink,
                "nominatedby" => $nominatedby,
                "awarded_to_name" => $awarded_to_name,
                "out_phone" => $out_phone,
                "out_email" => $out_email,
                "out_scode" => $out_scode,
                "out_flag" => $out_flag,
                "pdf_certificate" => $pdf_certificate,
                "template_code" => $template_code,
            ];

            // Initialize arrays for dynamic query construction
            $fields = [];
            $values = [];
            $params = [];
            $index = 1; // Placeholder index for parameterized query

            foreach ($data as $field => $value) {
                if (!empty($value) || $value === "0") { // Include "0" to account for valid numeric zero
                    $fields[] = $field;
                    $values[] = '$' . $index;
                    $params[] = $value;
                    $index++;
                }
            }

            // Construct the query
            if (!empty($fields)) {
                $field_list = implode(", ", $fields);
                $value_placeholders = implode(", ", $values);

                $query = "INSERT INTO certificate ($field_list) VALUES ($value_placeholders)";
                $result = pg_query_params($con, $query, $params);

                if ($result) {
                    $cmdtuples = pg_affected_rows($result);
                } else {
                    error_log("Query failed: " . pg_last_error($con));
                    die("An error occurred while processing your request.");
                }
            } else {
                die("No valid data to insert.");
            }

            $resultt = pg_query($con, "Select fullname,email from rssimyaccount_members where associatenumber='$awarded_to_id'");
            @$nameassociate = pg_fetch_result($resultt, 0, 0);
            @$emailassociate = pg_fetch_result($resultt, 0, 1);

            $resulttt = pg_query($con, "Select studentname,emailaddress from rssimyprofile_student where student_id='$awarded_to_id'");
            @$namestudent = pg_fetch_result($resulttt, 0, 0);
            @$emailstudent = pg_fetch_result($resulttt, 0, 1);

            $fullname_nominee = $nameassociate . $namestudent . $awarded_to_name;
            $email_nominee = $emailassociate . $emailstudent . $out_email;

            if ($badge_name == 'Offer Letter') {
                $emailtemplate = 'offerletter';
            } else if ($badge_name == 'Joining Letter') {
                $emailtemplate = 'joiningletter';
            } else {
                $emailtemplate = 'badge';
            }
            sendEmail($emailtemplate, array(
                "badge_name" => $badge_name,
                "awarded_to_id" => $awarded_to_id,
                "fullname" => $fullname_nominee,
                "doclink" => $doclink,
            ), $email_nominee, False);
        }
    }
}
?>
<?php
$search_mode = isset($_GET['search_mode']) ? $_GET['search_mode'] : '';
$get_certificate_no = isset($_GET['get_certificate_no']) ? strtoupper($_GET['get_certificate_no']) : '';
$get_nomineeid = isset($_GET['get_nomineeid']) ? strtoupper($_GET['get_nomineeid']) : '';
$is_user = isset($_GET['is_user']) ? $_GET['is_user'] : '';
$academic_year = isset($_GET['academic_year']) ? $_GET['academic_year'] : '';
$view_all = isset($_GET['view_all']) ? $_GET['view_all'] : '';
$user_associatenumber = $associatenumber; // Get the user's associate number

// Base query
$query = "SELECT certificate.*, 
    faculty.fullname AS awarded_to_name, faculty.email AS awarded_to_email, faculty.phone AS awarded_to_phone, 
    student.studentname AS awarded_to_student_name, student.emailaddress AS awarded_to_student_email, student.contact AS awarded_to_student_phone,
    nominator.fullname AS nominated_by_name
FROM certificate  
LEFT JOIN (SELECT associatenumber, fullname, email, phone FROM rssimyaccount_members) faculty 
    ON certificate.awarded_to_id = faculty.associatenumber
LEFT JOIN (SELECT student_id, studentname, emailaddress, contact FROM rssimyprofile_student) student 
    ON certificate.awarded_to_id = student.student_id
LEFT JOIN (SELECT associatenumber, fullname FROM rssimyaccount_members) nominator 
    ON certificate.nominatedby = nominator.associatenumber";

$conditions = [];

// Determine current academic year for default loading
$currentMonth = date('m');
$currentYear = date('Y');
$academicYear = ($currentMonth < 4) ? $currentYear - 1 : $currentYear;
$start_date = $academicYear . '-04-01';
$end_date = ($academicYear + 1) . '-03-31';

if ($role == 'Admin') {
    // Admin-specific search logic
    if (empty($search_mode) && empty($get_certificate_no) && empty($get_nomineeid) && empty($academic_year)) {
        // Default view - Current academic year
        $conditions[] = "issuedon BETWEEN '$start_date' AND '$end_date'";
    } else {
        switch ($search_mode) {
            case 'certificate_no':
                if (!empty($get_certificate_no)) {
                    $conditions[] = "certificate_no='$get_certificate_no'";
                }
                break;
            case 'nominee_id':
                if (!empty($get_nomineeid)) {
                    $conditions[] = "awarded_to_id='$get_nomineeid'";
                }
                break;
            case 'combo':
                if (!empty($get_certificate_no)) {
                    $conditions[] = "certificate_no='$get_certificate_no'";
                }
                if (!empty($get_nomineeid)) {
                    $conditions[] = "awarded_to_id='$get_nomineeid'";
                }
                if (!empty($academic_year)) {
                    $start_date = $academic_year . '-04-01';
                    $end_date = ($academic_year + 1) . '-03-31';
                    $conditions[] = "issuedon BETWEEN '$start_date' AND '$end_date'";
                }
                break;
        }
    }
} else {
    // Non-admin-specific search logic
    if ($view_all) {
        $conditions[] = "awarded_to_id='$user_associatenumber'";
    } elseif (!empty($get_certificate_no)) {
        $conditions[] = "certificate_no='$get_certificate_no' AND awarded_to_id='$user_associatenumber'";
    } elseif (!empty($academic_year)) {
        $start_date = $academic_year . '-04-01';
        $end_date = ($academic_year + 1) . '-03-31';
        if (!empty($get_certificate_no)) {
            $conditions[] = "certificate_no='$get_certificate_no' AND issuedon BETWEEN '$start_date' AND '$end_date' AND awarded_to_id='$user_associatenumber'";
        } else {
            $conditions[] = "issuedon BETWEEN '$start_date' AND '$end_date' AND awarded_to_id='$user_associatenumber'";
        }
    } else {
        // Default for non-admin: current academic year
        $conditions[] = "issuedon BETWEEN '$start_date' AND '$end_date' AND awarded_to_id='$user_associatenumber'";
    }
}

// Append conditions to query
if (!empty($conditions)) {
    $query .= " WHERE " . implode(' AND ', $conditions);
}
$query .= " ORDER BY issuedon DESC";

$result = pg_query($con, $query);
if (!$result) {
    echo "An error occurred.\n";
    exit;
}

$resultArr = pg_fetch_all($result);
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

    <title>My Certificate</title>
    <meta content="" name="description">
    <meta content="" name="keywords">

    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">


    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />

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
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
    <!-- AJAX for Associatenumber and Course Dropdowns -->
    <script>
        $(document).ready(function() {
            // Fetch Associates
            // Initialize Select2 for associatenumber dropdown
            $('#nominatedby').select2({
                ajax: {
                    url: 'fetch_associates.php', // Path to the PHP script
                    dataType: 'json',
                    delay: 250, // Delay in milliseconds before sending the request
                    data: function(params) {
                        return {
                            q: params.term // Search term
                        };
                    },
                    processResults: function(data) {
                        // Map the results to the format expected by Select2
                        return {
                            results: data.results
                        };
                    },
                    cache: true // Cache results for better performance
                },
                minimumInputLength: 1 // Require at least 1 character to start searching
            });
        });
    </script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>


    <style>
        #passwordHelpBlock,
        #passwordHelpBlock_awarded_to_id,
        #passwordHelpBlock_out_name,
        #passwordHelpBlock_out_phone,
        #passwordHelpBlock_out_email,
        #passwordHelpBlock_out_scode {
            display: block;
        }

        .input-help {
            vertical-align: top;
            display: inline-block;
        }
    </style>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>My Certificate</h1>
            <nav>
                <!-- <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item active">Class details</li>
                </ol> -->
                <?php if ($role == 'Admin') { ?>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="#">My Services</a></li>
                        <li class="breadcrumb-item"><a href="document.php">My Document</a></li>
                        <li class="breadcrumb-item active">Certificate Management System (CMS)</li>
                    </ol>
                <?php } else { ?>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="#">My Services</a></li>
                        <li class="breadcrumb-item"><a href="document.php">My Document</a></li>
                        <li class="breadcrumb-item active">My Certificate</li>
                    </ol>
                <?php } ?>
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
                                <?php if ($role == 'Admin') { ?>
                                    <?php if (@$certificate_no != null && @$cmdtuples == 0) { ?>
                                        <div class="alert alert-danger alert-dismissible text-center" role="alert">
                                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                            <i class="bi bi-exclamation-triangle"></i>
                                            <span>ERROR: Oops, something wasn't right.</span>
                                        </div>
                                    <?php } else if (@$cmdtuples == 1) { ?>
                                        <div class="alert alert-success alert-dismissible text-center" role="alert">
                                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                            <i class="bi bi-check2-circle"></i>
                                            <span>Certificate no <?php echo @$certificate_no ?> has been added.</span>
                                        </div>
                                        <script>
                                            if (window.history.replaceState) {
                                                window.history.replaceState(null, null, window.location.href);
                                            }
                                        </script>
                                    <?php } ?>
                                <?php } ?>
                            </div>

                            <?php if ($role == 'Admin') { ?>

                                <form autocomplete="off" name="cms" id="cms" action="my_certificate.php" method="POST" enctype="multipart/form-data">
                                    <div class="form-group">
                                        <div class="input-help">
                                            <input type="text" name="awarded_to_id" id="awarded_to_id" class="form-control" style="width: max-content; display: inline-block" placeholder="Nominee id" value="<?php echo @$_GET['awarded_to_id']; ?>" required>
                                            <small id="passwordHelpBlock_awarded_to_id" class="form-text text-muted">Nominee id*</small>
                                        </div>
                                        <div class="input-help">
                                            <input type="text" name="out_name" id="out_name" class="form-control" style="width: max-content; display: inline-block" placeholder="Nominee name" value="<?php echo @$_GET['awarded_to_name']; ?>" required>
                                            <small id="passwordHelpBlock_out_name" class="form-text text-muted">Nominee name*</small>
                                        </div>
                                        <div class="input-help">
                                            <input type="email" name="out_email" id="out_email" class="form-control" style="width: max-content; display: inline-block" placeholder="Nominee email" value="<?php echo @$_GET['out_email']; ?>" required>
                                            <small id="passwordHelpBlock_out_email" class="form-text text-muted">Nominee email*</small>
                                        </div>
                                        <div class="input-help">
                                            <input type="number" name="out_phone" id="out_phone" class="form-control" style="width: max-content; display: inline-block" placeholder="Nominee phone" value="<?php echo @$_GET['out_phone']; ?>" required>
                                            <small id="passwordHelpBlock_out_phone" class="form-text text-muted">Nominee phone*</small>
                                        </div>
                                        <div class="input-help">
                                            <input type="number" name="out_scode" id="out_scode" class="form-control" style="width: max-content; display: inline-block" placeholder="Scode" value="<?php echo @$_GET['out_scode']; ?>" required>
                                            <small id="passwordHelpBlock_out_scode" class="form-text text-muted">Scode*</small>
                                        </div>
                                        <div class="input-help">
                                            <select name="badge_name" class="form-select" style="width: max-content; display: inline-block" required>
                                                <?php if ($badge_name == null) { ?>
                                                    <option disabled selected hidden>Badge name</option>
                                                <?php } else { ?>
                                                    <option hidden selected><?php echo $badge_name ?></option>
                                                <?php } ?>
                                                <option>Certificate Of Appreciation</option>
                                                <option>Certificate Of Appreciation (Smile)</option>
                                                <option>Completion Certificate</option>
                                                <option>Experience Letter</option>
                                                <option>Joining Letter</option>
                                                <option>Learning Achievement Award</option>
                                                <option>Offer Letter</option>
                                                <option>Provisional Certificate</option>
                                                <option>Service & Commitment Award</option>
                                                <option>Smile</option>
                                                <option>Star Of The Month</option>
                                                <option>Star Of The Quarter</option>
                                                <option>Volunteer Of The Quarter</option>
                                            </select>
                                            <small id="passwordHelpBlock" class="form-text text-muted">Badge name*</small>
                                        </div>
                                        <div class="input-help">
                                            <textarea type="text" name="comment" id="comment" class="form-control" placeholder="Remarks" maxlength="500" oninput="updateCharacterCount()"></textarea>
                                            <small id="charCount" class="form-text text-muted">0/500 characters used</small>
                                        </div>
                                        <div class="input-help">
                                            <input type="number" name="gems" class="form-control" placeholder="Gems" min="1">
                                            <small id="passwordHelpBlock" class="form-text text-muted">Gems</small>
                                        </div>
                                        <div class="input-help">
                                            <input type="file" name="certificate_url" class="form-control" />
                                            <small id="passwordHelpBlock" class="form-text text-muted">Documents</small>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label for="nominatedby" class="form-label">Nominated by</label>
                                            <select class="form-control select2" id="nominatedby" name="nominatedby" required>
                                                <option value="">Select Associate</option>

                                            </select>
                                        </div>
                                        <!-- Dropdown for selecting certificate template -->
                                        <div id="certificate_dropdown" style="display: none; width: max-content" class="mb-3">
                                            <label for="template_code" class="form-label">Select Certificate Template</label>
                                            <select name="template_code" id="template_code" class="form-select">
                                                <option value="">-- Select Template --</option>
                                                <option value="certificate_of_achievement">Certificate of Achievement</option>
                                                <option value="certificate_of_appreciation">Certificate of Appreciation</option>
                                            </select>
                                        </div>
                                        <button type="submit" name="search_by_id" class="btn btn-danger btn-sm mb-3" style="outline: none;">
                                            <i class="bi bi-plus-lg"></i>&nbsp;&nbsp;Add
                                        </button>
                                    </div>
                                    <div class="form-check" id="filter-checkss">
                                        <input type="checkbox" name="is_users" id="is_users" class="form-check-input" value="1" <?php if (isset($_GET['is_users'])) echo "checked='checked'"; ?> />
                                        <label for="is_users" class="form-check-label">Non-registered candidate</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="checkbox" name="pdf_certificate" id="pdf_certificate" class="form-check-input" value="true" onchange="toggleCertificateDropdown()">
                                        <label for="pdf_certificate" class="form-check-label">Include PDF Certificate</label>
                                    </div>

                                    <script>
                                        function toggleCertificateDropdown() {
                                            var checkbox = document.getElementById("pdf_certificate");
                                            var dropdown = document.getElementById("certificate_dropdown");
                                            var select = document.getElementById("template_code");

                                            if (checkbox.checked) {
                                                dropdown.style.display = "inline-block";
                                                select.setAttribute("required", "required"); // Add required attribute
                                            } else {
                                                dropdown.style.display = "none";
                                                select.removeAttribute("required"); // Remove required attribute
                                            }
                                        }
                                    </script>
                                </form>


                                <script>
                                    if ($('#is_users').not(':checked').length > 0) {
                                        document.getElementById("awarded_to_id").disabled = false;
                                        $('#awarded_to_id').get(0).type = 'text';
                                        document.getElementById("passwordHelpBlock_awarded_to_id").style.display = 'block';
                                        document.getElementById("out_name").disabled = true;
                                        document.getElementById("out_phone").disabled = true;
                                        document.getElementById("out_email").disabled = true;
                                        document.getElementById("out_scode").disabled = true;
                                        $('#out_name').get(0).type = 'hidden';
                                        $('#out_phone').get(0).type = 'hidden';
                                        $('#out_email').get(0).type = 'hidden';
                                        $('#out_scode').get(0).type = 'hidden';
                                        document.getElementById("passwordHelpBlock_out_name").style.display = 'none';
                                        document.getElementById("passwordHelpBlock_out_phone").style.display = 'none';
                                        document.getElementById("passwordHelpBlock_out_email").style.display = 'none';
                                        document.getElementById("passwordHelpBlock_out_scode").style.display = 'none';
                                    } else {
                                        document.getElementById("awarded_to_id").disabled = true;
                                        $('#awarded_to_id').get(0).type = 'hidden';
                                        document.getElementById("passwordHelpBlock_awarded_to_id").style.display = 'none';
                                        document.getElementById("out_name").disabled = false;
                                        document.getElementById("out_phone").disabled = false;
                                        document.getElementById("out_email").disabled = false;
                                        document.getElementById("out_scode").disabled = false;
                                        $('#out_name').get(0).type = 'text';
                                        $('#out_phone').get(0).type = 'number';
                                        $('#out_email').get(0).type = 'email';
                                        $('#out_scode').get(0).type = 'text';
                                        document.getElementById("passwordHelpBlock_out_name").style.display = 'block';
                                        document.getElementById("passwordHelpBlock_out_phone").style.display = 'block';
                                        document.getElementById("passwordHelpBlock_out_email").style.display = 'block';
                                        document.getElementById("passwordHelpBlock_out_scode").style.display = 'block';
                                    }

                                    const checkboxs = document.getElementById('is_users');

                                    checkboxs.addEventListener('change', (event) => {
                                        if (event.target.checked) {
                                            document.getElementById("awarded_to_id").disabled = true;
                                            $('#awarded_to_id').get(0).type = 'hidden';
                                            document.getElementById("passwordHelpBlock_awarded_to_id").style.display = 'none';
                                            document.getElementById("out_name").disabled = false;
                                            document.getElementById("out_phone").disabled = false;
                                            document.getElementById("out_email").disabled = false;
                                            document.getElementById("out_scode").disabled = false;
                                            $('#out_name').get(0).type = 'text';
                                            $('#out_phone').get(0).type = 'number';
                                            $('#out_email').get(0).type = 'email';
                                            $('#out_scode').get(0).type = 'text';
                                            document.getElementById("passwordHelpBlock_out_name").style.display = 'block';
                                            document.getElementById("passwordHelpBlock_out_phone").style.display = 'block';
                                            document.getElementById("passwordHelpBlock_out_email").style.display = 'block';
                                            document.getElementById("passwordHelpBlock_out_scode").style.display = 'block';
                                        } else {
                                            document.getElementById("awarded_to_id").disabled = false;
                                            $('#awarded_to_id').get(0).type = 'text';
                                            document.getElementById("passwordHelpBlock_awarded_to_id").style.display = 'block';
                                            document.getElementById("out_name").disabled = true;
                                            document.getElementById("out_phone").disabled = true;
                                            document.getElementById("out_email").disabled = true;
                                            document.getElementById("out_scode").disabled = true;
                                            $('#out_name').get(0).type = 'hidden';
                                            $('#out_phone').get(0).type = 'hidden';
                                            $('#out_email').get(0).type = 'hidden';
                                            $('#out_scode').get(0).type = 'hidden';
                                            document.getElementById("passwordHelpBlock_out_name").style.display = 'none';
                                            document.getElementById("passwordHelpBlock_out_phone").style.display = 'none';
                                            document.getElementById("passwordHelpBlock_out_email").style.display = 'none';
                                            document.getElementById("passwordHelpBlock_out_scode").style.display = 'none';
                                        }
                                    });
                                </script>

                                <div style="display: inline-block; width:100%; text-align:right;">Record count:&nbsp;<?php echo sizeof($resultArr) ?>
                                </div>


                            <?php } ?>

                            <form action="" method="GET">
                                <div class="form-group" style="display: inline-block;">

                                    <!-- Search Mode Selector (Admin Only) -->
                                    <?php if ($role == 'Admin') { ?>
                                        <div class="mb-3">
                                            <label>Search Mode:</label>
                                            <select name="search_mode" id="search_mode" class="form-select" style="width: auto; display: inline-block;">
                                                <option value="certificate_no" <?php echo isset($_GET['search_mode']) && $_GET['search_mode'] == 'certificate_no' ? 'selected' : ''; ?>>By Certificate No</option>
                                                <option value="nominee_id" <?php echo isset($_GET['search_mode']) && $_GET['search_mode'] == 'nominee_id' ? 'selected' : ''; ?>>By Nominee ID</option>
                                                <option value="combo" <?php echo isset($_GET['search_mode']) && $_GET['search_mode'] == 'combo' ? 'selected' : ''; ?>>Combination</option>
                                            </select>
                                        </div>
                                    <?php } ?>

                                    <!-- Certificate No. -->
                                    <div class="col2" style="display: inline-block;">
                                        <input name="get_certificate_no" id="get_certificate_no" class="form-control" style="width: auto;" placeholder="Certificate No." value="<?php echo $get_certificate_no; ?>" <?php echo $role == 'Admin' && isset($_GET['search_mode']) && $_GET['search_mode'] == 'nominee_id' ? 'disabled' : ''; ?>>
                                    </div>

                                    <!-- Nominee ID (Admin Only) -->
                                    <?php if ($role == 'Admin') { ?>
                                        <div class="col2" style="display: inline-block;">
                                            <input name="get_nomineeid" id="get_nomineeid" class="form-control" style="width: auto;" placeholder="Nominee ID" value="<?php echo $get_nomineeid; ?>" <?php echo (!isset($_GET['search_mode']) || $_GET['search_mode'] == 'certificate_no') ? 'disabled' : ''; ?>>
                                        </div>
                                    <?php } ?>

                                    <!-- Academic Year -->
                                    <div class="col2" style="display: inline-block;">
                                        <select name="academic_year" id="academic_year" class="form-select" style="width: auto;">
                                            <option value="" disabled>Select Year</option>
                                            <?php
                                            $currentYear = date('Y');
                                            $currentMonth = date('m');
                                            $displayCurrentYear = ($currentMonth < 4) ? $currentYear - 1 : $currentYear;

                                            for ($year = 2020; $year <= $currentYear + 1; $year++) {
                                                $nextYear = $year + 1;
                                                $selected = (isset($_GET['academic_year']) && $_GET['academic_year'] == $year) ? 'selected' : '';
                                                if (!isset($_GET['academic_year']) && $year == $displayCurrentYear) {
                                                    $selected = 'selected';
                                                }
                                                echo "<option value='$year' $selected>$year-$nextYear</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>

                                    <!-- Search Button -->
                                    <div class="col2 left" style="display: inline-block;">
                                        <button type="submit" name="search" class="btn btn-success btn-sm">
                                            <i class="bi bi-search"></i>&nbsp;Search
                                        </button>
                                    </div>
                                </div>

                                <!-- View All (Non-Admin) -->
                                <?php if ($role != 'Admin') { ?>
                                    <div class="form-check mt-2">
                                        <input type="checkbox" name="view_all" id="view_all" class="form-check-input" value="1" <?php echo isset($_GET['view_all']) ? 'checked' : ''; ?>>
                                        <label for="view_all">View All Certificates</label>
                                    </div>
                                <?php } ?>

                                <!-- Dynamic Form Control -->
                                <script>
                                    const searchMode = document.getElementById('search_mode');
                                    const certNo = document.getElementById("get_certificate_no");
                                    const nomineeId = document.getElementById("get_nomineeid");
                                    const academicYear = document.getElementById("academic_year");
                                    const viewAll = document.getElementById("view_all");

                                    // Admin-specific function to toggle fields
                                    function toggleAdminFields() {
                                        const mode = searchMode ? searchMode.value : 'certificate_no';
                                        certNo.disabled = (mode === 'nominee_id');
                                        nomineeId.disabled = (mode === 'certificate_no');
                                        academicYear.disabled = (mode === 'nominee_id' || mode === 'certificate_no');

                                        // Set required attribute based on disabled state and mode
                                        if (mode === 'combo') {
                                            certNo.required = false;
                                            nomineeId.required = false;
                                            academicYear.required = false;
                                        } else {
                                            certNo.required = !certNo.disabled;
                                            nomineeId.required = !nomineeId.disabled;
                                            academicYear.required = !academicYear.disabled;
                                        }
                                    }

                                    // Non-admin-specific function to toggle fields
                                    function toggleNonAdminFields() {
                                        if (viewAll && viewAll.checked) {
                                            certNo.disabled = true;
                                            academicYear.disabled = true;
                                        } else if (certNo.value.trim() !== "") {
                                            certNo.disabled = false;
                                            academicYear.disabled = true;
                                        } else {
                                            certNo.disabled = false;
                                            academicYear.disabled = false;
                                        }
                                    }

                                    // Initialize fields based on the role
                                    function initializeFields() {
                                        if (searchMode) {
                                            // Admin user
                                            toggleAdminFields();
                                            searchMode.addEventListener('change', toggleAdminFields);
                                        } else {
                                            // Non-admin user
                                            toggleNonAdminFields();
                                            if (viewAll) viewAll.addEventListener('change', toggleNonAdminFields);
                                            certNo.addEventListener('input', toggleNonAdminFields);
                                        }
                                    }

                                    // Execute initialization on page load
                                    initializeFields();
                                </script>

                            </form>


                            <br>

                            <?php echo '
                   <div class="table-responsive">
                    <table class="table" id="table-id">
                        <thead>
                            <tr>
                            <th scope="col">Certificate no</th>' ?>
                            <?php if ($role == 'Admin') { ?>
                                <?php echo '<th scope="col">Nominee Details</th>' ?>
                            <?php } ?>
                            <?php echo ' <th scope="col">Badge name</th>
                            <th scope="col" width="20%">Remarks</th>
                            <th scope="col">Gems</th>
                            <th scope="col">Issued on</th>
                            <th scope="col">Nominated by</th>
                            <th scope="col">Certificate</th>' ?>
                            <?php if ($role == 'Admin') { ?>
                                <?php echo '<th scope="col"></th>' ?>
                            <?php } ?>
                            <?php echo '</tr>
                            </thead>' ?>
                            <?php if ($resultArr != null) {
                                echo '<tbody>';
                                foreach ($resultArr as $array) {
                                    echo '
                            <tr>
                                <td>' . $array['certificate_no'] . '</td>' ?>
                                    <?php if ($role == 'Admin') { ?>
                                        <?php echo '<td>' . $array['awarded_to_id'] . '<br>' . @$array['fullname'] . @$array['awarded_to_student_name'] . @$array['awarded_to_name'] . '</td>' ?>
                                    <?php } ?>
                                    <?php echo '<td>' . $array['badge_name'] . '</td><td>' ?>

                                    <?php
                                    $comment = $array['comment'];
                                    $certificateNo = $array['certificate_no'];
                                    ?>

                                    <div id="comment-container-<?php echo $certificateNo; ?>">
                                        <p>
                                            <?php if (isset($comment) && strlen($comment) > 90) { ?>
                                                <span id="full-comment-<?php echo $certificateNo; ?>" class="d-inline">
                                                    <?php echo substr($comment, 0, 90); ?>
                                                    <span id="more-text-<?php echo $certificateNo; ?>" class="d-none">
                                                        <?php echo substr($comment, 90); ?>
                                                    </span>
                                                </span>
                                                <a href="javascript:void(0);" onclick="toggleComment('<?php echo $certificateNo; ?>')" id="toggle-link-<?php echo $certificateNo; ?>">
                                                    <span id="toggle-text-<?php echo $certificateNo; ?>">Show more</span>
                                                    <span id="toggle-text-more-<?php echo $certificateNo; ?>" class="d-none">Show less</span>
                                                </a>
                                            <?php } else { ?>
                                                <span><?php echo $comment; ?></span>
                                            <?php } ?>
                                        </p>
                                    </div>
                                    <?php echo '</td><td>' . $array['gems'] . '</td>' ?>
                                    <?php if ($array['issuedon'] == null) { ?>
                                        <?php echo '<td></td>' ?>
                                    <?php } else { ?>
                                        <?php echo '<td>' . @date("d/m/Y g:i a", strtotime($array['issuedon'])) . '</td>' ?>
                                    <?php } ?>

                                    <?php echo '<td>' . $array['nominated_by_name'] . '</td>' ?>

                                    <?php if ($array['certificate_url'] == null && $array['pdf_certificate'] == true) { ?>
                                        <?php echo '<td><a href="pdf_certificate_of_appreciation.php?certificate_no=' . $array['certificate_no'] . '" target="_blank"><i class="bi bi-file-earmark-pdf" style="font-size: 16px ;color:#777777" title="' . $array['certificate_no'] . '" display:inline;></i></a></td>' ?>
                                    <?php } elseif ($array['certificate_url'] == null) { ?>
                                        <?php echo '<td></td>' ?>
                                    <?php } else { ?>
                                        <?php echo '<td><a href="' . $array['certificate_url'] . '" target="_blank"><i class="bi bi-file-earmark-pdf" style="font-size: 16px ;color:#777777" title="' . $array['certificate_no'] . '" display:inline;></i></a></td>' ?>
                                    <?php } ?>

                                    <?php if ($role == 'Admin') { ?>

                                        <?php echo '

                                <td>' ?>
                                        <?php if (@$array['awarded_to_phone'] != null || @$array['out_phone'] != null || @$array['awarded_to_student_phone'] != null) {

                                            if (@$array['badge_name'] == 'Offer Letter' || @$array['badge_name'] == 'Joining Letter') {

                                                echo '<a href="https://api.whatsapp.com/send?phone=91' . @$array['awarded_to_phone'] . @$array['awarded_to_student_phone'] . @$array['out_phone'] . '&text=Dear ' . @$array['fullname'] . @$array['studentname'] . @$array['awarded_to_name'] . ' (' . $array['awarded_to_id'] . '),%0A%0AYour ' . $array['badge_name'] . ' has been issued. Please check your email and take the necessary action.%0A%0A--RSSI%0A%0A**This is an automatically generated SMS" target="_blank"><i class="bi bi-whatsapp" style="color:#444444;" title="Send SMS ' . @$array['awarded_to_phone'] . @$array['awarded_to_student_phone'] . @$array['out_phone'] . '"></i></a>' ?>

                                            <?php } else {

                                                echo '<a href="https://api.whatsapp.com/send?phone=91' . @$array['awarded_to_phone'] . @$array['awarded_to_student_phone'] . @$array['out_phone'] . '&text=Dear ' . @$array['fullname'] . @$array['studentname'] . @$array['awarded_to_name'] . ' (' . $array['awarded_to_id'] . '),%0A%0AYou have received ' . $array['badge_name'] . '. To view your e-Certificate and Gems (if applicable), please log on to your Profile > My Documents > My Certificate or you can click on the link below to access it directly.%0A%0A' . $array['certificate_url'] . '%0A%0A--RSSI%0A%0A**This is an automatically generated SMS" target="_blank"><i class="bi bi-whatsapp" style="color:#444444;" title="Send SMS ' . @$array['awarded_to_phone'] . @$array['awarded_to_student_phone'] . @$array['out_phone'] . '"></i></a>' ?>

                                            <?php }
                                        } else { ?>
                                            <?php echo '<i class="bi bi-whatsapp" style="color:#A2A2A2;" title="Send SMS"></i>' ?>
                                        <?php } ?>

                                        <?php echo '&nbsp;&nbsp;&nbsp;<form name="cmsdelete_' . $array['certificate_no'] . '" action="#" method="POST" style="display: -webkit-inline-box;">
                                <input type="hidden" name="form-type" type="text" value="cmsdelete">
                                <input type="hidden" name="cmsid" id="cmsid" type="text" value="' . $array['certificate_no'] . '">
                                
                                <button type="submit" onclick=validateForm() style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Delete ' . $array['certificate_no'] . '"><i class="bi bi-x-lg"></i></button> </form>
                                </td>' ?>
                                    <?php } ?>
                                <?php }
                                echo '</tr>' ?>
                            <?php
                            } else if (@$get_certificate_no == "" && @$get_nomineeid == "" && $academic_year == "") { ?>
                                <tr>
                                    <td colspan="5">Please select Filter value.</td>
                                </tr>
                                ?>
                                <tr>
                                    <td colspan="5">Please select Filter value.</td>
                                </tr>
                            <?php
                            } else if (sizeof($resultArr) == 0 || (@$get_certificate_no != "" || @$get_nomineeid != "")) { ?>
                                <tr>
                                    <td colspan="5">No matching records found. Please try different filter values.</td>
                                </tr>
                            <?php
                            }
                            echo '</tbody>
                    </table>
                    </div>'
                            ?>

                            <script>
                                var data = <?php echo json_encode($resultArr) ?>;
                                const scriptURL = 'payment-api.php'

                                function validateForm() {
                                    if (confirm('Are you sure you want to delete this record? Once you click OK the record cannot be reverted.')) {

                                        data.forEach(item => {
                                            const form = document.forms['cmsdelete_' + item.certificate_no]
                                            form.addEventListener('submit', e => {
                                                e.preventDefault()
                                                fetch(scriptURL, {
                                                        method: 'POST',
                                                        body: new FormData(document.forms['cmsdelete_' + item.certificate_no])
                                                    })
                                                    .then(response =>
                                                        alert("Record has been deleted.") +
                                                        location.reload()
                                                    )
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
        function toggleComment(certificateNo) {
            const moreText = document.getElementById('more-text-' + certificateNo);
            const toggleText = document.getElementById('toggle-text-' + certificateNo);
            const toggleTextMore = document.getElementById('toggle-text-more-' + certificateNo);

            if (moreText.classList.contains('d-none')) {
                moreText.classList.remove('d-none');
                toggleText.classList.add('d-none');
                toggleTextMore.classList.remove('d-none');
            } else {
                moreText.classList.add('d-none');
                toggleText.classList.remove('d-none');
                toggleTextMore.classList.add('d-none');
            }
        }
    </script>
    <script>
        $(document).ready(function() {

            // Character count update
            $('#comment').on('input', function() {
                const charCount = $('#charCount');
                charCount.text(`${this.value.length}/500 characters used`);
            });
        });
    </script>
</body>

</html>