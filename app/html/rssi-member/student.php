<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
  $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
  header("Location: index.php");
  exit;
}
validation();

@$module = $_POST['get_module'];
@$id = $_POST['get_id'];
@$category = $_POST['get_category'];
@$class = $_POST['get_class'];
@$stid = $_POST['get_stid'];
@$is_user = $_POST['is_user'];
// $categories = "'".implode("','", $category)."'";


if ($category == null && $class == null) {
  $result = pg_query($con, "SELECT 
        s.*, 
        COALESCE(SUM(f.fees), 0) AS total_admission_fee,
        CASE 
            WHEN COUNT(f.studentid) > 0 THEN 'Paid' 
            ELSE null 
        END AS admission_fee_status
    FROM rssimyprofile_student AS s
    LEFT JOIN fees AS f ON f.studentid = s.student_id AND f.ptype = 'Admission Fee'
    WHERE s.filterstatus = '$id' AND s.module = '$module' 
    GROUP BY s.student_id, s.studentname, s.class
    ORDER BY s.category ASC, s.class ASC, s.studentname ASC");
}

if ($category != null && $class == null) {
  $result = pg_query($con, "SELECT rssimyprofile_student.*, COALESCE(fees.total_admission_fee, 0) AS total_admission_fee, fees.admission_fee_status
    FROM rssimyprofile_student
    LEFT JOIN (
        SELECT f.studentid, 
               COALESCE(SUM(f.fees), 0) AS total_admission_fee,
               CASE 
                   WHEN COUNT(f.studentid) > 0 THEN 'Paid' 
                   ELSE null 
               END AS admission_fee_status
        FROM fees AS f
        WHERE f.ptype = 'Admission Fee'
        GROUP BY f.studentid
    ) AS fees ON fees.studentid = rssimyprofile_student.student_id
    WHERE rssimyprofile_student.filterstatus = '$id' 
        AND rssimyprofile_student.module = '$module' 
        AND rssimyprofile_student.category = '$category' 
    ORDER BY rssimyprofile_student.category ASC, rssimyprofile_student.class ASC, rssimyprofile_student.studentname ASC");
}

if ($category == null && $class != null) {
  @$classs = implode("','", $class);
  $result = pg_query($con, "SELECT rssimyprofile_student.*, COALESCE(fees.total_admission_fee, 0) AS total_admission_fee, fees.admission_fee_status
    FROM rssimyprofile_student
    LEFT JOIN (
        SELECT f.studentid, 
               COALESCE(SUM(f.fees), 0) AS total_admission_fee,
               CASE 
                   WHEN COUNT(f.studentid) > 0 THEN 'Paid' 
                   ELSE null 
               END AS admission_fee_status
        FROM fees AS f
        WHERE f.ptype = 'Admission Fee'
        GROUP BY f.studentid
    ) AS fees ON fees.studentid = rssimyprofile_student.student_id
    WHERE rssimyprofile_student.filterstatus = '$id' 
        AND rssimyprofile_student.module = '$module' 
        AND rssimyprofile_student.class IN ('$classs') 
    ORDER BY rssimyprofile_student.category ASC, rssimyprofile_student.class ASC, rssimyprofile_student.studentname ASC");
}


if ($category != null && $class != null) {
  @$classs = implode("','", $class);
  $result = pg_query($con, "SELECT rssimyprofile_student.*, COALESCE(fees.total_admission_fee, 0) AS total_admission_fee, fees.admission_fee_status
    FROM rssimyprofile_student
    LEFT JOIN (
        SELECT f.studentid, 
               COALESCE(SUM(f.fees), 0) AS total_admission_fee,
               CASE 
                   WHEN COUNT(f.studentid) > 0 THEN 'Paid' 
                   ELSE null 
               END AS admission_fee_status
        FROM fees AS f
        WHERE f.ptype = 'Admission Fee'
        GROUP BY f.studentid
    ) AS fees ON fees.studentid = rssimyprofile_student.student_id
    WHERE rssimyprofile_student.filterstatus = '$id' 
        AND rssimyprofile_student.module = '$module' 
        AND rssimyprofile_student.class IN ('$classs') 
        AND rssimyprofile_student.category = '$category' 
    ORDER BY rssimyprofile_student.category ASC, rssimyprofile_student.class ASC, rssimyprofile_student.studentname ASC");
}


if ($stid != null) {
  $result = pg_query($con, "SELECT rssimyprofile_student.*, COALESCE(fees.total_admission_fee, 0) AS total_admission_fee, fees.admission_fee_status
    FROM rssimyprofile_student
    LEFT JOIN (
        SELECT f.studentid, 
               COALESCE(SUM(f.fees), 0) AS total_admission_fee,
               CASE 
                   WHEN COUNT(f.studentid) > 0 THEN 'Paid' 
                   ELSE null 
               END AS admission_fee_status
        FROM fees AS f
        WHERE f.ptype = 'Admission Fee'
        GROUP BY f.studentid
    ) AS fees ON fees.studentid = rssimyprofile_student.student_id
    WHERE rssimyprofile_student.student_id = '$stid'");
}

if (!$result) {
  echo "An error occurred.\n";
  exit;
}

$resultArr = pg_fetch_all($result);
$classlist = [
  "Nursery",
  "LKG",
  "UKG",
  "Pre-school",
  "1",
  "2",
  "3",
  "4",
  "5",
  "6",
  "7",
  "8",
  "9",
  '10',
  "11",
  "12",
  "Vocational training",
  "x"
]
?>

<!doctype html>
<html lang="en">

<head>
  <!-- Google tag (gtag.js) -->
  <script async src="https://www.googletagmanager.com/gtag/js?id=AW-11316670180"></script>
  <script>
    window.dataLayer = window.dataLayer || [];

    function gtag() {
      dataLayer.push(arguments);
    }
    gtag('js', new Date());

    gtag('config', 'AW-11316670180');
  </script>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title>Student Database</title>

  <!-- Favicons -->
  <link href="../img/favicon.ico" rel="icon">
  <!-- Vendor CSS Files -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
  <script src="https://kit.fontawesome.com/58c4cdb942.js" crossorigin="anonymous"></script>
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

    @media (min-width:767px) {
      .left {
        margin-left: 2%;
      }
    }

    @media (max-width:767px) {

      #cw,
      #cw1 {
        width: 100% !important;
      }

    }

    #cw {
      width: 7%;
    }

    #cw1 {
      width: 20%;
    }

    #passwordHelpBlock {
      display: block;
    }

    .input-help {
      vertical-align: top;
      display: inline-block;
    }
  </style>
  <!-- CSS Library Files -->
  <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
  <!-- JavaScript Library Files -->
  <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
  <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
  <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>
</head>

<body>
  <?php include 'inactive_session_expire_check.php'; ?>
  <?php include 'header.php'; ?>

  <main id="main" class="main">

    <div class="pagetitle">
      <h1>Student Database</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="home.php">Home</a></li>
          <li class="breadcrumb-item"><a href="#">Work</a></li>
          <li class="breadcrumb-item active">Student Database</li>
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
              <div class="row">
                <div class="col" style="display: inline-block; width:50%;">
                  Record count:&nbsp;<?php echo sizeof($resultArr) ?>
                </div>

                <?php if ($role == 'Admin' || $role == 'Offline Manager') { ?>
                  <div class="col" style="display: inline-block; width:47%; text-align:right">
                    <a href="fees.php" target="_self" class="btn btn-danger btn-sm" role="button">Fees Details</a>

                    <br><br>
                    <form method="POST" action="export_function.php">
                      <input type="hidden" value="student" name="export_type" />
                      <input type="hidden" value="<?php echo $module ?>" name="module" />
                      <input type="hidden" value="<?php echo $id ?>" name="id" />
                      <input type="hidden" value="<?php echo $category ?>" name="category" />
                      <input type="hidden" value="<?php echo $classs ?>" name="classs" />
                      <input type="hidden" value="<?php echo $class ?>" name="class" />
                      <input type="hidden" value="<?php echo $stid ?>" name="stid" />

                      <button type="submit" id="export" name="export" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none;
                        padding: 0px;
                        border: none;" title="Export CSV"><i class="bi bi-file-earmark-excel" style="font-size:large;"></i></button>
                    </form>
                  </div>
                <?php } else { ?>
                  <div class="col" style="display: inline-block; width:47%; text-align:right">
                    <a href="javascript:void(0);" target="_self" class="btn btn-danger btn-sm disabled" role="button">Fees
                      Details</a>
                  </div>
                <?php } ?>
              </div>

              <!-- <span style="color:red;font-style: oblique; font-family:'Times New Roman', Times, serif;">All (*) marked fields are mandatory</span> -->
              <form action="" method="POST">
                <div class="form-group" style="display: inline-block;">
                  <div class="col2" style="display: inline-block;">
                    <input type="hidden" name="form-type" type="text" value="search">
                    <span class="input-help">
                      <select name="get_module" id="get_module" class="form-select" style="width:max-content; display:inline-block" required>
                        <?php if ($module == null) { ?>
                          <option disabled selected hidden>Select Module</option>
                        <?php
                        } else { ?>
                          <option hidden selected><?php echo $module ?></option>
                        <?php }
                        ?>
                        <option>National</option>
                        <option>State</option>
                      </select>
                      <small id="passwordHelpBlock" class="form-text text-muted">Module<span style="color:red">*</span></small>
                    </span>
                    <span class="input-help">
                      <select name="get_id" id="get_id" class="form-select" style="width:max-content; display:inline-block" required>
                        <?php if ($id == null) { ?>
                          <option disabled selected hidden>Select Status</option>
                        <?php
                        } else { ?>
                          <option hidden selected><?php echo $id ?></option>
                        <?php }
                        ?>
                        <option>Active</option>
                        <option>Inactive</option>
                      </select>
                      <small id="passwordHelpBlock" class="form-text text-muted">Status<span style="color:red">*</span></small>
                    </span>
                    <span class="input-help">
                      <select name="get_category" id="get_category" class="form-select" style="width:max-content;display:inline-block">
                        <?php if ($category == null) { ?>
                          <option disabled selected hidden>Select Category</option>
                        <?php
                        } else { ?>
                          <option hidden selected><?php echo $category ?></option>
                        <?php }
                        ?>
                        <option>LG1</option>
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
                      </select>
                      <small id="passwordHelpBlock" class="form-text text-muted">Category</small>
                    </span>
                    <span class="input-help">
                      <select name="get_class[]" id="get_class" class="form-control" style="width:max-content; display:inline-block" multiple>
                        <?php if ($class == null) { ?>
                          <option disabled selected hidden>Select Class</option>

                          <?php foreach ($classlist as $cls) { ?>
                            <option><?php echo $cls ?></option>
                          <?php } ?>

                          <?php
                        } else {

                          foreach ($classlist as $cls) { ?>
                            <option <?php if (in_array($cls, $class)) {
                                      echo "selected";
                                    } ?>><?php echo $cls ?></option>
                        <?php }
                        }
                        ?>
                      </select>
                      <small id="passwordHelpBlock" class="form-text text-muted">Class</small>
                    </span>
                    <span class="input-help">
                      <input name="get_stid" id="get_stid" class="form-control" style="width:max-content; display:inline-block" placeholder="Student ID" value="<?php echo $stid ?>" required>
                      <small id="passwordHelpBlock" class="form-text text-muted">Student Id<span style="color:red">*</span></small>
                    </span>
                  </div>
                </div>
                <div class="col2 left" style="display: inline-block;">
                  <button type="submit" name="search_by_id" class="btn btn-success btn-sm" style="outline: none;">
                    <i class="bi bi-search"></i>&nbsp;Search</button>
                </div>
                <div id="filter-checks">
                  <input type="checkbox" name="is_user" id="is_user" value="1" <?php if (isset($_POST['is_user']))
                                                                                  echo "checked='checked'"; ?> />
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
              function formatContact($role, $contact)
              {
                return ($role == 'Admin' || $role == 'Offline Manager') ? $contact : "xxxxxx" . substr($contact, 6);
              }

              function formatPaidBadge($maxmonth, $role, $student_id)
              {
                return ($maxmonth != null && ($role == 'Admin' || $role == 'Offline Manager')) ? '<p style="display: inline !important;">' . $maxmonth . '</p>&nbsp;<a href="fees.php?get_stid=' . $student_id . '" target=_blank"><i class="bi bi-clock-history" style="font-size: 16px ;color:#777777" title="View Payment History"></i></a>' : '';
              }

              ?>

              <div class="table-responsive">
                <table class="table" id="table-id">
                  <thead>
                    <tr>
                      <th id="cw">Photo</th>
                      <th>Student ID</th>
                      <th>Student Name</th>
                      <th>Gender</th>
                      <th>Age</th>
                      <th>DOA</th>
                      <th>DOT</th>
                      <th>Aadhar</th>
                      <th>Class</th>
                      <th>School</th>
                      <th>Contact</th>
                      <th>Status</th>
                      <th>Pay type</th>
                      <!-- <th>Access</th> -->
                      <th>Admission fee</th>
                      <th>Month paid</th>
                      <!-- <th >Misc (Pay & Others)</th> -->
                      <th>Due</th>
                      <th></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (sizeof($resultArr) > 0) :
                      foreach ($resultArr as $array) :
                        // $paidBadge = formatPaidBadge($array['maxmonth'], $role, $array['student_id']);
                        $contact = formatContact($role, $array['contact']);
                    ?>
                        <tr>
                          <td><img src="<?php echo (($array['photourl'] !== null && $array['photourl'] !== '') ? $array['photourl'] : 'https://res.cloudinary.com/hs4stt5kg/image/upload/v1609410219/faculties/blank.jpg'); ?>" width="50"></td>
                          <td style="white-space: unset;"><?php echo $array['student_id']; ?></td>
                          <td style="white-space: unset;"><?php echo $array['studentname']; ?></td>
                          <td style="white-space: unset;"><?php echo $array['gender']; ?></td>
                          <td style="white-space: unset;"><?php echo $array['age']; ?></td>
                          <td style="white-space: unset;"><?php echo date('d/m/Y', strtotime($array['doa'])); ?></td>
                          <td style="white-space: unset;"><?php echo (empty($array['effectivefrom']) ? NULL : date('d/m/Y', strtotime($array['effectivefrom']))); ?></td>
                          <td style="white-space: unset;"><?php echo $array['aadhar_available']; ?></td>
                          <td style="white-space: unset;"><?php echo $array['class'] . '/' . $array['category']; ?></td>
                          <td style="white-space: unset;"><?php echo $array['nameoftheschool']; ?></td>
                          <td style="white-space: unset;">
                            <?php echo $contact . (isset($array['emailaddress']) ? '<br>' . $array['emailaddress'] : ''); ?>
                          </td>
                          <td style="white-space: unset"><?php echo $array['filterstatus']; ?></td>
                          <td style="white-space: unset"><?php echo @substr($array['payment_type'], 0, 3); ?></td>
                          <!-- <td style="white-space: unset"><?php echo (($array['access_category'] == 'Premium') ? 'Prem' : (($array['access_category'] == 'Regular') ? 'Reg' : '')); ?></td> -->
                          <!-- <td style="white-space: unset;"><?php echo $paidBadge . '&nbsp;<i class="bi bi-bag" style="font-size: 17px ;color:#777777" title="Distribution History"></i>'; ?></td> -->
                          <td style="white-space: unset;"><?php echo ($array['admission_fee_status'] == "Paid") ? $array['admission_fee_status'] . '-' . $array['total_admission_fee'] : $array['admission_fee_status']; ?></td>
                          <td style="white-space: unset;"></td>
                          <td style="white-space: unset;"></td>
                          <td style="white-space: unset"><a href="admission_admin.php?student_id=<?php echo $array['student_id']; ?> " target="_blank">Edit Profile</a>&nbsp;|&nbsp;<a href="javascript:void(0)" onclick="showDetails('<?php echo $array['student_id']; ?>')">misc.</a></td>
                        </tr>
                      <?php
                      endforeach;
                    elseif ($module == "" && $stid == "") :
                      ?>
                      <tr>
                        <td colspan="13">Please select a Module and Status from the dropdown menus to view the results.</td>
                      </tr>
                    <?php
                    elseif (sizeof($resultArr) == 0 && $stid == "") :
                    ?>
                      <tr>
                        <td colspan="13">No record found for <?php echo $module . ', ' . $id . ' and ' . $category . ' ' . str_replace("'", "", $classs); ?></td>
                      </tr>
                    <?php
                    elseif (sizeof($resultArr) == 0 && $stid != "") :
                    ?>
                      <tr>
                        <td colspan="13">No record found for <?php echo $stid; ?></td>
                      </tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
              <!--------------- POP-UP BOX ------------
-------------------------------------->
              <style>
                .modal {
                  background-color: rgba(0, 0, 0, 0.4);
                  /* Black w/ opacity */
                }
              </style>
              <div class="modal" id="myModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-xl">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h1 class="modal-title fs-5" id="exampleModalLabel">Student Details</h1>
                      <button type="button" id="closedetails-header" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">

                      <div class="d-flex align-items-center">
                        <img id="profileimage" src="#" class="rounded-circle me-2" width="50" height="50" />
                        <div>
                          <b><span class="studentname"></span>&nbsp;(<span class="student_id"></span>)</b>
                          <span id="status" class="badge"></span>
                        </div>
                        <!-- <a id="profile" href="#" target="_blank" class="ms-auto text-secondary"><i class="fa-regular fa-pen-to-square" style="font-size: 20px;" title="Edit Profile"></i></a> -->
                      </div><br>
                      <!-- <p>
                        Subject: <span class="nameofthesubjects"></span>
                      </p> -->
                      <p>Remarks: <span class="remarks"></span></p>

                      <script>
                        var data = <?php echo json_encode($resultArr) ?>;
                        var aid = <?php echo '"' . $_SESSION['aid'] . '"' ?>;

                        // Get the modal
                        var modal = document.getElementById("myModal");
                        // Get the <span> element that closes the modal
                        var closedetails = [
                          document.getElementById("closedetails-header"),
                          document.getElementById("closedetails-footer")
                        ];

                        function showDetails(id) {
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

                          //Print something start

                          var status = document.getElementById("status")
                          status.innerHTML = mydata["filterstatus"]
                          if (mydata["filterstatus"] === "Active") {
                            status.classList.add("bg-success")
                            status.classList.remove("bg-danger")
                          } else {
                            status.classList.remove("bg-success")
                            status.classList.add("bg-danger")
                          }
                          // laddu.innerHTML = mydata["student_id"] + mydata["student_id"]
                          //Print something END
                          var profileimage = document.getElementById("profileimage")
                          profileimage.src = mydata["photourl"]

                          var studentid = document.getElementById("studentid")
                          studentid.value = mydata["student_id"]

                          var collectedby = document.getElementById("collectedby")
                          collectedby.value = aid

                          var distributedto = document.getElementById("distributedto")
                          distributedto.value = mydata["student_id"]
                          var distributedby = document.getElementById("distributedby")
                          distributedby.value = aid
                        }

                        closedetails.forEach(function(element) {
                          element.addEventListener("click", closeModal);
                        });

                        function closeModal() {
                          var modal1 = document.getElementById("myModal");
                          modal1.style.display = "none";
                        }
                      </script>

                    </div>
                  </div>
                </div><!-- End Reports -->
              </div>
    </section>
    <!-- Bootstrap Modal -->
    <div class="modal fade" id="myModal_p" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-body">
            <div class="text-center">
              <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
              <p id="loadingMessage">Submission in progress. Please do not close or reload this page.</p>
            </div>
          </div>
        </div>
      </div>
    </div>

  </main><!-- End #main -->

  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- Vendor JS Files -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

  <!-- Template Main JS File -->
  <script src="../assets_new/js/main.js"></script>
  <script>
    $(document).ready(function() {
      // Check if resultArr is empty
      <?php if (!empty($resultArr)) : ?>
        // Initialize DataTables only if resultArr is not empty
        $('#table-id').DataTable({
          paging: false,
          // other options...
        });
      <?php endif; ?>
    });
  </script>
</body>

</html>