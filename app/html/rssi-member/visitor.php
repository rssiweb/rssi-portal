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
$today = date("Y-m-d");

// Retrieve form parameters
@$visitid = $_GET['visitid'];
@$contact = $_GET['contact'];
@$visitdatefrom = $_GET['visitdatefrom'];

// Initialize the WHERE clause of the query
$whereClause = " WHERE 1=1"; // Always true condition to start with

// Add conditions based on the filled input fields
if (!empty($visitid)) {
    $whereClause .= " AND vd.visitid = '$visitid'";
}

if (!empty($contact)) {
    $whereClause .= " AND ud.tel = '$contact'";
}

if (!empty($visitdatefrom)) {
    $whereClause .= " AND DATE(vd.visitstartdatetime) = '$visitdatefrom'";
}

// Check if any filter parameters are not empty
if (!empty($visitid) || !empty($contact) || !empty($visitdatefrom)) {
    // Finalize the query with the WHERE clause
    $query = "SELECT * FROM visitor_userdata AS ud
    JOIN visitor_visitdata AS vd ON ud.tel = vd.tel $whereClause";

    // Execute the query
    $result = pg_query($con, $query);

    if (!$result) {
        echo "An error occurred.\n";
        exit;
    }

    $resultArr = pg_fetch_all($result);
} else {
    // If all filter parameters are empty, set an empty result array
    $resultArr = [];
}

?>

<!doctype html>
<html lang="en">

