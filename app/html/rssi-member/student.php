<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
  $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
  header("Location: index.php");
  exit;
}
validation();

// Initialize variables
$module = $_POST['get_module'] ?? null;
$id = $_POST['get_id'] ?? null;
$category = $_POST['get_category'] ?? null;
$class = $_POST['get_class'] ?? null;
$stid = $_POST['get_stid'] ?? null;
$searchByIdOnly = isset($_POST['search_by_id_only']);

// Initialize result array
$resultArr = [];

// Only query if we have valid parameters
if ($searchByIdOnly) {
  // Search by Student ID only
  if (!empty($stid)) {
    $result = pg_query_params(
      $con,
      "SELECT * FROM rssimyprofile_student WHERE student_id = $1",
      [$stid]
    );
    $resultArr = $result ? pg_fetch_all($result) : [];
  }
} else {
  // Normal search (requires module and status)
  if (!empty($module) && !empty($id)) {
    $query = "SELECT * FROM rssimyprofile_student 
                 WHERE filterstatus = $1 AND module = $2";
    $params = [$id, $module]; // Assuming filterstatus should be 'Active'

    $paramCount = 3; // Start counting from 3

    if (!empty($category)) {
      $query .= " AND category = $$paramCount";
      $params[] = $category;
      $paramCount++;
    }

    if (!empty($class)) {
      if (is_array($class)) {
        // Generate numbered placeholders for each class
        $placeholders = [];
        foreach ($class as $classItem) {
          $placeholders[] = "$$paramCount";
          $params[] = $classItem;
          $paramCount++;
        }
        $query .= " AND class IN (" . implode(',', $placeholders) . ")";
      } else {
        $query .= " AND class = $$paramCount";
        $params[] = $class;
        $paramCount++;
      }
    }

    $query .= " ORDER BY category ASC, class ASC, studentname ASC";
    $result = pg_query_params($con, $query, $params);
    $resultArr = $result ? pg_fetch_all($result) : [];
  }
}

// Calculate dashboard statistics
$totalStudents = count($resultArr);
$maleCount = 0;
$femaleCount = 0;
$binaryCount = 0;

$ageBands = [
  '0-5' => 0,
  '6-10' => 0,
  '11-15' => 0,
  '16-20' => 0,
  '21+' => 0
];

$casteStats = [
  'SC' => 0,
  'ST' => 0,
  'OBC' => 0,
  'General' => 0,
  'Not Declared' => 0
];

$aadharStats = [
  'Available' => 0,
  'Not Available' => 0
];

