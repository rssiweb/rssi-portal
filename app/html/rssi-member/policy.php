<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");


if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

if ($password_updated_by == null || $password_updated_on < $default_pass_updated_on) {

    echo '<script type="text/javascript">';
    echo 'window.location.href = "defaultpasswordreset.php";';
    echo '</script>';
} ?>

<?php if ($role == 'Admin') { ?>

<?php if ($_POST) {
        @$policyid = "POL" . time();
        @$policytype = $_POST['policytype'];
        @$policyname = $_POST['policyname'];
        @$remarks = $_POST['remarks'];
        @$policydoc = $_POST['policydoc'];
        @$issuedby = $associatenumber;
        @$issuedon = date('Y-m-d H:i:s');
        if ($policyid != "") {
            $policy = "INSERT INTO policy (policyid, policytype, policyname, remarks, policydoc, issuedby, issuedon) VALUES ('$policyid', '$policytype', '$policyname', '$remarks', '$policydoc', '$issuedby', '$issuedon')";
            $result = pg_query($con, $policy);
            $cmdtuples = pg_affected_rows($result);
        }
    }
} ?>

<!doctype html>
<html lang="en">

<head>
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=AW-11316670180"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'AW-11316670180');
</script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>HR Policy</title>

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

    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>HR Policy</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Work</a></li>
                    <li class="breadcrumb-item active">HR Policy</li>
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
                                        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                                        <span class="blink_me"><i class="bi bi-exclamation-triangle"></i></span>&nbsp;&nbsp;<span>ERROR: Oops, something wasn't right.</span>
                                    </div>
                                <?php
                                } else if (@$cmdtuples == 1) { ?>

                                    <div class="alert alert-success alert-dismissible" role="alert" style="text-align: -webkit-center;">
                                        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                                        <i class="bi bi-check2-circle" style="font-size: medium;"></i></span>&nbsp;&nbsp;<span>Database has been updated successfully for policy id <?php echo @$policyid ?>.</span>
                                    </div>
                                    <script>
                                        if (window.history.replaceState) {
                                            window.history.replaceState(null, null, window.location.href);
                                        }
                                    </script>
                                <?php } ?>
                            <?php } ?>

                            <?php if ($role == 'Admin') { ?>
                                <form autocomplete="off" name="pms" id="pms" action="policy.php" method="POST">
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
                                                </select>
                                                <small id="passwordHelpBlock" class="form-text text-muted">Category</small>
                                            </span>

                                            <span class="input-help">
                                                <input type="text" name="policyname" class="form-control" style="width:max-content; display:inline-block" placeholder="Policy name" value=""></input>
                                                <small id="passwordHelpBlock" class="form-text text-muted">Policy name</small>
                                            </span>

                                            <span class="input-help">
                                                <textarea type="text" name="remarks" class="form-control" style="width:max-content; display:inline-block" placeholder="Remarks" value=""></textarea>
                                                <small id="passwordHelpBlock" class="form-text text-muted">Remarks</small>
                                            </span>

                                            <span class="input-help">
                                                <input type="policydoc" name="policydoc" class="form-control" style="width:max-content; display:inline-block" placeholder="URL" value="">
                                                <small id="passwordHelpBlock" class="form-text text-muted">URL</small>
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

                            <?php echo '
                    <p>Select Number Of Rows</p>
                    <div class="form-group">
                        <!--		Show Numbers Of Rows 		-->
                        <select class="form-select" name="state" id="maxRows">
                            <option value="5000">Show ALL Rows</option>
                            <option value="5">5</option>
                            <option value="10">10</option>
                            <option value="15">15</option>
                            <option value="20">20</option>
                            <option value="50">50</option>
                            <option value="70">70</option>
                            <option value="100">100</option>
                        </select>
            
                    </div>
                    <div class="table-responsive">
                    <table class="table" id="table-id">
                        <thead>
                            <tr>
                            <th scope="col">Policy Id</th>
                            <th scope="col">Policy name</th>
                            <th scope="col">Details</th>
                            <th scope="col">Policy document</th>' ?>
                            <?php if ($role == 'Admin') { ?>
                                <?php echo '<th></th>' ?>
                            <?php } ?>
                            <?php echo '</tr>
                        </thead>
                        <tbody>';
                            foreach ($resultArr as $array) {
                                echo '
                            <tr>
                                <td>' . $array['policyid'] . '</td>
                                <td>' . $array['policyname'] . '&nbsp;<p class="badge bg-secondary">' . $array['policytype'] . '</p></td>
                                <td>
                                
                                <form name="policybody_' . $array['policyid'] . '" action="#" method="POST" style="display: -webkit-inline-box;">
                                <input type="hidden" name="form-type" type="text" value="policybodyedit">
                                <input type="hidden" name="policyid" id="policyid" type="text" value="' . $array['policyid'] . '">
                                <textarea id="inp_' . $array['policyid'] . '" name="remarks" type="text" disabled>' . $array['remarks'] . '</textarea>' ?>

                                <?php if ($role == 'Admin') { ?>

                                    <?php echo '&nbsp;

                                <button type="button" id="edit_' . $array['policyid'] . '" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Edit"><i class="bi bi-pencil-square"></i></button>&nbsp;

                                <button type="submit" id="save_' . $array['policyid'] . '" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Save"><i class="bi bi-save"></i></button>
                                
                                ' ?>

                                <?php } ?>

                                <?php echo '</form></td>' ?>

                                <?php if ($array['policydoc'] == null) { ?>
                                    <?php echo '<td></td>' ?>

                                <?php } else { ?>

                                    <?php echo '<td><a href="' . $array['policydoc'] . '" target="_blank"><i class="bi bi-file-earmark-pdf" style="color:#777777" title="' . $array['policyid'] . '" display:inline;></i></a></td>'; ?>
                                <?php } ?>

                                <?php if ($role == 'Admin') { ?>
                                    <?php echo '<td><form name="policydelete_' . $array['policyid'] . '" action="#" method="POST" style="display: -webkit-inline-box;">
                                    <input type="hidden" name="form-type1" type="text" value="policydelete">
                                    <input type="hidden" name="policydeleteid" id="policydeleteid" type="text" value="' . $array['policyid'] . '">

                                    <button type="submit" onclick=validateForm() style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Delete ' . $array['policyid'] . '"><i class="bi bi-x-lg"></i></button>
                                    </td>' ?>
                                <?php } ?>
                            <?php }
                            echo '</form></tr></tbody>
                        </table>
                        </div>';
                            ?>
                            <!-- Start Pagination -->
                            <div class="pagination-container">
                                <nav>
                                    <ul class="pagination">
                                        <li class="page-item" data-page="prev">
                                            <button class="page-link pagination-button" aria-label="Previous">&lt;</button>
                                        </li>
                                        <!-- Here the JS Function Will Add the Rows -->
                                        <li class="page-item">
                                            <button class="page-link pagination-button">1</button>
                                        </li>
                                        <li class="page-item">
                                            <button class="page-link pagination-button">2</button>
                                        </li>
                                        <li class="page-item">
                                            <button class="page-link pagination-button">3</button>
                                        </li>
                                        <li class="page-item" data-page="next" id="prev">
                                            <button class="page-link pagination-button" aria-label="Next">&gt;</button>
                                        </li>
                                    </ul>
                                </nav>
                            </div>

                            <script>
                                getPagination('#table-id');

                                function getPagination(table) {
                                    var lastPage = 1;

                                    $('#maxRows').on('change', function(evt) {
                                        lastPage = 1;
                                        $('.pagination').find('li').slice(1, -1).remove();
                                        var trnum = 0;
                                        var maxRows = parseInt($(this).val());

                                        if (maxRows == 5000) {
                                            $('.pagination').hide();
                                        } else {
                                            $('.pagination').show();
                                        }

                                        var totalRows = $(table + ' tbody tr').length;
                                        $(table + ' tr:gt(0)').each(function() {
                                            trnum++;
                                            if (trnum > maxRows) {
                                                $(this).hide();
                                            }
                                            if (trnum <= maxRows) {
                                                $(this).show();
                                            }
                                        });

                                        if (totalRows > maxRows) {
                                            var pagenum = Math.ceil(totalRows / maxRows);
                                            for (var i = 1; i <= pagenum; i++) {
                                                $('.pagination #prev').before('<li class="page-item" data-page="' + i + '">\
                                                <button class="page-link pagination-button">' + i + '</button>\
                                                </li>').show();
                                            }
                                        }

                                        $('.pagination [data-page="1"]').addClass('active');
                                        $('.pagination li').on('click', function(evt) {
                                            evt.stopImmediatePropagation();
                                            evt.preventDefault();
                                            var pageNum = $(this).attr('data-page');

                                            var maxRows = parseInt($('#maxRows').val());

                                            if (pageNum == 'prev') {
                                                if (lastPage == 1) {
                                                    return;
                                                }
                                                pageNum = --lastPage;
                                            }
                                            if (pageNum == 'next') {
                                                if (lastPage == $('.pagination li').length - 2) {
                                                    return;
                                                }
                                                pageNum = ++lastPage;
                                            }

                                            lastPage = pageNum;
                                            var trIndex = 0;
                                            $('.pagination li').removeClass('active');
                                            $('.pagination [data-page="' + lastPage + '"]').addClass('active');
                                            limitPagging();
                                            $(table + ' tr:gt(0)').each(function() {
                                                trIndex++;
                                                if (
                                                    trIndex > maxRows * pageNum ||
                                                    trIndex <= maxRows * pageNum - maxRows
                                                ) {
                                                    $(this).hide();
                                                } else {
                                                    $(this).show();
                                                }
                                            });
                                        });
                                        limitPagging();
                                    }).val(5).change();
                                }

                                function limitPagging() {
                                    if ($('.pagination li').length > 7) {
                                        if ($('.pagination li.active').attr('data-page') <= 3) {
                                            $('.pagination li.page-item:gt(5)').hide();
                                            $('.pagination li.page-item:lt(5)').show();
                                            $('.pagination [data-page="next"]').show();
                                        }
                                        if ($('.pagination li.active').attr('data-page') > 3) {
                                            $('.pagination li.page-item').hide();
                                            $('.pagination [data-page="next"]').show();
                                            var currentPage = parseInt($('.pagination li.active').attr('data-page'));
                                            for (let i = currentPage - 2; i <= currentPage + 2; i++) {
                                                $('.pagination [data-page="' + i + '"]').show();
                                            }
                                        }
                                    }
                                }
                            </script>
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

</body>

</html>