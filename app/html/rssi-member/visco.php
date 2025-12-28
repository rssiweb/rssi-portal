<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}

validation();

?>


<!DOCTYPE html>
<html lang="en">

<head>
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=AW-11316670180"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'AW-11316670180');
</script>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <title>VISCO</title>
    <meta content="" name="description">
    <meta content="" name="keywords">

    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">


    <script src="//netdna.bootstrapcdn.com/bootstrap/3.1.0/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>

    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/gh/manucaralmo/GlowCookies@3.0.1/src/glowCookies.min.js"></script>
    <!-- Glow Cookies v3.0.1 -->
    <script>
        glowCookies.start('en', {
            analytics: 'G-S25QWTFJ2S',
            //facebookPixel: '',
            policyLink: 'https://www.rssi.in/disclaimer'
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
<?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>VISCO</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">iExplore Learner</a></li>
                    <li class="breadcrumb-item active">VISCO</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section dashboard">
            <div class="row">

                <!-- Reports -->
                <div class="col-12">
                    <div class="card">

                        <div class="card-body">
                            <br>

                            <div class="container">
                                <div class="row bg-light" style="background-color: rgb(255, 245, 194); height: 110%; padding-top: 0; padding-bottom: 1.5%; padding-left: 1.5%">
                                    <div class="col-2">
                                        <label for="name" class="form-label">Category<span style="color: #F2545F"></span>&nbsp;</label>
                                        <select name="name" id="name" class="form-select notranslate">
                                            <option value="--" selected>ALL</option>
                                            <option>LG3</option>
                                            <option>LG4S1</option>
                                            <option>LG4</option>
                                            <option>LG4S2</option>
                                            <option>WLG3</option>
                                            <option>WLG4S1</option>
                                        </select>
                                    </div>
                                    <div class="col-2">
                                        <label for="name1" class="form-label">Subject<span style="color: #F2545F"></span>&nbsp;</label>
                                        <select name="name1" id="name1" class="form-select notranslate">
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
                                    <div class="col-2 d-flex align-items-end">
                                        <button type="button" class="btn btn-primary btn-sm me-2" onclick="loaddata()">
                                            <i class="bi bi-search"></i> Search
                                        </button>
                                        <a href="https://docs.google.com/forms/d/e/1FAIpQLSehs8jikEjGGysmho6ceQl939Xfe_Vwdvqe5Os2RJpfmsxZ4w/viewform?usp=pp_url&entry.333758641=<?php echo $fullname ?>" target="_blank">
                                            <button type="button" class="btn btn-danger btn-sm">
                                                <i class="bi bi-plus-lg"></i> Add
                                            </button>
                                        </a>
                                    </div>
                                </div>
                            </div>

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
                        </div>

                    </div>
                </div><!-- End Reports -->
            </div>
        </section>

    </main><!-- End #main -->

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

    <!-- Template Main JS File -->
      <script src="../assets_new/js/main.js"></script>
  
    <div class="overlay"></div>

</body>

</html>