foreach ($resultArr as $student) {
  // Gender count
  $gender = strtolower($student['gender'] ?? '');
  if ($gender === 'male') $maleCount++;
  elseif ($gender === 'female') $femaleCount++;
  else $binaryCount++;

  // Age band calculation
  if (!empty($student['dateofbirth'])) {
    $birthDate = new DateTime($student['dateofbirth']);
    $today = new DateTime();
    $age = $today->diff($birthDate)->y;

    if ($age <= 5) $ageBands['0-5']++;
    elseif ($age <= 10) $ageBands['6-10']++;
    elseif ($age <= 15) $ageBands['11-15']++;
    elseif ($age <= 20) $ageBands['16-20']++;
    else $ageBands['21+']++;
  }

  // Caste statistics
  $caste = $student['caste'] ?? 'Not Declared';
  if (array_key_exists($caste, $casteStats)) {
    $casteStats[$caste]++;
  } else {
    $casteStats['Not Declared']++;
  }

  // Aadhar availability
  $aadhar = $student['aadhar_available'] ?? '';
  if (strtolower($aadhar) === 'yes') {
    $aadharStats['Available']++;
  } else {
    $aadharStats['Not Available']++;
  }
}

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
];
?>
<?php
function formatContact($role, $contact)
{
  return ($role == 'Admin' || $role == 'Offline Manager') ? $contact : "xxxxxx" . substr($contact, 6);
}

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

    /* Dashboard Styles */
    .dashboard-card {
      border-radius: 10px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      margin-bottom: 20px;
      background: white;
      overflow: hidden;
    }

    .dashboard-card-header {
      background-color: #f8f9fa;
      padding: 15px 20px;
      border-bottom: 1px solid #e9ecef;
      font-weight: 600;
    }

    .dashboard-card-body {
      padding: 20px;
    }

    .stat-number {
      font-size: 2rem;
      font-weight: bold;
      color: #3b7ddd;
    }

    .stat-label {
      font-size: 0.9rem;
      color: #6c757d;
    }

    .gender-badge {
      display: inline-block;
      padding: 5px 10px;
      border-radius: 15px;
      margin: 5px;
      font-size: 0.85rem;
    }

    .age-band-item,
    .caste-item,
    .aadhar-item {
      display: flex;
      justify-content: space-between;
      padding: 8px 0;
      border-bottom: 1px solid #f1f1f1;
    }

    .age-band-item:last-child,
    .caste-item:last-child,
    .aadhar-item:last-child {
      border-bottom: none;
    }

    .progress {
      height: 8px;
      margin-top: 5px;
    }
  </style>
  <!-- CSS Library Files -->
  <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
  <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
  <!-- JavaScript Library Files -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
  <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>

  <!-- JavaScript Library Files -->
  <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>

  <script>
    $(document).ready(function() {
      // Initialize Select2 for student IDs
      $('#get_stid').select2({
        ajax: {
          url: 'fetch_students.php',
          dataType: 'json',
          delay: 250,
          data: function(params) {
            return {
              q: params.term
            };
          },
          processResults: function(data) {
            return {
              results: data.results || []
            };
          },
          cache: true
        },
        minimumInputLength: 2,
        placeholder: 'Select student',
        allowClear: true,
        width: '100%' // Ensure proper width
      });
    });
  </script>
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
                <div class="row">
                  <div class="col" style="display:inline-block; width:50%;">
                    Record count:&nbsp;<?php echo count($resultArr); ?>
                  </div>

                  <?php if ($role === 'Admin' || $role === 'Offline Manager') { ?>
                    <div class="col" style="display:inline-block; width:47%; text-align:right;">

                      <form method="POST" action="export_function.php" target="_blank" style="display:inline-block;">
                        <input type="hidden" name="export_type" value="student">

                        <!-- Preserve search parameters -->
                        <input type="hidden" name="get_module" value="<?= htmlspecialchars($module ?? '') ?>">
                        <input type="hidden" name="get_id" value="<?= htmlspecialchars($id ?? '') ?>">
                        <input type="hidden" name="get_category" value="<?= htmlspecialchars($category ?? '') ?>">
                        <input type="hidden" name="get_class"
                          value="<?= is_array($class) ? htmlspecialchars(implode(',', $class)) : htmlspecialchars($class ?? '') ?>">
                        <input type="hidden" name="get_stid" value="<?= htmlspecialchars($stid ?? '') ?>">
                        <input type="hidden" name="search_by_id_only" value="<?= $searchByIdOnly ? '1' : '0' ?>">

                        <button type="submit"
                          id="export"
                          name="export"
                          title="Export CSV"
                          style="background:none;border:none;padding:0;outline:none;">
                          <i class="bi bi-file-earmark-excel" style="font-size:large;"></i>
                        </button>
                      </form>

                      <span style="margin: 0 6px;">|</span>

                      <a href="sps.php" title="Sync class and plan with active plan">
                        Sync Profile with Active Plan
                      </a>

                    </div>
                  <?php } ?>
                </div>

                <div class="accordion mt-3 mb-3" id="accordionExample">
                  <div class="accordion-item">
                    <h2 class="accordion-header" id="headingOne">
                      <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                        Student Insights
                      </button>
                    </h2>
                    <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#accordionExample">
                      <div class="accordion-body">
                        <!-- Dashboard Cards -->
                        <div class="col-12">
                          <div class="row">
                            <!-- Card 1: Total Students and Gender Distribution -->
                            <div class="col-md-6 col-lg-3">
                              <div class="dashboard-card">
                                <div class="dashboard-card-header">
                                  Student Overview
                                </div>
                                <div class="dashboard-card-body text-center">
                                  <div class="stat-number"><?php echo $totalStudents; ?></div>
                                  <div class="stat-label">Total Students</div>
                                  <div class="mt-3">
                                    <span class="gender-badge text-bg-primary">M: <?php echo $maleCount; ?></span>
                                    <span class="gender-badge text-bg-danger">F: <?php echo $femaleCount; ?></span>
                                    <span class="gender-badge text-bg-secondary">Other: <?php echo $binaryCount; ?></span>
                                  </div>
                                </div>
                              </div>
                            </div>

                            <!-- Card 2: Age Band Distribution -->
                            <div class="col-md-6 col-lg-3">
                              <div class="dashboard-card">
                                <div class="dashboard-card-header">
                                  Age Distribution
                                </div>
                                <div class="dashboard-card-body">
                                  <?php foreach ($ageBands as $band => $count):
                                    $percentage = $totalStudents > 0 ? round(($count / $totalStudents) * 100) : 0;
                                  ?>
                                    <div class="age-band-item">
                                      <span><?php echo $band; ?> yrs</span>
                                      <span><?php echo $count; ?></span>
                                    </div>
                                    <div class="progress">
                                      <div class="progress-bar" role="progressbar" style="width: <?php echo $percentage; ?>%"
                                        aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                  <?php endforeach; ?>
                                </div>
                              </div>
                            </div>

                            <!-- Card 3: Caste Information -->
                            <div class="col-md-6 col-lg-3">
                              <div class="dashboard-card">
                                <div class="dashboard-card-header">
                                  Caste Distribution
                                </div>
                                <div class="dashboard-card-body">
                                  <?php foreach ($casteStats as $caste => $count):
                                    $percentage = $totalStudents > 0 ? round(($count / $totalStudents) * 100) : 0;
                                  ?>
                                    <div class="caste-item">
                                      <span><?php echo $caste; ?></span>
                                      <span><?php echo $count; ?></span>
                                    </div>
                                    <div class="progress">
                                      <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $percentage; ?>%"
                                        aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                  <?php endforeach; ?>
                                </div>
                              </div>
                            </div>

                            <!-- Card 4: Aadhar Availability -->
                            <div class="col-md-6 col-lg-3">
                              <div class="dashboard-card">
                                <div class="dashboard-card-header">
                                  Aadhar Status
                                </div>
                                <div class="dashboard-card-body">
                                  <?php foreach ($aadharStats as $status => $count):
                                    $percentage = $totalStudents > 0 ? round(($count / $totalStudents) * 100) : 0;
                                  ?>
                                    <div class="aadhar-item">
                                      <span><?php echo $status; ?></span>
                                      <span><?php echo $count; ?></span>
                                    </div>
                                    <div class="progress">
                                      <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo $percentage; ?>%"
                                        aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                  <?php endforeach; ?>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- <span style="color:red;font-style: oblique; font-family:'Times New Roman', Times, serif;">All (*) marked fields are mandatory</span> -->
              <form action="" method="POST" id="searchForm">
                <div class="form-group d-flex flex-wrap align-items-end gap-3">
                  <input type="hidden" name="form-type" value="search">

                  <!-- Module (required unless searching by ID) -->
                  <div class="d-flex flex-column" style="width: max-content;">
                    <select name="get_module" id="get_module" class="form-select" required>
                      <?php if ($module == null) { ?>
                        <option value="" disabled selected hidden>Select Module</option>
                      <?php } else { ?>
                        <option value="<?php echo $module ?>" hidden selected><?php echo $module ?></option>
                      <?php } ?>
                      <option value="National">National</option>
                      <option value="State">State</option>
                    </select>
                    <small class="form-text text-muted">Module<span style="color:red">*</span></small>
                  </div>

                  <!-- Status (required unless searching by ID) -->
                  <div class="d-flex flex-column" style="width: max-content;">
                    <select name="get_id" id="get_id" class="form-select" required>
                      <?php if ($id == null) { ?>
                        <option value="" disabled selected hidden>Select Status</option>
                      <?php } else { ?>
                        <option value="<?php echo $id ?>" hidden selected><?php echo $id ?></option>
                      <?php } ?>
                      <option value="Active">Active</option>
                      <option value="Inactive">Inactive</option>
                    </select>
                    <small class="form-text text-muted">Status<span style="color:red">*</span></small>
                  </div>

                  <!-- Category (optional) -->
                  <div class="d-flex flex-column" style="width: max-content;">
                    <select name="get_category" id="get_category" class="form-select">
                      <option value="" disabled selected hidden>Select Category</option>
                      <?php
                      $categories = ['LG1', 'LG2-A', 'LG2-B', 'LG2-C', 'LG3', 'LG4', 'LG4S1', 'LG4S2', 'WLG3', 'WLG4S1', 'Undefined'];
                      foreach ($categories as $cat) {
                        $selected = ($category == $cat) ? 'selected' : '';
                        echo "<option value=\"$cat\" $selected>$cat</option>";
                      }
                      ?>
                    </select>
                    <small class="form-text text-muted">Category</small>
                  </div>

                  <!-- Class (optional) -->
                  <div class="d-flex flex-column" style="width: max-content;">
                    <select name="get_class[]" id="get_class" class="form-control" multiple>
                      <option disabled hidden>Select Class</option>
                      <?php foreach ($classlist as $cls) {
                        $selected = ($class && in_array($cls, (array)$class)) ? 'selected' : '';
                        echo "<option value=\"$cls\" $selected>$cls</option>";
                      } ?>
                    </select>
                    <small class="form-text text-muted">Class</small>
                  </div>

                  <!-- Student ID (required when checkbox checked) -->

                  <!-- AAID Dropdown -->
                  <div class="col-md-3 col-lg-2">
                    <div class="form-group">
                      <select class="form-select" id="get_stid" name="get_stid" required>
                        <?php if (!empty($stid)): ?>
                          <option value="<?= $stid ?>" selected><?= $stid ?></option>
                        <?php endif; ?>
                      </select>
                      <small class="form-text text-muted">Student ID<span id="stid-required" style="color:red; display:none">*</span></small>
                    </div>
                  </div>

                  <div class="d-flex flex-column">
                    <button type="submit" name="search_by_id" class="btn btn-success btn-sm" style="outline: none;">
                      <i class="bi bi-search"></i>&nbsp;Search
                    </button>
                  </div>
                </div>

                <div id="filter-checks" class="mt-2">
                  <input type="checkbox" name="search_by_id_only" id="search_by_id_only" class="form-check-input" value="1"
                    <?= isset($_POST['search_by_id_only']) ? 'checked' : '' ?> />
                  <label for="search_by_id_only" style="font-weight: 400;">Search by Student ID only</label>
                </div>
              </form>

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
                      <th>Emergency</th>
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
                          <td style="white-space: unset;"><?php echo (new DateTime($array['dateofbirth']))->diff(new DateTime())->y ?></td>
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
                          <td style="white-space: unset;">
                            <?php
                            echo $array['emergency_contact_number'];
                            if (!empty($array['alternate_number'])) {
                              echo ", " . $array['alternate_number'];
                            }
                            ?>
                          </td>
                          <td style="white-space: unset"><a href="admission_admin.php?student_id=<?php echo $array['student_id']; ?> ">Edit Profile</a>&nbsp;|&nbsp;
                            <a href="javascript:void(0)"
                              onclick="showDetails('<?php echo $array['student_id']; ?>')"
                              data-bs-toggle="modal"
                              data-bs-target="#exampleModal">misc.</a>
                          </td>
                        </tr>
                      <?php
                      endforeach;
                    elseif ($module == "" && $stid == "") :
                      ?>
                      <tr>
                        <td colspan="15">Please select a Module and Status from the dropdown menus to view the results.</td>
                      </tr>
                    <?php
                    elseif (sizeof($resultArr) == 0 && $stid == "") :
                    ?>
                      <tr>
                        <td colspan="15">No record found for <?php echo $module . ', ' . $id . ' and ' . $category . ' ' . str_replace("'", "", (is_array($class) ? implode(', ', $class) : ($class ?? ''))); ?></td>
                      </tr>
                    <?php
                    elseif (sizeof($resultArr) == 0 && $stid != "") :
                    ?>
                      <tr>
                        <td colspan="15">No record found for <?php echo $stid; ?></td>
                      </tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
              <!-- Modal -->
              <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h1 class="modal-title fs-5" id="exampleModalLabel">Student Details</h1>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                      <div class="d-flex align-items-center">
                        <img id="profileimage" src="#" class="rounded-circle me-2" width="50" height="50" />
                        <div>
                          <b><span class="studentname"></span>&nbsp;(<span class="student_id"></span>)</b>
                          <span id="status" class="badge"></span>
                        </div>
                      </div>
                      <br>
                      <p>Subject: <span class="nameofthesubjects"></span></p>
                      <p>Remarks: <span class="remarks"></span></p>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main><!-- End #main -->

  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- Vendor JS Files -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

  <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>
  
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const searchForm = document.getElementById('searchForm');
      const idOnlyCheckbox = document.getElementById('search_by_id_only');
      const stidField = document.getElementById('get_stid');
      const stidRequired = document.getElementById('stid-required');

      function toggleFields() {
        const idOnly = idOnlyCheckbox.checked;

        // Toggle disabled state
        document.getElementById("get_module").disabled = idOnly;
        document.getElementById("get_id").disabled = idOnly;
        document.getElementById("get_category").disabled = idOnly;
        document.getElementById("get_class").disabled = idOnly;
        stidField.disabled = !idOnly;
        stidRequired.style.display = idOnly ? 'inline' : 'none';

        // Toggle required attributes
        document.getElementById("get_module").required = !idOnly;
        document.getElementById("get_id").required = !idOnly;
        stidField.required = idOnly;
      }

      // Initial setup
      toggleFields();

      // Add event listener for checkbox change
      idOnlyCheckbox.addEventListener('change', toggleFields);

      // Form validation
      searchForm.addEventListener('submit', function(e) {
        if (idOnlyCheckbox.checked && !stidField.value.trim()) {
          e.preventDefault();
          alert('Please enter a Student ID when searching by ID');
          stidField.focus();
        }
      });
    });
  </script>
  <script>
    var data = <?php echo json_encode($resultArr) ?>;

    function showDetails(id) {
      var mydata = data.find(item => item["student_id"] == id);
      if (!mydata) return;

      var modal = document.getElementById("exampleModal");

      modal.querySelector(".studentname").textContent = mydata["studentname"];
      modal.querySelector(".student_id").textContent = mydata["student_id"];
      modal.querySelector(".nameofthesubjects").textContent = mydata["nameofthesubjects"];
      modal.querySelector(".remarks").textContent = mydata["remarks"];

      var profileimage = document.getElementById("profileimage");
      profileimage.src = mydata["photourl"] || "#";

      var status = document.getElementById("status");
      status.textContent = mydata["filterstatus"];
      status.className = "badge " + (mydata["filterstatus"] === "Active" ? "bg-success" : "bg-danger");
    }
  </script>
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