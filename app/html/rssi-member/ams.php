<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");
include("../../util/drive.php");


if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();

date_default_timezone_set('Asia/Kolkata');
if ($role == 'Admin') {
    if ($_POST) {
        $noticeid = uniqid();
        $refnumber = $_POST['refnumber'];
        $category = $_POST['category'];
        $noticesub = $_POST['noticesub'];
        $noticebody = htmlspecialchars($_POST['noticebody'], ENT_QUOTES, 'UTF-8');
        $now = date('Y-m-d H:i:s');
        // Upload and insert passbook page if provided
        if (!empty($_FILES['notice']['name'])) {
            $notice = $_FILES['notice'];
            $filename = $noticeid . "_notice_" . time();
            $parent = '1LPHtex89XQK_HPmMsbthQyQXlLmyQuJ0';
            $doclink = uploadeToDrive($notice, $parent, $filename);
        }
        if ($doclink !== null) {
            // Insert passbook page into bankdetails table
            $notice = "INSERT INTO notice (noticeid, refnumber, date, subject, url, issuedby, category, noticebody) VALUES ('$noticeid','$refnumber','$now','$noticesub','$doclink','$associatenumber','$category','$noticebody')";
        } else {
            $notice = "INSERT INTO notice (noticeid, refnumber, date, subject, issuedby, category, noticebody) VALUES ('$noticeid','$refnumber','$now','$noticesub','$associatenumber','$category','$noticebody')";
        }
        $result = pg_query($con, $notice);
        $cmdtuples = pg_affected_rows($result);
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

    <title>Announcement</title>

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
    <!-- CSS Library Files -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
    <!-- JavaScript Library Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>

</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Announcement</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Work</a></li>
                    <li class="breadcrumb-item active">Announcement</li>
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
                            <?php if (@$noticeid != null && @$cmdtuples == 0) { ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert" style="text-align: -webkit-center;">
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    <i class="bi bi-exclamation-triangle"></i>
                                    <span>ERROR: Oops, something wasn't right.</span>
                                </div>
                            <?php } else if (@$cmdtuples == 1) { ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert" style="text-align: -webkit-center;">
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    <i class="bi bi-check2-circle"></i>
                                    <span>Database has been updated successfully for notice id <?php echo @$noticeid ?>.</span>
                                </div>
                                <script>
                                    if (window.history.replaceState) {
                                        window.history.replaceState(null, null, window.location.href);
                                    }
                                </script>
                            <?php } ?>
                            <?php if ($role == 'Admin') { ?>
                                <form autocomplete="off" name="ams" id="ams" action="ams.php" method="POST" enctype="multipart/form-data">
                                    <div class="form-group" style="display: inline-block;">
                                        <div class="col2" style="display: inline-block;">

                                            <span class="input-help">
                                                <input type="text" name="refnumber" class="form-control" style="width:max-content; display:inline-block" placeholder="Ref. Number" required>
                                                <small id="passwordHelpBlock" class="form-text text-muted">Ref. Number</small>
                                            </span>

                                            <span class="input-help">
                                                <select name="category" class="form-select" style="width:max-content; display:inline-block" required>
                                                    <?php if ($category == null) { ?>
                                                        <option disabled selected hidden>Category</option>
                                                    <?php
                                                    } else { ?>
                                                        <option hidden selected><?php echo $category ?></option>
                                                    <?php }
                                                    ?>
                                                    <option>Internal</option>
                                                    <option>Public</option>
                                                </select>
                                                <small id="passwordHelpBlock" class="form-text text-muted">Category</small>
                                            </span>

                                            <span class="input-help">
                                                <input type="text" name="noticesub" class="form-control" style="width:max-content; display:inline-block" placeholder="Notice Subject" value=""></input>
                                                <small id="passwordHelpBlock" class="form-text text-muted">Subject</small>
                                            </span>

                                            <span class="input-help">
                                                <textarea type="text" name="noticebody" class="form-control" style="width:max-content; display:inline-block" placeholder="Notice Body" value=""></textarea>
                                                <small id="passwordHelpBlock" class="form-text text-muted">Details</small>
                                            </span>

                                            <span class="input-help">
                                                <input class="form-control" type="file" id="notice" name="notice" required>
                                                <div class="form-text">Upload File</div>
                                            </span>

                                        </div>

                                    </div>

                                    <div class="col2 left" style="display: inline-block;">
                                        <button type="submit" name="search_by_id" class="btn btn-danger btn-sm" style="outline: none;">
                                            <i class="bi bi-plus-lg"></i>&nbsp;&nbsp;Add</button>
                                    </div>
                                </form>
                            <?php } ?>
                            <?php

                            $result = pg_query($con, "SELECT * FROM notice order by date desc");
                            if (!$result) {
                                echo "An error occurred.\n";
                                exit;
                            }

                            $resultArr = pg_fetch_all($result);
                            ?>

                            <div class="table-responsive">
                                <table class="table" id="table-id">
                                    <thead>
                                        <tr>
                                            <th>Notice Id</th>
                                            <th>Ref. Number</th>
                                            <th>Category</th>
                                            <th>Date</th>
                                            <th>Subject</th>
                                            <th>Details</th>
                                            <th>Document</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($resultArr as $array) { ?>
                                            <tr>
                                                <td><?php echo $array['noticeid']; ?></td>
                                                <td><?php echo $array['refnumber']; ?></td>
                                                <td><?php echo $array['category']; ?></td>
                                                <td><?php echo @date("d/m/Y g:i a", strtotime($array['date'])); ?></td>
                                                <td><?php echo $array['subject']; ?></td>
                                                <td>
                                                    <form name="noticebody_<?php echo $array['noticeid']; ?>" action="#" method="POST" style="display: -webkit-inline-box;">
                                                        <input type="hidden" name="form-type" value="noticebodyedit">
                                                        <input type="hidden" name="noticeid" value="<?php echo $array['noticeid']; ?>">
                                                        <textarea id="inp_<?php echo $array['noticeid']; ?>" name="noticebody" disabled><?php echo $array['noticebody']; ?></textarea>

                                                        <?php if ($role == 'Admin') { ?>
                                                            &nbsp;
                                                            <button type="button" id="edit_<?php echo $array['noticeid']; ?>" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Edit">
                                                                <i class="bi bi-pencil-square"></i>
                                                            </button>&nbsp;
                                                            <button type="submit" id="save_<?php echo $array['noticeid']; ?>" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Save">
                                                                <i class="bi bi-save"></i>
                                                            </button>
                                                        <?php } ?>
                                                    </form>
                                                </td>
                                                <?php if ($array['url'] == null) { ?>
                                                    <td></td>
                                                <?php } else { ?>
                                                    <td><a href="<?php echo $array['url']; ?>" target="_blank"><i class="bi bi-file-earmark-pdf" style="font-size: 16px; color:#777777" title="<?php echo $array['noticeid']; ?>"></i></a></td>
                                                <?php } ?>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>

                            <script>
                                var data = <?php echo json_encode($resultArr) ?>;

                                data.forEach(item => {

                                    const form = document.getElementById('edit_' + item.noticeid);

                                    form.addEventListener('click', function() {
                                        document.getElementById('inp_' + item.noticeid).disabled = false;
                                    });
                                })

                                //For form submission - to update Remarks
                                const scriptURL = 'payment-api.php'

                                data.forEach(item => {
                                    const form = document.forms['noticebody_' + item.noticeid]
                                    form.addEventListener('submit', e => {
                                        e.preventDefault()
                                        fetch(scriptURL, {
                                                method: 'POST',
                                                body: new FormData(document.forms['noticebody_' + item.noticeid])
                                            })
                                            .then(response => alert("Notice body has been updated.") +
                                                location.reload())
                                            .catch(error => console.error('Error!', error.message))
                                    })

                                    console.log(item)
                                })
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

</body>

</html>