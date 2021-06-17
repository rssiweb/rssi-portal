<?php
session_start();
// Storing Session
$user_check = $_SESSION['aid'];

if (!$_SESSION['aid']) {

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
    <title>Visco</title>
    <link href="//netdna.bootstrapcdn.com/bootstrap/3.1.0/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <style><?php include '../css/style.css'; ?></style>
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
                <div class=col style="text-align: right;">Last synced: <?php echo $lastupdatedon ?></div>
                <section class="box" style="padding: 2%;">

                    <div class="container">
                        <!--<span style="color: #F2545F; display: inline;"></span>-->
                        <p style="display: inline;">You have to enter at least one value to watch video.</p>
                        <div class="row" style="background-color: rgb(255, 245,
                194);height: 110%; padding-top: 0; padding-bottom:
                1.5%;padding-left: 1.5%">


                            <div class="col2">
                                <label for="name">Category<span style="color: #F2545F"></span>&nbsp;</label>
                                <select name="name" id="name" class="notranslate">
                                    <option selected>--</option>
                                    <option>LG3</option>
                                    <option>LG4S1</option>
                                    <option>LG4</option>
                                    <option>LG4S2</option>
                                </select></div>
                            <div class="col2">
                                <label for="name1">Subject<span style="color: #F2545F"></span>&nbsp;</label>
                                <select name="name1" id="name1" class="notranslate">
                                    <option selected>--</option>
                                    <option>Hindi</option>
                                    <option>English</option>
                                    <option>Bengali</option>
                                    <option>Sanskrit</option>
                                    <option>Physics</option>
                                    <option>Chemistry</option>
                                    <option>Mathematics</option>
                                    <option>Biology</option>
                                    <option>Science</option>
                                    <option>Social Science</option>
                                    <option>Computer</option>
                                    <option>GK</option>
                                    <option>Accountancy</option>
                                </select>
                            </div>
                            <div class="col2">
                                
                                <input type="checkbox" id="name2" name="name2" style="margin: .4rem; width:fit-content">
                                <label for="name2">All video</label>
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
            var flag = document.getElementById('name2').value

            $.getJSON("https://spreadsheets.google.com/feeds/list/1wuNRtDoSYUDyaTWCfgBze8wn7k0VbDvF2qOOOug_Df8/2/public/values?alt=json",
                function(data) {
                    document.getElementById('demo').innerHTML = ""
                    var sheetData = data.feed.entry;
                    var i;
                    var records = []
                    for (i = 0; i < sheetData.length; i++) {
                        var Timestamp = data.feed.entry[i]['gsx$timestamp']['$t'];
                        var Subject = data.feed.entry[i]['gsx$subject']['$t'];
                        var Category = data.feed.entry[i]['gsx$category']['$t'];
                        var Uploadvideo = data.feed.entry[i]['gsx$uploadvideo']['$t'];
                        var Class = data.feed.entry[i]['gsx$class']['$t'];
                        var Uploadedby = data.feed.entry[i]['gsx$uploadedby']['$t'];
                        var Topicofthevideo = data.feed.entry[i]['gsx$topicofthevideo']['$t'];
                        var Flag = data.feed.entry[i]['gsx$flag']['$t'];

                        if ((category === '--' && subject === '--' && Flag === flag) ||
                            (Category === category && subject === '--') ||
                            (category === '--' && Subject === subject) ||
                            (Category === category && Subject === subject)) {
                            // sort records
                            records.push({
                                Timestamp: Timestamp,
                                Subject: Subject,
                                Category: Category,
                                Uploadvideo: Uploadvideo,
                                Uploadedby: Uploadedby,
                                Topicofthevideo: Topicofthevideo,
                                Class: Class,
                                Flag: Flag
                            })
                        }

                        // else {document.getElementById('demo').innerHTML= "no record2";}
                    }
                    if (records.length == 0) {
                        document.getElementById('demo').innerHTML += ('<tr>' + '<td>' + '<p style="color:#F2545F">No record found.</p>' + '</td></tr>');
                    } else {
                        var order = ["LG3", "LG4S1", "LG4", "LG4S2"]
                        // var order = ["1/CT01", "1/CT02", "QT1", "2/CT01", "2/CT02", "QT2", "3/CT01", "3/CT02", "QT3"]
                        order.forEach(sub => {
                            records.forEach(item => {
                                if (sub === item.Category) {
                                    document.getElementById('demo').innerHTML += ('<tr>' + '<td style="line-height:2">' + item.Subject + '&nbsp;-&nbsp;' + item.Category + '&nbsp;/&nbsp;' + item.Class + '<br>' + item.Topicofthevideo + '<br><br>Uploaded by&nbsp;' + item.Uploadedby + '&nbsp;on&nbsp;' + item.Timestamp + '</td>' + '<td><div class="embed-responsive embed-responsive-16by9"><iframe class="embed-responsive-item" src="' + item.Uploadvideo + '" allowfullscreen></iframe></div></td>' + '</tr>');
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
    <script> 
   $(document).ready(function() {
         $('#name2').click(function(){
             if($(this).is(':checked'))
             {              
                  $(this).val('1');
             }
             else
             {
                 $(this).val('0');
             }
         });
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