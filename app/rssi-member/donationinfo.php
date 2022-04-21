<?php
include("database.php");
@$id = strtoupper($_POST['get_id']);
$view_users_query = "select * from donation WHERE invoice='$id'"; //select query for viewing users.  
$run = pg_query($con, $view_users_query); //here run the sql query.  

while ($row = pg_fetch_array($run)) //while look to fetch the result and store in a array $row.  
{
    $approvedby=$row[0];
    $profile=$row[1];
    $mergestatus=$row[2];
    $timestamp=$row[3];
    $firstname=$row[4];
    $emailaddress=$row[5];
    $mobilenumber=$row[6];
    $transactionid=$row[7];
    $currencyofthedonatedamount=$row[8];
    $donatedamount=$row[9];
    $additionalnote=$row[10];
    $panno=$row[11];
    $dateofbirth=$row[12];
    $address=$row[13];
    $ack=$row[14];
    $modeofpayment=$row[15];
    $cauthenticationcode=$row[16];
    $nameofitemsyoushared=$row[17];
    $sauthenticationcode=$row[18];
    $lastname=$row[19];
    $youwantustospendyourdonationfor=$row[20];
    $code=$row[21];
    $invoice=$row[22];
    $filename=$row[23];
    $dlastupdatedon=$row[24];
    
?>
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
    <style>
        <?php include '../css/style.css'; ?>
    </style>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script src="https://kit.fontawesome.com/58c4cdb942.js" crossorigin="anonymous"></script>
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
                            <input name="get_id" class="form-control" style="width:max-content; display:inline-block" placeholder="Invoice number" value="<?php echo $id ?>">
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
                            <th scope="col">Name</th>
                            <th scope="col">PAN No</th>
                            <th scope="col">Donation Date</th>
                            <th scope="col">Donated Amount/Item(s)</th>
                            <th scope="col">Invoice No</th>
                            <th scope="col">Status</th>
                        </tr>
                    </thead>
                    <?php if (@$firstname > 0) {
                    ?>
                        <tbody>
                            <tr>
                                <td><b><?php echo $firstname ?> <?php echo $lastname ?></td>
                                <td><?php echo $panno ?></td>
                                <td><?php echo $timestamp ?></td>
                                <td><?php echo $currencyofthedonatedamount ?> <?php echo $donatedamount ?> <?php echo $nameofitemsyoushared ?>
                                
                                <?php if ($nameofitemsyoushared != "") { ?>
                                        <p>Quantity: <?php echo $code ?></p>

                                    <?php } else{}?></td>

                                <td><?php echo $invoice ?></td>
                                <td>

                                    <?php if ($approvedby == "rejected") { ?>
                                        <p class="label label-danger"><?php echo $approvedby ?></p>

                                    <?php } else if ($approvedby == "--") { ?>
                                        <p class="label label-info">on hold</p>

                                    <?php } else { ?>
                                        <p class="label label-success">accepted</p> <?php } ?>
                                </td>
                            </tr>
                        </tbody>
                </table>
            <?php
                    } else if ($id == "") {
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


    <!-- Back top -->
    <script>
        $(document).ready(function() {
            $(window).scroll(function() {
                if ($(this).scrollTop() > 50) {
                    $('#back-to-top').fadeIn();
                } else {
                    $('#back-to-top').fadeOut();
                }
            });
            // scroll body to 0px on click
            $('#back-to-top').click(function() {
                $('body,html').animate({
                    scrollTop: 0
                }, 400);
                return false;
            });
        });
    </script>
    <a id="back-to-top" href="#" class="go-top" role="button"><i class="fa fa-angle-up"></i></a>
</body>

</html>