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

if ($password_updated_by == NULL || $password_updated_on < $default_pass_updated_on) {

    echo '<script type="text/javascript">';
    echo 'window.location.href = "defaultpasswordreset.php";';
    echo '</script>';
}
?>
<?php
if (@$_POST['form-type'] == "manager_remarks_update") {
    $goalsheetid = $_POST['goalsheetid'];
    $appraisee_associatenumber = $_POST['appraisee_associatenumber'];
    $manager_associatenumber = $_POST['manager_associatenumber'];
    $manager_remarks_1 = $_POST['manager_remarks_1'];
    $manager_remarks_2 = $_POST['manager_remarks_2'];
    $manager_remarks_3 = $_POST['manager_remarks_3'];
    $manager_remarks_4 = $_POST['manager_remarks_4'];
    $manager_remarks_5 = $_POST['manager_remarks_5'];
    $manager_remarks_6 = $_POST['manager_remarks_6'];
    $manager_remarks_7 = $_POST['manager_remarks_7'];
    $manager_remarks_8 = $_POST['manager_remarks_8'];
    $manager_remarks_9 = $_POST['manager_remarks_9'];
    $manager_remarks_10 = $_POST['manager_remarks_10'];
    $manager_remarks_11 = $_POST['manager_remarks_11'];
    $manager_remarks_12 = $_POST['manager_remarks_12'];
    $manager_remarks_13 = $_POST['manager_remarks_13'];
    $manager_remarks_14 = $_POST['manager_remarks_14'];
    $manager_remarks_15 = $_POST['manager_remarks_15'];
    $manager_remarks_16 = $_POST['manager_remarks_16'];
    $manager_remarks_17 = $_POST['manager_remarks_17'];
    $manager_remarks_18 = $_POST['manager_remarks_18'];
    $manager_remarks_19 = $_POST['manager_remarks_19'];
    $manager_remarks_20 = $_POST['manager_remarks_20'];
    $rating_obtained_1 = $_POST['rating_obtained_1'] ?? 'NULL';
    $rating_obtained_2 = $_POST['rating_obtained_2'] ?? 'NULL';
    $rating_obtained_3 = $_POST['rating_obtained_3'] ?? 'NULL';
    $rating_obtained_4 = $_POST['rating_obtained_4'] ?? 'NULL';
    $rating_obtained_5 = $_POST['rating_obtained_5'] ?? 'NULL';
    $rating_obtained_6 = $_POST['rating_obtained_6'] ?? 'NULL';
    $rating_obtained_7 = $_POST['rating_obtained_7'] ?? 'NULL';
    $rating_obtained_8 = $_POST['rating_obtained_8'] ?? 'NULL';
    $rating_obtained_9 = $_POST['rating_obtained_9'] ?? 'NULL';
    $rating_obtained_10 = $_POST['rating_obtained_10'] ?? 'NULL';
    $rating_obtained_11 = $_POST['rating_obtained_11'] ?? 'NULL';
    $rating_obtained_12 = $_POST['rating_obtained_12'] ?? 'NULL';
    $rating_obtained_13 = $_POST['rating_obtained_13'] ?? 'NULL';
    $rating_obtained_14 = $_POST['rating_obtained_14'] ?? 'NULL';
    $rating_obtained_15 = $_POST['rating_obtained_15'] ?? 'NULL';
    $rating_obtained_16 = $_POST['rating_obtained_16'] ?? 'NULL';
    $rating_obtained_17 = $_POST['rating_obtained_17'] ?? 'NULL';
    $rating_obtained_18 = $_POST['rating_obtained_18'] ?? 'NULL';
    $rating_obtained_19 = $_POST['rating_obtained_19'] ?? 'NULL';
    $rating_obtained_20 = $_POST['rating_obtained_20'] ?? 'NULL';



    $manager_remarks_update = "UPDATE appraisee_response
    SET manager_remarks_1 = '$manager_remarks_1',
        manager_remarks_2 = '$manager_remarks_2',
        manager_remarks_3 = '$manager_remarks_3',
        manager_remarks_4 = '$manager_remarks_4',
        manager_remarks_5 = '$manager_remarks_5',
        manager_remarks_6 = '$manager_remarks_6',
        manager_remarks_7 = '$manager_remarks_7',
        manager_remarks_8 = '$manager_remarks_8',
        manager_remarks_9 = '$manager_remarks_9',
        manager_remarks_10 = '$manager_remarks_10',
        manager_remarks_11 = '$manager_remarks_11',
        manager_remarks_12 = '$manager_remarks_12',
        manager_remarks_13 = '$manager_remarks_13',
        manager_remarks_14 = '$manager_remarks_14',
        manager_remarks_15 = '$manager_remarks_15',
        manager_remarks_16 = '$manager_remarks_16',
        manager_remarks_17 = '$manager_remarks_17',
        manager_remarks_18 = '$manager_remarks_18',
        manager_remarks_19 = '$manager_remarks_19',
        manager_remarks_20 = '$manager_remarks_20',
        rating_obtained_1 = $rating_obtained_1,
        rating_obtained_2 = $rating_obtained_2,
        rating_obtained_3 = $rating_obtained_3,
        rating_obtained_4 = $rating_obtained_4,
        rating_obtained_5 = $rating_obtained_5,
        rating_obtained_6 = $rating_obtained_6,
        rating_obtained_7 = $rating_obtained_7,
        rating_obtained_8 = $rating_obtained_8,
        rating_obtained_9 = $rating_obtained_9,
        rating_obtained_10 = $rating_obtained_10,
        rating_obtained_11 = $rating_obtained_11,
        rating_obtained_12 = $rating_obtained_12,
        rating_obtained_13 = $rating_obtained_13,
        rating_obtained_14 = $rating_obtained_14,
        rating_obtained_15 = $rating_obtained_15,
        rating_obtained_16 = $rating_obtained_16,
        rating_obtained_17 = $rating_obtained_17,
        rating_obtained_18 = $rating_obtained_18,
        rating_obtained_19 = $rating_obtained_19,
        rating_obtained_20 = $rating_obtained_20
        WHERE goalsheetid='$goalsheetid'";

    $result = pg_query($con, $manager_remarks_update);
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
    <title>Manager Response</title>
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
            <a href="manager_response.php?goalsheetid=<?php echo $goalsheetid ?>" class="btn btn-primary">Back to Goal Sheet</a>
        </div>
    </div>

<?php } else if (@$cmdtuples == 1) { ?>

    <div class="container">
        <div class="alert alert-success" role="alert">
            <h4 class="alert-heading">Goal Sheet has been saved successfully!</h4>
            <hr>
            <p>Your goal sheet has been saved. The unique goal sheet ID is <?php echo $goalsheetid ?>.</p>
            <a href="manager_response.php?goalsheetid=<?php echo $goalsheetid ?>" class="btn btn-primary">Back to Goal Sheet</a>
        </div>
    </div>

    <script>
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
<?php } ?>