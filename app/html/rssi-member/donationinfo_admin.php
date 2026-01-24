<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");


if (!isLoggedIn("aid")) {
  $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
  header("Location: index.php");
  exit;
}
validation();

function calculateFinancialYear($timestamp)
{
  $date = date('Y-m-d', $timestamp);
  $year = date('Y', $timestamp);
  $startYear = ($date < $year . '-04-01') ? ($year - 1) : $year;
  $endYear = $startYear + 1;

  $financialYear = $startYear . '-' . $endYear;
  return $financialYear;
}

$searchField = isset($_POST['searchField']) ? trim($_POST['searchField']) : '';
$fyear = isset($_POST['get_fyear']) ? $_POST['get_fyear'] : '';

function fetchDataAndTotalAmount($con, $searchField, $fyear)
{
  $query = "SELECT
    pd.*,
    ud.*,
    CASE 
        WHEN EXTRACT(MONTH FROM pd.timestamp) >= 4 THEN EXTRACT(YEAR FROM pd.timestamp)
        ELSE EXTRACT(YEAR FROM pd.timestamp) - 1
    END || '-' ||
    CASE 
        WHEN EXTRACT(MONTH FROM pd.timestamp) >= 4 THEN EXTRACT(YEAR FROM pd.timestamp) + 1
        ELSE EXTRACT(YEAR FROM pd.timestamp)
    END AS financial_year
    FROM donation_paymentdata AS pd
    LEFT JOIN donation_userdata AS ud ON pd.tel = ud.tel
    WHERE (
        (
            pd.donationid LIKE '%' || $1 || '%' OR
            pd.tel LIKE '%' || $1 || '%'
        ) OR $1 IS NULL
    ) AND (
        (
            CASE 
                WHEN EXTRACT(MONTH FROM pd.timestamp) >= 4 THEN EXTRACT(YEAR FROM pd.timestamp)
                ELSE EXTRACT(YEAR FROM pd.timestamp) - 1
            END || '-' ||
            CASE 
                WHEN EXTRACT(MONTH FROM pd.timestamp) >= 4 THEN EXTRACT(YEAR FROM pd.timestamp) + 1
                ELSE EXTRACT(YEAR FROM pd.timestamp)
            END
        ) = $2 OR $2 IS NULL
    )
    ORDER BY pd.timestamp DESC";

  $params = array();
  if ($searchField !== '') {
    $params[] = $searchField;
  } else {
    $params[] = null;
  }

  if ($fyear !== '') {
    $params[] = $fyear;
  } else {
    $params[] = null;
  }

  $result = pg_query_params($con, $query, $params);
  $resultArr = pg_fetch_all($result);

  $totalAmountQuery = "SELECT SUM(pd.amount)
                        FROM donation_paymentdata AS pd
                        WHERE (
                            (
                                pd.donationid LIKE '%' || $1 || '%' OR
                                pd.tel LIKE '%' || $1 || '%'
                            ) OR $1 IS NULL
                        ) AND (
                            (
                                CASE 
                                    WHEN EXTRACT(MONTH FROM pd.timestamp) >= 4 THEN EXTRACT(YEAR FROM pd.timestamp)
                                    ELSE EXTRACT(YEAR FROM pd.timestamp) - 1
                                END || '-' ||
                                CASE 
                                    WHEN EXTRACT(MONTH FROM pd.timestamp) >= 4 THEN EXTRACT(YEAR FROM pd.timestamp) + 1
                                    ELSE EXTRACT(YEAR FROM pd.timestamp)
                                END
                            ) = $2 OR $2 IS NULL
                        ) AND status='Approved'";

  $totalAmountResult = pg_query_params($con, $totalAmountQuery, $params);
  $totalDonatedAmount = pg_fetch_result($totalAmountResult, 0, 0);

  return array($resultArr, $totalDonatedAmount);
}

