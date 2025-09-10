<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");
include("../../util/email.php");
include("../../util/drive.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();

// Determine the current academic year
$academic_year = (date('m') <= 3)
    ? (date('Y') - 1) . '-' . date('Y')
    : date('Y') . '-' . (date('Y') + 1);

$now = date('Y-m-d H:i:s');
$currentAcademicYear = $academic_year;
$lyear = $_POST['adj_academicyear'] ?? $currentAcademicYear;

// Fetch leave history
$queryConditions = [
    "lyear = '$lyear'", // Use the lyear value directly from the post data
    "applicantid = '$associatenumber'", // Always filter by applicant ID
];

$whereClause = implode(" AND ", $queryConditions);

$historyQuery = "SELECT *, REPLACE(doc, 'view', 'preview') docp, r.fullname as reviewer_name 
                 FROM leavedb_leavedb l
                 LEFT JOIN rssimyaccount_members r ON l.reviewer_id = r.associatenumber
                 WHERE $whereClause 
                 ORDER BY timestamp DESC";

$result = pg_query($con, $historyQuery);

if (!$result) {
    echo "An error occurred.\n";
    exit;
}

$resultArr = pg_fetch_all($result);
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>My Leave</title>

    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
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
        .x-btn:focus,
        .button:focus,
        [type="submit"]:focus {
            outline: none;
        }

        #passwordHelpBlock {
            display: block;
        }

        .input-help {
            vertical-align: top;
            display: inline-block;
        }

        #hidden-panel,
        #hidden-panel_ack,
        #hidden-panel_creason {
            display: none;
        }

        .modal {
            background-color: rgba(0, 0, 0, 0.4);
            /* Black w/ opacity */
        }
    </style>
    <!-- CSS Library Files -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
    <!-- JavaScript Library Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>My Leave</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">My Services</a></li>
                    <li class="breadcrumb-item"><a href="leave.php">Apply for Leave</a></li>
                    <li class="breadcrumb-item active">My Leave</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section dashboard">
            <div class="row">

                <!-- Reports -->
                <div class="col-12">
                    <div class="card">

                        <div class="card-body">
                            <div class="container">

                                <br>
                                <form autocomplete="off" name="academicyear" id="academicyear" action="#" method="POST" class="mb-2">
                                    <div class="form-group">
                                        <label for="adj_academicyear" style="margin-bottom: 8px;">Academic year:</label>
                                        <select name="adj_academicyear" id="adj_academicyear" onchange="this.form.submit()" class="form-select" style="width: 20vh;">
                                            <?php if ($lyear != null) { ?>
                                                <option hidden selected><?php echo $lyear ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </form>

                                <div class="table-responsive">
                                    <table class="table" id="table-id">
                                        <thead>
                                            <tr>
                                                <th scope="col">Leave ID</th>
                                                <th scope="col">Applied on</th>
                                                <th scope="col">From-To</th>
                                                <th scope="col">Day(s) count</th>
                                                <th scope="col">Type of Leave</th>
                                                <th scope="col">Applied by</th>
                                                <th scope="col">Status</th>
                                                <th scope="col">HR remarks</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (sizeof($resultArr) > 0) : ?>
                                                <?php foreach ($resultArr as $array) : ?>
                                                    <tr>
                                                        <?php if ($array['doc'] != null) : ?>
                                                            <td>
                                                                <a href="javascript:void(0)" onclick="showpdf('<?php echo htmlspecialchars($array['leaveid'] ?? ''); ?>')">
                                                                    <?php echo htmlspecialchars($array['leaveid'] ?? ''); ?>
                                                                </a>
                                                            </td>
                                                        <?php else : ?>
                                                            <td><?php echo htmlspecialchars($array['leaveid'] ?? ''); ?></td>
                                                        <?php endif; ?>

                                                        <td><?php echo date("d/m/Y g:i a", strtotime($array['timestamp'] ?? '')); ?></td>
                                                        <td><?php echo date("d/m/Y", strtotime($array['fromdate'] ?? '')) . ' — ' . date("d/m/Y", strtotime($array['todate'] ?? '')); ?></td>
                                                        <td><?php echo htmlspecialchars($array['days'] ?? ''); ?></td>
                                                        <td>
                                                            <?php
                                                            // Prepare type of leave with optional shift value
                                                            $typeofLeave = htmlspecialchars($array['typeofleave'] ?? '');
                                                            $shift = htmlspecialchars($array['shift'] ?? '');
                                                            echo $typeofLeave . ($shift ? '-' . $shift : '');
                                                            ?><br>
                                                            <?php echo htmlspecialchars($array['creason'] ?? ''); ?><br>

                                                            <?php
                                                            // Ensure the comment is a string and handle null values
                                                            $applicantComment = $array['applicantcomment'] ?? '';
                                                            // Shorten the applicant comment
                                                            $shortComment = strlen($applicantComment) > 30 ? substr($applicantComment, 0, 30) . "..." : $applicantComment;
                                                            ?>

                                                            <span class="short-comment"><?php echo htmlspecialchars($shortComment); ?></span>
                                                            <span class="full-comment" style="display: none;"><?php echo htmlspecialchars($applicantComment); ?></span>

                                                            <?php if (strlen($applicantComment) > 30) : ?>
                                                                <a href="#" class="more-link">more</a>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php echo ($array['appliedby'] === $array['applicantid']) ? 'Self' : 'System';  ?>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($array['status'] ?? ''); ?></td>
                                                        <td>
                                                            <?php echo htmlspecialchars($array['comment'] ?? ''); ?><br>
                                                            <?php echo htmlspecialchars($array['reviewer_id'] ?? ''); ?><br>
                                                            <?php echo htmlspecialchars($array['reviewer_name'] ?? ''); ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else : ?>
                                                <tr>
                                                    <td colspan="8">
                                                        <?php if ($lyear == null) : ?>
                                                            Please select Filter value.
                                                        <?php else : ?>
                                                            No record was found for the selected filter value.
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>


                                <div class="modal" id="myModalpdf" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-xl">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h1 class="modal-title fs-5" id="exampleModalLabel">Supporting documents</h1>
                                                <button type="button" id="closepdf-header" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div style="width:100%;">
                                                    <span style="float: left;">Leave Id: <span class="leaveid"></span></span>
                                                    <span style="float: right;">
                                                        <p id="status2" class="badge" style="display: inline !important;"><span class="status"></span></p>
                                                    </span>
                                                </div>
                                                <object name="docid" id="" data="" type="application/pdf" width="100%" height="450px"></object>
                                                <div class="modal-footer">
                                                    <button type="button" id="closepdf-footer" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div><!-- End Reports -->
                </div>
            </div>
        </section>
    </main><!-- End #main -->

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
    <script>
        $(document).ready(function() {
            // Toggle full comment visibility on "more" link click
            $('.more-link').click(function(e) {
                e.preventDefault();
                var shortComment = $(this).siblings('.short-comment');
                var fullComment = $(this).siblings('.full-comment');
                if (fullComment.is(':visible')) {
                    // If full comment is visible, toggle to show short comment
                    shortComment.show();
                    fullComment.hide();
                    $(this).text('more');
                } else {
                    // If short comment is visible, toggle to show full comment
                    shortComment.hide();
                    fullComment.show();
                    $(this).text('less');
                }
            });
        });
    </script>
    <!--Here .typeofleave is a class and has been assigned to the input filed id=typeofleave-->
    <script type="text/javascript">
        $(document).ready(function() {
            $("select.typeofleave").change(function() {
                var selectedtypeofleave = $(".typeofleave option:selected").val();
                $.ajax({
                    type: "POST",
                    url: "process-request.php",
                    data: {
                        typeofleave: selectedtypeofleave
                    }
                }).done(function(data) {
                    $("#response").html(data);
                });
            });
        });
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
                    // paging: false,
                    "order": [] // Disable initial sorting
                    // other options...
                });
            <?php endif; ?>
        });
    </script>
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
        // Create a new Bootstrap modal instance with backdrop: 'static' and keyboard: false options
        const myModal = new bootstrap.Modal(document.getElementById("myModal"), {
            backdrop: 'static',
            keyboard: false
        });
        // Add event listener to intercept Escape key press
        document.body.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                // Prevent default behavior of Escape key
                event.preventDefault();
            }
        });
    </script>
    <script>
        // Function to show loading modal
        function showLoadingModal() {
            $('#myModal').modal('show');
        }

        // Function to hide loading modal
        function hideLoadingModal() {
            $('#myModal').modal('hide');
        }

        // Add event listener to form submission
        document.getElementById('leaveapply').addEventListener('submit', function(event) {
            // Show loading modal when form is submitted
            showLoadingModal();
        });

        // Optional: Close loading modal when the page is fully loaded
        window.addEventListener('load', function() {
            // Hide loading modal
            hideLoadingModal();
        });
    </script>
    <script>
        function checkLeaveType() {
            var fromdate = new Date(document.getElementById("fromdate").value);
            var todate = new Date(document.getElementById("todate").value);
            var today = new Date();

            // Reset typeofleave if fromdate or todate changes
            document.getElementById("typeofleave").value = ""; // Reset the selected value

            // Disable Casual Leave if fromdate or todate is today or in the past
            if (fromdate <= today || todate <= today) {
                document.getElementById("typeofleave").options[2].disabled = true; // Disable Casual Leave
                document.getElementById("leaveWarning").innerText = "You have selected current or past date. You are not eligible to apply for Casual Leave.";
            } else {
                document.getElementById("typeofleave").options[2].disabled = false; // Enable Casual Leave
                document.getElementById("leaveWarning").innerText = ""; // Clear warning message
            }
        }
    </script>

    <script>
        if (<?php echo $slbalance ?> <= 0) {
            document.getElementById("typeofleave").options[1].disabled = true;
        } else {
            document.getElementById("typeofleave").options[1].disabled = false;
        }

        if (<?php echo $clbalance ?> <= 0) {
            document.getElementById("typeofleave").options[2].disabled = true;
        } else {
            document.getElementById("typeofleave").options[2].disabled = false;
        }
    </script>
    <script>
        function toggleShiftField() {
            var isHalfDay = document.getElementById('is_userh').checked;
            var shiftField = document.getElementById('shiftField');
            var shiftSelect = document.getElementById('shift');
            var isDisabled = document.getElementById('is_userh').disabled;

            if (isHalfDay && !isDisabled) {
                shiftField.style.display = 'block';
                shiftSelect.required = true;
            } else {
                shiftField.style.display = 'none';
                shiftSelect.required = false;
                shiftSelect.value = '';
            }
        }

        function cal() {
            if (document.getElementById("todate") || document.getElementById("fromdate")) {
                function GetDays() {
                    var todate = new Date(document.getElementById("todate").value);
                    var fromdate = new Date(document.getElementById("fromdate").value);
                    var diffDays = (todate - fromdate) / (24 * 3600 * 1000) + 1;

                    var todatecheck = document.forms["leaveapply"]["todate"].value;
                    var fromdatecheck = document.forms["leaveapply"]["fromdate"].value;

                    if ((todatecheck == null || fromdatecheck == null) || diffDays !== 1) {
                        document.getElementById("is_userh").disabled = true;
                        document.getElementById("is_userh").checked = false;
                        toggleShiftField(); // Call to hide shift field if needed
                    } else {
                        document.getElementById("is_userh").disabled = false;
                    }
                    if ($('#is_userh').not(':checked').length > 0) {
                        return (diffDays);

                    } else if (event.target.checked) {
                        return (diffDays / 2);
                    }
                    const checkbox = document.getElementById('is_userh');
                    checkbox.addEventListener('change', (event) => {
                        if (event.target.checked) {
                            return (diffDays / 2);
                        } else if ($('#is_userh').not(':checked').length > 0) {
                            return (diffDays);
                        }
                    })
                }
                document.getElementById("numdays2").value = GetDays();

                document.getElementById("todate").min = document.getElementById("fromdate").value;
                document.getElementById("fromdate").max = document.getElementById("todate").value;
            }
        }

        //Showing document upload for sick leave only 
        $(document).ready(function() {
            $("#typeofleave").change(function() {
                if ($("#typeofleave").val() == "Sick Leave") {
                    $("#hidden-panel").show()
                    $("#hidden-panel_ack").show()
                    $("#hidden-panel_creason").show()
                } else if ($("#typeofleave").val() == "Casual Leave") {
                    $("#hidden-panel").hide()
                    $("#hidden-panel_ack").hide()
                    $("#hidden-panel_creason").show()
                } else if ($("#typeofleave").val() == "Leave Without Pay") {
                    $("#hidden-panel").hide()
                    $("#hidden-panel_ack").hide()
                    $("#hidden-panel_creason").hide()
                } else {
                    $("#hidden-panel").hide()
                    $("#hidden-panel_ack").hide()
                    $("#hidden-panel_creason").hide()
                }
            })
        });
    </script>
    <!--To make a filed (acknowledgement) required based on a dropdown value (sick leave)-->
    <script>
        if (document.getElementById('typeofleave').value == "Leave Without Pay" && document.getElementById('typeofleave').value == "Casual Leave") {

            document.getElementById("ack").required = false;
        } else {

            document.getElementById("ack").required = true;
        }

        const randvar = document.getElementById('typeofleave');

        randvar.addEventListener('change', (event) => {
            if (document.getElementById('typeofleave').value == "Sick Leave") {

                document.getElementById("ack").required = true;

            } else {

                document.getElementById("ack").required = false;
            }
        })
    </script>
    <script>
        var data1 = <?php echo json_encode($resultArr) ?>

        // Get the modal
        var modal1 = document.getElementById("myModalpdf");
        var closepdf = [
            document.getElementById("closepdf-header"),
            document.getElementById("closepdf-footer")
        ];

        function showpdf(id1) {
            var mydata1 = undefined
            data1.forEach(item1 => {
                if (item1["leaveid"] == id1) {
                    mydata1 = item1;
                }
            })
            var keys1 = Object.keys(mydata1)
            keys1.forEach(key => {
                var span1 = modal1.getElementsByClassName(key)
                if (span1.length > 0)
                    span1[0].innerHTML = mydata1[key];
            })
            modal1.style.display = "block";

            //class add 
            var statuss = document.getElementById("status2")
            if (mydata1["status"] === "Approved") {
                statuss.classList.add("bg-success")
                statuss.classList.remove("bg-danger")
            } else if (mydata1["status"] === "Rejected") {
                statuss.classList.remove("bg-success")
                statuss.classList.add("bg-danger")
            } else {
                statuss.classList.remove("bg-success")
                statuss.classList.remove("bg-danger")
            }
            //class add end
            document.getElementsByName("docid")[0].id = "docid" + mydata1["leaveid"];

            randomvar = document.getElementById("docid" + mydata1["leaveid"])
            randomvar.data = mydata1["docp"]

            closepdf.forEach(function(element) {
                element.addEventListener("click", closeModel);
            });

            function closeModel() {
                var modal1 = document.getElementById("myModalpdf");
                modal1.style.display = "none";
            }
        }
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
            $('#adj_academicyear').append(new Option(year, year));
            $('#adj_academicyear_A').append(new Option(year, year));
            currentYear--;
        }
    </script>
    <script>
        $(document).ready(function() {
            // Function to add the red asterisk next to required fields
            function addAsteriskToRequiredFields(element) {
                var label = $(element).closest('.form-group').find('label');

                // Check if the field is required and the asterisk isn't already added
                if ($(element).prop('required') && !label.find('span').length) {
                    label.append(' <span style="color: red">*</span>');
                }
            }

            // Loop through all select, input, and textarea fields to add asterisks where required
            $('select, input, textarea').each(function() {
                addAsteriskToRequiredFields(this);
            });
        });
    </script>
</body>

</html>