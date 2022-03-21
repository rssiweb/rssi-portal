<?php
include("database.php");
@$id = strtoupper($_POST['get_id']);
$view_users_query = "select * from rssimyaccount_members WHERE associatenumber='$id'"; //select query for viewing users.  
$run = pg_query($con, $view_users_query); //here run the sql query.  

while ($row = pg_fetch_array($run)) //while look to fetch the result and store in a array $row.  
{
    $associatenumber = $row[1];
    $fullname = $row[2];
    $photo = $row[28];
    $astatus = $row[23];
    $engagement = $row[48];
    $approveddate = $row[78];?>
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
    <link href="//netdna.bootstrapcdn.com/bootstrap/3.1.0/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <!-- Main css -->
    <!-- Main css -->
    <style>
        <?php include '../css/style.css'; ?>
    </style>
    <script src="//netdna.bootstrapcdn.com/bootstrap/3.1.0/js/bootstrap.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <!------ Include the above in your HEAD tag ---------->

    <script src="https://cdn.jsdelivr.net/gh/manucaralmo/GlowCookies@3.0.1/src/glowCookies.min.js"></script>
    <!-- Glow Cookies v3.0.1 -->
    <script>
        glowCookies.start('en', {
            analytics: 'G-S25QWTFJ2S',
            //facebookPixel: '',
            policyLink: 'https://drive.google.com/file/d/1o-ULIIYDLv5ipSRfUa6ROzxJZyoEZhDF/view'
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
            <section class="box" style="padding: 2%;">
                <form action="" method="POST">
                    <div class="form-group" style="display: inline-block;">
                        <div class="col2" style="display: inline-block;">
                        <input name="get_id" class="form-control" style="width:max-content; display:inline-block" placeholder="Associate ID" value="<?php echo $id ?>">
                        </div>
                    </div>
                    <div class="col2 left" style="display: inline-block;">
                        <button type="submit" name="search_by_id" class="btn btn-primary" style="outline: none;">
                            <span class="glyphicon glyphicon-search"></span>&nbsp;Search</button>
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
                                    <span style="line-height: 3;"><?php echo $engagement ?>
                                </td>
                                <td id="cw1" style="line-height: 1.7;"><?php echo $astatus ?></td>
                                <td id="cw1" style="line-height: 1.7;"><?php echo $approveddate ?></td>
                            </tr>
                        </tbody>
                </table>
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


        </div>
        </div>
        </div>
    </section>
    </div>
    </section>
</body>

</html>