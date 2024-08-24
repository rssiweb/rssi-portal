<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");
include("../../util/drive.php");


if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation(); ?>

<?php if ($role == 'Admin') { ?>

<?php if ($_POST) {
        $policyid = uniqid();
        $policytype = $_POST['policytype'];
        $policyname = $_POST['policyname'];
        $remarks = $_POST['remarks'];
        $issuedon = date('Y-m-d H:i:s');
        // Upload and insert passbook page if provided
        if (!empty($_FILES['policydoc']['name'])) {
            $policydoc = $_FILES['policydoc'];
            $filename = $policyid . "_" . $policyname . "_" . time();
            $parent = '1KlYwKIuAHkWdYJkT3SdhW1nx_T6tZqXA';
            $doclink = uploadeToDrive($policydoc, $parent, $filename);
        }
        if ($doclink !== null) {
            $policy = "INSERT INTO policy (policyid, policytype, policyname, remarks, policydoc, issuedby, issuedon) VALUES ('$policyid', '$policytype', '$policyname', '$remarks', '$doclink', '$associatenumber', '$issuedon')";
        }
        $result = pg_query($con, $policy);
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

    <title>Info Hub</title>

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
            <h1>Info Hub</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Work</a></li>
                    <li class="breadcrumb-item active">Info Hub</li>
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
                            <?php if ($role == 'Admin') { ?>
                                <?php if (@$policyid != null && @$cmdtuples == 0) { ?>

                                    <div class="alert alert-danger alert-dismissible" role="alert" style="text-align: -webkit-center;">
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                        <i class="bi bi-exclamation-triangle"></i>
                                        <span>ERROR: Oops, something wasn't right.</span>
                                    </div>
                                <?php
                                } else if (@$cmdtuples == 1) { ?>

                                    <div class="alert alert-success alert-dismissible" role="alert" style="text-align: -webkit-center;">
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                        <i class="bi bi-check2-circle"></i>
                                        <span>Database has been updated successfully for policy id <?php echo @$policyid ?>.</span>
                                    </div>
                                    <script>
                                        if (window.history.replaceState) {
                                            window.history.replaceState(null, null, window.location.href);
                                        }
                                    </script>
                                <?php } ?>
                            <?php } ?>

                            <?php if ($role == 'Admin') { ?>
                                <form autocomplete="off" name="policy" id="policy" action="infohub.php" method="POST" enctype="multipart/form-data">
                                    <div class="form-group" style="display: inline-block;">
                                        <div class="col2" style="display: inline-block;">

                                            <span class="input-help">
                                                <select name="policytype" class="form-select" style="width:max-content; display:inline-block" required>
                                                    <?php if ($category == null) { ?>
                                                        <option value="" disabled selected hidden>Category</option>
                                                    <?php
                                                    } else { ?>
                                                        <option hidden selected><?php echo $category ?></option>
                                                    <?php }
                                                    ?>
                                                    <option>Internal</option>
                                                    <option>Confidential</option>
                                                    <option>Public</option>
                                                    <option>HR Policy</option>
                                                </select>
                                                <small id="passwordHelpBlock" class="form-text text-muted">Category</small>
                                            </span>

                                            <span class="input-help">
                                                <input type="text" name="policyname" class="form-control" style="width:max-content; display:inline-block" placeholder="Policy name" value="" required></input>
                                                <small id="passwordHelpBlock" class="form-text text-muted">Policy name</small>
                                            </span>

                                            <span class="input-help">
                                                <textarea type="text" name="remarks" class="form-control" style="width:max-content; display:inline-block" placeholder="Remarks" value=""></textarea>
                                                <small id="passwordHelpBlock" class="form-text text-muted">Remarks</small>
                                            </span>

                                            <span class="input-help">
                                                <input class="form-control" type="file" id="policydoc" name="policydoc" required>
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

                            $result = pg_query($con, "SELECT * FROM policy order by issuedon desc");
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
                                            <th>Policy Id</th>
                                            <th>Category</th>
                                            <th>Date</th>
                                            <th>Policy name</th>
                                            <th>Details</th>
                                            <th>Policy document</th>
                                            <?php if ($role == 'Admin') { ?>
                                                <th></th>
                                            <?php } ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($resultArr as $array) { ?>
                                            <tr>
                                                <td><?php echo $array['policyid']; ?></td>
                                                <td><?php echo $array['policytype']; ?></td>
                                                <td><?php echo @date("d/m/Y g:i a", strtotime($array['issuedon'])); ?></td>
                                                <td><?php echo $array['policyname']; ?></td>
                                                <td>
                                                    <form name="policybody_<?php echo $array['policyid']; ?>" action="#" method="POST" style="display: -webkit-inline-box;">
                                                        <input type="hidden" name="form-type" value="policybodyedit">
                                                        <input type="hidden" name="policyid" value="<?php echo $array['policyid']; ?>">
                                                        <textarea id="inp_<?php echo $array['policyid']; ?>" name="remarks" disabled><?php echo $array['remarks']; ?></textarea>

                                                        <?php if ($role == 'Admin') { ?>
                                                            &nbsp;
                                                            <button type="button" id="edit_<?php echo $array['policyid']; ?>" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Edit">
                                                                <i class="bi bi-pencil-square"></i>
                                                            </button>&nbsp;
                                                            <button type="submit" id="save_<?php echo $array['policyid']; ?>" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Save">
                                                                <i class="bi bi-save"></i>
                                                            </button>
                                                        <?php } ?>
                                                    </form>
                                                </td>

                                                <?php if ($array['policydoc'] == null) { ?>
                                                    <td></td>
                                                <?php } else { ?>
                                                    <td><a href="<?php echo $array['policydoc']; ?>" target="_blank"><i class="bi bi-file-earmark-pdf" style="color:#777777" title="<?php echo $array['policyid']; ?>"></i></a></td>
                                                <?php } ?>

                                                <?php if ($role == 'Admin') { ?>
                                                    <td>
                                                        <form name="policydelete_<?php echo $array['policyid']; ?>" action="#" method="POST" style="display: -webkit-inline-box;">
                                                            <input type="hidden" name="form-type" value="policydelete">
                                                            <input type="hidden" name="policydeleteid" value="<?php echo $array['policyid']; ?>">
                                                            <button type="submit" onclick="validateForm()" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Delete <?php echo $array['policyid']; ?>">
                                                                <i class="bi bi-x-lg"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                <?php } ?>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>';
                            ?>
                            <script>
                                var data = <?php echo json_encode($resultArr) ?>;

                                data.forEach(item => {

                                    const form = document.getElementById('edit_' + item.policyid);

                                    form.addEventListener('click', function() {
                                        document.getElementById('inp_' + item.policyid).disabled = false;
                                    });
                                })

                                //For form submission - to update Remarks
                                const scriptURL = 'payment-api.php'

                                data.forEach(item => {
                                    const form = document.forms['policybody_' + item.policyid]
                                    form.addEventListener('submit', e => {
                                        e.preventDefault()
                                        fetch(scriptURL, {
                                                method: 'POST',
                                                body: new FormData(document.forms['policybody_' + item.policyid])
                                            })
                                            .then(response => alert("Record has been updated.") +
                                                location.reload())
                                            .catch(error => console.error('Error!', error.message))
                                    })

                                    console.log(item)
                                })

                                function validateForm() {
                                    if (confirm('Are you sure you want to delete this record? Once you click OK the record cannot be reverted.')) {

                                        data.forEach(item => {
                                            const form = document.forms['policydelete_' + item.policyid]
                                            form.addEventListener('submit', e => {
                                                e.preventDefault()
                                                fetch(scriptURL, {
                                                        method: 'POST',
                                                        body: new FormData(document.forms['policydelete_' + item.policyid])
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

</body>

</html>