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
?>
<?php
$date = date('Y-m-d H:i:s');

// Retrieve POST values safely
$id = isset($_POST['get_id']) ? $_POST['get_id'] : null;
$aaid = isset($_POST['get_aaid']) ? strtoupper($_POST['get_aaid']) : null;
$lyear = isset($_POST['adj_academicyear']) ? $_POST['adj_academicyear'] : null;
$is_user = isset($_POST['is_user']) ? $_POST['is_user'] : null;

// Define reusable SQL components
$commonJoins = "
    LEFT JOIN (SELECT status, userid FROM asset) asset ON asset.userid = rssimyaccount_members.associatenumber
    LEFT JOIN (SELECT DISTINCT username, MAX(logintime) AS logintime FROM userlog_member GROUP BY username) userlog_member ON rssimyaccount_members.associatenumber = userlog_member.username
    LEFT JOIN (SELECT taggedto FROM gps where asset_status='Active') gps ON rssimyaccount_members.associatenumber = gps.taggedto
    LEFT JOIN (SELECT applicantid, COALESCE(SUM(days), 0) AS sltd FROM leavedb_leavedb WHERE typeofleave = 'Sick Leave' AND lyear = '$lyear' AND status = 'Approved' GROUP BY applicantid) sltaken ON rssimyaccount_members.associatenumber = sltaken.applicantid
    LEFT JOIN (SELECT applicantid, COALESCE(SUM(days), 0) AS cltd FROM leavedb_leavedb WHERE typeofleave = 'Casual Leave' AND lyear = '$lyear' AND status = 'Approved' GROUP BY applicantid) cltaken ON rssimyaccount_members.associatenumber = cltaken.applicantid
    LEFT JOIN (SELECT applicantid, COALESCE(SUM(days), 0) AS lwptd FROM leavedb_leavedb WHERE (typeofleave = 'Leave Without Pay'OR typeofleave = 'Adjustment Leave') AND lyear = '$lyear' AND status = 'Approved' GROUP BY applicantid) lwptaken ON rssimyaccount_members.associatenumber = lwptaken.applicantid
    LEFT JOIN (SELECT applicantid, 1 AS onleave FROM leavedb_leavedb WHERE CURRENT_DATE BETWEEN fromdate AND todate AND lyear = '$lyear' AND status = 'Approved') onleave ON rssimyaccount_members.associatenumber = onleave.applicantid
    LEFT JOIN (SELECT allo_applicantid, COALESCE(SUM(allo_daycount), 0) AS slad FROM leaveallocation WHERE allo_leavetype = 'Sick Leave' AND allo_academicyear = '$lyear' GROUP BY allo_applicantid) slallo ON rssimyaccount_members.associatenumber = slallo.allo_applicantid
    LEFT JOIN (SELECT allo_applicantid, COALESCE(SUM(allo_daycount), 0) AS clad FROM leaveallocation WHERE allo_leavetype = 'Casual Leave' AND allo_academicyear = '$lyear' GROUP BY allo_applicantid) clallo ON rssimyaccount_members.associatenumber = clallo.allo_applicantid
    LEFT JOIN (SELECT adj_applicantid, COALESCE(SUM(adj_day), 0) AS sladd FROM leaveadjustment WHERE adj_leavetype = 'Sick Leave' AND adj_academicyear = '$lyear' GROUP BY adj_applicantid) sladj ON rssimyaccount_members.associatenumber = sladj.adj_applicantid
    LEFT JOIN (SELECT adj_applicantid, COALESCE(SUM(adj_day), 0) AS cladd FROM leaveadjustment WHERE adj_leavetype = 'Casual Leave' AND adj_academicyear = '$lyear' GROUP BY adj_applicantid) cladj ON rssimyaccount_members.associatenumber = cladj.adj_applicantid
    LEFT JOIN (SELECT adj_applicantid, COALESCE(SUM(adj_day), 0) AS lwpadd FROM leaveadjustment WHERE (adj_leavetype = 'Leave Without Pay' OR adj_leavetype = 'Adjustment Leave') AND adj_academicyear = '$lyear' GROUP BY adj_applicantid) lwpadj ON rssimyaccount_members.associatenumber = lwpadj.adj_applicantid
    LEFT JOIN (SELECT onboarding_associate_id, onboard_initiated_by, onboard_initiated_on FROM onboarding) onboarding ON rssimyaccount_members.associatenumber = onboarding.onboarding_associate_id
    LEFT JOIN (SELECT exit_associate_id, exit_initiated_by, exit_initiated_on FROM associate_exit) associate_exit ON rssimyaccount_members.associatenumber = associate_exit.exit_associate_id
