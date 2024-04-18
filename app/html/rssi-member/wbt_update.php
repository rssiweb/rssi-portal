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
if (@$_POST['form-type'] == "wbt") {
    $courseid = $_POST['courseid'];
    $coursename = $_POST['coursename'];
    $language = $_POST['language'];
    $passingmarks = $_POST['passingmarks'];
    $url = $_POST['url'];
    $validity = $_POST['validity'];
    $issuedby = $_POST['issuedby'];
    $type = $_POST['type'];
    $now = date('Y-m-d H:i:s');
    $updateQuery = "UPDATE wbt 
                                    SET date = '$now', 
                                        coursename = '$coursename', 
                                        language = '$language', 
                                        passingmarks = '$passingmarks', 
                                        url = '$url', 
                                        issuedby = '$issuedby', 
                                        validity = '$validity',
                                        type = '$type' 
                                    WHERE courseid = '$courseid'";
    $result = pg_query($con, $updateQuery);
    $cmdtuples = pg_affected_rows($result);
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
    <title>Course update</title>
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.0/css/bootstrap.min.css">
    <script src="https://kit.fontawesome.com/58c4cdb942.js" crossorigin="anonymous"></script>
</head>

<?php if (@$cmdtuples == 0) { ?>
    <script>
        alert("Error updating Course! Unfortunately, there was an error updating Course. Please try again later or contact support for assistance.");
        window.location.href = "iexplore.php";
    </script>
<?php } else if (@$cmdtuples > 0) { ?>
    <script>
        alert("Course has been updated successfully! Course has been updated.");
        window.location.href = "iexplore.php";
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
<?php } ?>