<head>
    <style>
        .col2 {
            display: inline-block;
            vertical-align: top;
            /* Align the col2 elements to the top */
        }

        #passwordHelpBlock {
            display: block;
        }
    </style>
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

    <title>Visitor pass</title>

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
            policyLink: 'https://www.rssi.in/disvisiter'
        });
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Visitor pass</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Work</a></li>
                    <li class="breadcrumb-item"><a href="#">Process Hub</a></li>
                    <li class="breadcrumb-item active">Visitor pass</li>
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
                            <div class="text-end">
                                Record count: <?php echo sizeof($resultArr) ?>
                            </div>
                            <form id="myform" action="" method="GET">
                                <div class="form-group" style="display: inline-block;">
                                    <div class="col2">
                                        <input name="visitid" class="form-control" style="width: max-content; display: inline-block;" placeholder="Visit ID" value="<?php echo $visitid ?>">
                                        <small id="passwordHelpBlock" class="form-text text-muted">Visit Id<span style="color:red"></span></small>
                                    </div>
                                    <div class="col2">
                                        <input name="contact" class="form-control" style="width: max-content; display: inline-block;" placeholder="Contact" value="<?php echo $contact ?>">
                                        <small id="passwordHelpBlock" class="form-text text-muted">Contact<span style="color:red"></span></small>
                                    </div>
                                    <div class="col2">
                                        <input type="date" name="visitdatefrom" class="form-control" style="width: max-content; display: inline-block;" placeholder="Select visit date" value="<?php echo $visitdatefrom ?>">
                                        <small id="passwordHelpBlock" class="form-text text-muted">Visit date<span style="color:red"></span></small>
                                    </div>
                                </div>
                                <div class="col2 left" style="display: inline-block;">
                                    <button type="submit" name="search_by_id" id="search_by_id" class="btn btn-success btn-sm" style="outline: none;">
                                        <i class="bi bi-search"></i>&nbsp;Search
                                    </button>&nbsp;
                                    <a href="https://rssi.in/visit-us" target="_blank" class="btn btn-warning btn-sm" role="button">
                                        <i class="bi bi-plus-lg"></i>&nbsp;Registration
                                    </a>
                                </div>
                            </form>

                            <?php echo '
                       <table class="table">
                        <thead>
                            <tr>
                                <th scope="col">Visit ID</th>
                                <th scope="col">Visitor details</th>
                                <th scope="col">Visit date from</th>
                                <th scope="col">Visit date to</th>
                                <th scope="col">Identity proof</th>
                                <th scope="col">Photo</th>
                                <th scope="col">Purpose of visit</th>
                                <th scope="col">Branch name</th>
                                <th scope="col">HR remarks</th>
                                <th scope="col"></th>
                            </tr>
                        </thead>' ?>
                            <?php if (sizeof($resultArr) > 0) { ?>
                                <?php
                                echo '<tbody>';
                                foreach ($resultArr as $array) {
                                    echo '<tr><td>' ?>
                                    <?php echo $array['visitid'] ?>
                                    <?php echo '</td>
                                <td>' . $array['fullname'] . '<br>' . $array['tel'] . '<br>' . $array['email'] . '</td>
                                <td>' . date("d/m/Y h:i A", strtotime($array['visitstartdatetime'])) . '</td>
                                
                                <td>' . date("d/m/Y", strtotime($array['visitenddate'])) . '</td>
                                
                                <td><span class="noticea"><a href="' . $array['nationalid'] . '" target="_blank"><i class="bi bi-filetype-pdf" style="font-size:17px;color: #767676;"></i></a></span></td>' ?>
                                    <?php
                                    // Extracting the file ID from the URL
                                    $urlParts = parse_url($array['photo']);
                                    $pathParts = explode('/', $urlParts['path']);
                                    $fileId = $pathParts[3];

                                    // Generating the desired URL format
                                    $thumbnailUrl = "https://drive.google.com/thumbnail?id=$fileId";
                                    ?>
                                    <?php echo '<td><img src="' . str_replace("/open?id=", "/thumbnail?id=", $thumbnailUrl) . '" width="50" height="50"/></td>
                                <td>' . $array['visitpurpose'] . ($array['other_reason'] ? ' - ' . $array['other_reason'] : '') . '</td>
                                <td>' . $array['visitbranch'] . '</td>'  ?>


                                    <?php if ($array['visitstatus'] == 'Approved' && $array['visitenddate'] >= $today) { ?>
                                        <?php echo '<td><p class="badge bg-success">approved</p></td>' ?>
                                    <?php } else if ($array['visitstatus'] == 'Rejected') { ?>
                                        <?php echo '<td><p class="badge bg-danger">rejected</p></td>' ?>
                                    <?php } else if ($array['visitstatus'] == null && $array['visitenddate'] >= $today) { ?>
                                        <?php echo '<td><p class="badge bg-secondary">under review</p></td>' ?>
                                    <?php } else if ($array['visitstatus'] != 'Visited' && $array['visitenddate'] < $today) { ?>
                                        <?php echo '<td><p class="badge bg-secondary">expired</p></td>' ?>
                                    <?php } else if ($array['visitstatus'] == 'Visited') { ?>
                                        <?php echo '<td><p class="badge bg-warning">visited</p></td>' ?>
                                    <?php } ?>

                                    <?php echo '<td>
                                            <button type="button" href="javascript:void(0)" onclick="showDetails(\'' . $array['visitid'] . '\')" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Details">
                                            <i class="bi bi-box-arrow-up-right" style="font-size:14px;color:#777777" title="Show Details" display:inline;></i></button>' ?>
                                    <?php if ($role == "Admin") { ?>
                                        <?php if ($array['tel'] != null && @$array['visitstatus'] != null) { ?>

                                            <?php if ($array['visitstatus'] == "Approved") { ?>
                                                <?php echo '
                  
                  <a href="https://api.whatsapp.com/send?phone=91' . $array['tel'] . '&text=Dear ' . $array['fullname'] . ',%0A%0AYour visit request has been approved. Visit Id: *' . $array['visitid'] . '*%0A%0AYou are all set to visit RSSI Learning Centre, Lucknow. This pass is valid for the period from ' . date("d/m/Y h:i A", strtotime($array['visitstartdatetime'])) . ' to ' . date("d/m/Y", strtotime($array['visitenddate'])) . '. Upon arrival at the Learning Centre, the centre in-charge will take you through visitor guidelines.%0A%0A*General guidelines:*%0A%0A✔ Please declare your identity at the security check and submit if you have prohibited items.%0A✔ Please note that no weapons, flammable liquids, or gases are allowed inside the center premises.%0A✔ If you wish to donate or contribute anything to the beneficiaries, please inform in advance by email to info@rssi.in. Under no circumstances are any loose food items allowed to be distributed. For any packaged food, please check the batch and expiry date properly.%0A✔ Do not donate cash directly to anyone at the centre. Kindly follow the donation process, for more details please visit the donation portal https://www.rssi.in/donation-portal%0A%0AWe look forward to meeting you.%0A%0A-- RSSI NGO
" target="_blank"><i class="bi bi-whatsapp" style="color:#444444;" title="Send SMS ' . $array['tel'] . '"></i></a>'
                                                ?>
                                                <?php echo '
                  
                  <a href="https://api.whatsapp.com/send?phone=919956623060&text=Dear Centre In-charge,%0A%0AA visit to RSSI Learning Centre, Lucknow has been scheduled. Please refer to the details below.%0A%0AVisit ID - *' . $array['visitid'] . '*%0ADate - ' . date("d/m/Y h:i A", strtotime($array['visitstartdatetime'])) . ' to ' . date("d/m/Y", strtotime($array['visitenddate'])) . '.%0APurpose of visit - ' . $array['visitpurpose'] . ($array['other_reason'] ? ' - ' . $array['other_reason'] : '') . '%0A%0APlease inform the students and concerned class teachers accordingly. During this period all the students and teachers should be present in the centre and the centre should be functional as per schedule including academic activities.%0A%0ATo check visitor details, please click here https://login.rssi.in/rssi-member/visitor.php?get_appid=' . $array['visitid'] . '%0A%0A-- RSSI NGO%0A%0A**This is a system generated message." target="_blank"><i class="bi bi-bell" style="color:#444444;" title="Notify Centre Incharge"></i></a>'
                                                ?>
                                            <?php } else if ($array['visitstatus'] == "Rejected") { ?>
                                                <?php echo '
                  
                  <a href="https://api.whatsapp.com/send?phone=91' . $array['tel'] . '&text=Dear ' . $array['fullname'] . ',%0A%0AYour visit request (' . $array['visitid'] . ') has been REJECTED in the system due to any of the reasons mentioned below.%0A%0A1) The document is invalid.%0A2) The National Identifier Number is invalid.%0A3) Improper scanning of the uploaded document. Please scan the entire document and if the address or any other relevant information is mentioned on the other side, scan both the sides of the National Identifier.%0A%0APlease ensure that the scanned document is clearly legible, and re-upload the same.%0A%0A-- RSSI%0A%0A**This is a system generated message." target="_blank"><i class="bi bi-whatsapp" style="color:#444444;" title="Send SMS ' . $array['tel'] . '"></i></a>'
                                                ?>
                                            <?php } else if ($array['visitstatus'] == "Visited") { ?>
                                                <?php echo '
                  
                  <a href="https://api.whatsapp.com/send?phone=91' . $array['tel'] . '&text=Dear ' . $array['fullname'] . ' (' . $array['visitid'] . '),%0A%0AThank you for visiting RSSI Offline Centre, Lucknow. Hope you have a great time with the kids.%0A%0AAlso, we would love to hear your feedback, please rate us and share your experience here - https://g.page/r/CQkWqmErGMS7EAg/review%0A%0AHope to see you again.%0A%0A-- Team RSSI
" target="_blank"><i class="bi bi-whatsapp" style="color:#444444;" title="Send SMS ' . $array['tel'] . '"></i></a>'
                                                ?>
                                            <?php } ?>
                                        <?php } else { ?>
                                            <?php echo '<i class="bi bi-whatsapp" style="color:#A2A2A2;" title="Send SMS"></i>' ?>
                                        <?php } ?>
                                        <?php if (@$array['email'] != null && @$array['visitstatus'] != null) { ?>
                                            <?php echo '<form  action="#" name="email-form-' . $array['visitid'] . '" method="POST" style="display: -webkit-inline-box;" >' ?>

                                            <?php if (@$array['visitstatus'] == 'Approved') { ?>
                                                <input type="hidden" name="template" type="text" value="visitapprove">
                                            <?php } else if (@$array['visitstatus'] == 'Rejected') { ?>
                                                <input type="hidden" name="template" type="text" value="visitreject">
                                            <?php } else if (@$array['visitstatus'] == 'Visited') { ?>
                                                <input type="hidden" name="template" type="text" value="visited">
                                            <?php } ?>

                                            <?php echo '<input type="hidden" name="data[visitid]" type="text" value="' . $array['visitid'] . '">
                  <input type="hidden" name="data[fullname]" type="text" value="' . $array['fullname'] . '">
                  <input type="hidden" name="data[visitstartdatetime]" type="text" value="' . date("d/m/Y h:i A", strtotime($array['visitstartdatetime'])) . '">
                  <input type="hidden" name="data[visitenddate]" type="text" value="' . date("d/m/Y", strtotime($array['visitenddate'])) . '">
                  <input type="hidden" name="data[visitstatus]" type="text" value="' . @strtoupper($array['visitstatus']) . '">
                  <input type="hidden" name="email" type="text" value="' . @$array['email'] . '">

                  <button style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" type="submit"><i class="bi bi-envelope-at" style="color:#444444;" title="Send Email ' . @$array['email'] . '"></i></button>
                  </form>' ?>
                                        <?php } else { ?>
                                            <?php echo '<i class="bi bi-envelope-at" style="color:#A2A2A2;" title="Send Email"></i>' ?>
                                    <?php }
                                    } ?>
                                <?php echo '</td></tr>';
                                } ?>
                            <?php } else if ($visitid == null && $contact == null && $visitdatefrom == null) {
                            ?>
                                <tr>
                                    <td colspan="5">Please enter either the Visit ID, Contact or the Visit Date.</td>
                                </tr>
                            <?php
                            } else {
                            ?>
                                <tr>
                                    <td colspan="5">No record was found for the selected filter value.</td>
                                </tr>
                            <?php }

                            echo '</tbody>
                                    </table>';
                            ?>
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

    <!--------------- POP-UP BOX ------------
