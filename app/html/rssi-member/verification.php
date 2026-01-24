<?php
require_once __DIR__ . "/../../bootstrap.php";

@$id = strtoupper($_GET['get_id']);
$result = pg_query($con, "
    SELECT DISTINCT ON (certificate.awarded_to_id) 
           certificate.*, faculty.*
    FROM certificate
    LEFT JOIN (
        SELECT associatenumber, scode, fullname, photo, engagement, position, filterstatus
        FROM rssimyaccount_members
    ) faculty 
    ON certificate.awarded_to_id = faculty.associatenumber
    WHERE faculty.associatenumber = '$id'
      AND certificate.badge_name = 'Offer Letter'
    ORDER BY certificate.awarded_to_id, certificate.issuedon DESC
");
$resultArr = pg_fetch_all($result);
if (!$result) {
    echo "An error occurred.\n";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

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
    <?php include 'includes/meta.php' ?>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <!-- Main css -->
    <!-- Main css -->
    <link rel="stylesheet" href="/css/style.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
    <script src="https://kit.fontawesome.com/58c4cdb942.js" crossorigin="anonymous"></script>
    <!------ Include the above in your HEAD tag ---------->

    <script src="https://cdn.jsdelivr.net/gh/manucaralmo/GlowCookies@3.0.1/src/glowCookies.min.js"></script>
    <!-- Glow Cookies v3.0.1 -->
    <script>
        glowCookies.start('en', {
            analytics: 'G-S25QWTFJ2S',
            //facebookPixel: '',
            policyLink: 'https://www.rssi.in/disclaimer'
        });
    </script>
    <style>
        @media (max-width:767px) {
            td {
                width: 100%
            }

            .page-topbar .logo-area {
                width: 240px !important;
                margin-top: 2.5%;
            }
        }

        .page-topbar,
        .logo-area {
            -webkit-transition: 0ms;
            -moz-transition: 0ms;
            -o-transition: 0ms;
            transition: 0ms;
        }
    </style>

</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <div class="page-topbar">
        <div class="logo-area"> </div>
    </div>
    <section class="wrapper main-wrapper row">
        <div class="col-md-12">
            <form id="myform" action="" method="GET" style="display: flex; align-items: center;">
                <div class="form-group" style="margin-right: 10px;">
                    <input name="get_id" class="form-control" placeholder="Associate ID" value="<?php echo $id ?>">
                </div>
                <button type="submit" name="search_by_id" class="btn btn-primary btn-sm" style="outline: none;">
                    <i class="bi bi-search"></i>&nbsp;Search
                </button>
            </form>

            <?php if (sizeof($resultArr) > 0) { ?>
                <?php foreach ($resultArr as $array) { ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th scope="col">Photo</th>
                                <th scope="col" id="cw2">Associate details</th>
                                <th scope="col" id="cw1">Current Status</th>
                                <th scope="col" id="cw1">Offer letter issued on</th>
                            </tr>
                        </thead>
                        <?php if (@$array['associatenumber'] > 0) {
                        ?>
                            <tbody>
                                <tr>

                                    <td>
                                        <img src="<?php echo !empty($array['photo']) ? $array['photo'] : 'https://t4.ftcdn.net/jpg/02/15/84/43/360_F_215844325_ttX9YiIIyeaR7Ne6EaLLjMAmy4GvPC69.jpg'; ?>" width="100px" />
                                    </td>

                                    <td id="cw2"><b><?php echo $array['fullname'] ?> (<?php echo $array['associatenumber'] ?>)</b><br>
                                        <span style="line-height: 3;"><?php echo $array['engagement'] ?></span><br>
                                        <?php echo $array['position'] ?>
                                    </td>
                                    <td id="cw1"><?php echo $array['filterstatus'] ?></td>
                                    <!-- <td id="cw1">
                                        <?php
                                        $status = $array['filterstatus'];
                                        if ($status == 'Active') {
                                            echo '<span class="badge bg-success">Active</span>';
                                        } elseif ($status == 'Inactive') {
                                            echo '<span class="badge bg-danger">Inactive</span>';
                                        } elseif ($status == 'In Progress') {
                                            echo '<span class="badge bg-warning">In Progress</span>';
                                        } else {
                                            echo 'Unknown Status';
                                        }
                                        ?>
                                    </td> -->

                                    <td id="cw1"><?php echo date("d/m/Y g:i a", strtotime($array['issuedon'])) ?></td>
                                </tr>
                            <?php
                        } else if ($id == "") {
                            ?>
                                <tr>
                                    <td>Please enter Associate ID.</td>
                                </tr>
                            <?php
                        } else {
                            ?>
                                <tr>
                                    <td>No record found for <?php echo $id ?></td>
                                </tr>
                            <?php }
                            ?>
                            </tbody>
                    </table>
            <?php }
            } ?>

    </section>
</body>

</html>