";

// Build query based on input
if (!empty($id)) {
    $query = "SELECT DISTINCT * FROM rssimyaccount_members $commonJoins WHERE filterstatus = '$id' ORDER BY fullname";
} elseif (!empty($aaid)) {
    $query = "SELECT DISTINCT * FROM rssimyaccount_members $commonJoins WHERE associatenumber = '$aaid' ORDER BY fullname";
} else {
    $query = "SELECT * FROM rssimyaccount_members WHERE associatenumber IS NULL";
}

// Execute query
$result = pg_query($con, $query);

if (!$result) {
    echo "An error occurred.\n";
    exit;
}

// Fetch results
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
    <?php include 'includes/meta.php' ?>

    

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
        @media (max-width:767px) {
            td {
                width: 100%
            }
        }

        @media (min-width:767px) {
            .left {
                margin-left: 2%;
            }
        }

        @media (max-width:767px) {

            #cw,
            #cw1,
            #cw2,
            #cw3 {
                width: 100% !important;
            }

        }

        #cw {
            width: 7%;
        }

        #cw1 {
            width: 17%;
        }

        #cw2 {
            width: 15%;
        }

        #cw3 {
            width: 25%;
        }

        .modal {
            background-color: rgba(0, 0, 0, 0.4);
            /* Black w/ opacity */
        }
    </style>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
    <!-- CSS Library Files -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
    <!-- JavaScript Library Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>
    <!-- Include Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize Select2 for associate numbers
            $('#get_aaid').select2({
                ajax: {
                    url: 'fetch_associates.php',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data.results
                        };
                    },
                    cache: true
                },
                minimumInputLength: 2,
                placeholder: 'Select associate(s)',
                allowClear: true,
                // multiple: true
            });
        });
    </script>

</head>

