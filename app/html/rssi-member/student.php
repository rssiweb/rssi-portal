<?php
session_start();
// Storing Session
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
  $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
  header("Location: index.php");
  exit;
}
if ($filterstatus != 'Active') {

  echo '<script type="text/javascript">';
  echo 'alert("Access Denied. You are not authorized to access this web page.");';
  echo 'window.location.href = "home.php";';
  echo '</script>';
}

@$module = $_POST['get_module'];
@$id = $_POST['get_id'];
@$category = $_POST['get_category'];
@$class = $_POST['get_class'];
@$stid = $_POST['get_stid'];
@$is_user = $_POST['is_user'];
// $categories = "'".implode("','", $category)."'";



if ($id == 'ALL' && $category == 'ALL' && ($class == 'ALL' || $class == null)) {
  $result = pg_query($con, "SELECT * FROM rssimyprofile_student 
  left join (SELECT studentid, TO_CHAR(TO_DATE (max(month)::text, 'MM'), 'Mon'
    ) AS maxmonth FROM fees group by studentid) fees ON fees.studentid=rssimyprofile_student.student_id
  WHERE module='$module' order by filterstatus asc, category asc, studentname asc");
}

if ($id == 'ALL' && $category == 'ALL' && ($class != 'ALL' && $class != null)) {
  $result = pg_query($con, "SELECT * FROM rssimyprofile_student
  left join (SELECT studentid, TO_CHAR(TO_DATE (max(month)::text, 'MM'), 'Mon'
    ) AS maxmonth FROM fees group by studentid) fees ON fees.studentid=rssimyprofile_student.student_id
  WHERE class='$class' AND module='$module' order by category asc, studentname asc");
}

if ($id != 'ALL' && $category == 'ALL' && ($class == null || $class == 'ALL')) {
  $result = pg_query($con, "SELECT * FROM rssimyprofile_student 
  left join (SELECT studentid, TO_CHAR(TO_DATE (max(month)::text, 'MM'), 'Mon'
    ) AS maxmonth FROM fees group by studentid) fees ON fees.studentid=rssimyprofile_student.student_id
  WHERE filterstatus='$id' AND module='$module' order by category asc, studentname asc");
}

if ($id != 'ALL' && $category != 'ALL' && ($class != 'ALL' && $class != null)) {
  $result = pg_query($con, "SELECT * FROM rssimyprofile_student left join (SELECT studentid, TO_CHAR(TO_DATE (max(month)::text, 'MM'), 'Mon'
    ) AS maxmonth FROM fees group by studentid) fees ON fees.studentid=rssimyprofile_student.student_id
  WHERE filterstatus='$id' AND module='$module' AND category='$category' order by category asc, studentname asc");
}

if ($id == 'ALL' && $category != 'ALL' && $class != 'ALL' && $class != null) {
  $result = pg_query($con, "SELECT * FROM rssimyprofile_student left join (SELECT studentid, TO_CHAR(TO_DATE (max(month)::text, 'MM'), 'Mon'
    ) AS maxmonth FROM fees group by studentid) fees ON fees.studentid=rssimyprofile_student.student_id
  WHERE class='$class' AND module='$module' AND category='$category' order by filterstatus asc,category asc, studentname asc");
}

if ($id != 'ALL' && $category != 'ALL' && $class != 'ALL' && $class != null) {
  $result = pg_query($con, "SELECT * FROM rssimyprofile_student left join (SELECT studentid, TO_CHAR(TO_DATE (max(month)::text, 'MM'), 'Mon'
    ) AS maxmonth FROM fees group by studentid) fees ON fees.studentid=rssimyprofile_student.student_id
  WHERE class='$class' AND module='$module' AND filterstatus='$id' AND category='$category' order by category asc, studentname asc");
}

if ($id != 'ALL' && $category == 'ALL' && $class == 'ALL') {
  $result = pg_query($con, "SELECT * FROM rssimyprofile_student left join (SELECT studentid, TO_CHAR(TO_DATE (max(month)::text, 'MM'), 'Mon'
    ) AS maxmonth FROM fees group by studentid) fees ON fees.studentid=rssimyprofile_student.student_id
  WHERE module='$module' AND filterstatus='$id' order by category asc, studentname asc");
}

