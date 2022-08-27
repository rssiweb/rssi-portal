<?php
session_start();
// Storing Session
include("../util/login_util.php");


if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

if ($password_updated_by == null || $password_updated_on < $default_pass_updated_on) {

    echo '<script type="text/javascript">';
    echo 'window.location.href = "defaultpasswordreset.php";';
    echo '</script>';
}

if ($role != 'Admin' && $role != 'Offline Manager') {
    echo '<script type="text/javascript">';
    echo 'alert("Access Denied. You are not authorized to access this web page.");';
    echo 'window.location.href = "home.php";';
    echo '</script>';
}

date_default_timezone_set('Asia/Kolkata');

if ($_POST) {
    @$itemid = $_POST['itemid'];
    @$itemtype = $_POST['itemtype'];
    @$itemname = $_POST['itemname'];
    @$quantity = $_POST['quantity'];
    @$remarks = $_POST['remarks'];
    @$collectedby = $_POST['collectedby'];
    @$now = date('Y-m-d H:i:s');
    if ($itemtype != "") {
        $gps = "INSERT INTO gps (itemid, date, itemtype, itemname, quantity, remarks, collectedby) VALUES ('$itemid','$now','$itemtype','$itemname','$quantity','$remarks','$collectedby')";
        $result = pg_query($con, $gps);
        $cmdtuples = pg_affected_rows($result);
    }
}

@$taggedto = $_GET['taggedto'];
@$item_type = $_GET['item_type'];

if ($item_type == 'ALL' && $taggedto == "") {
    $gpsdetails = "SELECT * from gps order by date desc";
} else if ($item_type == 'ALL' && $taggedto != "") {
    $gpsdetails = "SELECT * from gps where taggedto='$taggedto'";
} else if ($item_type == "" && $taggedto != "") {
    $gpsdetails = "SELECT * from gps where taggedto='$taggedto'";
} else if ($item_type != "ALL" && $item_type != "" && $taggedto != "") {
    $gpsdetails = "SELECT * from gps where taggedto='$taggedto' and itemtype='$item_type'";
} else if ($item_type != "ALL" && $item_type != "" && $taggedto == "") {
    $gpsdetails = "SELECT * from gps where itemtype='$item_type'";
} else {
    $gpsdetails = "SELECT * from gps where itemid=''";
}

$result = pg_query($con, $gpsdetails);

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
    <title>RSSI-GPS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <!-- Main css -->
    <style>
        <?php include '../css/style.css'; ?>
    </style>

    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script src="https://kit.fontawesome.com/58c4cdb942.js" crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
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
        .checkbox {
            padding: 0;
            margin: 0;
            vertical-align: bottom;
            position: relative;
            top: 0px;
            overflow: hidden;
        }

        .x-btn:focus,
        .button:focus,
        [type="submit"]:focus {
            outline: none;
        }

        #passwordHelpBlock {
            font-size: x-small;
            display: block;
        }

        .input-help {
            vertical-align: top;
            display: inline-block;
        }
    </style>
</head>

<!-- =========================
     NAVIGATION LINKS     
============================== -->

