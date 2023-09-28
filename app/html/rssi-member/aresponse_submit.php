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
// if (@$_POST['form-type'] == "appraisee_response_update") {
//     $goalsheetid = $_POST['goalsheetid'];
//     $appraisee_associatenumber = $_POST['appraisee_associatenumber'];
//     $appraisee_response_1 = $_POST['appraisee_response_1'];
//     $appraisee_response_2 = $_POST['appraisee_response_2'];
//     $appraisee_response_3 = $_POST['appraisee_response_3'];
//     $appraisee_response_4 = $_POST['appraisee_response_4'];
//     $appraisee_response_5 = $_POST['appraisee_response_5'];
//     $appraisee_response_6 = $_POST['appraisee_response_6'];
//     $appraisee_response_7 = $_POST['appraisee_response_7'];
//     $appraisee_response_8 = $_POST['appraisee_response_8'];
//     $appraisee_response_9 = $_POST['appraisee_response_9'];
//     $appraisee_response_10 = $_POST['appraisee_response_10'];
//     $appraisee_response_11 = $_POST['appraisee_response_11'];
//     $appraisee_response_12 = $_POST['appraisee_response_12'];
//     $appraisee_response_13 = $_POST['appraisee_response_13'];
//     $appraisee_response_14 = $_POST['appraisee_response_14'];
//     $appraisee_response_15 = $_POST['appraisee_response_15'];
//     $appraisee_response_16 = $_POST['appraisee_response_16'];
//     $appraisee_response_17 = $_POST['appraisee_response_17'];
//     $appraisee_response_18 = $_POST['appraisee_response_18'];
//     $appraisee_response_19 = $_POST['appraisee_response_19'];
//     $appraisee_response_20 = $_POST['appraisee_response_20'];
//     $goalsheet_submitted_by = $associatenumber;
//     $goalsheet_submitted_on = date('Y-m-d H:i:s');



//     $appraisee_response_update = "UPDATE appraisee_response
//     SET appraisee_response_complete = 'yes',
//         appraisee_response_1 = '$appraisee_response_1',
//         appraisee_response_2 = '$appraisee_response_2',
//         appraisee_response_3 = '$appraisee_response_3',
//         appraisee_response_4 = '$appraisee_response_4',
//         appraisee_response_5 = '$appraisee_response_5',
//         appraisee_response_6 = '$appraisee_response_6',
//         appraisee_response_7 = '$appraisee_response_7',
//         appraisee_response_8 = '$appraisee_response_8',
//         appraisee_response_9 = '$appraisee_response_9',
//         appraisee_response_10 = '$appraisee_response_10',
//         appraisee_response_11 = '$appraisee_response_11',
//         appraisee_response_12 = '$appraisee_response_12',
//         appraisee_response_13 = '$appraisee_response_13',
//         appraisee_response_14 = '$appraisee_response_14',
//         appraisee_response_15 = '$appraisee_response_15',
//         appraisee_response_16 = '$appraisee_response_16',
//         appraisee_response_17 = '$appraisee_response_17',
//         appraisee_response_18 = '$appraisee_response_18',
//         appraisee_response_19 = '$appraisee_response_19',
//         appraisee_response_20 = '$appraisee_response_20',
//         goalsheet_submitted_by = '$goalsheet_submitted_by',
//         goalsheet_submitted_on = '$goalsheet_submitted_on'
//         WHERE goalsheetid='$goalsheetid'";

//     $result = pg_query($con, $appraisee_response_update);
//     $cmdtuples = pg_affected_rows($result);
//     if (!$result) {
//         echo "An error occurred.\n";
//         exit;
//     }

//     $resultArr = pg_fetch_all($result);

// Assuming you have already established a PostgreSQL database connection using pg_connect().

// Assuming you have already established a database connection, $pdo, using PDO.


if (@$_POST['form-type'] === "appraisee_response_update") {
    $goalsheetid = $_POST['goalsheetid'];
    $appraisee_associatenumber = $_POST['appraisee_associatenumber'];
    $goalsheet_submitted_by = $associatenumber;
    $goalsheet_submitted_on = date('Y-m-d H:i:s');

    // Initialize an array to store appraisee responses
    $appraisee_responses = array();

    // Collect the appraisee responses from $_POST and store them in the array
    for ($i = 1; $i <= 20; $i++) {
        $appraisee_responses[$i] = $_POST['appraisee_response_' . $i];
    }

    // Prepare the SQL update query with placeholders
    $sql = "UPDATE appraisee_response 
            SET appraisee_response_complete = 'yes', 
            goalsheet_submitted_by = '$goalsheet_submitted_by',
            goalsheet_submitted_on = '$goalsheet_submitted_on',";

    // Generate the SET clause of the query dynamically
    for ($i = 1; $i <= 20; $i++) {
        $sql .= "appraisee_response_$i = $" . $i;
        if ($i !== 20) {
            $sql .= ", ";
        }
    }

    $sql .= " WHERE goalsheetid = $21";

    // Prepare the statement
    $stmt = pg_prepare($con, "update_query", $sql);

    // Build the parameters array
    $params = array_merge($appraisee_responses, array($goalsheetid));

    // Execute the prepared statement with the parameters
    $result = pg_execute($con, "update_query", $params);

    if ($result) {
        $cmdtuples = pg_affected_rows($result);
    } else {
        echo "Error while executing the update query: " . pg_last_error($con);
    }







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

    if (@$cmdtuples == 1 && $manager_email != "") {
        sendEmail("goal_sheet_evaluation_request", array(
            "goalsheetid" => $goalsheetid,
            "appraisaltype" => @$appraisaltype,
            "appraisalyear" => @$appraisalyear,
            "appraisee_name" => @$appraisee_name,
            "appraiseeemail" => @$appraisee_email,
            "appraiseeid" => @$appraisee_associatenumber,
            "manager_name" => @$manager_name,
        ), $manager_email);
    }
} ?>

<head>
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=AW-11316670180"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'AW-11316670180');
</script>
    <meta name="description" content="">
    <meta name="author" content="">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Appraisee Response</title>
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
            <a href="appraisee_response.php?goalsheetid=<?php echo $goalsheetid ?>" class="btn btn-primary">Back to Goal Sheet</a>
        </div>
    </div>

<?php } else if (@$cmdtuples == 1) { ?>

    <div class="container">
        <div class="alert alert-success" role="alert">
            <h4 class="alert-heading">Goal Sheet has been submitted successfully!</h4>
            <hr>
            <p>Your goal sheet has been submitted. The unique goal sheet ID is <?php echo $goalsheetid ?>.</p>
            <a href="appraisee_response.php?goalsheetid=<?php echo $goalsheetid ?>" class="btn btn-primary">Back to Goal Sheet</a>
        </div>
    </div>

    <script>
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
<?php } ?>