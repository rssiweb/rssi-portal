<?php
session_start();
// Storing Session
include("../util/login_util.php");

if(! isLoggedIn("aid")){
    header("Location: index.php");
}
$user_check = $_SESSION['aid'];

if (!$_SESSION['aid']) {

    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;  
} else if ($_SESSION['filterstatus'] != 'Active') {

    //header("Location: javascript:history.back()"); //redirect to the login page to secure the welcome page without login access.
    echo '<script type="text/javascript">';
    echo 'alert("Access Denied. You are not authorized to access this web page.");';
    echo 'window.location.href = "home.php";';
    echo '</script>';
}
?>
<?php
include("member_data.php");
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="description" content="">
    <meta name="author" content="">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Library</title>
    <link href="//netdna.bootstrapcdn.com/bootstrap/3.1.0/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
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

</head>




<style>
    body {
        line-height: unset;
    }

    body,
    h1,
    h2,
    h3,
    h4,
    h5,
    h6 {
        font-weight: unset;
    }

    .text-center {
        text-align: left !important;
    }

    .table-striped tbody tr:nth-of-type(odd) {
        background-color: #fff;
    }

    .table-striped tbody tr {
        background-color: #FAFAFB
    }

    input,
    select {
        width: 150px;
    }

    input:focus,
    textarea:focus,
    select:focus {
        outline: none;
    }
    .columnExam2 {
        float: left;
        width: unset !important;
        padding-top: 1%;
    }

    table.table a {
        margin: 0;
        color: whitesmoke;
    }

    .col2ass {
        margin-left: 5%;
    }

    .col2 {
        width: unset;
        margin-top: 4%;
        margin-left: 5%;
    }

    .table-striped>tbody>tr:nth-child(odd)>td,
    .table-striped>tbody>tr:nth-child(odd)>th {
        background-color: unset;
    }

    h1,
    h2,
    h3,
    h4,
    h5,
    h6 {
        font-family: Roboto;
    }

    .profile-info .profile-details .profile-title {
        color: rgba(174, 178, 183, 1.0);
        font-size: 13px !important;
        margin: 0px 0px 6px 14px !important;
        line-height: unset !important;
    }

    .profile-info .profile-details h3 {
        margin: 19px 0px 5px 14px;
        line-height: 1.7;
    }

    #main-menu-wrapper ul {
        margin-top: 2px;
    }

    .dropdown-toggle::after {
        display: none;
    }

    .col2ass {
        width: 15%;
        margin-top: 25px;
    }

    @media screen and (max-width: 800px) {

        .col1,
        .col2,
        .col2ass {
            width: 100%;
            padding-left: 3%;
            margin-top: 10px;
        }

        .exam_btn {
            margin-top: 2%;
        }
    }

    .btn {
        background-color: DodgerBlue;
        border: none;
        font-size: 13px;
        cursor: pointer;
    }

    /* Darker background on mouse-over */
    .btn:hover {
        background-color: #90BAA4;
    }

    .visited {
        background-color: #90BAA4;
    }

    a.disabled {
        pointer-events: none;
        cursor: default;
    }
</style>
<!-- =========================
     NAVIGATION LINKS     
============================== -->

