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
    $g_role = $_POST['role'];

    // Initialize arrays for parameters, expectations, and max ratings
    $parameters = [];
    $expectations = [];
    $max_ratings = [];

    // Loop through parameters, expectations, and max ratings
    for ($i = 1; $i <= 20; $i++) {
        $parameters[$i] = htmlspecialchars($_POST['parameter_' . $i], ENT_QUOTES, 'UTF-8');
        $expectations[$i] = htmlspecialchars($_POST['expectation_' . $i], ENT_QUOTES, 'UTF-8');
        $max_ratings[$i] = isset($_POST['max_rating_' . $i]) && !empty($_POST['max_rating_' . $i]) ? $_POST['max_rating_' . $i] : 'NULL';
    }

    // Build the SQL query
    $appraisee_response = "UPDATE rolebasedgoal SET ";

    // Append parameters, expectations, and max ratings to the query
    for ($i = 1; $i <= 20; $i++) {
        $appraisee_response .= "parameter_$i = '{$parameters[$i]}', expectation_$i = '{$expectations[$i]}', max_rating_$i = {$max_ratings[$i]}, ";
    }

    // Remove the trailing comma and space
    $appraisee_response = rtrim($appraisee_response, ', ');

    // Append the WHERE clause to the query
    $appraisee_response .= " WHERE role_search = '$g_role';";

    // Execute the query
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
            <h4 class="alert-heading">Error updating Goal Sheet!</h4>
            <hr>
            <p>Unfortunately, there was an error updating your goal sheet. Please try again later or contact support for assistance.</p>
            <a href="process.php?role_search=<?php echo $g_role ?>" class="btn btn-primary">Back to Goal Sheet</a>
        </div>
    </div>

<?php } else if (@$cmdtuples > 0) { ?>

    <div class="container">
        <div class="alert alert-success" role="alert">
            <h4 class="alert-heading">Goal Sheet has been updated successfully!</h4>
            <hr>
            <p>Your goal sheet has been updated.</p>
            <a href="process.php?role_search=<?php echo $g_role ?>" class="btn btn-primary">Back to Goal Sheet</a>
        </div>
    </div>

    <script>
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
<?php } ?>