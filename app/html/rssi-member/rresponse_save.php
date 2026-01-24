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
if (@$_POST['form-type'] == "reviewer_remarks_update") {
    $goalsheetid = $_POST['goalsheetid'];
    $appraisee_associatenumber = $_POST['appraisee_associatenumber'];
    $manager_associatenumber = $_POST['manager_associatenumber'];
    $reviewer_associatenumber = $_POST['reviewer_associatenumber'];
    $reviewer_remarks = $_POST['reviewer_remarks'];

    // Prepare the SQL update query using a parameterized query
    $sql = "UPDATE appraisee_response
            SET manager_evaluation_complete = null,
            reviewer_response_complete = null,
                reviewer_remarks = $1  -- Use placeholder $1 for the value
            WHERE goalsheetid = $2"; // Use placeholder $2 for the value

    // Prepare the statement
    $stmt = pg_prepare($con, "reviewer_remarks_update_query", $sql);

    // Bind the values to the placeholders in the prepared statement
    $result = pg_execute($con, "reviewer_remarks_update_query", array(
        $reviewer_remarks,
        $goalsheetid
    ));

    // Check if the query was successful
    if ($result) {
        $cmdtuples = pg_affected_rows($result);
    } else {
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
  function gtag(){dataLayer.push(arguments);}
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
            <h4 class="alert-heading">Error saving Goal Sheet!</h4>
            <hr>
            <p>We encountered an error while unlocking the goal sheet. Please try again later or contact support for assistance.</p>
            <a href="reviewer_response.php" class="btn btn-primary">Back to Goal Sheet</a>
        </div>
    </div>

<?php } else if (@$cmdtuples == 1) { ?>

    <div class="container">
        <div class="alert alert-warning" role="alert">
            <h4 class="alert-heading">Goal Sheet has been unlocked successfully!</h4>
            <hr>
            <p>The goal sheet has been unlocked. The unique goal sheet ID is <?php echo $goalsheetid ?>.</p>
            <a href="reviewer_response.php?goalsheetid=<?php echo $goalsheetid ?>" class="btn btn-primary">Back to Goal Sheet</a>
        </div>
    </div>

    <script>
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
<?php } ?>