if ($id != 'ALL' && $category == 'ALL' && $class != 'ALL' && $class != null) {
  $result = pg_query($con, "SELECT * FROM rssimyprofile_student left join (SELECT studentid, TO_CHAR(TO_DATE (max(month)::text, 'MM'), 'Mon'
    ) AS maxmonth FROM fees group by studentid) fees ON fees.studentid=rssimyprofile_student.student_id
  WHERE class='$class' AND module='$module' AND filterstatus='$id' order by category asc, studentname asc");
}

if ($id != 'ALL' && $category != 'ALL' && ($class == 'ALL' || $class == null)) {
  $result = pg_query($con, "SELECT * FROM rssimyprofile_student left join (SELECT studentid, TO_CHAR(TO_DATE (max(month)::text, 'MM'), 'Mon'
    ) AS maxmonth FROM fees group by studentid) fees ON fees.studentid=rssimyprofile_student.student_id
  WHERE category='$category' AND module='$module' AND filterstatus='$id' order by category asc, studentname asc");
}

if ($id == 'ALL' && $category != 'ALL' && ($class == 'ALL' || $class == null)) {
  $result = pg_query($con, "SELECT * FROM rssimyprofile_student left join (SELECT studentid, TO_CHAR(TO_DATE (max(month)::text, 'MM'), 'Mon'
    ) AS maxmonth FROM fees group by studentid) fees ON fees.studentid=rssimyprofile_student.student_id
  WHERE module='$module' AND category='$category' order by category asc, studentname asc");
}

if ($stid != null && $module == null  && $id == null && $category == null && $class == null) {
  $result = pg_query($con, "SELECT * FROM rssimyprofile_student 
  left join (SELECT studentid, TO_CHAR(TO_DATE (max(month)::text, 'MM'), 'Mon'
    ) AS maxmonth FROM fees group by studentid) fees ON fees.studentid=rssimyprofile_student.student_id
  WHERE student_id='$stid' order by filterstatus asc, category asc, studentname asc");
}


if (!$result) {
  echo "An error occurred.\n";
  exit;
}

$resultArr = pg_fetch_all($result);
?>

<!DOCTYPE html>
<html>

<head>
  <meta name="description" content="">
  <meta name="author" content="">
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
  <title>Student database</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
  <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
  <!-- Main css -->
  <style>
    <?php include '../css/style.css'; ?>
  </style>
  <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>

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
    }

    td {

      /* css-3 */
      white-space: -o-pre-wrap;
      word-wrap: break-word;
      white-space: pre-wrap;
      white-space: -moz-pre-wrap;
      white-space: -pre-wrap;

    }

    table {
      table-layout: fixed;
      width: 100%
    }

    @media (min-width:767px) {
      .left {
        margin-left: 2%;
      }
    }

    @media (max-width:767px) {

      #cw,
      #cw1,
      #cw2,
      #cw3 {
        width: 100% !important;
      }

    }

    #cw {
      width: 7%;
    }

    #cw1 {
      width: 20%;
    }

    #cw2 {
      width: 8%;
    }

    #cw3 {
      width: 15%;
    }

    #cw4 {
      width: 10%;
    }

    #yes {
      border: none !important;
    }
  </style>

</head>

