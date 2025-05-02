<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");
include("../../util/email.php");


if (!isLoggedIn("aid")) {
  $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
  header("Location: index.php");
  exit;
}

validation();

// this page is hit with get and post both
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  @$lyear = $_POST['academicYear'];
  @$associate_number = @strtoupper($_POST['employeeId']);
} else {
  @$lyear = $_GET['academicYear'];
  @$associate_number = @strtoupper($_GET['lookupEmployeeId']);
}
$query = "
    SELECT *, rssimyaccount_members.email AS employee_email, rssimyaccount_members.fullname AS employee_fullname, rssimyaccount_members.associatenumber AS employee_associatenumber, rssimyaccount_members.phone AS employee_phone
    FROM rssimyaccount_members
    LEFT JOIN (
        SELECT applicantid, COALESCE(SUM(CASE WHEN typeofleave='Sick Leave' THEN days ELSE 0 END), 0) AS sltd,
               COALESCE(SUM(CASE WHEN typeofleave='Casual Leave' THEN days ELSE 0 END), 0) AS cltd,
               COALESCE(SUM(CASE WHEN typeofleave='Leave Without Pay' THEN days ELSE 0 END), 0) AS lwptd
        FROM leavedb_leavedb
        WHERE lyear='$lyear' AND status='Approved'
        GROUP BY applicantid
    ) AS leave_stats ON rssimyaccount_members.associatenumber = leave_stats.applicantid
    LEFT JOIN (
        SELECT applicantid, 1 AS onleave
        FROM leavedb_leavedb
        WHERE CURRENT_DATE BETWEEN fromdate AND todate AND lyear='$lyear' AND status='Approved'
    ) AS on_leave ON rssimyaccount_members.associatenumber = on_leave.applicantid
    LEFT JOIN (
        SELECT allo_applicantid, COALESCE(SUM(CASE WHEN allo_leavetype='Sick Leave' THEN allo_daycount ELSE 0 END), 0) AS slad,
               COALESCE(SUM(CASE WHEN allo_leavetype='Casual Leave' THEN allo_daycount ELSE 0 END), 0) AS clad
        FROM leaveallocation
        WHERE allo_academicyear='$lyear'
        GROUP BY allo_applicantid
    ) AS leave_allocation ON rssimyaccount_members.associatenumber = leave_allocation.allo_applicantid
    LEFT JOIN (
        SELECT adj_applicantid, COALESCE(SUM(CASE WHEN adj_leavetype='Sick Leave' THEN adj_day ELSE 0 END), 0) AS sladd,
               COALESCE(SUM(CASE WHEN adj_leavetype='Casual Leave' THEN adj_day ELSE 0 END), 0) AS cladd,
               COALESCE(SUM(CASE WHEN adj_leavetype='Leave Without Pay' THEN adj_day ELSE 0 END), 0) AS lwpadd
        FROM leaveadjustment
        WHERE adj_academicyear='$lyear'
        GROUP BY adj_applicantid
    ) AS leave_adjustment ON rssimyaccount_members.associatenumber = leave_adjustment.adj_applicantid
    WHERE rssimyaccount_members.associatenumber = '$associate_number'
";

$result = pg_query($con, $query);

if ($row = pg_fetch_assoc($result)) {
  $employee_email = $row['employee_email'];
  $employee_fullname = $row['employee_fullname'];
  $employee_phone = $row['employee_phone'];
  $employee_associatenumber = $row['employee_associatenumber'];
}

$resultArr = pg_fetch_all($result);
if (!$result) {
  echo "An error occurred.\n";
  exit;
}

