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

if ($password_updated_by == null || $password_updated_on < $default_pass_updated_on) {

    echo '<script type="text/javascript">';
    echo 'window.location.href = "defaultpasswordreset.php";';
    echo '</script>';
}

if ($role != 'Admin') {

    echo '<script type="text/javascript">';
    echo 'alert("Access Denied. You are not authorized to access this web page.");';
    echo 'window.location.href = "home.php";';
    echo '</script>';
}
?>

<?php
// Retrieve student ID from form input
@$applicant_search = $_GET['applicant_search'];
// Query database for student information based on ID

$result = pg_query($con, "SELECT * FROM candidatepool WHERE application_number = '$applicant_search'");
$resultArr = pg_fetch_all($result);

if (!$result) {
    echo "An error occurred.\n";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="description" content="">
    <meta name="author" content="">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Interview Scheduling Form</title>
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.0/css/bootstrap.min.css">
    <script src="https://kit.fontawesome.com/58c4cdb942.js" crossorigin="anonymous"></script>
</head>

<body>
    <?php if (@$application_number != null && @$cmdtuples == 0) { ?>

        <div class="alert alert-danger alert-dismissible fade show" role="alert" style="text-align: -webkit-center;">
            Oops, something went wrong! We were unable to schedule the interview for the application number <?php echo $application_number ?>. Please try again later or contact support for assistance.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php } else if (@$cmdtuples == 1) { ?>

        <div class="alert alert-success alert-dismissible fade show" role="alert" style="text-align: -webkit-center;">
            Congratulations! The interview has been scheduled for the application number <?php echo $application_number ?> has been generated successfully. The unique goal sheet ID is <?php echo $goalsheetid ?>.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <script>
            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.href);
            }
        </script>
    <?php } ?>
    <div class="container py-5">
        <form method="GET" action="">
            <div class="form-group mb-3">
                <label for="applicant_search" class="form-label">Application number:</label>
                <input type="text" class="form-control" id="applicant_search" name="applicant_search" value="<?php echo $applicant_search ?>" required>
            </div>
            <input type="submit" name="submit" value="Search" class="btn btn-primary">
        </form>

        <br>
        <h1 class="mb-4">Interview Scheduling Form</h1>
        <?php if (sizeof($resultArr) > 0) { ?>
            <?php
            foreach ($resultArr as $array) {
            ?>
                <form method="post" name="interview_1" id="interview_1" action="interview_1.php">
                    <div class="form-group mb-3">
                        <label for="application-number" class="form-label">Application Number:</label>
                        <input type="text" class="form-control" id="application-number" name="application-number" value="<?php echo $array['application_number'] ?>" required readonly>
                    </div>

                    <div class="form-group mb-3">
                        <label for="candidate-name" class="form-label">Candidate Name:</label>
                        <div class="row">
                            <div class="col">
                                <input type="text" class="form-control" id="applicant_f_name" name="applicant_f_name" value="<?php echo $array['applicant_f_name'] ?>" required readonly>
                            </div>
                            <div class="col">
                                <input type="text" class="form-control" id="applicant_l_name" name="applicant_l_name" value="<?php echo $array['applicant_l_name'] ?>" readonly>
                            </div>
                        </div>
                    </div>


                    <div class="form-group mb-3">
                        <label for="position-applied-for" class="form-label">Position Applied For:</label>
                        <input type="text" class="form-control" id="position-applied-for" name="position-applied-for" value="<?php echo $array['association_type'] ?>" required readonly>
                    </div>

                    <div class="form-group mb-3">
                        <label for="email" class="form-label">Email ID:</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo $array['email'] ?>" required readonly>
                    </div>

                    <div class="form-group mb-3">
                        <label for="phone-number" class="form-label">Phone Number:</label>
                        <input type="tel" class="form-control" id="phone-number" name="phone-number" value="<?php echo $array['contact'] ?>" required readonly>
                    </div>
                    <div class="form-group mb-3">
                        <label for="resume" class="form-label">Resume:</label>
                        <input type="url" class="form-control" id="resume" name="resume" value="<?php echo $array['cv'] ?>" required readonly>
                    </div>

                    <div class="row">
                        <div class="form-group col-md-6 mb-3">
                            <label for="identity-verification" class="form-label">Identity Verification:</label>
                            <input type="text" class="form-control" id="identity-verification" name="identity-verification" value="<?php echo $array['national_identifier_number'] ?>" required readonly>
                        </div>
                        <div class="form-group col-md-6 mb-3">
                            <label for="supportingdoc" class="form-label">Supporting Document:</label>
                            <input type="text" class="form-control" id="supportingdoc" name="supportingdoc" value="<?php echo $array['supporting_document'] ?>" required readonly>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label for="interview-location" class="form-label">Interview Location:</label>
                        <input type="text" class="form-control" id="interview-location" name="interview-location" value="<?php echo $array['base_branch'] ?>" required>
                    </div>

                    <div class="row">
                        <div class="form-group col-md-6 mb-3">
                            <label for="interview-date" class="form-label">Interview Date:</label>
                            <input type="date" class="form-control" id="interview-date" name="interview-date" required>
                        </div>
                        <div class="form-group col-md-6 mb-3">
                            <label for="interview-time" class="form-label">Reporting Time:</label>
                            <input type="time" class="form-control" id="interview-time" name="interview-time" required>
                        </div>
                    </div>
                    <div class="form-group mb-3">
                        <label for="reporting-address" class="form-label">Reporting Address:</label>
                        <textarea class="form-control" id="reporting-address" name="reporting-address" rows="4" required>624V/195/01, Vijayipur, Vijaipur Village, Vishesh Khand 2, Gomti Nagar, Lucknow, Uttar Pradesh 226010</textarea>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="rssi-tet" name="rssi-tet">
                        <label class="form-check-label" for="rssi-tet">RSSI TET Required</label>
                    </div>


                    <button type="submit" class="btn btn-primary">Schedule</button>
                </form>
        <?php }
        } ?>
    </div>

    <!-- Bootstrap JS -->

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.0/js/bootstrap.min.js"></script>
    <script>
        $(document).ready(function() {
            $('input[required], select[required], textarea[required]').each(function() {
                $(this).closest('.form-group').find('label').append(' <span style="color: red">*</span>');
            });
        });
    </script>
</body>

</html>