<body>
    <?php include 'header.php'; ?>
    <section id="main-content">
        <section class="wrapper main-wrapper row">
            <div class="col-md-12">
                <?php if (@$itemid != null && @$cmdtuples == 0) { ?>

                    <div class="alert alert-danger alert-dismissible" role="alert" style="text-align: -webkit-center;">
                        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                        <span class="blink_me"><i class="glyphicon glyphicon-warning-sign"></i></span>&nbsp;&nbsp;<span>ERROR: Oops, something wasn't right.</span>
                    </div>
                <?php
                } else if (@$cmdtuples == 1) { ?>

                    <div class="alert alert-success alert-dismissible" role="alert" style="text-align: -webkit-center;">
                        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                        <i class="glyphicon glyphicon-ok" style="font-size: medium;"></i></span>&nbsp;&nbsp;<span>Database has been updated successfully for item id <?php echo @$itemid ?>.</span>
                    </div>
                <?php } ?>
                <div class="row">
                    <section class="box" style="padding: 2%;">
                        <p>Home / GPS (Global Procurement System)</p><br><br>

                        <form autocomplete="off" name="gps" id="gps" action="gps.php" method="POST">
                            <div class="form-group" style="display: inline-block;">
                                <div class="col2" style="display: inline-block;">

                                    <span class="input-help">
                                        <input type="text" name="itemid" class="form-control" style="width:max-content; display:inline-block" placeholder="Item ID" value="A-<?php echo time() ?>" required readonly>
                                        <small id="passwordHelpBlock" class="form-text text-muted">Item ID</small>
                                    </span>

                                    <span class="input-help">
                                        <select name="itemtype" class="form-control" style="width:max-content; display:inline-block" required>
                                            <?php if ($itemtype == null) { ?>
                                                <option value="" disabled selected hidden>Item type</option>
                                            <?php
                                            } else { ?>
                                                <option hidden selected><?php echo $itemtype ?></option>
                                            <?php }
                                            ?>
                                            <option>Purchased</option>
                                            <option>Donation</option>
                                        </select>
                                        <small id="passwordHelpBlock" class="form-text text-muted">Item type</small>
                                    </span>

                                    <span class="input-help">
                                        <input type="text" name="itemname" class="form-control" style="width:max-content; display:inline-block" placeholder="Item name" value="" required>
                                        <small id="passwordHelpBlock" class="form-text text-muted">Item name</small>
                                    </span>

                                    <span class="input-help">
                                        <input type="number" name="quantity" class="form-control" style="width:max-content; display:inline-block" placeholder="Quantity" value="" min="1" required>
                                        <small id="passwordHelpBlock" class="form-text text-muted">Quantity</small>
                                    </span>

                                    <span class="input-help">
                                        <textarea type="text" name="remarks" class="form-control" style="width:max-content; display:inline-block" placeholder="Remarks" value=""></textarea>
                                        <small id="passwordHelpBlock" class="form-text text-muted">Remarks (Optional)</small>
                                    </span>

                                    <input type="hidden" name="collectedby" class="form-control" style="width:max-content; display:inline-block" placeholder="Collected by" value="<?php echo $fullname ?>" required readonly>

                                </div>

                            </div>

                            <div class="col2 left" style="display: inline-block;">
                                <button type="submit" name="search_by_id" class="btn btn-danger btn-sm" style="outline: none;">
                                    <i class="fa-solid fa-arrows-rotate"></i>&nbsp;&nbsp;Update</button>
                            </div>
                        </form>


                        <br><span class="heading">Asset details</span><br><br>


                        <form name="gpsdetails" id="gpsdetails" action="" method="GET">
                            <div class="form-group" style="display: inline-block;">
                                <div class="col2" style="display: inline-block;">

                                    <select name="item_type" class="form-control" style="width:max-content; display:inline-block">
                                        <?php if ($item_type == null) { ?>
                                            <option value="" disabled selected hidden>Item type</option>
                                        <?php
                                        } else { ?>
                                            <option hidden selected><?php echo $item_type ?></option>
                                        <?php }
                                        ?>
                                        <option>Purchased</option>
                                        <option>Donation</option>
                                        <option>ALL</option>
                                    </select>&nbsp;
                                    <input type="text" name="taggedto" class="form-control" style="width:max-content; display:inline-block" placeholder="Tagged to" value="<?php echo $taggedto ?>">
                                </div>
                            </div>
                            <div class="col2 left" style="display: inline-block;">
                                <button type="submit" name="search_by_idd" class="btn btn-primary btn-sm" style="outline: none;">
                                    <i class="fa-solid fa-magnifying-glass"></i>&nbsp;Search</button>
                            </div>
                        </form>
                        <div class="col" style="display: inline-block; width:99%; text-align:right">
                            Record count:&nbsp;<?php echo sizeof($resultArr) ?><br><br>
                            <form method="POST" action="export_function.php">
                                <input type="hidden" value="gps" name="export_type" />
                                <input type="hidden" value="<?php echo $item_type ?>" name="item_type" />
                                <input type="hidden" value="<?php echo $taggedto ?>" name="taggedto" />

                                <button type="submit" id="export" name="export" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none;
                        padding: 0px;
                        border: none;" title="Export CSV"><i class="fa-regular fa-file-excel" style="font-size:large;"></i></button>
                            </form>
                        </div>

                        <?php echo '
                        <table class="table">
                        <thead>
                            <tr>
                                <th scope="col">Item Id</th>
                                <th scope="col">Item name</th>
                                <th scope="col">Item type</th>
                                <th scope="col">Quantity</th>
                                <th scope="col">Remarks</th>
                                <th scope="col">Tagged to</th>
                                
                            </tr>
                        </thead>' ?>
                        <?php if (sizeof($resultArr) > 0) { ?>
                            <?php
                            echo '<tbody>';
                            foreach ($resultArr as $array) {
                                echo '<tr>
                                <!--<td>' . @date("d/m/Y g:i a", strtotime($array['date'])) . '</td>-->
                                <td>' . $array['itemid'] . '</td>' ?>

                                <?php if ($role != 'Admin') { ?>

                                    <?php echo '<td>' . substr($array['itemname'], 0, 35) . '
                                    
                                    &nbsp;<button type="button" href="javascript:void(0)" onclick="showDetails(\'' . $array['itemid'] . '\')" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Details">
                                    <i class="fa-solid fa-arrow-up-right-from-square"></i></button>
                                    
                                    </td>' ?>

                                <?php } ?>

                                <?php if ($role == 'Admin') { ?>

                                    <?php echo '
                                    
                                <td>
                                
                                <form name="name_' . $array['itemid'] . '" action="#" method="POST" onsubmit="myFunction()" style="display: -webkit-inline-box;">
                                <input type="hidden" name="form-type" type="text" value="nameedit">
                                <input type="hidden" name="itemid" id="itemid" type="text" value="' . $array['itemid'] . '">
                                <input id="inpname_' . $array['itemid'] . '" name="itemname" type="text" value="' . $array['itemname'] . '" disabled>
                                &nbsp;
                                
                                <button type="button" href="javascript:void(0)" onclick="showDetails(\'' . $array['itemid'] . '\')" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Details">
                                <i class="fa-solid fa-arrow-up-right-from-square"></i></button>
                                
                                &nbsp;

                                <button type="button" id="editname_' . $array['itemid'] . '" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Edit"><i class="fa-regular fa-pen-to-square"></i></button>&nbsp;

                                <button type="submit" id="savename_' . $array['itemid'] . '" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Save"><i class="fa-regular fa-floppy-disk"></i></button></form></td>' ?>

                                <?php } ?>

                                <?php echo '
                                
                                <td>' . $array['itemtype'] . '</td>
                                <td>' . $array['quantity'] . '</td>
                                <td>
                                <form name="remarks_' . $array['itemid'] . '" action="#" method="POST" onsubmit="myFunctionn()" style="display: -webkit-inline-box;">
                                <input type="hidden" name="form-type" type="text" value="remarksedit">
                                <input type="hidden" name="itemid" id="itemid" type="text" value="' . $array['itemid'] . '">
                                <textarea id="inp_' . $array['itemid'] . '" name="remarks" type="text" disabled>' . $array['remarks'] . '</textarea>' ?>

                                <?php if ($role == 'Admin') { ?>

                                    <?php echo '&nbsp;

                                <button type="button" id="edit_' . $array['itemid'] . '" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Edit"><i class="fa-regular fa-pen-to-square"></i></button>&nbsp;

                                <button type="submit" id="save_' . $array['itemid'] . '" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Save"><i class="fa-regular fa-floppy-disk"></i></button>' ?>

                                <?php } ?>

                                <?php echo '</form></td>' ?>

                                <?php if ($role != 'Admin') { ?>

                                    <?php echo '<td>' . $array['taggedto'] . '</td>' ?>

                                <?php } ?>



                                <?php if ($role == 'Admin') { ?>

                                    <?php echo '
                                

                                <td><form name="tag_' . $array['itemid'] . '" action="#" method="POST" onsubmit="myFunctiontag()" style="display: -webkit-inline-box;">
                                <input type="hidden" name="form-type" type="text" value="tagedit">
                                <input type="hidden" name="itemid" id="itemid" type="text" value="' . $array['itemid'] . '">
                                <input id="inptag_' . $array['itemid'] . '" name="taggedto" type="text" value="' . $array['taggedto'] . '" disabled>
                                    
                                    &nbsp;

                                <button type="button" id="edittag_' . $array['itemid'] . '" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Edit"><i class="fa-regular fa-pen-to-square"></i></button>&nbsp;

                                <button type="submit" id="savetag_' . $array['itemid'] . '" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Save"><i class="fa-regular fa-floppy-disk"></i></button>' ?>

                                <?php } ?>

                            <?php echo '</form></td>
                                </tr>';
                            } ?>
                        <?php
                        } else if ($taggedto == null && $item_type == null) {
                        ?>
                            <tr>
                                <td colspan="5">Please select Filter value.</td>
                            </tr>
                        <?php
                        } else {
                        ?>
                            <tr>
                                <td colspan="5">No record was found for the selected filter value.</td>
                            </tr>
                        <?php }

                        echo '</tbody>
                            </table>';
                        ?>

                    </section>
                </div>
        </section>
    </section>

    <!--------------- POP-UP BOX ------------
