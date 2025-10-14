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

validation();
?>
<?php
if (@$_POST['form-type'] === "appraisee_response_update") {
    $goalsheetid = $_POST['goalsheetid'];
    $appraisee_associatenumber = $_POST['appraisee_associatenumber'];

    // Initialize an array to store appraisee responses
    $appraisee_responses = array();

    // Collect the appraisee responses from $_POST and store them in the array
    for ($i = 1; $i <= 20; $i++) {
        $appraisee_responses[$i] = $_POST['appraisee_response_' . $i] ?? null;
    }

    // Prepare the SQL update query with placeholders
    $sql = "UPDATE appraisee_response
            SET ";

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
}

// Close the connection
pg_close($con);
?>

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