if ($searchField !== '' || $fyear !== '') {
  list($resultArr, $totalDonatedAmount) = fetchDataAndTotalAmount($con, $searchField, $fyear);
} else {
  $resultArr = array();
  $totalDonatedAmount = 0;
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
    <?php include 'includes/meta.php' ?>

  

  <!-- Favicons -->
  <link href="../img/favicon.ico" rel="icon">
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
  <!-- CSS Library Files -->
  <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
  <!-- JavaScript Library Files -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
  <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>

</head>

<!-- =========================
     NAVIGATION LINKS     
============================== -->

<body>
  <?php include 'inactive_session_expire_check.php'; ?>
  <?php include 'includes/header.php'; ?>

  <main id="main" class="main">

    <div class="pagetitle">
      <h1><?php echo getPageTitle(); ?></h1>
      <?php echo generateDynamicBreadcrumb(); ?>
    </div><!-- End Page Title -->

    <section class="section dashboard">
      <div class="row">

        <!-- Reports -->
        <div class="col-12">
          <div class="card">

            <div class="card-body">
              <br>
              <div class="row">
                <div class="col">
                  Record count:&nbsp;<?php echo sizeof($resultArr) ?>
                  <br>Total donated amount:&nbsp;<p class="badge bg-secondary"><?php echo $totalDonatedAmount ?></p>
                </div>
                <div class="col text-end">
                  <a href="old_donationinfo_admin.php">Donation till June 23 >></a>
                </div>
              </div>
              <div class="d-flex justify-content-between align-items-center position-absolute top-5 end-0 p-3">
                <form method="POST" action="export_function.php">
                  <input type="hidden" value="donation" name="export_type" />
                  <input type="hidden" value="<?php echo $searchField ?>" name="searchField_export" />
                  <input type="hidden" value="<?php echo $fyear ?>" name="fyear_export" />

                  <button type="submit" id="export" name="export" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none;
                        padding: 0px;
                        border: none;" title="Export CSV">
                    <i class="bi bi-file-earmark-excel" style="font-size:large;"></i>
                  </button>
                </form>
              </div>
              <br>
              <form action="" method="POST">
                <div class="form-group" style="display: inline-block;">
                  <div class="col2" style="display: inline-block;">
                    <input name="searchField" class="form-control" style="width:max-content; display:inline-block" placeholder="Donation ref or contact number" value="<?php echo htmlspecialchars($searchField); ?>">
                    <select name="get_fyear" id="get_fyear" class="form-select" style="width:max-content;display:inline-block" required>
                      <?php if ($fyear == null) { ?>
                        <option disabled selected hidden>Select Year</option>
                      <?php
                      } else { ?>
                        <option hidden selected><?php echo $fyear ?></option>
                      <?php }
                      ?>
                    </select>
                  </div>
                </div>
                <div class="col2 left" style="display: inline-block;">
                  <button type="submit" name="search_by_id" class="btn btn-success btn-sm" style="outline: none;">
                    <i class="bi bi-search"></i>&nbsp;Search</button>
                </div>
              </form>
              <div class="table-responsive">
                <table class="table" id="table-id">
                  <thead>
                    <tr>
                      <th scope="col">Date</th>
                      <th scope="col">Name</th>
                      <th scope="col">FY</th>
                      <th scope="col">Transaction id</th>
                      <th scope="col">Amount</th>
                      <th scope="col">National Identifier/Number</th>
                      <th scope="col">Mode of payment</th>
                      <th scope="col">Invoice</th>
                      <th scope="col">Status</th>
                      <th scope="col">Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if ($resultArr != null): ?>
                      <?php foreach ($resultArr as $array): ?>
                        <tr>
                          <td><?= date("d/m/Y", strtotime($array['timestamp'])) ?></td>
                          <td>
                            <?= $array['fullname'] ?><br>
                            <?= $array['tel'] ?><br>
                            <?= $array['email'] ?>
                          </td>
                          <td><?= $array['financial_year'] ?></td>
                          <td><?= $array['transactionid'] ?></td>
                          <td><?= $array['currency'] ?>&nbsp;<?= $array['amount'] ?></td>
                          <td>
                            <?php if (!empty($array['nationalid'])): ?>
                              <a href="<?= htmlspecialchars($array['nationalid']) ?>" target="_blank">
                                <?= htmlspecialchars($array['id_type']) ?>
                              </a>/<?= $array['id_number'] ?>
                            <?php else: ?>
                              <?= htmlspecialchars($array['id_type']) ?>/<?= $array['id_number'] ?>
                            <?php endif; ?>
                          </td>
                          <td>Online</td>
                          <td>
                            <?php if ($array['status'] === 'Approved'): ?>
                              <a href="/donation_invoice.php?searchField=<?= $array['donationid'] ?>" target="_blank"><?= $array['donationid'] ?></a>
                            <?php else: ?>
                              <?= $array['donationid'] ?>
                            <?php endif; ?>
                          </td>
                          <td><?= $array['status'] ?></td>
                          <td>
                            <?php if ($role == 'Admin'): ?>
                              <div class="dropdown">
                                <button class="btn btn-sm btn-light dropdown-toggle" type="button" id="dropdownMenuLink_<?= $array['donationid'] ?>" data-bs-toggle="dropdown" aria-expanded="false" style="padding: 4px 8px;">
                                  <i class="bi bi-three-dots-vertical" style="font-size:14px;"></i>
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="dropdownMenuLink_<?= $array['donationid'] ?>">
                                  <li>
                                    <button class="dropdown-item" type="button" onclick="showDetails('<?= $array['donationid'] ?>')">
                                      <i class="bi bi-box-arrow-up-right me-2"></i> Show Details
                                    </button>
                                  </li>
                                  <?php if (!empty($array['tel']) && $array['status'] == 'Approved'): ?>
                                    <?php
                                    $phone = $array['tel'];
                                    $fullname = $array['fullname'];
                                    $donationid = $array['donationid'];
                                    $message = urlencode("Dear $fullname ($phone),\n\nThank you for your generous donation! Your invoice for Donation ID $donationid has been issued and emailed to your registered email address.\n\nWe greatly appreciate your support for our cause. Please feel free to reach out if you have any questions or need assistance.\n\n--RSSI\n\n**This is an automatically generated SMS");
                                    $whatsappLink = "https://api.whatsapp.com/send?phone=91$phone&text=$message";
                                    ?>
                                    <li>
                                      <a class="dropdown-item" href="<?= $whatsappLink ?>" target="_blank">
                                        <i class="bi bi-whatsapp me-2"></i> Send WhatsApp
                                      </a>
                                    </li>
                                  <?php endif; ?>
                                  <?php if (!empty($array['email']) && $array['status'] == 'Approved'): ?>
                                    <li>
                                      <form action="#" name="email-form-<?= $array['donationid'] ?>" method="POST" style="display: inline;">
                                        <input type="hidden" name="template" value="donation_invoice">
                                        <input type="hidden" name="data[donationid]" value="<?= $array['donationid'] ?>">
                                        <input type="hidden" name="data[fullname]" value="<?= $array['fullname'] ?>">
                                        <input type="hidden" name="email" value="<?= $array['email'] ?>">
                                        <button type="submit" class="dropdown-item">
                                          <i class="bi bi-envelope-at me-2"></i> Send Email
                                        </button>
                                      </form>
                                    </li>
                                  <?php endif; ?>
                                </ul>
                              </div>
                            <?php endif; ?>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    <?php elseif ($searchField == null && $fyear == null): ?>
                      <tr>
                        <td colspan="10">Please select Filter value.</td>
                      </tr>
                    <?php else: ?>
                      <tr>
                        <td colspan="10">No record found for <?= $searchField ?> <?= $fyear ?></td>
                      </tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>

              <script>
                <?php if (date('m') == 1 || date('m') == 2 || date('m') == 3) { ?>
                  var currentYear = new Date().getFullYear() - 1;
                <?php } else { ?>
                  var currentYear = new Date().getFullYear();
                <?php } ?>

                for (var i = 0; i < 5; i++) {
                  var next = currentYear + 1;
                  var status = currentYear + '-' + next;
                  //next.toString().slice(-2) 
                  $('#get_fyear').append(new Option(status, status));
                  currentYear--;
                }
              </script>


            </div>
          </div>
        </div><!-- End Reports -->
      </div>
    </section>

  </main><!-- End #main -->

  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- POP-UP BOX -->
  <div class="modal fade" id="myModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h1 class="modal-title fs-5" id="exampleModalLabel">Donation Review</h1>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div style="width: 100%; text-align: right;">
            <p id="status" class="badge bg-secondary"><span class="donationid"></span></p>
          </div>
          <p>Message from Donor</p>
          <p><span class="message"></span></p>

          <form id="donation_review" action="#" method="POST">
            <input type="hidden" name="form-type" value="donation_review" readonly>
            <input type="hidden" name="reviewer_id" value="<?php echo $associatenumber ?>" readonly>
            <input type="hidden" name="donationid" id="donationid" readonly>

            <div class="mb-3">
              <label for="reviewer_status" class="form-label">Status</label>
              <select name="reviewer_status" id="reviewer_status" class="form-select" required>
                <option value="" disabled selected hidden>Select status</option>
                <option value="Approved">Approved</option>
                <option value="Under review">Under review</option>
                <option value="Rejected">Rejected</option>
              </select>
            </div>

            <div class="mb-3">
              <label for="reviewer_remarks" class="form-label">Reviewer Remarks</label>
              <textarea name="reviewer_remarks" id="reviewer_remarks" class="form-control" placeholder="Reviewer remarks"></textarea>
              <small id="passwordHelpBlock" class="form-text text-muted">Reviewer remarks</small>
            </div>

            <button type="submit" id="donationupdate" class="btn btn-danger btn-sm">Update</button>
          </form>
          <div class="text-end p-2" style="font-size: small; font-style: italic; color: #A2A2A2;">
            Updated by: <span class="reviewedby"></span> on <span class="reviewedon"></span>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" id="closedetails-footer" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <script>
    var data = <?php echo json_encode($resultArr) ?>;

    function showDetails(id) {
      var modal = new bootstrap.Modal(document.getElementById('myModal'));
      var mydata = data.find(item => item["donationid"] === id);

      if (!mydata) return;

      // Fill fields
      document.querySelector(".donationid").innerHTML = mydata["donationid"];
      document.querySelector(".message").innerHTML = mydata["message"] ? mydata["message"] : "No message provided.";
      document.getElementById("donationid").value = mydata["donationid"];
      document.getElementById("reviewer_status").value = mydata["status"] || '';
      document.getElementById("reviewer_remarks").value = mydata["reviewer_remarks"] || '';
      document.querySelector(".reviewedby").innerHTML = mydata["reviewedby"] || '';
      document.querySelector(".reviewedon").innerHTML = mydata["reviewedon"] || '';

      // Set status badge
      var status = document.getElementById("status");
      status.classList.remove("bg-success", "bg-danger", "bg-secondary");
      if (mydata["status"] === "Approved") {
        status.classList.add("bg-success");
      } else if (mydata["status"] === "Rejected") {
        status.classList.add("bg-danger");
      } else {
        status.classList.add("bg-secondary");
      }

      // Disable or enable update button
      document.getElementById("donationupdate").disabled = (mydata["status"] === 'Approved' || mydata["status"] === 'Rejected');

      modal.show();
    }
  </script>
  <!-- Script to handle form submission and email sending -->
  <script>
    var data = <?php echo json_encode($resultArr) ?>;
    //For form submission - to update Remarks
    const scriptURL = 'payment-api.php'

    const form = document.getElementById('donation_review')
    form.addEventListener('submit', e => {
      e.preventDefault()
      fetch(scriptURL, {
          method: 'POST',
          body: new FormData(document.getElementById('donation_review'))
        })
        .then(response => response.text())
        .then(result => {
          if (result === 'success') {
            alert("Record has been updated.");
            location.reload();
          } else {
            alert("Error updating record. Please try again later or contact support.");
          }
        })
        .catch(error => {
          console.error('Error!', error.message);
        });
    });

    data.forEach(item => {
      const formId = 'email-form-' + item.donationid
      const form = document.forms[formId]
      form.addEventListener('submit', e => {
        e.preventDefault()
        fetch('mailer.php', {
            method: 'POST',
            body: new FormData(document.forms[formId])
          })
          .then(response =>
            alert("Email has been sent.")
          )
          .catch(error => console.error('Error!', error.message))
      })
    })
  </script>
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
          // paging: false,
          "order": [] // Disable initial sorting
          // other options...
        });
      <?php endif; ?>
    });
  </script>

</body>

</html>