-------------------------------------->
    <style>
        .modal {
            background-color: rgba(0, 0, 0, 0.4);
            /* Black w/ opacity */
        }
    </style>
    <div class="modal" id="myModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="exampleModalLabel">Visitor Status Control Panel</h1>
                    <button type="button" id="closedetails-header" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div style="width:100%; text-align:right">
                        <p>
                            Visit id: <span class="visitid"></span><br>
                            <span class="fullname"></span> | <span class="visitstartdatetime"></span>
                        </p>
                    </div>
                    <?php if ($role == "Admin") { ?>
                        <form id="visitreviewform" name="visitreviewform" action="#" method="POST" class="row g-3">

                            <input type="hidden" class="form-control" name="form-type" type="text" value="visitreviewform" readonly>
                            <input type="hidden" class="form-control" name="reviewer_id" id="reviewer_id" type="text" value="<?php echo $associatenumber ?>" readonly>
                            <input type="hidden" class="form-control" name="visitid" id="visitid" type="text" value="" readonly>

                            <div class="col-md-3">
                                <div class="input-help">
                                    <select class="form-select" id="visitbranch" name="visitbranch" required>
                                        <option value disabled selected>Select Branch</option>
                                        <option value="Gomti Nagar, Lucknow">Gomti Nagar, Lucknow</option>
                                    </select>
                                    <small id="passwordHelpBlock" class="form-text text-muted">Which branch do you want to visit?<span style="color:red">*</span></small>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="input-help">
                                    <input type="datetime-local" class="form-control" id="visitstartdatetime" name="visitstartdatetime" required>
                                    <small id="passwordHelpBlock" class="form-text text-muted">Visit start date<span style="color:red">*</span></small>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="input-help">
                                    <input type="date" class="form-control" id="visitenddate" name="visitenddate" required>
                                    <small id="passwordHelpBlock" class="form-text text-muted">Visit end date<span style="color:red">*</span></small>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="input-help">
                                    <select name="visitstatus" id="visitstatus" class="form-select" required>
                                        <option value="" disabled selected hidden>Status</option>
                                        <option value="Approved">Approved</option>
                                        <option value="Rejected">Rejected</option>
                                        <option value="Visited">Visited</option>
                                        <option value="Duplicate entry">Duplicate entry</option>
                                    </select>
                                    <small id="passwordHelpBlock" class="form-text text-muted">Visit status<span style="color:red">*</span></small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="input-help">
                                    <textarea type="text" name="hrremarks" id="hrremarks" class="form-control" placeholder="HR remarks"></textarea>
                                    <small id="passwordHelpBlock" class="form-text text-muted">HR remarks</small>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <button type="submit" id="visitupdate" class="btn btn-danger btn-sm">Update</button>
                            </div>
                        </form>

                    <?php } ?>
                    <p style="font-size:small; text-align: right; font-style: italic; color:#A2A2A2;">Updated by: <span class="visitstatusupdatedby"></span> on <span class="visitstatusupdatedon"></span>
                    <div class="modal-footer">
                        <button type="button" id="closedetails-footer" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        var data = <?php echo json_encode($resultArr) ?>

        // Get the modal
        var modal = document.getElementById("myModal");
        var closedetails = [
            document.getElementById("closedetails-header"),
            document.getElementById("closedetails-footer")
        ];

        function showDetails(id) {
            var mydata = undefined
            data.forEach(item => {
                if (item["visitid"] == id) {
                    mydata = item;
                }
            })

            var keys = Object.keys(mydata)
            keys.forEach(key => {
                var span = modal.getElementsByClassName(key)
                if (span.length > 0)
                    span[0].innerHTML = mydata[key];
            })
            modal.style.display = "block";

            // Update status_details content with the visitid value
            var statusDetailsElement = document.getElementById("status_details");
            if (statusDetailsElement) {
                statusDetailsElement.textContent = mydata["visitid"];
            }

            var profile = document.getElementById("visitid")
            profile.value = mydata["visitid"]

            if (mydata["visitbranch"] !== null) {
                profile = document.getElementById("visitbranch")
                profile.value = mydata["visitbranch"]
            } else {
                profile = document.getElementById("visitbranch")
                profile.value = ""
            }

            if (mydata["visitstartdatetime"] !== null) {
                profile = document.getElementById("visitstartdatetime")
                profile.value = mydata["visitstartdatetime"]
            } else {
                profile = document.getElementById("visitstartdatetime")
                profile.value = ""
            }

            if (mydata["visitenddate"] !== null) {
                profile = document.getElementById("visitenddate")
                profile.value = mydata["visitenddate"]
            } else {
                profile = document.getElementById("visitenddate")
                profile.value = ""
            }

            if (mydata["visitstatus"] !== null) {
                profile = document.getElementById("visitstatus")
                profile.value = mydata["visitstatus"]
            } else {
                profile = document.getElementById("visitstatus")
                profile.value = ""
            }
            if (mydata["remarks"] !== null) {
                profile = document.getElementById("hrremarks")
                profile.value = mydata["remarks"]
            } else {
                profile = document.getElementById("hrremarks")
                profile.value = ""
            }

            if (mydata["visitstatus"] == 'Visited' || mydata["visitstatus"] == 'Rejected') {
                document.getElementById("visitupdate").disabled = true;
            } else {
                document.getElementById("visitupdate").disabled = false;
            }
        }
        closedetails.forEach(function(element) {
            element.addEventListener("click", closeModal);
        });

        function closeModal() {
            var modal1 = document.getElementById("myModal");
            modal1.style.display = "none";
        }
    </script>
    <script>
        var data = <?php echo json_encode($resultArr) ?>;
        const scriptURL = 'payment-api.php'
        const form = document.getElementById('visitreviewform')
        form.addEventListener('submit', e => {
            e.preventDefault()
            fetch(scriptURL, {
                    method: 'POST',
                    body: new FormData(document.getElementById('visitreviewform'))
                })
                .then(response =>
                    alert("Record has been updated.") +
                    location.reload()
                )
                .catch(error => console.error('Error!', error.message))
        })

        data.forEach(item => {
            const formId = 'email-form-' + item.visitid
            const form = document.forms[formId]
            form.addEventListener('submit', e => {
                e.preventDefault()
                fetch('mailer.php', {
                        method: 'POST',
                        body: new FormData(document.forms[formId])
                    })
                    .then(response =>
                        alert("Email has been sent.")
                    )
                    .catch(error => console.error('Error!', error.message))
            })
        })
    </script>

</body>

</html>