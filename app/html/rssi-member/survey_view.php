<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();

// Handle AJAX request for record fetching and updating
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if the request is for updating statuses via AJAX
    if (isset($_POST['formType']) && $_POST['formType'] === 'ajax') {
        // Process status update via AJAX
        $updatedStatus = $_POST['updatedStatus'] ?? [];

        foreach ($updatedStatus as $statusData) {
            $id = $statusData['id'];
            $status = $statusData['status'];

            // Update the status in the database
            $sql = "UPDATE student_data SET status = $1 WHERE id = $2";
            $result = pg_query_params($con, $sql, array($status, $id));

            if (!$result) {
                echo json_encode(['success' => false, 'message' => "Error updating status for id: $id. " . pg_last_error($con)]);
                exit;
            }
        }

        // Return success response
        echo json_encode(['success' => true]);
        exit;
    } else {
        // Handle the case for fetching records
        $offset = $_POST['offset'] ?? 0;
        $limit = $_POST['limit'] ?? 10;

        // Apply LIMIT and OFFSET directly in the query
        $limitQuery = $limit ? "LIMIT $limit OFFSET $offset" : "";

        $result = pg_query($con, "SELECT s.family_id, s.contact, s.parent_name, sd.id, sd.status, sd.student_name, sd.age, sd.gender, sd.grade, 
                   s.timestamp, rm.fullname AS surveyor_name, s.address, s.earning_source, s.other_earning_source_input, sd.already_going_school, sd.school_type, sd.already_coaching, sd.coaching_name
            FROM survey_data s 
            LEFT JOIN student_data sd ON s.family_id = sd.family_id
            JOIN rssimyaccount_members rm ON s.surveyor_id = rm.associatenumber
            ORDER BY s.timestamp DESC, sd.id ASC -- Stable sorting to ensure unique rows
            $limitQuery");

        if ($result) {
            $records = pg_fetch_all($result) ?: [];
            $isLastBatch = !$limit || count($records) < $limit;

            echo json_encode([
                "success" => true,
                "records" => $records,
                "isLastBatch" => $isLastBatch,
            ]);
        } else {
            echo json_encode([
                "success" => false,
                "message" => "Error fetching data.",
            ]);
        }
        exit;
    }
}
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

    <title>View Survey Results</title>

    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">
    <!-- CSS Library Files -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">

    <!-- Scripts to Fetch Data and Initialize DataTable -->
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>


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

        /* Loader styling */
        #progressLoader .loader {
            border: 8px solid #f3f3f3;
            border-top: 8px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 2s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>

