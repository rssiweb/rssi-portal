<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");
include("../../util/email.php");
include("../../util/drive.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}

validation();
?>
<?php
if (@$_POST['form-type'] == "appraisee_response") {
    // Initialize $g_role
     $g_role = $_POST['role'];

    // Initialize an array to hold parameter data
    $parameters = [];

    // Loop through parameters and expectations
    for ($i = 1; $i <= 20; $i++) {
        $parameter = htmlspecialchars($_POST["parameter_$i"], ENT_QUOTES, 'UTF-8');
        $expectation = htmlspecialchars($_POST["expectation_$i"], ENT_QUOTES, 'UTF-8');
        $maxRating = isset($_POST["max_rating_$i"]) && !empty($_POST["max_rating_$i"]) ? $_POST["max_rating_$i"] : 'NULL';

        // Add parameter data to the array
        $parameters[] = compact("parameter", "expectation", "maxRating");
    }

    // Build the SQL query
    $appraisee_response = "INSERT INTO rolebasedgoal (role_search, ";
    for ($i = 1; $i <= 20; $i++) {
        $appraisee_response .= "parameter_$i, expectation_$i, max_rating_$i, ";
    }
    $appraisee_response = rtrim($appraisee_response, ', ') . ") VALUES ('$g_role', ";

    // Add values to the SQL query
    foreach ($parameters as $index => $paramData) {
        $appraisee_response .= "'{$paramData['parameter']}', '{$paramData['expectation']}', {$paramData['maxRating']}, ";
    }

    // Complete the SQL query and execute it
    $appraisee_response = rtrim($appraisee_response, ', ') . ")";
    $result = pg_query($con, $appraisee_response);

    // Check for errors
    if (!$result) {
        echo "An error occurred.\n";
        exit;
    }
    $cmdtuples = pg_affected_rows($result);
    $resultArr = pg_fetch_all($result);
}
?>

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