<?php
require_once __DIR__ . "/../../bootstrap.php";

@$id = strtoupper($_GET['get_id']);
$view_users_query = "select * from rssimyaccount_members WHERE associatenumber='$id'"; //select query for viewing users.  
$run = pg_query($con, $view_users_query); //here run the sql query.  

while ($row = pg_fetch_array($run)) //while look to fetch the result and store in a array $row.  
{
    $associatenumber = $row[1];
    $fullname = $row[2];
    $position = $row[16];
    $photo = $row[28];
    $astatus = $row[23];
    $engagement = $row[48];
    $approveddate = $row[78]; ?>
<?php } ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="description" content="">
    <meta name="author" content="">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Details Verification</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
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
    <div class="page-topbar">
        <div class="logo-area"> </div>
    </div>
    <section class="wrapper main-wrapper row">
        <div class="col-md-12">
            
                <form id="myform" action="" method="GET">
                    <div class="form-group" style="display: inline-block;">
                        <div class="col2" style="display: inline-block;">
                            <input name="get_id" class="form-control" style="width:max-content; display:inline-block" placeholder="Associate ID" value="<?php echo $id ?>">
                        </div>
                    </div>
                    <div class="col2 left" style="display: inline-block;">
                        <button type="submit" name="search_by_id" class="btn btn-primary btn-sm" style="outline: none;">
                            <i class="bi bi-search"></i>&nbsp;Search</button>
                    </div>
                </form>

                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Photo</th>
                            <th scope="col" id="cw2">Associate details</th>
                            <th scope="col" id="cw1">Current Status</th>
                            <th scope="col" id="cw1">Offer letter issued on</th>
                        </tr>
                    </thead>
                    <?php if (@$associatenumber > 0) {
                    ?>
                        <tbody>
                            <tr>

                                <td style="line-height: 1.7;"><img src="<?php echo $photo ?>" width=100px /></td>
                                <td id="cw2" style="line-height: 1.7;"><b><?php echo $fullname ?> (<?php echo $associatenumber ?>)</b><br>
                                    <span style="line-height: 3;"><?php echo $engagement ?></span><br>
                                    <?php echo substr($position, 0, strrpos($position, "-")) ?>
                                </td>
                                <td id="cw1" style="line-height: 1.7;"><?php echo $astatus ?></td>
                                <td id="cw1" style="line-height: 1.7;"><?php echo $approveddate ?></td>
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

            </section>
        </div>
    </section>
</body>

</html>