-------------------------------------->
    <style>
        .modal {
            display: none;
            /* Hidden by default */
            position: fixed;
            /* Stay in place */
            z-index: 100;
            /* Sit on top */
            padding-top: 100px;
            /* Location of the box */
            left: 0;
            top: 0;
            width: 100%;
            /* Full width */
            height: 100%;
            /* Full height */
            overflow: auto;
            /* Enable scroll if needed */
            background-color: rgb(0, 0, 0);
            /* Fallback color */
            background-color: rgba(0, 0, 0, 0.4);
            /* Black w/ opacity */
        }

        /* Modal Content */

        .modal-content {
            background-color: #fefefe;
            margin: auto;
            padding: 20px;
            border: 1px solid #888;
            width: 100vh;
        }

        @media (max-width:767px) {
            .modal-content {
                width: 50vh;
            }
        }

        /* The Close Button */

        .close {
            color: #aaaaaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            text-align: right;
        }

        .close:hover,
        .close:focus {
            color: #000;
            text-decoration: none;
            cursor: pointer;
        }
    </style>

    <div id="myModal" class="modal">

        <!-- Modal content -->
        <div class="modal-content">
            <span class="close">&times;</span>

            <div style="width:100%; text-align:right">
                <p class="label label-info" style="display: inline !important;"><span class="itemid"></span></p>
            </div>


            <p style="font-size: small;">
                <span class="itemname"></span><br /><br />
                <span style="font-size:10px;">Registered by&nbsp;<span class="collectedby"></span>&nbsp;on&nbsp;<span class="date"></span></span>
            </p>
        </div>

    </div>
    <script>
        var data = <?php echo json_encode($resultArr) ?>

        // Get the modal
        var modal = document.getElementById("myModal");
        // Get the <span> element that closes the modal
        var span = document.getElementsByClassName("close")[0];

        function showDetails(id) {
            // console.log(modal)
            // console.log(modal.getElementsByClassName("data"))
            var mydata = undefined
            data.forEach(item => {
                if (item["itemid"] == id) {
                    mydata = item;
                }
            })

            var keys = Object.keys(mydata)
            keys.forEach(key => {
                var span = modal.getElementsByClassName(key)
                if (span.length > 0)
                    span[0].innerHTML = mydata[key];
            })
            modal.style.display = "block";
        }
        // When the user clicks the button, open the modal 
        // When the user clicks on <span> (x), close the modal
        span.onclick = function() {
            modal.style.display = "none";
        }
        // When the user clicks anywhere outside of the modal, close it
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>

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
    <script>
        function myFunction() {
            alert("Item name has been updated.");
            location.reload();
        }

        function myFunctionn() {
            alert("Remarks has been updated.");
            location.reload();
        }

        function myFunctiontag() {
            alert("Tagged to has been updated.");
            location.reload();
        }
    </script>
    <script>
        var data = <?php echo json_encode($resultArr) ?>;

        data.forEach(item => {

            const form = document.getElementById('editname_' + item.itemid);

            form.addEventListener('click', function() {
                document.getElementById('inpname_' + item.itemid).disabled = false;
            });
        })


        data.forEach(item => {

            const form = document.getElementById('edit_' + item.itemid);

            form.addEventListener('click', function() {
                document.getElementById('inp_' + item.itemid).disabled = false;
            });
        })


        data.forEach(item => {

            const form = document.getElementById('edittag_' + item.itemid);

            form.addEventListener('click', function() {
                document.getElementById('inptag_' + item.itemid).disabled = false;
            });
        })

        //For form submission - to update Remarks
        const scriptURL = 'payment-api.php'

        data.forEach(item => {
            const form = document.forms['name_' + item.itemid]
            form.addEventListener('submit', e => {
                e.preventDefault()
                fetch(scriptURL, {
                        method: 'POST',
                        body: new FormData(document.forms['name_' + item.itemid])
                    })
                    .then(response => console.log('Success!', response))
                    .catch(error => console.error('Error!', error.message))
            })

            console.log(item)
        })


        data.forEach(item => {
            const form = document.forms['remarks_' + item.itemid]
            form.addEventListener('submit', e => {
                e.preventDefault()
                fetch(scriptURL, {
                        method: 'POST',
                        body: new FormData(document.forms['remarks_' + item.itemid])
                    })
                    .then(response => console.log('Success!', response))
                    .catch(error => console.error('Error!', error.message))
            })

            console.log(item)
        })

        data.forEach(item => {
            const form = document.forms['tag_' + item.itemid]
            form.addEventListener('submit', e => {
                e.preventDefault()
                fetch(scriptURL, {
                        method: 'POST',
                        body: new FormData(document.forms['tag_' + item.itemid])
                    })
                    .then(response => console.log('Success!', response))
                    .catch(error => console.error('Error!', error.message))
            })

            console.log(item)
        })
    </script>
    <a id="back-to-top" href="#" class="go-top" role="button"><i class="fa fa-angle-up"></i></a>
</body>

</html>