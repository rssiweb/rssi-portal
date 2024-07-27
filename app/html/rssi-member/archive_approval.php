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

date_default_timezone_set('Asia/Kolkata');
include("../../util/email.php");
if ($role == 'Admin') {

    @$user_id = strtoupper($_GET['user_id']);

    if ($user_id > 0) {
        $result = pg_query($con, "SELECT archive.remarks aremarks,*
        FROM archive
        JOIN rssimyaccount_members ON archive.uploaded_for = rssimyaccount_members.associatenumber WHERE uploaded_for='$user_id'");
    } else {
        $result = pg_query($con, "SELECT *
        FROM archive
        JOIN rssimyaccount_members ON archive.uploaded_for = rssimyaccount_members.associatenumber order by doc_id desc"); //select query for viewing users.
    }
}
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

    <title>Document Approval</title>
    <meta content="" name="description">
    <meta content="" name="keywords">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">


    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>

    <script src="https://cdn.jsdelivr.net/gh/manucaralmo/GlowCookies@3.0.1/src/glowCookies.min.js"></script>
    <!-- Glow Cookies v3.0.1 -->
    <script>
        glowCookies.start('en', {
            analytics: 'G-S25QWTFJ2S',
            //facebookPixel: '',
            policyLink: 'https://www.rssi.in/disclaimer'
        });
    </script>

    <!-- Add DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/2.0.6/css/dataTables.bootstrap5.min.css">

    <!-- Add DataTables JS -->
    <script type="text/javascript" src="https://cdn.datatables.net/2.0.6/js/dataTables.min.js"></script>

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
    </style>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Document Approval</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Work</a></li>
                    <li class="breadcrumb-item active">Document Approval</li>
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
                            <div style="display: inline-block; width:100%; text-align:right;">Record count:&nbsp;<?php echo sizeof($resultArr) ?>
                            </div>
                            <?php if ($role == 'Admin' && $filterstatus == 'Active') { ?>

                                <form action="" method="GET">
                                    <div class="form-group" style="display: inline-block;">
                                        <div class="col2" style="display: inline-block;">
                                            <input name="user_id" id="user_id" class="form-control" style="width:max-content; display:inline-block" placeholder="User id" value="<?php echo $user_id ?>">
                                        </div>
                                    </div>
                                    <div class="col2 left" style="display: inline-block;">
                                        <button type="submit" name="search_by_id" class="btn btn-success btn-sm" style="outline: none;">
                                            <i class="bi bi-search"></i>&nbsp;Search</button>
                                    </div>
                                </form>
                            <?php } ?>
                            <br>
                            <div class="table-responsive">
                                <table class="table table-hover" id="table-id">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Associate number</th>
                                            <th>File Name</th>
                                            <th>File Path</th>
                                            <th>Uploaded By</th>
                                            <th>Uploaded on</th>
                                            <th>Verification Status</th>
                                            <th>Field Status</th>
                                            <th>Reviewed by</th>
                                            <th>Reviewed on</th>
                                            <?php if ($role == 'Admin') : ?>
                                                <th></th>
                                            <?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($resultArr != null) : ?>
                                            <?php foreach ($resultArr as $array) : ?>
                                                <tr>
                                                    <td><?= $array['doc_id'] ?></td>
                                                    <td><?= $array['fullname'] . '&nbsp;(' . $array['uploaded_for'] . ')' ?></td>
                                                    <td><?= $array['file_name'] ?></td>
                                                    <td><a href="<?= $array['file_path'] ?>" target="_blank">Document</a></td>
                                                    <td><?= $array['uploaded_by'] ?></td>
                                                    <td><?= $array['uploaded_on'] ? date("d/m/Y g:i a", strtotime($array['uploaded_on'])) : '' ?></td>
                                                    <td><?= $array['verification_status'] ?></td>
                                                    <td><?= $array['field_status'] ?></td>
                                                    <td><?= $array['reviewed_by'] ?></td>
                                                    <td><?= $array['reviewed_on'] ? date("d/m/Y g:i a", strtotime($array['reviewed_on'])) : '' ?></td>
                                                    <?php if ($role == 'Admin') : ?>
                                                        <td>
                                                            <button type="button" href="javascript:void(0)" onclick="showDetails('<?= $array['doc_id'] ?>')" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Details">
                                                                <i class="bi bi-box-arrow-up-right" style="font-size: 14px ;color:#777777" title="Show Details" display:inline;></i>
                                                            </button>
                                                            <!-- Add other Admin controls here -->
                                                        </td>
                                                    <?php endif; ?>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php elseif (@$get_certificate_no == "" && @$get_nomineeid == "") : ?>
                                            <tr>
                                                <td colspan="7">Please select Filter value.</td>
                                            </tr>
                                        <?php elseif (sizeof($resultArr) == 0 || (@$get_certificate_no != "" || @$get_nomineeid != "")) : ?>
                                            <tr>
                                                <td colspan="7">No record found for <?= $get_certificate_no . $get_nomineeid ?>.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!--------------- POP-UP BOX --------------->
                            <style>
                                .modal {
                                    background-color: rgba(0, 0, 0, 0.4);
                                    /* Black w/ opacity */
                                }
                            </style>
                            <div class="modal" id="myModal" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h1 class="modal-title fs-5" id="exampleModalLabel">Document Approval</h1>
                                            <button type="button" id="closedetails-header" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div style="width:100%; text-align:right">
                                                <p id="status_details" class="badge" style="display: inline;"></p>
                                            </div>

                                            <form id="archiveform" action="#" method="POST">
                                                <input type="hidden" name="form-type" value="archiveapproval" readonly>
                                                <input type="hidden" name="reviewer_id" id="reviewer_id" value="<?php echo $associatenumber ?>" readonly>
                                                <input type="hidden" name="doc_idd" id="doc_idd" value="" readonly>

                                                <div class="mb-3">
                                                    <label for="reviewer_status" class="form-label">Status</label>
                                                    <select name="reviewer_status" id="reviewer_status" class="form-select" required>
                                                        <option value="" disabled selected hidden>Review Status</option>
                                                        <option value="Verified">Verified</option>
                                                        <option value="Rejected">Rejected</option>
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="field_status" class="form-label">Status</label>
                                                    <select name="field_status" id="field_status" class="form-select" required>
                                                        <option value="" disabled selected hidden>Field Status</option>
                                                        <option value="disabled">disabled</option>
                                                        <option value="null">null</option>
                                                    </select>
                                                </div>

                                                <div class="mb-3">
                                                    <label for="reviewer_remarks" class="form-label">Reviewer Remarks</label>
                                                    <textarea name="reviewer_remarks" id="reviewer_remarks" class="form-control" placeholder="Reviewer remarks"></textarea>
                                                    <small id="passwordHelpBlock" class="form-text text-muted">Reviewer remarks</small>
                                                </div>

                                                <button type="submit" id="archiveupdate" class="btn btn-danger btn-sm">Update</button>
                                            </form>

                                            <div class="modal-footer">
                                                <button type="button" id="closedetails-footer" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <script>
                                var data = <?php echo json_encode($resultArr) ?>;
                                // Get the modal
                                var modal = document.getElementById("myModal");
                                // Get the <span> element that closes the modal
                                var closedetails = [
                                    document.getElementById("closedetails-header"),
                                    document.getElementById("closedetails-footer")
                                ];

                                function showDetails(id) {
                                    var mydata = undefined;
                                    data.forEach(item => {
                                        if (item["doc_id"] == id) {
                                            mydata = item;
                                        }
                                    });

                                    var keys = Object.keys(mydata);
                                    keys.forEach(key => {
                                        var span = modal.getElementsByClassName(key);
                                        if (span.length > 0)
                                            span[0].innerHTML = mydata[key];
                                    });
                                    modal.style.display = "block";
                                    // Update status_details content with the doc_id value
                                    var statusDetailsElement = document.getElementById("status_details");
                                    if (statusDetailsElement) {
                                        statusDetailsElement.textContent = mydata["doc_id"];
                                    }
                                    //class add 
                                    var status = document.getElementById("status_details");
                                    if (mydata["verification_status"] === "Verified") {
                                        status.classList.add("bg-success");
                                        status.classList.remove("bg-danger");
                                    } else {
                                        status.classList.remove("bg-success");
                                        status.classList.add("bg-danger");
                                    }
                                    //class add end

                                    var profile = document.getElementById("doc_idd");
                                    profile.value = mydata["doc_id"];

                                    if (mydata["verification_status"] !== null) {
                                        profile = document.getElementById("reviewer_status");
                                        profile.value = mydata["verification_status"];
                                    }
                                    if (mydata["field_status"] !== null) {
                                        profile = document.getElementById("field_status");
                                        profile.value = mydata["field_status"];
                                    }

                                    var textarea = document.getElementById("reviewer_remarks");
                                    if (mydata["aremarks"] !== null) {
                                        textarea.value = mydata["aremarks"];
                                    } else {
                                        textarea.value = ""; // or any default value you want to set when remarks is null
                                    }

                                    if (mydata["verification_status"] == 'Rejected') {
                                        document.getElementById("archiveupdate").disabled = true;
                                    } else {
                                        document.getElementById("archiveupdate").disabled = false;
                                    }
                                }

                                // When the user clicks the button, open the modal 
                                // When the user clicks on <span> (x), close the modal
                                closedetails.forEach(function(element) {
                                    element.addEventListener("click", closeModal);
                                });

                                function closeModal() {
                                    var modal1 = document.getElementById("myModal");
                                    modal1.style.display = "none";
                                }
                                // When the user clicks anywhere outside of the modal, close it
                                // window.onclick = function(event) {
                                //     if (event.target == modal) {
                                //         modal.style.display = "none";
                                //     }
                                // }
                            </script>

                            <script>
                                var data = <?php echo json_encode($resultArr) ?>;
                                //For form submission - to update Remarks
                                const scriptURL = 'payment-api.php'

                                function validateForm() {
                                    if (confirm('Are you sure you want to delete this record? Once you click OK the record cannot be reverted.')) {

                                        data.forEach(item => {
                                            const form = document.forms['archivedelete_' + item.doc_id]
                                            form.addEventListener('submit', e => {
                                                e.preventDefault()
                                                fetch(scriptURL, {
                                                        method: 'POST',
                                                        body: new FormData(document.forms['archivedelete_' + item.doc_id])
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

                                const form = document.getElementById('archiveform')
                                form.addEventListener('submit', e => {
                                    e.preventDefault()
                                    fetch(scriptURL, {
                                            method: 'POST',
                                            body: new FormData(document.getElementById('archiveform'))
                                        })
                                        .then(response =>
                                            alert("Record has been updated.") +
                                            location.reload()
                                        )
                                        .catch(error => console.error('Error!', error.message))
                                })

                                // data.forEach(item => {
                                //     const formId = 'email-form-' + item.doc_id
                                //     const form = document.forms[formId]
                                //     form.addEventListener('submit', e => {
                                //         e.preventDefault()
                                //         fetch('mailer.php', {
                                //                 method: 'POST',
                                //                 body: new FormData(document.forms[formId])
                                //             })
                                //             .then(response =>
                                //                 alert("Email has been sent.")
                                //             )
                                //             .catch(error => console.error('Error!', error.message))
                                //     })
                                // })
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


</body>

</html>