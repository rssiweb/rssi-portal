<?php
session_start();
// Storing Session
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
    <title>Question</title>
    <link href="//netdna.bootstrapcdn.com/bootstrap/3.1.0/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <!-- Main css -->
    <style>
    <?php include '../css/style.css'; ?></style>
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

    .navbar {
        padding: unset;
        font-weight: unset;
        display: inline-block;
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
                <section class="box" style="padding: 2%;">

                    <div class="container">
                        <!--<span style="color: #F2545F; display: inline;"></span>-->
                        <p style="display: inline;">You have to enter at least one value to get the question paper.</p>
                        <div class="row" style="background-color: rgb(255, 245,
                194);height: 110%; padding-top: 0; padding-bottom:
                1.5%;padding-left: 1.5%">


                            <div class="col2ass">
                                <label for="name">Category<span style="color: #F2545F"></span>&nbsp;</label>
                                <select name="name" id="name" class="notranslate">
                                    <option selected>--</option>
                                    <option>LG2</option>
                                    <option>LG3</option>
                                    <option>LG4</option>
                                    <option>LG4S1</option>
                                    <option>LG4S2</option>
                                    <option>WLG4S1</option>
                                </select>
                            </div>
                            <div class="col2ass">
                                <label for="name1">Subject<span style="color: #F2545F"></span>&nbsp;</label>
                                <select name="name1" id="name1" class="notranslate">
                                    <option selected>--</option>
                                    <option>Hindi</option>
                                    <option>English</option>
                                    <option>Physics</option>
                                    <option>Chemistry</option>
                                    <option>Mathematics</option>
                                    <option>Biology</option>
                                    <option>Science</option>
                                    <option>Life science</option>
                                    <option>Physical science</option>
                                    <option>Social Science</option>
                                    <option>Computer</option>
                                    <option>GK</option>
                                    <option>Accountancy</option>
                                </select>
                            </div>
                            <div class="col2ass">
                                <label for="name2">Year<span style="color: #F2545F"></span>&nbsp;</label>
                                <select name="name2" id="name2" class="notranslate">
                                    <option selected>--</option>
                                    <option>2021-2022</option>
                                    <option>2020-2021</option>
                                </select>
                            </div>

                            <div class="col2ass">
                                <label for="name3">Exam Name<span style="color: #F2545F"></span>&nbsp;</label>
                                <select name="name3" id="name3" class="notranslate">
                                    <option selected>--</option>
                                    <option>1/CT01</option>
                                    <option>1/CT02</option>
                                    <option>QT1</option>
                                    <option>2/CT01</option>
                                    <option>2/CT02</option>
                                    <option>QT2</option>
                                    <option>3/CT01</option>
                                    <option>3/CT02</option>
                                    <option>QT3</option>
                                </select>
                            </div>

                            <div class="col2">
                                <button type="button1" class="exam_btn" onclick="loaddata()"><i class="fas fa-search"></i>
                                    search</button>
                            </div>


                        </div>
                    </div>


                    <div class="container">
                        <div class="row">
                            <div class="col">

                                <body>

                                    <!-- <div style="text-align: right;" class="white
                            hoverdecoration"><a href="#" TARGET="" class="primary_btn" style="padding-right:1.5vh; line-height: 35px;
                                padding-left:1.5vh; margin-right: 1.5%;
                                margin-top: 1.5%;margin-bottom: -10px;
                                background-color:#708090; border-color:#708090;
                                text-transform: unset;" onClick="window.open('/code', '',
                                'toolbar=no,location=no,directories=no,status=no,menubar=no,'
                                +
                                'scrollbars=no,resizable=no,width=400,height=400,left=100,top=100');return
                                false;">
                                            Test Code&nbsp;&nbsp;<i style="font-size: 15px;" class="fas fa-user-lock"></i></a></div>-->


                                    <div class="container text-center my-4" style="overflow-x:
                            auto;">
                                        <!-- Table  -->
                                        <table class="table bordered table-striped" id="testTable">
                                            <!-- Table head -->
                                            <thead style="background-color: whitesmoke;
                                    font-style: oblique;">
                                                <tr>
                                                    <th><b>Category</b></th>
                                                    <th><b>Subject</b></th>
                                                    <th><b>Test ID</b></th>
                                                    <th><b>FM</b></th>
                                                    <th><b>Exam name</b></th>
                                                    <th><b>Question paper</b></th>
                                                    <th><b>Password</b></th>



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
<script>
        // Initiate an Ajax request on button click
        $(document).on("click", "button1", function() {
            // Adding timestamp to set cache false
            $.get("question.php?v=" + $.now(), function(data) {
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
    <script type="text/javascript">
        var loaddata = function() {
            var category = document.getElementById('name').value
            var subject = document.getElementById('name1').value
            var year = document.getElementById('name2').value
            var examname = document.getElementById('name3').value

            $.getJSON("https://sheets.googleapis.com/v4/spreadsheets/1I5WANuMzHDcb2m9-PoXIOV3aZIrABUGSfwPOPNh0HBY/values/Question?alt=json&key=AIzaSyAO7Z3VLtKImi3UGFE6n6QKhDqfDBBCT3o",
                function(data) {
                    document.getElementById('demo').innerHTML = ""
                    var sheetData = data.values;
                    var i;
                    var records = []
                    for (i = 0; i < sheetData.length; i++) {
                        var Category = sheetData[i][0];
                        var Examname = sheetData[i][1];
                        var Subject = sheetData[i][2];
                        var Topic = sheetData[i][3];
                        var Fullmarks = sheetData[i][4];
                        var Year = sheetData[i][5];
                        var Testcode = sheetData[i][6];
                        var Url = sheetData[i][7];
                        

                        if ((Category === category && Subject === subject && Year === year && Examname === examname) ||
                            (Category === category && subject === '--' && year === '--' && examname === '--') ||
                            (category === '--' && Subject === subject && year === '--' && examname === '--') ||
                            (category === '--' && subject === '--' && Year === year && examname === '--') ||
                            (category === '--' && subject === '--' && year === '--' && Examname === examname) ||
                            (category === '--' && Subject === subject && year === '--' && Examname === examname) ||

                            (Category === category && Subject === subject && year === '--' && examname === '--') ||
                            (Category === category && subject === '--' && Year === year && examname === '--') ||
                            (Category === category && subject === '--' && year === '--' && Examname === examname) ||
                            (category === '--' && subject === '--' && Year === year && Examname === examname) ||
                            (category === '--' && Subject === subject && Year === year && examname === '--') ||

                            (Category === category && Subject === subject && Year === year && examname === '--') ||
                            (Category === category && Subject === subject && year === '--' && Examname === examname) ||
                            (Category === category && subject === '--' && Year === year && Examname === examname) ||
                            (category === '--' && Subject === subject && Year === year && Examname === examname)) {
                            // sort records
                            records.push({
                                Category: Category,
                                Subject: Subject,
                                Topic: Topic,
                                Fullmarks: Fullmarks,
                                Year: Year,
                                Examname: Examname,
                                Url: Url,
                                Testcode: Testcode
                            })
                        }

                        // else {document.getElementById('demo').innerHTML= "no record2";}
                    }
                    if (records.length == 0) {
                        document.getElementById('demo').innerHTML += ('<tr>' + '<td>' + '<p style="color:#F2545F">No record found.</p>' + '</td></tr>');
                    } else {
                        var order = ["LG3", "LG4", "LG4S1", "LG4S2"]
                        // var order = ["1/CT01", "1/CT02", "QT1", "2/CT01", "2/CT02", "QT2", "3/CT01", "3/CT02", "QT3"]
                        order.forEach(sub => {
                            records.forEach(item => {
                                if (sub === item.Category) {
                                    document.getElementById('demo').innerHTML += ('<tr>' + '<td>' + item.Category + '</td>' + '<td>' + item.Subject + '</td>' + '<td>' + item.Testcode + '</td>' + '<td>' + item.Fullmarks + '</td>' + '<td>' + item.Examname + '</td>' + '<td>' + '<a href="' + item.Url + '"target="blank"><div class="columnExam2 exam_btn" style="text-transform: unset;">view</div></a>' + '</td>' + '<td>' + item.Topic + '</td>' + '</tr>');
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
    <div class="overlay"></div>
</body>

</html>