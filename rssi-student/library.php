<?php
session_start();
// Storing Session
$user_check = $_SESSION['sid'];

if (!$_SESSION['sid']) {

    header("Location: index.php"); //redirect to the login page to secure the welcome page without login access.  
} else if ($_SESSION['filterstatus'] != 'Active') {

    //header("Location: javascript:history.back()"); //redirect to the login page to secure the welcome page without login access.
    echo '<script type="text/javascript">';
    echo 'alert("Access Denied. You are not authorized to access this web page.");';
    echo 'window.location.href = "home.php";';
    echo '</script>';
}
?>
<?php
include("student_data.php");
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

    .exam_btn:hover {
        background: #90BAA4;
        border: 1px solid #90BAA4;
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
    }
</style>
<!-- =========================
     NAVIGATION LINKS     
============================== -->

<body>

    <?php include 'header.php'; ?>

    <section id="main-content">
        <section class="wrapper main-wrapper row">
            <div class="col-md-12">
                <div class=col style="text-align: right;">Last synced: <?php echo $lastupdatedon ?></div>
                <section class="box" style="padding: 2%;">

                    <div class="container">
                        <!--<span style="color: #F2545F; display: inline;"></span>
                        <p style="display: inline;">You have to enter at least one value to get the question paper.</p>-->
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
                                    <option>Hindi</option>
                                    <option>English</option>
                                    <option>Physics</option>
                                    <option>Chemistry</option>
                                    <option>Mathematics</option>
                                    <option>Biology</option>
                                    <option>Science</option>
                                    <option>Social Science</option>
                                    <option>Computer</option>
                                    <option>GK</option>
                                    <option>Accountancy</option>
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
                                <button type="button" class="exam_btn" onclick="loaddata()"><i class="fas fa-search"></i>
                                    search</button>
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
                                                    <th><b>Book Registration Number</b></th>
                                                    <th><b>Name of the book</b></th>
                                                    <th><b>Author</b></th>
                                                    <th><b>ISBN</b></th>
                                                    <!--<th><b>Price (₹)</b></th>-->
                                                    <th><b>Subject</b></th>
                                                    <th><b>Class</b></th>
                                                    <th><b>Board</b></th>
                                                    <th><b>Language</b></th>



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
        var loaddata = function() {
            var class1 = document.getElementById('name').value
            var subject = document.getElementById('name1').value
            var language = document.getElementById('name2').value

            $.getJSON("https://spreadsheets.google.com/feeds/list/1iwtUG3z5orDmOhgN_gIEIA7yf1waA3jrgMO9pDUHnbE/1/public/values?alt=json",
                function(data) {
                    document.getElementById('demo').innerHTML = ""
                    var sheetData = data.feed.entry;
                    var i;
                    var records = []
                    for (i = 0; i < sheetData.length; i++) {
                        var Bookregistrationnumber = data.feed.entry[i]['gsx$bookregistrationnumber']['$t'];
                        var Nameofthebook = data.feed.entry[i]['gsx$nameofthebook']['$t'];
                        var Author = data.feed.entry[i]['gsx$author']['$t'];
                        var Isbn = data.feed.entry[i]['gsx$isbn']['$t'];
                        var Price = data.feed.entry[i]['gsx$price']['$t'];
                        var Subject = data.feed.entry[i]['gsx$subject']['$t'];
                        var Class = data.feed.entry[i]['gsx$class1']['$t'];
                        var Board = data.feed.entry[i]['gsx$board']['$t'];
                        var Language = data.feed.entry[i]['gsx$language']['$t'];

                        if ((Class === class1 && Subject === subject && Language === language) ||
                            (Class === class1 && subject === '--' && language === '--') ||
                            (Class === class1 && subject === '--' && Language === language) ||
                            (Class === class1 && Subject === subject && language === '--') ||

                            (class1 === '--' && Subject === subject && language === '--') ||
                            (class1 === '--' && Subject === subject && Language === language) ||

                            (class1 === '--' && subject === '--' && Language === language)||
                            (class1 === '--' && subject === '--' && language === '--')) {
                            // sort records
                            records.push({
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
                        var order = ["Hindi", "English", "Physics", "Chemistry", "Mathematics", "Biology", "Science", "Social Science",
                            "Computer", "GK", "Accountancy", "Other"
                        ]
                        // var order = ["1/CT01", "1/CT02", "QT1", "2/CT01", "2/CT02", "QT2", "3/CT01", "3/CT02", "QT3"]
                        order.forEach(sub => {
                            records.forEach(item => {
                                if (sub === item.Subject) {
                                    document.getElementById('demo').innerHTML += ('<tr>' + '<td>' + item.Bookregistrationnumber + '</td>' + '<td>' + item.Nameofthebook + '</td>' + '<td>' + item.Author + '</td>' + '<td>' + item.Isbn + '</td>' + '<!--<td>' + item.Price + '</td>-->' + '<td>' + item.Subject + '</td>' + '<td>' + item.Class + '</td>' + '<td>' + item.Board + '</td>' + '<td>' + item.Language + '</td>' + '</tr>');
                                }
                            })
                        })

                    }
                });
        }

        document.addEventListener("DOMContentLoaded", function() {
            var lazyloadImages = document.querySelectorAll("img.lazy");
            var lazyloadThrottleTimeout;

            function lazyload() {
                if (lazyloadThrottleTimeout) {
                    clearTimeout(lazyloadThrottleTimeout);
                }

                lazyloadThrottleTimeout = setTimeout(function() {
                    var scrollTop = window.pageYOffset;
                    lazyloadImages.forEach(function(img) {
                        if (img.offsetTop < (window.innerHeight + scrollTop)) {
                            img.src = img.dataset.src;
                            img.classList.remove('lazy');
                        }
                    });
                    if (lazyloadImages.length == 0) {
                        document.removeEventListener("scroll", lazyload);
                        window.removeEventListener("resize", lazyload);
                        window.removeEventListener("orientationChange", lazyload);
                    }
                }, 20);
            }

            document.addEventListener("scroll", lazyload);
            window.addEventListener("resize", lazyload);
            window.addEventListener("orientationChange", lazyload);
        });
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
</body>

</html>