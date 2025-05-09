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

    @$courseid = $_POST['courseid'];
    @$coursename = $_POST['coursename'];
    @$language = $_POST['language'];
    @$passingmarks = $_POST['passingmarks'];
    @$url = $_POST['url'];
    @$validity = $_POST['validity'];
    @$issuedby = $_POST['issuedby'];
    @$type = $_POST['type'];
    @$material_names = $_POST['material_name']; // Array of study material names
    @$material_links = $_POST['material_link']; // Array of study material links
    @$mandatory_course = isset($_POST['mandatory_course']) && $_POST['mandatory_course'] == '1' ? 'true' : 'false';
    @$now = date('Y-m-d H:i:s');

    if ($courseid != "") {
        // Insert course into the wbt table
        $wbt = "INSERT INTO wbt (date, courseid, coursename, language, passingmarks, url, issuedby, validity, type,is_mandatory) 
                VALUES ('$now', '$courseid', '$coursename', '$language', '$passingmarks', '$url', '$issuedby', '$validity', '$type','$mandatory_course')";
        $result = pg_query($con, $wbt);
        $cmdtuples = pg_affected_rows($result);

        if ($cmdtuples > 0) {
            // Course inserted successfully, now insert study materials
            if (!empty($material_names) && !empty($material_links)) {
                foreach ($material_names as $index => $material_name) {
                    $material_link = $material_links[$index];

                    // Insert study material into wbt_study_materials table
                    $study_material_query = "INSERT INTO wbt_study_materials (courseid, material_name, link) 
                                             VALUES ('$courseid', '$material_name', '$material_link')";
                    $study_material_result = pg_query($con, $study_material_query);

                    if (!$study_material_result) {
                        // Log the error if study material insertion fails
                        error_log("Failed to insert study material: " . pg_last_error($con));
                    }
                }
            }

            // Redirect with success message
            echo "<script>
                    alert('Course and study materials have been added successfully!');
                    if (window.history.replaceState) {
                        // Update the URL without causing a page reload or resubmission
                        window.history.replaceState(null, null, window.location.href);
                    }
                   window.location.href = 'iexplore_admin.php';
                  </script>";
            exit;
        } else {
            // Redirect with error message if course insertion fails
            echo "<script>
                    alert('Error adding Course! Unfortunately, there was an error adding Course. Please try again later or contact support for assistance.');
                    window.history.back();
                  </script>";
            exit;
        }
    }
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
        alert("Error adding Course! Unfortunately, there was an error adding Course. Please try again later or contact support for assistance.");
        window.location.href = "iexplore_admin.php";
    </script>
<?php } else if (@$cmdtuples > 0) { ?>
    <script>
        alert("Course has been added successfully! Course has been added.");
        window.location.href = "iexplore_admin.php";
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
<?php } ?>