?>
<?php
// Suppress error reporting for specific lines

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form-type']) && $_POST['form-type'] === 'salaryForm') {
  // Retrieve form data
  $employeeId = $_POST['employeeId'];
  $paymonth = $_POST['payMonth'];
  $payyear = $_POST['payYear'];
  $dayspaid = $_POST['dayspaid'];
  $comment = $_POST['comment'];
  $uniqueId = uniqid();
  $now = date('Y-m-d H:i:s');
  $ip_address = $_SERVER['REMOTE_ADDR']; // Get the IP address of the user

  // Ensure that the form fields are arrays
  $salaryComponents = is_array($_POST['componentName']) ? $_POST['componentName'] : [$_POST['componentName']];
  $subCategories = is_array($_POST['subCategory']) ? $_POST['subCategory'] : [$_POST['subCategory']];
  $amounts = is_array($_POST['amount']) ? $_POST['amount'] : [$_POST['amount']];

  $success = false;
  $error_message = '';

  // Insert form data into the database
  try {
    // Insert into payslip_entry table first
    $query = "INSERT INTO payslip_entry (payslip_entry_id, employeeid, paymonth, payyear, dayspaid, comment, payslip_issued_by, payslip_issued_on, payslip_issued_ip) 
              VALUES ('$uniqueId', '$employeeId', '$paymonth', $payyear, $dayspaid, '$comment','$associatenumber','$now','$ip_address')";
    $result = pg_query($con, $query);

    if (!$result) {
      $error_message = "Error: " . pg_last_error($con);
      throw new Exception($error_message);
    }

    // Insert into payslip_component table for each component
    foreach ($salaryComponents as $index => $component) {
      $subCategory = $subCategories[$index];
      $amount = $amounts[$index];

      $query = "INSERT INTO payslip_component (payslip_entry_id, components, subcategory, amount) 
                VALUES ('$uniqueId', '$component', '$subCategory', $amount)";
      $result = pg_query($con, $query);
      $cmdtuples = pg_affected_rows($result);

      if (!$result) {
        $error_message = "Error: " . pg_last_error($con);
        throw new Exception($error_message);
      }
    }

    $success = true;
  } catch (Exception $e) {
    $error_message = $e->getMessage();
    echo "<script>alert('Error: Failed to create payslip. Please try again.\\n" . addslashes($error_message) . "');</script>";
    exit;
  }

  if ($success) {
    if (@$cmdtuples == 1 && $employee_email != "") {
      sendEmail("payslip", array(
        "month" => date('F', mktime(0, 0, 0, $paymonth, 1)),
        "year" => @$payyear,
        "fullname" => @$employee_fullname,
        "associatenumber" => @$employee_associatenumber,
        "payslipid" => @$uniqueId,
      ), $employee_email, False);
    }

    echo "<script>
    alert('Payslip created successfully! Payslip ID: {$uniqueId}');
    if (window.history.replaceState) {
        // Update the URL without causing a page reload or resubmission
        window.history.replaceState(null, null, window.location.href);
    }
    window.location.reload(); // Trigger a page reload to reflect changes
    </script>";
  }
}
?>

<!DOCTYPE html>
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
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Payroll Processing</title>
  <!-- Favicons -->
  <link href="../img/favicon.ico" rel="icon">

  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
  <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
  <!-- Template Main CSS File -->
  <link href="../assets_new/css/style.css" rel="stylesheet">
  <!-- JavaScript Library Files -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
  <!-- AJAX for Associatenumber and Course Dropdowns -->
  <script>
    $(document).ready(function() {
      // Fetch Associates
      // Initialize Select2 for associatenumber dropdown
      $('#lookupEmployeeId').select2({
        ajax: {
          url: 'fetch_associates.php', // Path to the PHP script
          dataType: 'json',
          delay: 250, // Delay in milliseconds before sending the request
          data: function(params) {
            return {
              q: params.term // Search term
            };
          },
          processResults: function(data) {
            // Map the results to the format expected by Select2
            return {
              results: data.results
            };
          },
          cache: true // Cache results for better performance
        },
        minimumInputLength: 1 // Require at least 1 character to start searching
      });
    });
  </script>
</head>

