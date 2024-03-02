<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");
include("../../util/email.php");


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
  echo 'window.location.href = "home.php";';
  echo '</script>';
}
if ($role != 'Admin') {
  echo '<script type="text/javascript">';
  echo 'alert("Access Denied. You are not authorized to access this web page.");';
  echo 'window.location.href = "home.php";';
  echo '</script>';
}

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

  // Insert form data into the database
  try {
    // Insert into payslip_entry table first
    $query = "INSERT INTO payslip_entry (payslip_entry_id, employeeid, paymonth, payyear, dayspaid, comment, payslip_issued_by, payslip_issued_on, payslip_issued_ip) 
              VALUES ('$uniqueId', '$employeeId', '$paymonth', $payyear, $dayspaid, '$comment','$associatenumber','$now','$ip_address')";
    $result = pg_query($con, $query);

    if (!$result) {
      echo "Error: " . pg_last_error($con);
      exit;
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
        echo "Error: " . pg_last_error($con);
        exit;
      }
    }
  } catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    exit;
  }

  if (@$cmdtuples == 1 && $employee_email != "") {
    sendEmail("payslip", array(
      "month" => date('F', mktime(0, 0, 0, $paymonth, 1)),
      "year" => @$payyear,
      "fullname" => @$employee_fullname,
      "associatenumber" => @$employee_associatenumber,
      "payslipid" => @$uniqueId,
    ), $employee_email, False);
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
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
</head>

<body>
  <?php if (@$employeeId != null && @$cmdtuples == 0) { ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert" style="text-align: center;">
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      <span class="blink_me"><i class="bi bi-x-lg"></i></span>&nbsp;&nbsp;<span>Error: Failed to create payslip. Please try again.</span>
    </div>
  <?php } else if (@$cmdtuples == 1) { ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert" style="text-align: center;">
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      <span class="blink_me"><i class="bi bi-check-lg"></i></span>&nbsp;&nbsp;<span>Payslip created successfully!</span>
    </div>
    <script>
      if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
      }
    </script>
  <?php } ?>
  <div class="container">
    <form id="employeeLookupForm" method="get">
      <h3>Associate Information Lookup</h3>
      <hr>
      <div class="row">
        <div class="col-md-6">
          <div class="mb-3">
            <label for="lookupEmployeeId" class="form-label">Associate Number:</label>
            <input type="text" class="form-control" id="lookupEmployeeId" name="lookupEmployeeId" value="<?php echo $associate_number ?>" placeholder="Enter associate number" required>
            <div class="form-text">Enter the associate number to search for their information.</div>
          </div>
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
                    <p><strong>Category:</strong> <?php echo $array['job_type'] ?></p>
                    <p><strong>Responsibility:</strong> <?php echo substr($array['position'], 0, strrpos($array['position'], "-")) ?></p>
                    <p><strong>Date of Joining:</strong> <?php echo date('M d, Y', strtotime($array['doj'])) ?></p>
                  </div>
                  <div class="col-md-4">
                    <p><strong>Base Branch:</strong> <?php echo $array['basebranch'] ?></p>
                    <p><strong>Deputed Branch:</strong> <?php echo $array['depb'] ?></p>
                    <p><strong>Base salary/month:</strong> <?php echo 'INR&nbsp;' . $array['salary'] / 12 ?></p>
                    <p><strong>Leave Balance:</strong> <?php echo 'LWP&nbsp;(' . ($array['lwptd'] - $array['lwpadd']) . ')&nbsp;s&nbsp;(' . ($array['slad'] + $array['sladd']) - $array['sltd'] . '),&nbsp;c&nbsp;(' . ($array['clad'] + $array['cladd']) - $array['cltd'] . ')' ?></p>
                  </div>
                  <div class="col-md-4">
                    <p><strong>Account Number:</strong> <?php echo @$array['accountnumber'] ?></p>
                    <p><strong>Bank Name:</strong> <?php echo @$array['bankname'] ?></p>
                    <p><strong>IFSC Code:</strong> <?php echo @$array['ifsccode'] ?></p>
                    <p><strong>PAN Card Number:</strong> <?php echo @$array['panno'] ?></p>
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
                <input type="number" class="form-control" id="dayspaid" name="dayspaid" required>
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

    <script>
      // ... Existing code ...

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

      // ... Existing code ...
    </script>
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

    <script>
      function addSalaryComponent() {
        var salaryComponentsDiv = document.getElementById("salaryComponents");

        // Create a new row for the salary component
        var row = document.createElement("div");
        row.className = "row mb-3";

        // Create the select fields for component name and sub-category
        var componentNameSelect = createSelectField("componentName[]", "Component Name", [
          "Earning", "Deduction"
        ]);
        var subCategorySelect = createSelectField("subCategory[]", "Sub-Category", []);
        var amountInput = createInputField("amount[]", "Amount");

        // Add the 'required' attribute to the input field
        amountInput.querySelector("input").required = true;
        subCategorySelect.querySelector("select").disabled = true;

        // Append the select fields and input field to the row
        row.appendChild(componentNameSelect);
        row.appendChild(subCategorySelect);
        row.appendChild(amountInput);

        // Create the plus and minus buttons
        var plusButton = createPlusButton();
        var minusButton = createMinusButton();

        // Create a container div for the buttons
        var buttonsContainer = document.createElement("div");
        buttonsContainer.className = "col";

        // Append the buttons to the container div
        buttonsContainer.appendChild(plusButton);
        buttonsContainer.appendChild(minusButton);

        // Append the container div to the row
        row.appendChild(buttonsContainer);

        // Append the row to the salary components div
        salaryComponentsDiv.appendChild(row);

        // Remove plus button from the previous component
        var previousComponent = row.previousElementSibling;
        if (previousComponent) {
          var previousPlusButton = previousComponent.querySelector(".btn-primary");
          previousPlusButton.parentNode.removeChild(previousPlusButton);
        }

        // Add event listener to the componentNameSelect dropdown
        componentNameSelect.querySelector("select").addEventListener("change", function() {
          updateSubCategoryOptions(this, subCategorySelect);
        });
      }

      function createSelectField(name, label, options) {
        var div = document.createElement("div");
        div.className = "col";

        var select = document.createElement("select");
        select.className = "form-select";
        select.name = name;
        select.required = true; // Add the required attribute

        var defaultOption = document.createElement("option");
        defaultOption.value = "";
        defaultOption.innerHTML = label;
        defaultOption.selected = true;
        defaultOption.disabled = true;

        select.appendChild(defaultOption);

        options.forEach(function(option) {
          var optionElement = document.createElement("option");
          optionElement.value = option;
          optionElement.text = option;
          select.appendChild(optionElement);
        });

        div.appendChild(select);
        return div;
      }

      function createInputField(name, placeholder) {
        var div = document.createElement("div");
        div.className = "col";

        var input = document.createElement("input");
        input.type = "number";
        input.className = "form-control";
        input.name = name;
        input.placeholder = placeholder;

        div.appendChild(input);
        return div;
      }

      function createPlusButton() {
        var button = document.createElement("button");
        button.type = "button";
        button.className = "btn btn-primary btn-sm me-2";
        button.innerHTML = "+ Add";
        button.onclick = addSalaryComponent;

        return button;
      }

      function createMinusButton() {
        var button = document.createElement("button");
        button.type = "button";
        button.className = "btn btn-danger btn-sm";
        button.innerHTML = "- Remove";
        button.onclick = deleteSalaryComponent;

        return button;
      }

      function deleteSalaryComponent() {
        var row = this.parentNode.parentNode;
        var salaryComponentsDiv = document.getElementById("salaryComponents");
        salaryComponentsDiv.removeChild(row);
      }

      function updateSubCategoryOptions(componentSelect) {
        var selectedComponent = componentSelect.value;
        var subCategorySelect = componentSelect.parentNode.nextSibling.querySelector("select");

        subCategorySelect.disabled = selectedComponent === "";

        // Clear existing sub-category options if "Component Name" is not selected
        if (selectedComponent === "") {
          subCategorySelect.innerHTML = "";
          var defaultOption = document.createElement("option");
          defaultOption.value = "";
          defaultOption.innerHTML = "Sub-Category";
          defaultOption.selected = true;
          defaultOption.disabled = true;
          subCategorySelect.appendChild(defaultOption);
        } else {
          // Add sub-category options based on the selected component
          subCategorySelect.innerHTML = ""; // Clear existing options
          var subCategoryOptions = [];

          if (selectedComponent === "Earning") {
            subCategoryOptions = ["Basic Salary", "Bonus"];
          } else if (selectedComponent === "Deduction") {
            subCategoryOptions = ["Payment adjustment", "LWP deduction", "Service Charge", "Salary Advance Recovery"];
          } else {
            subCategoryOptions = [];
          }

          subCategoryOptions.forEach(function(option) {
            var optionElement = document.createElement("option");
            optionElement.value = option;
            optionElement.text = option;
            subCategorySelect.appendChild(optionElement);
          });
        }
      }

      function validateForm() {
        var inputs = document.querySelectorAll("#salaryComponents input");
        var selectFields = document.querySelectorAll("#salaryComponents select");

        // Check if at least one component has been added
        if (inputs.length === 0 && selectFields.length === 0) {
          alert("Please add at least one salary component.");
          return false; // Prevent form submission
        }

        // Check for required fields
        for (var i = 0; i < inputs.length; i++) {
          if (inputs[i].hasAttribute("required") && !inputs[i].value) {
            alert("Please fill in all required fields in the salary components.");
            return false; // Prevent form submission
          }
        }

        return true; // Allow form submission
      }
    </script>

    <script>
      window.onload = function() {
        var myModal = new bootstrap.Modal(document.getElementById('myModal'), {
          backdrop: 'static',
          keyboard: false
        });
        myModal.show();
      };
    </script>

</body>

</html>