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
?>
<?php
if (@$_POST['form-type'] == "reviewer_remarks_update") {
    $goalsheetid = $_POST['goalsheetid'];
    $appraisee_associatenumber = $_POST['appraisee_associatenumber'];
    $manager_associatenumber = $_POST['manager_associatenumber'];
    $reviewer_associatenumber = $_POST['reviewer_associatenumber'];
    $reviewer_remarks = $_POST['reviewer_remarks'];
    $goalsheet_reviewed_by = $associatenumber;
    $goalsheet_reviewed_on = date('Y-m-d H:i:s');



    $reviewer_remarks_update = "UPDATE appraisee_response
    SET reviewer_response_complete = 'yes',
    reviewer_remarks = '$reviewer_remarks',
    goalsheet_reviewed_by = '$goalsheet_reviewed_by',
    goalsheet_reviewed_on = '$goalsheet_reviewed_on',
    ipf_response = null,
    ipf_response_on= null,
    ipf_response_by= null
    WHERE goalsheetid='$goalsheetid'";

    $result = pg_query($con, $reviewer_remarks_update);
    $cmdtuples = pg_affected_rows($result);
    if (!$result) {
        echo "An error occurred.\n";
        exit;
    }

    $resultArr = pg_fetch_all($result);

    $result_a = pg_query($con, "SELECT fullname,email
FROM appraisee_response
LEFT JOIN rssimyaccount_members ON rssimyaccount_members.associatenumber = appraisee_response.appraisee_associatenumber WHERE goalsheetid = '$goalsheetid'");
    @$appraisee_name = pg_fetch_result($result_a, 0, 0);
    @$appraisee_email = pg_fetch_result($result_a, 0, 1);


    $result_m = pg_query($con, "SELECT fullname, email
FROM appraisee_response
LEFT JOIN rssimyaccount_members ON rssimyaccount_members.associatenumber = appraisee_response.manager_associatenumber WHERE goalsheetid = '$goalsheetid'");
    @$manager_name = pg_fetch_result($result_m, 0, 0);
    @$manager_email = pg_fetch_result($result_m, 0, 1);


    $result_r = pg_query($con, "SELECT fullname, email
FROM appraisee_response
LEFT JOIN rssimyaccount_members ON rssimyaccount_members.associatenumber = appraisee_response.reviewer_associatenumber WHERE goalsheetid = '$goalsheetid'");
    @$reviewer_name = pg_fetch_result($result_r, 0, 0);
    @$reviewer_email = pg_fetch_result($result_r, 0, 1);


    $result_appraisal_details = pg_query($con, "SELECT appraisaltype, appraisalyear, appraisee_associatenumber FROM appraisee_response WHERE goalsheetid = '$goalsheetid'");
    @$appraisaltype = pg_fetch_result($result_appraisal_details, 0, 0);
    @$appraisalyear = pg_fetch_result($result_appraisal_details, 0, 1);
    @$appraisee_associatenumber = pg_fetch_result($result_appraisal_details, 0, 2);

    if (@$cmdtuples == 1 && $appraisee_email != "") {
        sendEmail("ipf_issued", array(
            "goalsheetid" => $goalsheetid,
            "appraisaltype" => @$appraisaltype,
            "appraisalyear" => @$appraisalyear,
            "appraisee_name" => @$appraisee_name,
            "appraiseeemail" => @$appraisee_email,
            "appraiseeid" => @$appraisee_associatenumber,
            "manager_name" => @$manager_name,
            "reviewer_name" => @$reviewer_name,
            "link" => "https://login.rssi.in/rssi-member/my_appraisal.php?form-type=appraisee&get_id=" . urlencode(@$appraisaltype) . "&get_year=" . @$appraisalyear,
        ), $appraisee_email);
    }
} ?>

<head>
    <meta name="description" content="">
    <meta name="author" content="">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Reviewer Response</title>
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.0/css/bootstrap.min.css">
    <script src="https://kit.fontawesome.com/58c4cdb942.js" crossorigin="anonymous"></script>
</head>

<?php if (@$cmdtuples == 0) { ?>

    <div class="container">
        <div class="alert alert-danger" role="alert">
            <h4 class="alert-heading">Error submitting Goal Sheet!</h4>
            <hr>
            <p>Unfortunately, there was an error submitting your goal sheet. Please try again later or contact support for assistance.</p>
            <a href="reviewer_response.php" class="btn btn-primary">Back to Goal Sheet</a>
        </div>
    </div>

<?php } else if (@$cmdtuples == 1) { ?>

    <div class="container">
        <div class="alert alert-success" role="alert">
            <h4 class="alert-heading">Goal Sheet has been submitted successfully!</h4>
            <hr>
            <p>Your goal sheet has been submitted. The unique goal sheet ID is <?php echo $goalsheetid ?>.</p>
            <a href="reviewer_response.php?goalsheetid=<?php echo $goalsheetid ?>" class="btn btn-primary">Back to Goal Sheet</a>
        </div>
    </div>

    <script>
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
<?php } ?>