<body>
    <?php $library_active = 'active'; ?>
    <?php include 'header.php'; ?>

    <section id="main-content">
        <section class="wrapper main-wrapper row">
        
            <div class="col-md-12">
                <section class="box" style="padding: 2%;">
                    <div class=col style="text-align: right;"><a href="library_status.php"><button type="button" class="exam_btn"><i class="fas fa-shopping-bag"></i>
                                My Book</button></a>
                        <?php
                        if ($role == 'Admin') {
                            echo '<a href="library_status_admin.php"><button type="button" class="exam_btn"><i class="fas fa-tags"></i>
                                    all orders</button></a>';
                        }
                        ?>
                    </div><br>
                    <div class="container">
                        <div class="row" style="background-color: rgb(255, 245,
                194);height: 110%; padding-top: 0; padding-bottom:
                1.5%;padding-left: 1.5%">


                            <div class="col2ass">
                                <label for="name">Class<span style="color: #F2545F"></span>&nbsp;</label>
                                <select name="name" id="name" class="notranslate">
                                    <option value="--" selected>ALL</option>
                                    <option>1</option>
                                    <option>2</option>
                                    <option>3</option>
                                    <option>4</option>
                                    <option>5</option>
                                    <option>6</option>
                                    <option>7</option>
                                    <option>8</option>
                                    <option>9</option>
                                    <option>10</option>
                                    <option>11</option>
                                    <option>12</option>
                                    <option>Other</option>
                                </select>
                            </div>
                            <div class="col2ass">
                                <label for="name1">Subject<span style="color: #F2545F"></span>&nbsp;</label>
                                <select name="name1" id="name1" class="notranslate">
                                    <option value="--" selected>ALL</option>
                                    <option>English</option>
                                    <option>Hindi</option>
                                    <option>Bengali</option>
                                    <option>Sanskrit</option>
                                    <option>Physics</option>
                                    <option>Chemistry</option>
                                    <option>Mathematics</option>
                                    <option>Biology</option>
                                    <option>Science</option>
                                    <option>Social Science</option>
                                    <option>Computer</option>
                                    <option>GK/Current Affairs</option>
                                    <option>Accountancy</option>
                                    <option>Business Studies</option>
                                    <option>Other</option>
                                </select>
                            </div>
                            <div class="col2ass">
                                <label for="name2">Language<span style="color: #F2545F"></span>&nbsp;</label>
                                <select name="name2" id="name2" class="notranslate">
                                    <option value="--" selected>ALL</option>
                                    <option>Hindi</option>
                                    <option>English</option>
                                    <option>Bengali</option>
                                </select>
                            </div>
                            <div class="col2">
                                <button id=search type="button1" class="exam_btn" onclick="loaddata()"><i class="fas fa-search"></i>
                                    search</button>
                                <?php
                                if ($role == 'Admin') {
                                    echo '<a href="https://docs.google.com/forms/d/e/1FAIpQLSeOUDHu8tt13ltUGT2rh4UmdQlWPicuz-GBIt-uU1deC8yZuA/viewform" target="_blank"><button type="button" class="exam_btn"><i class="fas fa-plus"></i>
add</button></a>';
                                }
                                ?>

                            </div>


                        </div>
                    </div>


                    <div class="container">
                        <div class="row">
                            <div class="col">

                                <body>
                                    <div class="container text-center my-4" style="overflow-x:
                            auto;">
                                        <!-- Table  -->
                                        <table class="table bordered table-striped" id="testTable">
                                            <!-- Table head -->
                                            <thead style="background-color: whitesmoke;
                                    font-style: oblique;">
                                                <tr>
                                                    <th><b>Book Reg. No</b></th>
                                                    <th><b>Name of the book</b></th>
                                                    <th><b>Author</b></th>
                                                    <!--<th><b>ISBN</b></th>-->
                                                    <!--<th><b>Price (₹)</b></th>-->
                                                    <th><b>Subject</b></th>
                                                    <th><b>Class</b></th>
                                                    <th><b>Board</b></th>
                                                    <th><b>Language</b></th>
                                                    <!--<th><b>Availability</b></th>-->
                                                    <th><b>Order Now</b></th>



                                                </tr>
                                            </thead>
                                            <!-- Table head -->

                                            <!-- Table body -->
                                            <tbody id="demo">


                                            </tbody>
                                            <!-- Table body -->
                                        </table>
                                        <!-- Table  -->

                                    </div>

                            </div>
                        </div>
                    </div>

                </section>
            </div>
        </section>
    </section>
    <!-- =========================
     SCRIPTS   
