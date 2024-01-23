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

if (@$_POST['form-type'] == "appraisee_response") {
    $g_role = $_POST['role'];
    $parameter_1 = $_POST['parameter_1'];
    $expectation_1 = $_POST['expectation_1'];
    $parameter_2 = $_POST['parameter_2'];
    $expectation_2 = $_POST['expectation_2'];
    $parameter_3 = $_POST['parameter_3'];
    $expectation_3 = $_POST['expectation_3'];
    $parameter_4 = $_POST['parameter_4'];
    $expectation_4 = $_POST['expectation_4'];
    $parameter_5 = $_POST['parameter_5'];
    $expectation_5 = $_POST['expectation_5'];
    $parameter_6 = $_POST['parameter_6'];
    $expectation_6 = $_POST['expectation_6'];
    $parameter_7 = $_POST['parameter_7'];
    $expectation_7 = $_POST['expectation_7'];
    $parameter_8 = $_POST['parameter_8'];
    $expectation_8 = $_POST['expectation_8'];
    $parameter_9 = $_POST['parameter_9'];
    $expectation_9 = $_POST['expectation_9'];
    $parameter_10 = $_POST['parameter_10'];
    $expectation_10 = $_POST['expectation_10'];
    $parameter_11 = $_POST['parameter_11'];
    $expectation_11 = $_POST['expectation_11'];
    $parameter_12 = $_POST['parameter_12'];
    $expectation_12 = $_POST['expectation_12'];
    $parameter_13 = $_POST['parameter_13'];
    $expectation_13 = $_POST['expectation_13'];
    $parameter_14 = $_POST['parameter_14'];
    $expectation_14 = $_POST['expectation_14'];
    $parameter_15 = $_POST['parameter_15'];
    $expectation_15 = $_POST['expectation_15'];
    $parameter_16 = $_POST['parameter_16'];
    $expectation_16 = $_POST['expectation_16'];
    $parameter_17 = $_POST['parameter_17'];
    $expectation_17 = $_POST['expectation_17'];
    $parameter_18 = $_POST['parameter_18'];
    $expectation_18 = $_POST['expectation_18'];
    $parameter_19 = $_POST['parameter_19'];
    $expectation_19 = $_POST['expectation_19'];
    $parameter_20 = $_POST['parameter_20'];
    $expectation_20 = $_POST['expectation_20'];
    $max_rating_1 = isset($_POST['max_rating_1']) && !empty($_POST['max_rating_1']) ? $_POST['max_rating_1'] : 'NULL';
    $max_rating_2 = isset($_POST['max_rating_2']) && !empty($_POST['max_rating_2']) ? $_POST['max_rating_2'] : 'NULL';
    $max_rating_3 = isset($_POST['max_rating_3']) && !empty($_POST['max_rating_3']) ? $_POST['max_rating_3'] : 'NULL';
    $max_rating_4 = isset($_POST['max_rating_4']) && !empty($_POST['max_rating_4']) ? $_POST['max_rating_4'] : 'NULL';
    $max_rating_5 = isset($_POST['max_rating_5']) && !empty($_POST['max_rating_5']) ? $_POST['max_rating_5'] : 'NULL';
    $max_rating_6 = isset($_POST['max_rating_6']) && !empty($_POST['max_rating_6']) ? $_POST['max_rating_6'] : 'NULL';
    $max_rating_7 = isset($_POST['max_rating_7']) && !empty($_POST['max_rating_7']) ? $_POST['max_rating_7'] : 'NULL';
    $max_rating_8 = isset($_POST['max_rating_8']) && !empty($_POST['max_rating_8']) ? $_POST['max_rating_8'] : 'NULL';
    $max_rating_9 = isset($_POST['max_rating_9']) && !empty($_POST['max_rating_9']) ? $_POST['max_rating_9'] : 'NULL';
    $max_rating_10 = isset($_POST['max_rating_10']) && !empty($_POST['max_rating_10']) ? $_POST['max_rating_10'] : 'NULL';
    $max_rating_11 = isset($_POST['max_rating_11']) && !empty($_POST['max_rating_11']) ? $_POST['max_rating_11'] : 'NULL';
    $max_rating_12 = isset($_POST['max_rating_12']) && !empty($_POST['max_rating_12']) ? $_POST['max_rating_12'] : 'NULL';
    $max_rating_13 = isset($_POST['max_rating_13']) && !empty($_POST['max_rating_13']) ? $_POST['max_rating_13'] : 'NULL';
    $max_rating_14 = isset($_POST['max_rating_14']) && !empty($_POST['max_rating_14']) ? $_POST['max_rating_14'] : 'NULL';
    $max_rating_15 = isset($_POST['max_rating_15']) && !empty($_POST['max_rating_15']) ? $_POST['max_rating_15'] : 'NULL';
    $max_rating_16 = isset($_POST['max_rating_16']) && !empty($_POST['max_rating_16']) ? $_POST['max_rating_16'] : 'NULL';
    $max_rating_17 = isset($_POST['max_rating_17']) && !empty($_POST['max_rating_17']) ? $_POST['max_rating_17'] : 'NULL';
    $max_rating_18 = isset($_POST['max_rating_18']) && !empty($_POST['max_rating_18']) ? $_POST['max_rating_18'] : 'NULL';
    $max_rating_19 = isset($_POST['max_rating_19']) && !empty($_POST['max_rating_19']) ? $_POST['max_rating_19'] : 'NULL';
    $max_rating_20 = isset($_POST['max_rating_20']) && !empty($_POST['max_rating_20']) ? $_POST['max_rating_20'] : 'NULL';

    $appraisee_response = "INSERT INTO rolebasedgoal (
        role_search,
        parameter_1, expectation_1, max_rating_1,
        parameter_2, expectation_2, max_rating_2,
        parameter_3, expectation_3, max_rating_3,
        parameter_4, expectation_4, max_rating_4,
        parameter_5, expectation_5, max_rating_5,
        parameter_6, expectation_6, max_rating_6,
        parameter_7, expectation_7, max_rating_7,
        parameter_8, expectation_8, max_rating_8,
        parameter_9, expectation_9, max_rating_9,
        parameter_10, expectation_10, max_rating_10,
        parameter_11, expectation_11, max_rating_11,
        parameter_12, expectation_12, max_rating_12,
        parameter_13, expectation_13, max_rating_13,
        parameter_14, expectation_14, max_rating_14,
        parameter_15, expectation_15, max_rating_15,
        parameter_16, expectation_16, max_rating_16,
        parameter_17, expectation_17, max_rating_17,
        parameter_18, expectation_18, max_rating_18,
        parameter_19, expectation_19, max_rating_19,
        parameter_20, expectation_20, max_rating_20
    ) VALUES (
        '$g_role',
        '$parameter_1', '$expectation_1', $max_rating_1,
        '$parameter_2', '$expectation_2', $max_rating_2,
        '$parameter_3', '$expectation_3', $max_rating_3,
        '$parameter_4', '$expectation_4', $max_rating_4,
        '$parameter_5', '$expectation_5', $max_rating_5,
        '$parameter_6', '$expectation_6', $max_rating_6,
        '$parameter_7', '$expectation_7', $max_rating_7,
        '$parameter_8', '$expectation_8', $max_rating_8,
        '$parameter_9', '$expectation_9', $max_rating_9,
        '$parameter_10', '$expectation_10', $max_rating_10,
        '$parameter_11', '$expectation_11', $max_rating_11,
        '$parameter_12', '$expectation_12', $max_rating_12,
        '$parameter_13', '$expectation_13', $max_rating_13,
        '$parameter_14', '$expectation_14', $max_rating_14,
        '$parameter_15', '$expectation_15', $max_rating_15,
        '$parameter_16', '$expectation_16', $max_rating_16,
        '$parameter_17', '$expectation_17', $max_rating_17,
        '$parameter_18', '$expectation_18', $max_rating_18,
        '$parameter_19', '$expectation_19', $max_rating_19,
        '$parameter_20', '$expectation_20', $max_rating_20
    )";    

    $result = pg_query($con, $appraisee_response);
    $cmdtuples = pg_affected_rows($result);
    if (!$result) {
        echo "An error occurred.\n";
        exit;
    }

    $resultArr = pg_fetch_all($result);
} ?>

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
    <meta name="description" content="">
    <meta name="author" content="">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Goal sheet update</title>
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.0/css/bootstrap.min.css">
    <script src="https://kit.fontawesome.com/58c4cdb942.js" crossorigin="anonymous"></script>
</head>

<?php if (@$cmdtuples == 0) { ?>

    <div class="container">
        <div class="alert alert-danger" role="alert">
            <h4 class="alert-heading">Error adding goal sheet.</h4>
            <hr>
            <p>Unfortunately, there was an error adding your goal sheet. Please try again later or contact support for assistance.</p>
            <a href="process.php?role_search=<?php echo $g_role ?>" class="btn btn-primary">Back to Goal Sheet</a>
        </div>
    </div>

<?php } else if (@$cmdtuples > 0) { ?>

    <div class="container">
        <div class="alert alert-success" role="alert">
            <h4 class="alert-heading">Goal Sheet has been added successfully!</h4>
            <hr>
            <p>Your goal sheet has been added.</p>
            <a href="process.php?role_search=<?php echo $g_role ?>" class="btn btn-primary">Back to Goal Sheet</a>
        </div>
    </div>

    <script>
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
<?php } ?>