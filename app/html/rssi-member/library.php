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

    <title>Libary</title>
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

<!-- =========================
     NAVIGATION LINKS     
============================== -->

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Libary</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">iExplore Learner</a></li>
                    <li class="breadcrumb-item active">Libary</li>
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
                            <div class="col" style="text-align: right;">
                                <a href="my_book.php">
                                    <button type="button" class="btn btn-success">
                                        <i class="bi bi-bag"></i> My Book
                                    </button>
                                </a>
                            </div>
                            <br>
                            <div class="row bg-light" style="padding-top: 0; padding-bottom: 1.5%; padding-left: 1.5%">
                                <div class="col-2">
                                    <label for="name" class="form-label">Class<span style="color: #F2545F"></span>&nbsp;</label>
                                    <select name="name" id="name" class="form-select notranslate">
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
                                <div class="col-2">
                                    <label for="name1" class="form-label">Subject<span style="color: #F2545F"></span>&nbsp;</label>
                                    <select name="name1" id="name1" class="form-select notranslate">
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
                                <div class="col-2">
                                    <label for="name2" class="form-label">Language<span style="color: #F2545F"></span>&nbsp;</label>
                                    <select name="name2" id="name2" class="form-select notranslate">
                                        <option value="--" selected>ALL</option>
                                        <option>Hindi</option>
                                        <option>English</option>
                                        <option>Bengali</option>
                                    </select>
                                </div>
                                <div class="col-2 d-flex align-items-end">
                                    <button id="search" type="button" class="btn btn-primary btn-sm me-2" onclick="loaddata()">
                                        <i class="bi bi-search"></i> Search
                                    </button>
                                    <?php if ($role == 'Admin') : ?>
                                        <a href="https://docs.google.com/forms/d/e/1FAIpQLSeOUDHu8tt13ltUGT2rh4UmdQlWPicuz-GBIt-uU1deC8yZuA/viewform" target="_blank">
                                            <button type="button" class="btn btn-danger btn-sm">
                                                <i class="bi bi-plus-lg"></i> Add
                                            </button>
                                        </a>
                                    <?php endif; ?>
                                </div>

                            </div>

                            <br>

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
                                                                '<td><button type="button" onClick="setTimeout(function(){window.location.reload();},10);" class="btn btn-primary btn-sm" onclick="onClick(this)"' + item.Status + '><a href="https://docs.google.com/forms/d/e/1FAIpQLScq95SXdR4fAhebTDykJKd-76AyvWlkkDlsUuKICvIWk98KmA/formResponse?entry.1000027=' + item.Bookregistrationnumber + '&entry.1929545355=' + item.Nameofthebook + '&entry.1000022=<?php echo $associatenumber ?>&entry.1000020=<?php echo $fullname ?>&entry.1000025=<?php echo $email ?>" target="_blank" class="' + item.Status + ' text-white" ><i class="bi bi-cart-plus"></i>&nbsp;Order</a></button></td>' + '</tr>');
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
  <script src="../assets_new/js/text-refiner.js"></script>
    <div class="overlay"></div>

</body>

</html>