<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'inactive_session_expire_check.php'; ?>

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
                            <div class="row">
                                <div class="col" style="display: inline-block;">
                                    Record count:&nbsp;<?php echo sizeof($resultArr) ?>
                                </div>
                                <div class="col" style="display: inline-block; width:47%; text-align:right">
                                    <a href="facultyexp.php" target="_self" class="btn btn-danger btn-sm" role="button">Faculty Details</a>
                                </div>
                            </div>

                            <form action="" method="POST" class="mb-4">
                                <!-- First Row - Main Filters -->
                                <div class="row g-3 align-items-end">
                                    <!-- Status Dropdown -->
                                    <div class="col-md-3 col-lg-2">
                                        <div class="form-group">
                                            <label for="get_id" class="form-label">Status</label>
                                            <select name="get_id" id="get_id" class="form-select" disabled>
                                                <?php if ($id == null) { ?>
                                                    <option disabled selected hidden>Select Status</option>
                                                <?php } else { ?>
                                                    <option hidden selected><?php echo $id ?></option>
                                                <?php } ?>
                                                <option>Active</option>
                                                <option>Inactive</option>
                                                <option>In Progress</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- AAID Dropdown -->
                                    <div class="col-md-3 col-lg-2">
                                        <div class="form-group">
                                            <label for="get_aaid" class="form-label">AAID</label>
                                            <select class="form-select" id="get_aaid" name="get_aaid" required>
                                                <?php if (!empty($aaid)): ?>
                                                    <option value="<?= $aaid ?>" selected><?= $aaid ?></option>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Academic Year Dropdown -->
                                    <div class="col-md-3 col-lg-2">
                                        <div class="form-group">
                                            <label for="adj_academicyear" class="form-label">Academic Year</label>
                                            <select name="adj_academicyear" id="adj_academicyear" class="form-select" required>
                                                <?php if ($lyear != null) { ?>
                                                    <option hidden selected><?php echo $lyear ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Search Button -->
                                    <div class="col-md-2 col-lg-1">
                                        <button type="submit" name="search_by_id" class="btn btn-success w-100">
                                            <i class="bi bi-search"></i> Search
                                        </button>
                                    </div>
                                </div>

                                <!-- Second Row - Checkbox Filter -->
                                <div class="row mt-2">
                                    <div class="col-12">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" name="is_user" id="is_user" value="1"
                                                <?php if (isset($_POST['is_user'])) echo "checked='checked'"; ?> />
                                            <label for="is_user" class="form-check-label">Search by Associate ID</label>
                                        </div>
                                    </div>
                                </div>
                            </form>
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
                                    $('#adj_academicyear').append(new Option(year, year));
                                    currentYear--;
                                }
                            </script>
                            <script>
                                if ($('#is_user').not(':checked').length > 0) {

                                    document.getElementById("get_id").disabled = false;
                                    document.getElementById("get_aaid").disabled = true;

                                } else {

                                    document.getElementById("get_id").disabled = true;
                                    document.getElementById("get_aaid").disabled = false;

                                }

                                const checkbox = document.getElementById('is_user');

                                checkbox.addEventListener('change', (event) => {
                                    if (event.target.checked) {
                                        document.getElementById("get_id").disabled = true;
                                        document.getElementById("get_aaid").disabled = false;
                                    } else {
                                        document.getElementById("get_id").disabled = false;
                                        document.getElementById("get_aaid").disabled = true;
                                    }
                                })
                            </script>
                            <div class="table-responsive">
                                <table class="table" id="table-id">
                                    <thead>
                                        <tr>
                                            <th id="cw">Photo</th>
                                            <th id="cw1">Volunteer Name & Details</th>
                                            <th>Contact Information</th>
                                            <th>Designation/Role</th>
                                            <th>Supervisor/Manager</th>
                                            <th id="cw2">Association Status</th>
                                            <th>Leave Balance (Days)</th>
                                            <th>Actions</th>
                                            <th>Select Message</th>
                                            <th>Send WhatsApp Message</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <?php if (sizeof($resultArr) > 0) { ?>
                                            <?php foreach ($resultArr as $array) { ?>
                                                <?php
                                                $messages = [
                                                    'Internship Completion' => "This is a system-generated message regarding the completion of your internship with RSSI NGO, scheduled to end on " . (!empty($array['effectivedate']) ? date('d/m/Y', strtotime($array['effectivedate'])) : 'N/A') . ". To ensure a smooth transition, please complete the following activities:\n\n"
                                                        . "Goal Sheet Submission and IPF Review:\n"
                                                        . "- Submit your goal sheet.\n"
                                                        . "- Review the issued Individual Performance Factor (IPF).\n"
                                                        . "- Accept the IPF in the system.\n\n"
                                                        . "Internship Report Signing and Submission:\n"
                                                        . "- Obtain the necessary signatures and seals on your internship report from the centre in charge.\n"
                                                        . "- Send a scanned copy to info@rssi.in.\n\n"
                                                        . "Feedback and Rating:\n"
                                                        . "- Share your brief internship experience and rate RSSI NGO through the following link:\n"
                                                        . "  - RSSI NGO: https://g.page/r/CQkWqmErGMS7EAg/review\n"
                                                        . "  - Kalpana Buds School: https://g.page/r/Car-7dCsy9HuEAI/review\n\n"
                                                        . "Separation Process and ID Card Submission:\n"
                                                        . "- Coordinate with the centre in charge on your last working day to complete the separation process in the system.\n"
                                                        . "- Submit your ID card.\n\n"
                                                        . "Upon successful completion of these activities, your work experience letter and relevant certificates will be issued through the system, serving as official proof of your internship with RSSI NGO.",
                                                    'NGO Darpan' => "You are informed that your details have been added to NGO Darpan, NITI Aayog, Government of India.",
                                                    'Google Chat' => "Please join the Google space for any internal communication purposes.\n\n"
                                                        . "https://mail.google.com/chat/u/0/#chat/space/AAAAO7ViKO4\n\n"
                                                        . "This is a one-way internal communication channel, for any personal query/query related to the information shared in this group, you are requested to initiate a one-on-one chat with RSSI NGO.\n\n"
                                                        . "How to join: https://youtube.com/shorts/ftA0TokZ28g",
                                                    'Offer letter reminder' => "As per system records, you have not yet replied to the email with the subject “RSSI Offer Letter - " . $array['associatenumber'] . "_" . $array['fullname'] . "”.\n\n"
                                                        . "If you do not see the email, please check your “junk mail” folder or “spam” folder.\n\n"
                                                        . "In both the cases, i.e., acceptance or rejection of the offer letter, please let us know by replying at the top of the email."
                                                ];
                                                ?>
                                                <?php
                                                // Example input dates
                                                $doj = $array["doj"]; // Date of Joining
                                                $effectiveFrom = $array["effectivedate"]; // Effective End Date, could be null
                                                // Check if DOJ is available and valid
                                                if (empty($doj) || !strtotime($doj)) {
                                                    $experience = "DOJ not available or invalid";
                                                } else {
                                                    // Parse dates
                                                    $dojDate = new DateTime($doj);
                                                    $currentDate = new DateTime(); // Current date
                                                    $endDate = $effectiveFrom ? new DateTime($effectiveFrom) : $currentDate; // Use effective date if set, otherwise use today

                                                    // Check if DOJ is in the future
                                                    if ($dojDate > $currentDate) {
                                                        // If the DOJ is in the future, display a message
                                                        $experience = "Not yet commenced";
                                                    } else {
                                                        // Calculate the difference
                                                        $interval = $dojDate->diff($endDate);

                                                        // Extract years, months, and days
                                                        $years = $interval->y;
                                                        $months = $interval->m;
                                                        $days = $interval->d;

                                                        // Determine the format to display
                                                        if ($years > 0) {
                                                            $experience = number_format($years + ($months / 12), 2) . " year(s)";
                                                        } elseif ($months > 0) {
                                                            $experience = number_format($months + ($days / 30), 2) . " month(s)";
                                                        } else {
                                                            $experience = number_format($days, 2) . " day(s)";
                                                        }
                                                    }
                                                }
                                                ?>
                                                <tr>
                                                    <td>

                                                        <?php if ($array['photo'] != null) { ?>
                                                            <div class="icon-container">
                                                                <img src="<?php echo $array['photo']; ?>" class="rounded-circle me-2" alt="User Photo" width="50" height="50" />
                                                            </div>
                                                        <?php } else {
                                                            // Extract initials from the full name
                                                            $nameParts = explode(" ", $array['fullname']);
                                                            $initials = '';
                                                            foreach ($nameParts as $part) {
                                                                if (!empty($part)) $initials .= $part[0];
                                                            }
                                                            $initials = strtoupper($initials);
                                                        ?>
                                                            <div class="icon-container" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; background-color: #ccc; border-radius: 50%;">
                                                                <span style="font-size: 20px; color: white;"><?php echo $initials; ?></span>
                                                            </div>
                                                        <?php } ?>

                                                        <div class="status-container">
                                                            <?php if ($array['logintime'] != null) {
                                                                if (date('Y-m-d H:i:s', strtotime($array['logintime'] . ' + 24 minute')) > $date) { ?>
                                                                    <div class="status-circle" title="Online"></div>
                                                                <?php } else { ?>
                                                                    <div class="status-circle" style="background-color: #E5E5E5;" title="Offline"></div>
                                                                <?php }
                                                            } else { ?>
                                                                <div class="status-circle" style="background-color: #E5E5E5;" title="Offline"></div>
                                                            <?php } ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <?php echo 'Name - ' . $array['fullname'] . '<br>Associate ID - ' . $array['associatenumber'] . '
                                                        <br>' . $array['gender'] . '&nbsp;(' . @(new DateTime($array['dateofbirth']))->diff(new DateTime())->y . ')<br><br>DOJ - ' . @date('d/m/y', strtotime($array['doj'])) . '<br>' . $experience . '</td>
                                                        <td>' . $array['phone'] . '<br>' . $array['email'] . '</td>
                                                        <td>' . $array['position'] . '</td>
                                                        <td>' . $array['supervisor'] ?>
                                                    </td>
                                                    <td style="white-space:unset">

                                                        <?php echo $array['filterstatus'] . '<br>';

                                                        if ($array['onleave'] != null) {
                                                            echo '<br><p class="badge bg-danger">on leave</p>';
                                                        }

                                                        // if ($array['today'] != 0 && $array['today'] != null && $array['filterstatus'] != 'Inactive') {
                                                        //     echo '<br><p class="badge bg-warning">Attd. pending</p>';
                                                        // }

                                                        if ($array['userid'] != null && $array['status'] != 'Closed') {
                                                            echo '<br><a href="asset-management.php?get_statuse=Associate&get_appid=' . $array['associatenumber'] . '" target="_blank" style="text-decoration:none" title="click here"><p class="badge bg-warning">agreement</p></a>';
                                                        }

                                                        if ($array['taggedto'] != null) {
                                                            echo '<br><a href="gps.php?taggedto=' . $array['associatenumber'] . '" target="_blank" style="text-decoration:none" title="click here"><p class="badge bg-danger">asset</p></a>';
                                                        }

                                                        echo '<br><br>' . (!empty($array['effectivedate']) ? $array['effectivedate'] . '&nbsp;' : '') . $array['remarks']; ?>
                                                    </td>

                                                    <td>
                                                        <?php echo 'LWP/Adj&nbsp;(' . ($array['lwptd'] - $array['lwpadd']) . ')&nbsp;s&nbsp;(' . ($array['slad'] + $array['sladd']) - $array['sltd'] . '),&nbsp;c&nbsp;(' . ($array['clad'] + $array['cladd']) - $array['cltd'] . ')' ?>
                                                    </td>
                                                    <td style="white-space: unset;">


                                                        <?php echo '<button type="button" href="javascript:void(0)" onclick="showDetails(\'' . $array['associatenumber'] . '\')" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Details">
                                                            <i class="bi bi-box-arrow-up-right"></i></button>
                                                        &nbsp;&nbsp;
                                                        <form name="initiatingonboarding' . $array['associatenumber'] . '" action="#" method="POST" style="display:inline;">
                                                            <input type="hidden" name="form-type" type="text" value="initiatingonboarding">
                                                            <input type="hidden" name="initiatedfor" type="text" value="' . $array['associatenumber'] . '" readonly>
                                                            <input type="hidden" name="initiatedby" type="text" value="' . $associatenumber . '" readonly>';
                                                        ?>
                                                        <!-- Initiate onboarding system -->
                                                        <?php if ($role == 'Admin' && $array['onboard_initiated_by'] == null) { ?>
                                                            <?php echo '<button type="submit" id="yes" onclick="validateForm()" style=" outline: none;background: none; padding: 0px; border: none;" title="Initiating Onboarding"><i class="bi bi-person-plus"></i></button>'; ?>
                                                        <?php } else {
                                                            echo date('d/m/y h:i:s a', strtotime($array['onboard_initiated_on'])) . ' by ' . $array['onboard_initiated_by'];
                                                        }
                                                        echo '</form>&nbsp;&nbsp;

                                                            <form name="initiatingexit' . $array['associatenumber'] . '" action="#" method="POST" style="display:inline;">
                                                            <input type="hidden" name="form-type" type="text" value="initiatingexit">
                                                            <input type="hidden" name="initiatedfor" type="text" value="' . $array['associatenumber'] . '" readonly>
                                                            <input type="hidden" name="initiatedby" type="text" value="' . $associatenumber . '" readonly>';
                                                        ?>
                                                        <!-- Initiate Exit system -->
                                                        <?php if ($role == 'Admin' && $array['exit_initiated_by'] == null) { ?>
                                                            <?php echo '<button type="submit" id="yes" onclick="exit_validateForm()" style=" outline: none;background: none; padding: 0px; border: none;" title="Initiating Exit"><i class="bi bi-box-arrow-in-right"></i></button>'; ?>
                                                        <?php } else {
                                                            echo date('d/m/y h:i:s a', strtotime($array['exit_initiated_on'])) . ' by ' . $array['exit_initiated_by'];
                                                        }
                                                        echo '</form>' ?>
                                                    </td>
                                                    <td>
                                                        <!-- Message Title Dropdown -->
                                                        <select id="message-title-<?php echo $array['associatenumber']; ?>" class="form-select">
                                                            <option value="">Select Message</option>
                                                            <?php foreach ($messages as $title => $message) { ?>
                                                                <option value="<?php echo $message; ?>"><?php echo $title; ?></option>
                                                            <?php } ?>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <!-- Send WhatsApp Button -->
                                                        <button type="button" onclick="sendMessage('<?php echo $array['associatenumber']; ?>', '<?php echo $array['phone']; ?>','<?php echo $array['fullname']; ?>')" class="btn btn-primary">Send</button>
                                                    </td>
                                                </tr>
                                            <?php }
                                        } else { ?>
                                            <tr>
                                                <td colspan="9">No Data Found</td>
                                            </tr>

                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="modal" id="myModal" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h1 class="modal-title fs-5" id="exampleModalLabel">Faculty Details</h1>
                                            <button type="button" id="closedetails-header" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="d-flex justify-content-end">
                                                <span id="status" class="fullname badge"></span>
                                            </div>
                                            <div class="row">
                                                <a id="wbt_details" href="#" target="_blank">
                                                    iExplore Defaulters
                                                </a>
                                            </div>
                                            <hr>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <a id="offer_letter" href="" target="_blank">Offer Letter</a><br>
                                                    <a id="joining_letter" href="" target="_blank">Joining Letter</a><br>
                                                    <a id="comp_letter" href="" target="_blank">Compensation Letter</a>
                                                </div>
                                                <div class="col-md-6">
                                                    <a id="certificate_issue" href="" target="_blank">Issue Document</a><br>
                                                    <a id="certificate_view" href="" target="_blank">View Document</a><br>
                                                </div>
                                            </div>
                                            <hr>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <a id="experience_letter" href="" target="_blank">Generate Experience Letter</a><br>
                                                    <a id="pdf_completion_certificate" href="" target="_blank">Download Completion Certificate</a>
                                                </div>
                                                <div class="col-md-6">
                                                    <a id="profile" href="" target="_blank">Profile</a>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" id="closedetails-footer" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <script>
                                var data = <?php echo json_encode($resultArr); ?>;

                                // Get the modal
                                var modal = document.getElementById("myModal");
                                // Get the <span> element that closes the modal
                                var closedetails = [
                                    document.getElementById("closedetails-header"),
                                    document.getElementById("closedetails-footer")
                                ];

                                function showDetails(id) {
                                    var mydata = data.find(item => item.associatenumber === id);

                                    if (mydata) {
                                        console.log(mydata); // Log the mydata object to the console for debugging
                                        var keys = Object.keys(mydata);
                                        keys.forEach(key => {
                                            var span = modal.querySelector("." + key);
                                            if (span)
                                                span.textContent = mydata[key];
                                        });

                                        var fullnameBadge = modal.querySelector(".fullname");
                                        if (fullnameBadge)
                                            fullnameBadge.textContent = mydata.fullname;

                                        modal.style.display = "block";

                                        var status = document.getElementById("status");
                                        if (mydata.filterstatus === "Active") {
                                            status.classList.add("bg-success");
                                            status.classList.remove("bg-danger");
                                        } else {
                                            status.classList.remove("bg-success");
                                            status.classList.add("bg-danger");
                                        }

                                        document.getElementById("wbt_details").href = "/rssi-member/iexplore_defaulters.php?associateId=" + mydata.associatenumber;
                                        document.getElementById("offer_letter").href = "/rssi-member/offerletter.php?get_id=" + mydata.associatenumber;
                                        document.getElementById("comp_letter").href = "/rssi-member/comp-letter.php?get_id=" + mydata.associatenumber;
                                        document.getElementById("certificate_issue").href = "/rssi-member/my_certificate.php?awarded_to_id=" + mydata.associatenumber + "&awarded_to_name=" + mydata.fullname;
                                        document.getElementById("certificate_view").href = "/rssi-member/my_certificate.php?get_nomineeid=" + mydata.associatenumber;
                                        document.getElementById("experience_letter").href = "/rssi-member/expletter.php?get_id=" + mydata.associatenumber;
                                        document.getElementById("pdf_completion_certificate").href = "/rssi-member/pdf_completion_certificate.php?scode=" + mydata.scode;
                                        document.getElementById("joining_letter").href = "/rssi-member/joiningletter.php?get_id=" + mydata.associatenumber;
                                        document.getElementById("profile").href = "/rssi-member/hrms.php?associatenumber=" + mydata.associatenumber;
                                    }
                                }

                                // Close modal using either cross or close button
                                closedetails.forEach(function(element) {
                                    element.addEventListener("click", closeModal);
                                });

                                function closeModal() {
                                    modal.style.display = "none";
                                }

                                // When the user clicks anywhere outside of the modal, close it
                                window.onclick = function(event) {
                                    if (event.target === modal) {
                                        modal.style.display = "none";
                                    }
                                };
                            </script>


                            <script>
                                var data = <?php echo json_encode($resultArr) ?>;
                                var aid = <?php echo '"' . $associatenumber . '"' ?>;

                                const scriptURL = 'payment-api.php'

                                function validateForm() {
                                    if (confirm('Are you sure you want to onboard this associate?')) {

                                        data.forEach(item => {
                                            const form = document.forms['initiatingonboarding' + item.associatenumber]
                                            form.addEventListener('submit', e => {
                                                e.preventDefault()
                                                fetch(scriptURL, {
                                                        method: 'POST',
                                                        body: new FormData(document.forms['initiatingonboarding' + item.associatenumber])
                                                    })
                                                    .then(response => response.text())
                                                    .then(result => {
                                                        if (result == 'success') {
                                                            alert("The associate's onboarding process has been initiated successfully.") + location.reload()
                                                        } else {
                                                            alert("An error occurred while processing your request. Please try again later.") + location.reload()
                                                        }
                                                    })
                                            })
                                        })
                                    } else {
                                        alert("The onboarding process has been cancelled.");
                                        return false;
                                    }
                                }

                                function exit_validateForm() {
                                    if (confirm('Are you sure you want to initiate the exit process for this associate?')) {

                                        data.forEach(item => {
                                            const form = document.forms['initiatingexit' + item.associatenumber]
                                            form.addEventListener('submit', e => {
                                                e.preventDefault()
                                                fetch(scriptURL, {
                                                        method: 'POST',
                                                        body: new FormData(document.forms['initiatingexit' + item.associatenumber])
                                                    })
                                                    .then(response => response.text())
                                                    .then(result => {
                                                        if (result == 'success') {
                                                            alert("The process has been successfully initiated.") + location.reload()
                                                        } else {
                                                            alert("An error occurred while processing your request. Please try again later.") + location.reload()
                                                        }
                                                    })
                                            })
                                        })
                                    } else {
                                        alert("The process has been canceled.");
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
                    paging: false,
                    "order": [] // Disable initial sorting
                    // other options...
                });
            <?php endif; ?>
        });
    </script>
    <script>
        // JavaScript to handle WhatsApp link generation based on dropdown selection
        function sendMessage(associatenumber, phone, fullname) {
            var messageTitle = document.getElementById('message-title-' + associatenumber).value;
            if (messageTitle) {
                // Construct the message with PHP data passed into JavaScript
                var message = "Dear " + fullname + " (" + associatenumber + "),\n\n" + messageTitle + "\n\n--RSSI\n\n**This is a system-generated message.";

                // Encode the message for WhatsApp URL
                var url = "https://api.whatsapp.com/send?phone=91" + phone + "&text=" + encodeURIComponent(message);
                window.open(url, '_blank');
            } else {
                alert("Please select a message title.");
            }
        }
    </script>
</body>

</html>