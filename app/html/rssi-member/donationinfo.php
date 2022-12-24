<?php
require_once __DIR__ . "/../../bootstrap.php";

@$id = strtoupper($_GET['get_id']);
$result = pg_query($con, "select * from donation WHERE invoice='$id'"); //select query for viewing users.  
if (!$result) {
    echo "An error occurred.\n";
    exit;
}
$resultArr = pg_fetch_all($result);
?>

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
    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
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
            <section class="box" style="padding: 2%;">
                <form action="" method="GET">
                    <div class="form-group" style="display: inline-block;">
                        <div class="col2" style="display: inline-block;">
                            <input name="get_id" class="form-control" style="width:max-content; display:inline-block" placeholder="Invoice number" value="<?php echo $id ?>">
                        </div>
                    </div>
                    <div class="col2 left" style="display: inline-block;">
                        <button type="submit" name="search_by_id" class="btn btn-primary btn-sm" style="outline: none;">
                            <i class="fa-solid fa-magnifying-glass"></i>&nbsp;Search</button>
                    </div>
                </form>

                <?php echo '<table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Name</th>
                            <th scope="col">National Identifier</th>
                            <th scope="col">National Identifier Number</th>
                            <th scope="col">Donation Date</th>
                            <th scope="col">Donated Amount/Item(s)</th>
                            <th scope="col">Invoice No</th>
                            <th scope="col">Status</th>
                        </tr>
                    </thead>' ?>
                <?php if ($resultArr != null) {
                    echo '<tbody>';
                    foreach ($resultArr as $array) {
                        echo '<tr>' ?>
                        <?php echo '
                                <td><b>' . $array['firstname'] . '&nbsp;' . $array['lastname'] . '</b></td>
                                <td>' . $array['uitype'] . '</td>
                                <td>' . $array['uinumber'] . '</td>
                                <td>' . date("d/m/Y H:i", strtotime($array['timestamp'])) . '</td><td>' ?>

                        <?php if ($array['nameofitemsyoushared'] != "") { ?>

                            <?php echo   $array['nameofitemsyoushared']. '<br>' . 'Quantity: ' . $array['code'] . '</td>' ?> <?php } ?>

                        <?php if ($array['nameofitemsyoushared'] == null) { ?>

                            <?php echo  $array['currencyofthedonatedamount'] . '&nbsp;' . $array['donatedamount'] . '</td>' ?> <?php } ?>

                        <?php echo '<td>' . $array['invoice'] . '</td><td>' ?>

                        <?php if ($array['approvedby'] == "rejected") { ?>
                            <?php echo '<p class="label label-danger"><?php echo $approvedby ?></p>' ?>

                        <?php } else if ($array['approvedby'] == "--") { ?>

                            <?php echo '<p class="label label-info">on hold</p>' ?>

                        <?php } else { ?>

                            <?php echo  '<p class="label label-success">accepted</p>' ?> <?php } ?>

                        <?php echo '</td>
                            </tr>
                        </tbody>
                </table>' ?>

                    <?php
                    }
                }
                else if ($id == "") {
                    ?>
                    <tr>
                        <td>Please enter Invoice number.</td>
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