<body>
  <?php include 'header.php'; ?>
  <section id="main-content">
    <section class="wrapper main-wrapper row">
      <div class="col-md-12">
        <div class="row">
          <div class="col" style="display: inline-block; width:50%;margin-left:1.5%">
            Record count:&nbsp;<?php echo sizeof($resultArr) ?>
          </div>
          <!-- <div class="col" style="display: inline-block; width:47%; text-align:right">
            Home / RSSI Student
          </div> -->
          <?php if ($role == 'Admin' || $role == 'Offline Manager') { ?>
            <div class="col" style="display: inline-block; width:47%; text-align:right">
              <a href="fees.php" target="_self" class="btn btn-danger btn-sm" role="button">Fees Details</a>

              <br><br>
              <form method="POST" action="export_function.php">
                <input type="hidden" value="student" name="export_type" />
                <input type="hidden" value="<?php echo $module ?>" name="module" />
                <input type="hidden" value="<?php echo $id ?>" name="id" />
                <input type="hidden" value="<?php echo $category ?>" name="category" />
                <input type="hidden" value="<?php echo $class ?>" name="class" />

                <button type="submit" id="export" name="export" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none;
                        padding: 0px;
                        border: none;" title="Export CSV"><i class="fa-regular fa-file-excel" style="font-size:large;"></i></button>
              </form>
            </div>
          <?php } else { ?>
            <div class="col" style="display: inline-block; width:47%; text-align:right">
              <a href="javascript:void(0);" target="_self" class="btn btn-danger btn-sm disabled" role="button">Fees Details</a>
            </div>
          <?php } ?>
        </div>
        <section class="box" style="padding: 2%;">
          <form action="" method="POST">
            <div class="form-group" style="display: inline-block;">
              <div class="col2" style="display: inline-block;">
                <input type="hidden" name="form-type" type="text" value="search">
                <select name="get_module" id="get_module" class="form-control" style="width:max-content; display:inline-block" required>
                  <?php if ($module == null) { ?>
                    <option value="" disabled selected hidden>Select Module</option>
                  <?php
                  } else { ?>
                    <option hidden selected><?php echo $module ?></option>
                  <?php }
                  ?>
                  <option>National</option>
                  <option>State</option>
                </select>
                <select name="get_id" id="get_id" class="form-control" style="width:max-content; display:inline-block" required>
                  <?php if ($id == null) { ?>
                    <option value="" disabled selected hidden>Select Status</option>
                  <?php
                  } else { ?>
                    <option hidden selected><?php echo $id ?></option>
                  <?php }
                  ?>
                  <option>Active</option>
                  <option>Inactive</option>
                  <option>ALL</option>
                </select>
                <select name="get_category" id="get_category" class="form-control" style="width:max-content;display:inline-block" required>
                  <?php if ($category == null) { ?>
                    <option value="" disabled selected hidden>Select Category</option>
                  <?php
                  } else { ?>
                    <option hidden selected><?php echo $category ?></option>
                  <?php }
                  ?>
                  <option>LG2-A</option>
                  <option>LG2-B</option>
                  <option>LG2-C</option>
                  <option>LG3</option>
                  <option>LG4</option>
                  <option>LG4S1</option>
                  <option>LG4S2</option>
                  <option>WLG3</option>
                  <option>WLG4S1</option>
                  <option>Undefined</option>
                  <option>ALL</option>
                </select>
                <select name="get_class" id="get_class" class="form-control" style="width:max-content; display:inline-block">
                  <?php if ($class == null) { ?>
                    <option value="" disabled selected hidden>Select Class</option>
                  <?php
                  } else { ?>
                    <option hidden selected><?php echo $class ?></option>
                  <?php }
                  ?>
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
                  <option>Vocational training</option>
                  <option>x</option>
                  <option>ALL</option>
                </select>
                <input name="get_stid" id="get_stid" class="form-control" style="width:max-content; display:inline-block" placeholder="Student ID" value="<?php echo $stid ?>" required>
              </div>
            </div>
            <div class="col2 left" style="display: inline-block;">
              <button type="submit" name="search_by_id" class="btn btn-success btn-sm" style="outline: none;">
                <i class="fa-solid fa-magnifying-glass"></i>&nbsp;Search</button>
            </div>
            <div id="filter-checks">
              <input type="checkbox" name="is_user" id="is_user" value="1" <?php if (isset($_POST['is_user'])) echo "checked='checked'"; ?> />
              <label for="is_user" style="font-weight: 400;">Search by Student ID</label>
            </div>
          </form>
          <script>
            if ($('#is_user').not(':checked').length > 0) {

              document.getElementById("get_module").disabled = false;
              document.getElementById("get_id").disabled = false;
              document.getElementById("get_category").disabled = false;
              document.getElementById("get_class").disabled = false;
              document.getElementById("get_stid").disabled = true;

            } else {

              document.getElementById("get_module").disabled = true;
              document.getElementById("get_id").disabled = true;
              document.getElementById("get_category").disabled = true;
              document.getElementById("get_class").disabled = true;
              document.getElementById("get_stid").disabled = false;

            }

            const checkbox = document.getElementById('is_user');

            checkbox.addEventListener('change', (event) => {
              if (event.target.checked) {
                document.getElementById("get_module").disabled = true;
                document.getElementById("get_id").disabled = true;
                document.getElementById("get_category").disabled = true;
                document.getElementById("get_class").disabled = true;
                document.getElementById("get_stid").disabled = false;
              } else {
                document.getElementById("get_module").disabled = false;
                document.getElementById("get_id").disabled = false;
                document.getElementById("get_category").disabled = false;
                document.getElementById("get_class").disabled = false;
                document.getElementById("get_stid").disabled = true;
              }
            })
          </script>

          <?php
          echo '<table class="table">
          <thead style="font-size: 12px;">
          <tr>
          <th scope="col" id="cw">Photo</th>
          <th scope="col" id="cw1">Student Details</th>
          <th scope="col">Class</th>
          <th scope="col">Contact</th>
          <th scope="col">Medium</th>
          <th scope="col">Badge</th>
          <th scope="col"></th>
        </tr>
        </thead>' ?>
          <?php if (sizeof($resultArr) > 0) { ?>
            <?php
            echo '<tbody>';
            foreach ($resultArr as $array) {
              echo '<tr>
            <td><img src="' . $array['photourl'] . '" width=50px/></td>
            <td>Name - <b>' . $array['studentname'] . '</b><br>Student ID - <b>' . $array['student_id'] . '</b>
            <br><b>' . $array['gender'] . '&nbsp;(' . $array['age'] . ')</b><br><br>DOA - ' . $array['doa'] . '</td>
            <td style="white-space: unset;">' . $array['class'] . '/' . $array['category'] ?>

              <?php if ($array['maxmonth'] != null && ($role == 'Admin' || $role == 'Offline Manager')) { ?>

                <?php echo '&nbsp;&nbsp;<p style="display: inline !important;" class="label label-default">PAID&nbsp;-&nbsp;' . $array['maxmonth'] . '</p></td><td style="white-space: unset;">' ?>
                <?php    } else { ?><?php echo '</td><td style="white-space: unset;">' ?><?php   } ?>

                <?php if ($role == 'Admin') { ?>

                  <?php echo $array['contact'] ?>
                <?php    } else { ?>

                  <?php echo "xxxxxx" . substr($array['contact'], 6) ?>

                <?php   } ?>
              <?php echo $array['emailaddress'] . '</td>
            <td>' . $array['medium'] . '/' . $array['nameoftheboard'] . '</td>
            <td style="white-space: unset">' . $array['badge'] . '</td>

            <td><a href="javascript:void(0)" onclick="showDetails(\'' . $array['student_id'] . '\')"><button type="button" id="btn" class="btn btn-info btn-sm" style="outline: none"><i class="fa-solid fa-eye"></i>&nbsp;Details</button></a>
            
            
            </td>
        </tr>';
            } ?>
            <?php
          } else if ($module == "" && $stid == "") {
            ?>
              <tr>
                <td colspan="5">Please select Filter value.</td>
              </tr>
            <?php
          } else if (sizeof($resultArr) == 0 && $stid == "") {
            ?>
              <tr>
                <td colspan="5">No record found for <?php echo $module ?>, <?php echo $id ?> and <?php echo $category ?>&nbsp;<?php echo $class ?></td>
              </tr>

            <?php } else if (sizeof($resultArr) == 0 && $stid != "") {
            ?>
              <tr>
                <td colspan="5">No record found for <?php echo $stid ?></td>
              </tr>
            <?php }

          echo '</tbody>
                        </table>';
            ?>
      </div>
      </div>
      </div>
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


      <img id="profileimage" src="#" class="img-circle img-inline" class="img-responsive img-circle" width="50" height="50" style="display: inline;" />&nbsp;

      <b><span class="studentname"></span>&nbsp;(<span class="student_id"></span>)</b>

      <p id="status" class="label " style="display: inline !important;"><span class="filterstatus"></span></p>

      <div class="col" style="display: inline-block; width:50%; text-align:right"><a id="profile" href="#" target="_blank"><i class="fa-regular fa-file-pdf" style="font-size: 20px ;color:#777777" title="Profile"></i></a></div>

      <br><br>
      <!-- <p id="laddu"></p> -->
      <p style="font-size: small; line-height:2">
        Subject: <span class="nameofthesubjects"></span><br>
        Attendance: <span class="attd"></span></p>
      Remarks:&nbsp;<span class="remarks"></span><br><br>

      <?php if ($role == 'Admin' || $role == 'Offline Manager') { ?>

        <b>
          <p style="display: inline; font-size: small;">Fee</p>&nbsp;
          <p style="display: inline !important;" class="label label-default">PAID&nbsp;-&nbsp;<span class="maxmonth"></span></p>
        </b><br><br>

        <form name="payment" action="#" method="POST" onsubmit="myFunction()">
          <input type="hidden" name="form-type" type="text" value="payment">
          <input type="hidden" class="form-control" name="sname" id="sname" type="text" value="">
          <input type="hidden" class="form-control" name="studentid" id="studentid" type="text" value="">
          <input type="hidden" class="form-control" name="collectedby" id="collectedby" type="text" value="">

          <select name="ptype" id="ptype" class="form-control" style="display: -webkit-inline-box; width:20vh; font-size: small;" required>
            <option value="" disabled selected hidden>Select Type</option>
            <option value="Fees" selected>Fees</option>
            <option value="Admission Fee">Admission Fee</option>
            <option value="Fine">Fine</option>
            <option value="Other">Other</option>
          </select>

          <select name="month" id="month" class="form-control" style="display: -webkit-inline-box; width:20vh; font-size: small;" required>
            <option value="" disabled selected hidden>Select Month</option>
            <option value="1">January</option>
            <option value="2">February</option>
            <option value="3">March</option>
            <option value="4">April</option>
            <option value="5">May</option>
            <option value="6">June</option>
            <option value="7">July</option>
            <option value="8">August</option>
            <option value="9">September</option>
            <option value="10">October</option>
            <option value="11">November</option>
            <option value="12">December</option>
          </select>

          <input type="number" name="fees" id="fees" class="form-control" style="display: -webkit-inline-box; width:15vh;font-size: small;" placeholder="Amount" required>&nbsp;
          <br><br>
          <button type="submit" id="yes" class="btn btn-danger btn-sm " style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none"><i class="fa-solid fa-arrows-rotate"></i>&nbsp;&nbsp;Update</button>
        </form><br>
        <script>
          function myFunction() {
            alert("Fee has been deposited successfully.");
          }
        </script>
        <script>
          const scriptURL = 'payment-api.php'
          const form = document.forms['payment']

          form.addEventListener('submit', e => {
            e.preventDefault()
            fetch(scriptURL, {
                method: 'POST',
                body: new FormData(document.forms['payment'])
              })
              .then(response => console.log('Success!', response))
              .catch(error => console.error('Error!', error.message))
          })
        </script>


    </div>

  </div>
<?php } ?>
<script>
  var data = <?php echo json_encode($resultArr) ?>;
  var aid = <?php echo '"' . $_SESSION['aid'] . '"' ?>;

  // Get the modal
  var modal = document.getElementById("myModal");
  // Get the <span> element that closes the modal
  var span = document.getElementsByClassName("close")[0];

  function showDetails(id) {
    // console.log(modal)
    // console.log(modal.getElementsByClassName("data"))
    var mydata = undefined
    data.forEach(item => {
      if (item["student_id"] == id) {
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
    //class add 
    var status = document.getElementById("status")
    if (mydata["filterstatus"] === "Active") {
      status.classList.add("label-success")
      status.classList.remove("label-danger")
    } else {
      status.classList.remove("label-success")
      status.classList.add("label-danger")
    }
    //class add end

    //Print something start

    // var laddu = document.getElementById("laddu")
    // laddu.innerHTML = mydata["student_id"] + mydata["student_id"]
    //Print something END

    var profileimage = document.getElementById("profileimage")
    profileimage.src = mydata["photourl"]


    var profile = document.getElementById("profile")
    profile.href = "/rssi-member/student-profile.php?get_id=" + mydata["student_id"]

    var sname = document.getElementById("sname")
    sname.value = mydata["studentname"]

    var studentid = document.getElementById("studentid")
    studentid.value = mydata["student_id"]

    var collectedby = document.getElementById("collectedby")
    collectedby.value = aid

    //Program to disable or enable a button using javascript

    // const monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
    // const d = new Date();

    // var dd = String(d.getDate()).padStart(2, '0');
    // var mm = String(d.getMonth() + 1).padStart(2, '0');
    // var yyyy = d.getFullYear();
    // document.write("The date is : " + monthNames[d.getMonth()]);

    // if (mydata["maxmonth"] === monthNames[d.getMonth() - 1] && mydata["feecycle"] !== "A") {
    //   yes.disabled = true; //button remains disabled
    // } else if (mydata["maxmonth"] === monthNames[d.getMonth()] && mydata["feecycle"] === "A") {
    //   yes.disabled = true;
    // } else {
    //   yes.disabled = false;
    // }
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
<a id="back-to-top" href="#" class="go-top" role="button"><i class="fa fa-angle-up"></i></a>
</body>

</html>
