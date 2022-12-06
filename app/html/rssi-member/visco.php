<?php
session_start();
// Storing Session
include("../../util/login_util.php");

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


if ($filterstatus != 'Active') {

    echo '<script type="text/javascript">';
    echo 'alert("Access Denied. You are not authorized to access this web page.");';
    echo 'window.location.href = "profile.php";';
    echo '</script>';
}

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="description" content="">
    <meta name="author" content="">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Visco</title>
    <link href="//netdna.bootstrapcdn.com/bootstrap/3.1.0/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <style>
        <?php include '../css/style.css'; ?>
    </style>
    <script src="//netdna.bootstrapcdn.com/bootstrap/3.1.0/js/bootstrap.min.js"></script>
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
        margin-top: 2%;
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

    section.box {
        width: 100%;
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
                        <div class="row" style="background-color: rgb(255, 245,
                194);height: 110%; padding-top: 0; padding-bottom:
                1.5%;padding-left: 1.5%">


                            <div class="col2">
                                <label for="name">Category<span style="color: #F2545F"></span>&nbsp;</label>
                                <select name="name" id="name" class="notranslate">
                                    <option value="--" selected>ALL</option>
                                    <option>LG3</option>
                                    <option>LG4S1</option>
                                    <option>LG4</option>
                                    <option>LG4S2</option>
                                    <option>WLG3</option>
                                    <option>WLG4S1</option>
                                </select>
                            </div>
                            <div class="col2">
                                <label for="name1">Subject<span style="color: #F2545F"></span>&nbsp;</label>
                                <select name="name1" id="name1" class="notranslate">
                                    <option value="--" selected>ALL</option>
                                    <option>Hindi</option>
                                    <option>English</option>
                                    <option>Bengali</option>
                                    <option>Sanskrit</option>
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
                            <div class="col2">
                                <button type="button" class="exam_btn" onclick="loaddata()"><i class="fas fa-search"></i>
                                    search</button>
                                <a href="https://docs.google.com/forms/d/e/1FAIpQLSehs8jikEjGGysmho6ceQl939Xfe_Vwdvqe5Os2RJpfmsxZ4w/viewform?usp=pp_url&entry.333758641=<?php echo $fullname ?>" target="_blank"><button type="button" class="exam_btn"><i class="fas fa-plus"></i>
                                        add</button></a>
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
                                                    <th><b>Details</b></th>
                                                    <th width="50%"><b>Watch video</b></th>

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
            var category = document.getElementById('name').value
            var subject = document.getElementById('name1').value

            $.getJSON("https://sheets.googleapis.com/v4/spreadsheets/1wuNRtDoSYUDyaTWCfgBze8wn7k0VbDvF2qOOOug_Df8/values/videos?alt=json&key=AIzaSyAO7Z3VLtKImi3UGFE6n6QKhDqfDBBCT3o",
                function(data) {
                    document.getElementById('demo').innerHTML = ""
                    var sheetData = data.values;
                    var i;
                    var records = []
                    for (i = 0; i < sheetData.length; i++) {
                        var Timestamp = sheetData[i][0];
                        var Subject = sheetData[i][1];
                        var Category = sheetData[i][2];
                        var Fuploadvideo = sheetData[i][8];
                        var Class = sheetData[i][6];
                        var Uploadedby = sheetData[i][4];
                        var Topicofthevideo = sheetData[i][5];

                        if ((Category === category && Subject === subject) ||
                            (category === '--' && Subject === subject) ||
                            (Category === category && subject === '--') ||
                            (category === '--' && subject === '--')) {
                            // sort records
                            records.push({
                                Timestamp: Timestamp,
                                Subject: Subject,
                                Category: Category,
                                Fuploadvideo: Fuploadvideo,
                                Uploadedby: Uploadedby,
                                Topicofthevideo: Topicofthevideo,
                                Class: Class,
                            })
                        }

                        // else {document.getElementById('demo').innerHTML= "no record2";}
                    }
                    if (records.length == 0) {
                        document.getElementById('demo').innerHTML += ('<tr>' + '<td>' + '<p style="color:#F2545F">No record found.</p>' + '</td></tr>');
                    } else {
                        var order = ["LG3", "LG4S1", "LG4", "LG4S2", "WLG3", "WLG4S1"]
                        // var order = ["1/CT01", "1/CT02", "QT1", "2/CT01", "2/CT02", "QT2", "3/CT01", "3/CT02", "QT3"]
                        order.forEach(sub => {
                            records.forEach(item => {
                                if (sub === item.Category) {
                                    document.getElementById('demo').innerHTML += ('<tr>' + '<td style="line-height:2">' + item.Subject + '&nbsp;-&nbsp;' + item.Category + '&nbsp;/&nbsp;' + item.Class + '<br>' + item.Topicofthevideo + '<br><br>Uploaded by&nbsp;' + item.Uploadedby + '&nbsp;on&nbsp;' + item.Timestamp + '</td>' + '<td><div class="embed-responsive embed-responsive-16by9"><iframe class="embed-responsive-item" src="' + item.Fuploadvideo + '/preview" allowfullscreen></iframe></div></td>' + '</tr>');
                                }
                            })
                        })

                    }
                });
        }
    </script>

    <script>
        // Initiate an Ajax request on button click
        $(document).on("click", "button", function() {
            // Adding timestamp to set cache false
            $.get("viso.php?v=" + $.now(), function(data) {
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