============================== -->
    <script type="text/javascript">
        var clicks = 0;

        function onClick(event) {
            event.className = "btn visited";
        };
    </script>
    <script>
        // Initiate an Ajax request on button click
        $(document).on("click", "button1", function() {
            // Adding timestamp to set cache false
            $.get("libray.php?v=" + $.now(), function(data) {
                $("body").html(data);
            });
        });

        // Add remove loading class on body element depending on Ajax request status
        $(document).on({
            ajaxStart: function() {
                $("body").addClass("loading");
            },
            ajaxStop: function() {
                $("body").removeClass("loading");
            }
        });
    </script>
    <script>
        function loaddata() {
            var class1 = document.getElementById('name').value
            var subject = document.getElementById('name1').value
            var language = document.getElementById('name2').value

            $.getJSON("https://sheets.googleapis.com/v4/spreadsheets/1iwtUG3z5orDmOhgN_gIEIA7yf1waA3jrgMO9pDUHnbE/values/booklist?alt=json&key=AIzaSyAO7Z3VLtKImi3UGFE6n6QKhDqfDBBCT3o",
                function(data) {
                    document.getElementById('demo').innerHTML = ""
                    var sheetData = data.values;
                    var i;
                    var records = []
                    for (i = 0; i < sheetData.length; i++) {
                        var Bookregistrationnumber = sheetData[i][1];
                        var Nameofthebook = sheetData[i][2];
                        var Author = sheetData[i][3];
                        var Isbn = sheetData[i][4];
                        var Price = sheetData[i][5];
                        var Subject = sheetData[i][6];
                        var Class = sheetData[i][7];
                        var Board = sheetData[i][8];
                        var Language = sheetData[i][9];
                        var Status = sheetData[i][10];
                        var Availability = sheetData[i][11];
                        

                        if ((Class === class1 && Subject === subject && Language === language) ||
                            (Class === class1 && subject === '--' && language === '--') ||
                            (Class === class1 && subject === '--' && Language === language) ||
                            (Class === class1 && Subject === subject && language === '--') ||

                            (class1 === '--' && Subject === subject && language === '--') ||
                            (class1 === '--' && Subject === subject && Language === language) ||

                            (class1 === '--' && subject === '--' && Language === language) ||
                            (class1 === '--' && subject === '--' && language === '--')) {
                            // sort records
                            records.push({
                                Availability: Availability,
                                Status: Status,
                                Bookregistrationnumber: Bookregistrationnumber,
                                Nameofthebook: Nameofthebook,
                                Author: Author,
                                Isbn: Isbn,
                                Price: Price,
                                Subject: Subject,
                                Class: Class,
                                Board: Board,
                                Language: Language
                            })
                        }

                        // else {document.getElementById('demo').innerHTML= "no record2";}
                    }
                    if (records.length == 0) {
                        document.getElementById('demo').innerHTML += ('<tr>' + '<td>' + '<p style="color:#F2545F">No record found.</p>' + '</td></tr>');
                    } else {
                        var order = ["English", "Hindi", "Bengali", "Sanskrit", "Physics", "Chemistry", "Mathematics", "Biology",
                            "Science", "Social Science", "Computer", "GK/Current Affairs", "Accountancy", "Business Studies", "Other"
                        ]
                        // var order = ["1/CT01", "1/CT02", "QT1", "2/CT01", "2/CT02", "QT2", "3/CT01", "3/CT02", "QT3"]
                        order.forEach(sub => {
                            records.forEach(item => {
                                if (sub === item.Subject) {
                                    document.getElementById('demo').innerHTML += ('<tr>' + '<td>' + item.Bookregistrationnumber + '</td>' + '<td>' + item.Nameofthebook + '</td>' + '<td>' + item.Author + '</td>' + '<!--<td>' + item.Isbn + '</td>-->' + '<!--<td>' + item.Price + '</td>-->' + '<td>' + item.Subject + '</td>' + '<td>' + item.Class + '</td>' + '<td>' + item.Board + '</td>' + '<td>' + item.Language + '</td>' +
                                        '<td><a href="https://docs.google.com/forms/d/e/1FAIpQLScq95SXdR4fAhebTDykJKd-76AyvWlkkDlsUuKICvIWk98KmA/formResponse?entry.1000027=' + item.Bookregistrationnumber + '&entry.1929545355=' + item.Nameofthebook + '&entry.1000022=<?php echo $associatenumber ?>&entry.1000020=<?php echo $fullname ?>&entry.1000025=<?php echo $email ?>" target="_blank" class="' + item.Status + '"><button onClick="setTimeout(function(){window.location.reload();},10);" class="btn ' + item.Status + '" style="color:#fff" onclick="onClick(this)"><i class="fas fa-cart-plus" style="font-size:16px;"></i> &nbsp;Order Now</button></a></td>' + '</tr>');
                                }
                            })
                        })

                    }
                });
        }
        window.onload = function() {
            document.getElementById('search').click();
        }
    </script>
    <!-- =========================
     FOOTER   
============================== -->
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
    <div class="overlay"></div>
</body>

</html>