</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>View Survey Results</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Survey</a></li>
                    <li class="breadcrumb-item active">View Survey Results</li>
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

                            <!-- Add progress loader HTML -->
                            <div id="pageOverlay"
                                style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 9999;">
                                <div id="progressLoader"
                                    style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">
                                    <p>Submitting, please wait...</p>
                                    <div class="loader"></div> <!-- Custom loader (you can style as needed) -->
                                </div>
                            </div>

                            <div class="text-end mb-3">
                                <label for="recordsPerLoad" class="form-label me-2">Records Per Load:</label>
                                <select id="recordsPerLoad" class="form-select d-inline-block" style="width: auto;">
                                    <option value="10" selected>10</option>
                                    <option value="20">20</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                            </div>

                            <form id="statusForm" method="POST">
                                <input type="hidden" name="form-type" id="formType" value="ajax">
                                <div class="text-end mb-3">
                                    <button type="button" id="editBtn" class="btn btn-primary">Edit</button>
                                    <button type="button" id="saveBtn" class="btn btn-success"
                                        style="display: none;">Save</button>
                                </div>

                                <div class="table-responsive">
                                    <table class="table" id="table-id">
                                        <thead>
                                            <tr>
                                                <th>SL</th>
                                                <th>Family ID</th>
                                                <th>Address</th>
                                                <th>Contact</th>
                                                <th>Parent Name</th>
                                                <th>Student Name</th>
                                                <th>Age</th>
                                                <th>Gender</th>
                                                <th>Grade</th>
                                                <th>Details</th>
                                                <th>Timestamp</th>
                                                <th>Surveyor Name</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="data-tbody"></tbody>
                                    </table>
                                </div>
                            </form>

                            <div class="text-center">
                                <button id="loadMoreBtn" class="btn btn-primary">Load More</button>
                            </div>
                        </div>
                    </div><!-- End Reports -->
                </div>
            </div>
        </section>

    </main><!-- End #main -->

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i
            class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4"
        crossorigin="anonymous"></script>
    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>

    <script>
        let offset = 0; // Start from the first record
        let table; // Declare a variable for DataTable

        const fetchRecords = async (limit = 10) => {
            try {
                const response = await $.post("", {
                    offset,
                    limit
                });
                const data = JSON.parse(response);

                if (data.success) {
                    const tbody = $("#data-tbody");
                    const records = data.records;

                    if (records.length > 0) {
                        records.forEach((record, index) => {
                            const isStudentNameNull = record.student_name === null;

                            const statusField = isStudentNameNull ?
                                `<span class="regular-text status-text">${record.status}</span>` :
                                `<span class="regular-text status-text" style="display: block;">${record.status}</span>
                             <select name="status[${record.id}]" class="edit-input status-dropdown form-select" style="display: none;">
                                 <option disabled selected>Select Status</option>
                                 <option value="No Show" ${record.status === 'No Show' ? 'selected' : ''}>No Show</option>
                                 <option value="Enrollment Completed" ${record.status === 'Enrollment Completed' ? 'selected' : ''}>Enrollment Completed</option>
                             </select>`;

                            tbody.append(`
                            <tr>
                                <td>${offset + index + 1}</td>
                                <td>${record.family_id}</td>
                                <td>
                                    <span class="short-address">${record.address.length > 30 ? record.address.substring(0, 30) + "..." : record.address}</span>
                                    <span class="full-address" style="display: none;">${record.address}</span>
                                    ${record.address.length > 30 ? '<a href="#" class="more-link">more</a>' : ''}
                                </td>
                                <td>${record.contact}</td>
                                <td>${record.parent_name}</td>
                                <td>${record.student_name}</td>
                                <td>${record.age}</td>
                                <td>${record.gender}</td>
                                <td>${record.grade}</td>
                                <td><a href="#" class="misc-link" data-bs-toggle="modal" data-bs-target="#miscModal${record.family_id}">View</a></td>
                                <td>${record.timestamp}</td>
                                <td>${record.surveyor_name}</td>
                                <td>${statusField}</td>
                            </tr>
                        `);

                            // Append modal to body
                            $("body").append(`
                        <div class="modal fade" id="miscModal${record.family_id}" tabindex="-1" aria-labelledby="miscModalLabel${record.family_id}" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="miscModalLabel${record.family_id}">Miscellaneous Data</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p>Student Name: ${record.student_name} (${record.family_id})</p>
                                        <p>Family Earning Source: 
                                            ${record.earning_source === "other" ? record.other_earning_source_input : record.earning_source}
                                        </p>
                                        <p>Already Going to School: ${record.already_going_school}</p>
                                        <p>School Type: ${record.school_type}</p>
                                        <p>Already Coaching: ${record.already_coaching}</p>
                                        <p>Coaching Name: ${record.coaching_name}</p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `);
                        });

                        offset += records.length;

                        // If DataTable is not initialized yet, initialize it
                        if (!table) {
                            table = $('#table-id').DataTable({
                                paging: false,
                                "order": [], // Disable initial sorting
                            });
                        } else {
                            // If DataTable is already initialized, just redraw it after adding new rows
                            table.clear().rows.add(tbody.find('tr')).draw();
                        }
                    }

                    if (data.isLastBatch) {
                        $("#loadMoreBtn").hide();
                    }
                } else {
                    alert(data.message || "Failed to load records.");
                }
            } catch (error) {
                console.error("Error fetching records:", error);
            }
        };

        const toggleEdit = () => {
            $(".status-text").toggle(); // Hide text view (show dropdown)
            $(".status-dropdown").toggle(); // Show dropdown for editing
            $("#saveBtn").show(); // Show the Save button
            $("#editBtn").hide(); // Hide the Edit button
        };

        const saveChanges = () => {
            const updatedStatus = [];
            $(".status-dropdown").each(function () {
                const statusValue = $(this).val();
                if (statusValue) { // Only push if the status is valid
                    updatedStatus.push({
                        id: $(this).attr("name").split("[")[1].split("]")[0],
                        status: statusValue
                    });
                }
            });

            // Show progress loader while saving
            $("#progressLoader").show();
            $("#pageOverlay").show(); // Show the overlay to block interaction

            $.ajax({
                url: "sur.php", // Correct PHP file for saving status
                type: "POST",
                data: {
                    formType: 'ajax',
                    updatedStatus: updatedStatus
                },
                success: function (response) {
                    const data = JSON.parse(response);
                    if (data.success) {
                        alert("Status updated successfully.");
                        $(".status-text").show(); // Show text again
                        $(".status-dropdown").hide(); // Hide dropdown
                        $("#saveBtn").hide(); // Hide Save button
                        $("#editBtn").show(); // Show Edit button

                        // Reload the page after showing the alert
                        location.reload();
                    } else {
                        alert("Error saving status.");
                    }
                },
                complete: function () {
                    // Hide progress loader after the request is complete
                    $("#progressLoader").hide();
                    $("#pageOverlay").hide(); // Hide the overlay after submission
                }
            });
        };

        $(document).ready(() => {
            fetchRecords();

            $("#loadMoreBtn").click(() => {
                const limit = $("#recordsPerLoad").val() === "ALL" ? null : parseInt($("#recordsPerLoad").val(), 10);
                fetchRecords(limit);
            });

            $("#editBtn").click(toggleEdit);

            $("#saveBtn").click(saveChanges);
        });
    </script>

    <script>
        $(document).on("click", ".more-link", function (e) {
            e.preventDefault();
            const shortAddress = $(this).siblings(".short-address");
            const fullAddress = $(this).siblings(".full-address");

            if (fullAddress.is(":visible")) {
                // Hide full address and show short address
                fullAddress.hide();
                shortAddress.show();
                $(this).text("more");
            } else {
                // Show full address and hide short address
                fullAddress.show();
                shortAddress.hide();
                $(this).text("less");
            }
        });
    </script>
</body>

</html>