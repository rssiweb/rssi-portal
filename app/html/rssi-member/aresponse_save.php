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
if (@$_POST['form-type'] == "appraisee_response_update") {
    $goalsheetid = $_POST['goalsheetid'];
    $appraisee_associatenumber = $_POST['appraisee_associatenumber'];
    $appraisee_response_1 = $_POST['appraisee_response_1'];
    $appraisee_response_2 = $_POST['appraisee_response_2'];
    $appraisee_response_3 = $_POST['appraisee_response_3'];
    $appraisee_response_4 = $_POST['appraisee_response_4'];
    $appraisee_response_5 = $_POST['appraisee_response_5'];
    $appraisee_response_6 = $_POST['appraisee_response_6'];
    $appraisee_response_7 = $_POST['appraisee_response_7'];
    $appraisee_response_8 = $_POST['appraisee_response_8'];
    $appraisee_response_9 = $_POST['appraisee_response_9'];
    $appraisee_response_10 = $_POST['appraisee_response_10'];
    $appraisee_response_11 = $_POST['appraisee_response_11'];
    $appraisee_response_12 = $_POST['appraisee_response_12'];
    $appraisee_response_13 = $_POST['appraisee_response_13'];
    $appraisee_response_14 = $_POST['appraisee_response_14'];
    $appraisee_response_15 = $_POST['appraisee_response_15'];
    $appraisee_response_16 = $_POST['appraisee_response_16'];
    $appraisee_response_17 = $_POST['appraisee_response_17'];
    $appraisee_response_18 = $_POST['appraisee_response_18'];
    $appraisee_response_19 = $_POST['appraisee_response_19'];
    $appraisee_response_20 = $_POST['appraisee_response_20'];



    $appraisee_response_update = "UPDATE appraisee_response
    SET appraisee_response_1 = '$appraisee_response_1',
        appraisee_response_2 = '$appraisee_response_2',
        appraisee_response_3 = '$appraisee_response_3',
        appraisee_response_4 = '$appraisee_response_4',
        appraisee_response_5 = '$appraisee_response_5',
        appraisee_response_6 = '$appraisee_response_6',
        appraisee_response_7 = '$appraisee_response_7',
        appraisee_response_8 = '$appraisee_response_8',
        appraisee_response_9 = '$appraisee_response_9',
        appraisee_response_10 = '$appraisee_response_10',
        appraisee_response_11 = '$appraisee_response_11',
        appraisee_response_12 = '$appraisee_response_12',
        appraisee_response_13 = '$appraisee_response_13',
        appraisee_response_14 = '$appraisee_response_14',
        appraisee_response_15 = '$appraisee_response_15',
        appraisee_response_16 = '$appraisee_response_16',
        appraisee_response_17 = '$appraisee_response_17',
        appraisee_response_18 = '$appraisee_response_18',
        appraisee_response_19 = '$appraisee_response_19',
        appraisee_response_20 = '$appraisee_response_20'
    WHERE goalsheetid='$goalsheetid'";

    $result = pg_query($con, $appraisee_response_update);
    $cmdtuples = pg_affected_rows($result);
    if (!$result) {
        echo "An error occurred.\n";
        exit;
    }

    $resultArr = pg_fetch_all($result);
} ?>

<head>
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
            <h4 class="alert-heading">Error saving Goal Sheet!</h4>
            <hr>
            <p>Unfortunately, there was an error saving your goal sheet. Please try again later or contact support for assistance.</p>
            <a href="appraisee_response.php?goalsheetid=<?php echo $goalsheetid ?>" class="btn btn-primary">Back to Goal Sheet</a>
        </div>
    </div>

<?php } else if (@$cmdtuples == 1) { ?>

    <div class="container">
        <div class="alert alert-success" role="alert">
            <h4 class="alert-heading">Goal Sheet has been saved successfully!</h4>
            <hr>
            <p>Your goal sheet has been saved. The unique goal sheet ID is <?php echo $goalsheetid ?>.</p>
            <a href="appraisee_response.php?goalsheetid=<?php echo $goalsheetid ?>" class="btn btn-primary">Back to Goal Sheet</a>
        </div>
    </div>

    <script>
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
<?php } ?>