<body>
  <?php include 'inactive_session_expire_check.php'; ?>
  <?php include 'header.php'; ?>

  <main id="main" class="main">

    <div class="pagetitle">
      <h1>Payroll Processing</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="home.php">Home</a></li>
          <li class="breadcrumb-item"><a href="#">Payroll</a></li>
          <li class="breadcrumb-item active">Payroll Processing</li>
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
                <form id="employeeLookupForm" method="get">
                  <h3>Associate Information Lookup</h3>
                  <hr>
                  <div class="row">
                    <div class="col-md-6">
                      <label for="lookupEmployeeId" class="form-label">Associate</label>
                      <select class="form-control select2" id="lookupEmployeeId" name="lookupEmployeeId">
                        <option value="">Select Associate</option>
                        <?php if ($associate_number): ?>
                          <!-- Pre-select the selected associate if it exists -->
                          <option value="<?= htmlspecialchars($associate_number) ?>" selected>
                            <?= htmlspecialchars($associate_number) ?> <!-- You can fetch and display the associate's name here if needed -->
                          </option>
                        <?php endif; ?>

                      </select>
                    </div>
                    <div class="col-md-6">
                      <div class="mb-3">
                        <label for="academicYear" class="form-label">Academic Year:</label>
                        <select class="form-select" id="academicYear" name="academicYear" required>
                          <?php if ($lyear != null) { ?>
                            <option hidden selected><?php echo $lyear ?></option>
                          <?php }
                          ?>
                          <?php
                          if (date('m') == 1 || date('m') == 2 || date('m') == 3) {
                            $currentYear = date('Y') - 1;
                          } else {
                            $currentYear = date('Y');
                          }

                          for ($i = 0; $i < 2; $i++) {
                            $nextYear = $currentYear + 1;
                            $yearRange = $currentYear . '-' . $nextYear;
                            echo '<option value="' . $yearRange . '">' . $yearRange . '</option>';
                            $currentYear--;
                          }
                          ?>
                        </select>
                      </div>
                    </div>
                  </div>

                  <button type="submit" class="btn btn-primary mb-3">Search</button>
                </form>
                <?php if (sizeof($resultArr) > 0) { ?>
                  <?php foreach ($resultArr as $array) { ?>
                    <div class="accordion" id="employeeAccordion">
                      <div class="accordion-item">
                        <h2 class="accordion-header" id="employeeDetailsHeading">
                          <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#employeeDetailsCollapse" aria-expanded="true" aria-controls="employeeDetailsCollapse">
                            Associate Details
                          </button>
                        </h2>
                        <div id="employeeDetailsCollapse" class="accordion-collapse collapse show" aria-labelledby="employeeDetailsHeading" data-bs-parent="#employeeAccordion">
                          <div class="accordion-body">
                            <div class="row">
                              <div class="col-md-12 d-flex justify-content-end mb-3">
                                <?php if ($array['filterstatus'] === 'Active') : ?>
                                  <span class="badge bg-success"><?php echo $array['filterstatus'] ?></span>
                                <?php else : ?>
                                  <span class="badge bg-danger"><?php echo $array['filterstatus'] ?></span>
                                <?php endif; ?>
                              </div>
                              <div class="col-md-4">
                                <p><strong>Associate number:</strong> <?php echo $array['associatenumber'] ?></p>
                                <p><strong>Name:</strong> <?php echo $array['fullname'] ?></p>
                                <p><strong>Association type:</strong> <?php echo $array['engagement'] ?></p>
                              </div>
                              <div class="col-md-4">
                                <p><strong>Base Branch:</strong> <?php echo $array['basebranch'] ?></p>
                                <p><strong>Deputed Branch:</strong> <?php echo $array['depb'] ?></p>
                                <p><strong>Base salary/month:</strong> <?php echo 'INR&nbsp;' . $array['salary'] / 12 ?></p>
                                <p><strong>Leave Balance:</strong> <?php echo 'LWP&nbsp;(' . ($array['lwptd'] - $array['lwpadd']) . ')&nbsp;s&nbsp;(' . ($array['slad'] + $array['sladd']) - $array['sltd'] . '),&nbsp;c&nbsp;(' . ($array['clad'] + $array['cladd']) - $array['cltd'] . ')' ?></p>
                              </div>
                              <div class="col-md-4">
                                <!-- <p><strong>Account Number:</strong> <?php echo @$array['accountnumber'] ?></p>
                    <p><strong>Bank Name:</strong> <?php echo @$array['bankname'] ?></p>
                    <p><strong>IFSC Code:</strong> <?php echo @$array['ifsccode'] ?></p>
                    <p><strong>PAN Card Number:</strong> <?php echo @$array['panno'] ?></p> -->
                                <p><strong>Category:</strong> <?php echo $array['job_type'] ?></p>
                                <p><strong>Responsibility:</strong> <?php echo substr($array['position'], 0, strrpos($array['position'], "-")) ?></p>
                                <p><strong>Date of Joining:</strong> <?php echo date('M d, Y', strtotime($array['doj'])) ?></p>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                    <hr>
                    <form id="salaryForm" method="POST" action="payroll_processing.php" onsubmit="return validateForm();">
                      <fieldset <?php echo ($array['filterstatus'] != "Active") ? "disabled" : ""; ?>>
                        <input type="hidden" name="form-type" value="salaryForm">
                        <input type="hidden" class="form-control" id="employeeId" name="employeeId" Value="<?php echo $array['associatenumber'] ?>" required>
                        <input type="hidden" class="form-control" name="lyear" Value="<?php echo $lyear ?>">
                        <div class="row">
                          <div class="col-md-4 mb-3">
                            <label for="payMonth" class="form-label">Pay Month:</label>
                            <select id="payMonth" name="payMonth" class="form-select" required onchange="updateDaysPaid()">
                              <option value="">Select Month</option>
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
                          </div>

                          <div class="col-md-4 mb-3">
                            <label for="payYear" class="form-label">Pay Year:</label>
                            <select id="payYear" name="payYear" class="form-select" required>
                              <option value="">Select Year</option>
                              <!-- Dynamically generate options for current and previous year using JavaScript -->
                            </select>
                          </div>

                          <div class="col-md-4 mb-3">
                            <label for="dayspaid" class="form-label">Days paid:</label>
                            <div class="input-group">
                              <input type="number" class="form-control" id="dayspaid" name="dayspaid" required>
                              <button type="button" class="btn btn-info" id="fetchComponentsBtn">
                                <span id="fetchBtnText">Fetch Components</span>
                                <span id="fetchSpinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                              </button>
                            </div>
                          </div>
                        </div>
                        <div id="salaryComponents">
                          <!-- Salary components will be dynamically added here -->
                        </div>
                        <div class="mb-3">
                          <label for="comment" class="form-label">Comment:</label>
                          <textarea id="comment" name="comment" class="form-control"></textarea>
                        </div>
                        <button type="button" class="btn btn-primary" onclick="addSalaryComponent()">Add Salary Component</button>
                        <button type="button" class="btn btn-warning" onclick="previewPayslip()">Preview Payslip</button>
                        <button type="submit" class="btn btn-success">Submit</button>
                      </fieldset>
                    </form>
                    <br>


                    <!-- Payslip Preview Modal -->
                    <div class="modal fade" id="payslipModal" tabindex="-1" aria-labelledby="payslipModalLabel" aria-hidden="true">
                      <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                          <div class="modal-header">
                            <h5 class="modal-title" id="payslipModalLabel">Payslip Preview</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                          </div>
                          <div class="modal-body" id="payslipPreview">
                            <!-- Placeholder for payslip preview -->
                          </div>
                          <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                          </div>
                        </div>
                      </div>
                    </div>
                  <?php } ?>
                <?php } else { ?>
                  <!-- Onboarding not initiated -->
                  <div class="modal fade" id="myModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h5 class="modal-title" id="exampleModalLabel">Error</h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                          <?php
                          if (pg_num_rows($result) == 0) {
                            $error_message = "No record found for the entered Associate Number";
                          }
                          if (isset($error_message)) {
                            echo $error_message;
                          } ?>
                        </div>
                      </div>
                    </div>
                  </div>
                <?php } ?>
              </div>
            </div>
          </div>
        </div><!-- End Reports -->
      </div>
    </section>

  </main><!-- End #main -->

  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
  <script>
    function updateDaysPaid() {
      var payMonth = document.getElementById("payMonth").value;
      var daysPaidInput = document.getElementById("dayspaid");

      if (payMonth) {
        var daysInMonth = new Date(new Date().getFullYear(), payMonth, 0).getDate();
        daysPaidInput.value = daysInMonth;
      } else {
        daysPaidInput.value = "";
      }
    }
  </script>

  <script>
    // Dynamically generate options for the Pay Year dropdown
    var payYearSelect = document.getElementById('payYear');
    var currentYear = new Date().getFullYear();
    var previousYear = currentYear - 1;
    var yearOptions = '';
    yearOptions += '<option value="' + currentYear + '">' + currentYear + '</option>';
    yearOptions += '<option value="' + previousYear + '">' + previousYear + '</option>';
    payYearSelect.innerHTML = yearOptions;
  </script>

  <!-- Bootstrap JavaScript -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Template Main JS File -->
  <script src="../assets_new/js/main.js"></script>

  <script>
    window.onload = function() {
      var myModal = new bootstrap.Modal(document.getElementById('myModal'), {
        backdrop: 'static',
        keyboard: false
      });
      myModal.show();
    };
  </script>

  <script>
    window.onload = function() {
      var myModal = new bootstrap.Modal(document.getElementById("myModal"), {
        backdrop: "static",
        keyboard: false,
      });
      myModal.show();
    };
  </script>

  <script>
    // Global variable to store component options
    let componentOptions = {
      Earning: [],
      Deduction: [],
    };

    // Fetch component options when page loads
    $(document).ready(function() {
      fetchComponentOptions();

      // Handle fetch components button click
      $("#fetchComponentsBtn").click(function() {
        const associateNumber = $("#employeeId").val();
        const payMonth = $("#payMonth").val();
        const payYear = $("#payYear").val();

        if (!associateNumber || !payMonth || !payYear) {
          alert("Please select associate, pay month and pay year first");
          return;
        }

        // Show spinner
        $("#fetchBtnText").text("Fetching...");
        $("#fetchSpinner").removeClass("d-none");
        $(this).prop("disabled", true);

        // Find the effective salary structure for this month/year
        $.ajax({
          url: "fetch_salary_components.php",
          method: "POST",
          data: {
            associate_number: associateNumber,
            pay_month: payMonth,
            pay_year: payYear,
          },
          dataType: "json",
          success: function(response) {
            // Hide spinner
            $("#fetchBtnText").text("Fetch Components");
            $("#fetchSpinner").addClass("d-none");
            $("#fetchComponentsBtn").prop("disabled", false);

            if (response.success) {
              // Populate the salary components dynamically
              fetchSalaryComponents(response.components);
            } else {
              alert(response.message || "Failed to fetch components");
            }
          },
          error: function() {
            // Hide spinner
            $("#fetchBtnText").text("Fetch Components");
            $("#fetchSpinner").addClass("d-none");
            $("#fetchComponentsBtn").prop("disabled", false);
            alert("Error fetching components");
          },
        });
      });
    });

    // Function to fetch component options from database
    function fetchComponentOptions() {
      $.ajax({
        url: "fetch_component_options.php",
        method: "GET",
        dataType: "json",
        success: function(response) {
          if (response.success) {
            componentOptions = response.options;
          } else {
            alert("Failed to load component options");
          }
        },
        error: function() {
          alert("Error loading component options");
        },
      });
    }

    function addSalaryComponent() {
      const salaryComponentsDiv = document.getElementById("salaryComponents");

      // Create a new row for the salary component
      const row = document.createElement("div");
      row.className = "row mb-3";

      const componentTypeSelect = createSelectField(
        "componentName[]",
        "Component Type",
        ["Earning", "Deduction"]
      );
      const subCategorySelect = createSelectField(
        "subCategory[]",
        "Sub-Category",
        [],
        true
      ); // Initially disabled
      const amountInput = createInputField("amount[]", "Amount");

      const buttonsContainer = document.createElement("div");
      buttonsContainer.className = "col";

      const minusButton = createMinusButton();
      buttonsContainer.appendChild(minusButton);

      // Append the elements to the row
      row.appendChild(componentTypeSelect);
      row.appendChild(subCategorySelect);
      row.appendChild(amountInput);
      row.appendChild(buttonsContainer);

      salaryComponentsDiv.appendChild(row);

      // Remove Add button from all rows except the last one
      updateAddButton();

      // Add event listener for componentTypeSelect dropdown
      componentTypeSelect.querySelector("select").addEventListener("change", function() {
        updateSubCategoryOptions(this, subCategorySelect);
      });
    }

    function createSelectField(name, label, options, disabled = false) {
      const div = document.createElement("div");
      div.className = "col";

      const select = document.createElement("select");
      select.className = "form-select";
      select.name = name;
      select.required = true;
      select.disabled = disabled;

      const defaultOption = document.createElement("option");
      defaultOption.value = "";
      defaultOption.innerHTML = label;
      defaultOption.selected = true;
      defaultOption.disabled = true;

      select.appendChild(defaultOption);

      options.forEach(function(option) {
        const optionElement = document.createElement("option");
        optionElement.value = option;
        optionElement.text = option;
        select.appendChild(optionElement);
      });

      div.appendChild(select);
      return div;
    }

    function createInputField(name, placeholder) {
      const div = document.createElement("div");
      div.className = "col";

      const input = document.createElement("input");
      input.type = "number";
      input.className = "form-control";
      input.name = name;
      input.placeholder = placeholder;
      input.step = "0.01";

      div.appendChild(input);
      return div;
    }

    function createPlusButton() {
      const button = document.createElement("button");
      button.type = "button";
      button.className = "btn btn-primary btn-sm me-2";
      button.innerHTML = "+ Add";
      button.onclick = addSalaryComponent;

      return button;
    }

    function createMinusButton() {
      const button = document.createElement("button");
      button.type = "button";
      button.className = "btn btn-danger btn-sm";
      button.innerHTML = "- Remove";
      button.onclick = deleteSalaryComponent;

      return button;
    }

    function deleteSalaryComponent() {
      const row = this.parentNode.parentNode;
      const salaryComponentsDiv = document.getElementById("salaryComponents");
      salaryComponentsDiv.removeChild(row);

      // Update Add button
      updateAddButton();
    }

    function updateAddButton() {
      const rows = document.querySelectorAll("#salaryComponents .row");
      rows.forEach((row, index) => {
        const buttonsContainer = row.querySelector(".col");
        const addButton = buttonsContainer.querySelector(".btn-primary");

        // Remove existing Add button
        if (addButton) {
          buttonsContainer.removeChild(addButton);
        }

        // Add Add button to the last row
        if (index === rows.length - 1) {
          const plusButton = createPlusButton();
          buttonsContainer.appendChild(plusButton);
        }
      });
    }

    function updateSubCategoryOptions(componentSelect, subCategorySelect) {
      const selectedComponent = componentSelect.value;
      const subCategorySelectElement = subCategorySelect.querySelector("select");

      // Clear existing options
      subCategorySelectElement.innerHTML = "";

      // Add default option
      const defaultOption = document.createElement("option");
      defaultOption.value = "";
      defaultOption.innerHTML = "Sub-Category";
      defaultOption.selected = true;
      defaultOption.disabled = true;
      subCategorySelectElement.appendChild(defaultOption);

      // Enable/disable based on selection
      subCategorySelectElement.disabled = selectedComponent === "";

      if (selectedComponent !== "") {
        // Get options from database
        const options = componentOptions[selectedComponent] || [];

        // Add options to select
        options.forEach(function(option) {
          const optionElement = document.createElement("option");
          optionElement.value = option;
          optionElement.text = option;
          subCategorySelectElement.appendChild(optionElement);
        });

        // Enable the select
        subCategorySelectElement.disabled = false;
      }
    }

    function fetchSalaryComponents(components) {
      const salaryComponentsDiv = document.getElementById("salaryComponents");
      salaryComponentsDiv.innerHTML = ""; // Clear existing components

      components.forEach((component) => {
        addSalaryComponentWithValues(
          component.is_deduction ? "Deduction" : "Earning",
          component.component_name,
          component.monthly_amount
        );
      });

      // Update Add button
      updateAddButton();
    }

    function addSalaryComponentWithValues(componentType, componentName, amount) {
      const salaryComponentsDiv = document.getElementById("salaryComponents");
      const row = document.createElement("div");
      row.className = "row mb-3";

      const componentTypeSelect = createSelectField(
        "componentName[]",
        "Component Type",
        ["Earning", "Deduction"]
      );
      componentTypeSelect.querySelector("select").value = componentType;

      const subCategorySelect = createSelectField(
        "subCategory[]",
        "Sub-Category",
        [],
        false
      );
      subCategorySelect.querySelector("select").innerHTML = `
      <option value="${componentName}" selected>${componentName}</option>
    `;

      const amountInput = createInputField("amount[]", "Amount");
      amountInput.querySelector("input").value = amount;

      const buttonsContainer = document.createElement("div");
      buttonsContainer.className = "col";

      const minusButton = createMinusButton();
      buttonsContainer.appendChild(minusButton);

      row.appendChild(componentTypeSelect);
      row.appendChild(subCategorySelect);
      row.appendChild(amountInput);
      row.appendChild(buttonsContainer);

      salaryComponentsDiv.appendChild(row);

      // Update Add button
      updateAddButton();
    }

    // Fix for payslip preview modal not showing up
    function previewPayslip() {
      // Get the employee ID, pay month, and pay year from the form
      var employeeId = document.getElementById('employeeId').value;
      var payMonth = document.getElementById('payMonth').value;
      var payYear = document.getElementById('payYear').value;
      var daysPaid = document.getElementById('dayspaid').value;

      // Get the selected salary components and their amounts
      var components = document.getElementsByName('componentName[]');
      var amounts = document.getElementsByName('amount[]');

      var payslipPreview = '<div class="mb-4">';
      payslipPreview += '<div class="card">';
      payslipPreview += '<div class="card-header">Employee Pay Information</div>';
      payslipPreview += '<div class="card-body">';
      payslipPreview += '<dl class="row">';
      payslipPreview += '<dt class="col-sm-4">Employee ID:</dt><dd class="col-sm-8">' + employeeId + '</dd>';
      payslipPreview += '<dt class="col-sm-4">Pay Month:</dt><dd class="col-sm-8">' + payMonth + '</dd>';
      payslipPreview += '<dt class="col-sm-4">Pay Year:</dt><dd class="col-sm-8">' + payYear + '</dd>';
      payslipPreview += '<dt class="col-sm-4">Days Paid:</dt><dd class="col-sm-8">' + daysPaid + '</dd>';
      payslipPreview += '</dl>';
      payslipPreview += '</div>';
      payslipPreview += '</div>';
      payslipPreview += '</div>';


      payslipPreview += '<div>';
      payslipPreview += '<h3 class="mb-3">Earnings:</h3>';
      payslipPreview += '<table class="table table-striped">';
      payslipPreview += '<thead><tr><th>Component</th><th>Subcategory</th><th>Amount</th></tr></thead><tbody>';

      var totalEarnings = 0;

      // Add the selected earnings components, their subcategories, and amounts to the table
      for (var i = 0; i < components.length; i++) {
        if (components[i].value === 'Earning') {
          var componentName = components[i].options[components[i].selectedIndex].text;
          var subCategory = components[i].parentNode.parentNode.querySelector("select[name='subCategory[]']").value;
          var amount = parseFloat(amounts[i].value);

          payslipPreview += '<tr><td>' + componentName + '</td><td>' + subCategory + '</td><td>' + amount + '</td></tr>';

          totalEarnings += amount;
        }
      }

      payslipPreview += '</tbody></table>';

      payslipPreview += '<h3 class="mt-4 mb-3">Deductions:</h3>';
      payslipPreview += '<table class="table table-striped">';
      payslipPreview += '<thead><tr><th>Component</th><th>Subcategory</th><th>Amount</th></tr></thead><tbody>';

      var totalDeductions = 0;

      // Add the selected deductions components, their subcategories, and amounts to the table
      for (var i = 0; i < components.length; i++) {
        if (components[i].value === 'Deduction') {
          var componentName = components[i].options[components[i].selectedIndex].text;
          var subCategory = components[i].parentNode.parentNode.querySelector("select[name='subCategory[]']").value;
          var amount = parseFloat(amounts[i].value);

          payslipPreview += '<tr><td>' + componentName + '</td><td>' + subCategory + '</td><td>' + amount + '</td></tr>';

          totalDeductions += amount;
        }
      }

      payslipPreview += '</tbody></table>';

      // Calculate net pay
      var netPay = totalEarnings - totalDeductions;

      // Display total earnings, deductions, and net pay
      payslipPreview += '<div class="mt-4">';
      payslipPreview += '<h3 class="mb-2">Summary:</h3>';
      payslipPreview += '<table class="table">';
      payslipPreview += '<tr><th>Total Earnings:</th><td>' + totalEarnings + '</td></tr>';
      payslipPreview += '<tr><th>Total Deductions:</th><td>' + totalDeductions + '</td></tr>';
      payslipPreview += '<tr><th>Net Pay:</th><td>' + netPay + '</td></tr>';
      payslipPreview += '</table>';
      payslipPreview += '</div>';

      payslipPreview += '</div></div>';

      // Set the payslip preview content in the modal
      document.getElementById('payslipPreview').innerHTML = payslipPreview;

      // Show the payslip preview modal
      var payslipModal = new bootstrap.Modal(document.getElementById('payslipModal'));
      payslipModal.show();
    }
  </script>

</body>

</html>