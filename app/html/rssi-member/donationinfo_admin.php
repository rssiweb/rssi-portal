<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");


if (!isLoggedIn("aid")) {
  $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
  header("Location: index.php");
  exit;
}

if ($role != 'Admin') {
  echo '<script type="text/javascript">';
  echo 'alert("Access Denied. You are not authorized to access this web page.");';
  echo 'window.location.href = "home.php";';
  echo '</script>';
}

function calculateFinancialYear($timestamp)
{
  $date = date('Y-m-d', $timestamp);
  $year = date('Y', $timestamp);
  $startYear = ($date < $year . '-04-01') ? ($year - 1) : $year;
  $endYear = $startYear + 1;

  $financialYear = $startYear . '-' . $endYear;
  return $financialYear;
}

$searchField = isset($_POST['searchField']) ? $_POST['searchField'] : '';
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
  WHERE ((pd.tel = $1 AND pd.donationid IS NOT NULL) OR $1 IS NULL) AND
        (((CASE 
            WHEN EXTRACT(MONTH FROM pd.timestamp) >= 4 THEN EXTRACT(YEAR FROM pd.timestamp)
            ELSE EXTRACT(YEAR FROM pd.timestamp) - 1
        END || '-' ||
        CASE 
            WHEN EXTRACT(MONTH FROM pd.timestamp) >= 4 THEN EXTRACT(YEAR FROM pd.timestamp) + 1
            ELSE EXTRACT(YEAR FROM pd.timestamp)
        END) = $2 AND $2 IS NOT NULL) OR $2 IS NULL) ORDER BY pd.timestamp DESC";

  $params = array();
  if ($searchField !== '') {
    $params[] = $searchField;
  } else {
    $params[] = null; // Placeholder value for $1
  }

  if ($fyear !== '') {
    $params[] = $fyear;
  } else {
    $params[] = null; // Placeholder value for $2
  }

  $result = pg_query_params($con, $query, $params);

  $resultArr = pg_fetch_all($result);

  $totalAmountQuery = "SELECT SUM(pd.amount) 
                      FROM donation_paymentdata AS pd
                      WHERE ((pd.tel = $1 AND pd.donationid IS NOT NULL) OR $1 IS NULL) AND
                            (((CASE 
                                WHEN EXTRACT(MONTH FROM pd.timestamp) >= 4 THEN EXTRACT(YEAR FROM pd.timestamp)
                                ELSE EXTRACT(YEAR FROM pd.timestamp) - 1
                            END || '-' ||
                            CASE 
                                WHEN EXTRACT(MONTH FROM pd.timestamp) >= 4 THEN EXTRACT(YEAR FROM pd.timestamp) + 1
                                ELSE EXTRACT(YEAR FROM pd.timestamp)
                            END) = $2 AND $2 IS NOT NULL) OR $2 IS NULL)";

  $totalAmountResult = pg_query_params($con, $totalAmountQuery, $params);
  $totalDonatedAmount = pg_fetch_result($totalAmountResult, 0, 0);

  return array($resultArr, $totalDonatedAmount);
}

$searchField = isset($_POST['searchField']) ? $_POST['searchField'] : '';
$fyear = isset($_POST['get_fyear']) ? $_POST['get_fyear'] : '';

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
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title>Donation</title>

  <!-- Favicons -->
  <link href="../img/favicon.ico" rel="icon">
  <!-- Vendor CSS Files -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

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
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
</head>

<!-- =========================
     NAVIGATION LINKS     
============================== -->

<body>

  <?php include 'header.php'; ?>

  <main id="main" class="main">

    <div class="pagetitle">
      <h1>Donation</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="home.php">Home</a></li>
          <li class="breadcrumb-item"><a href="#">Work</a></li>
          <li class="breadcrumb-item active">Donation</li>
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
                    <input name="searchField" class="form-control" style="width:max-content; display:inline-block" placeholder="Contact number" value="<?php echo $searchField ?>">
                    <select name="get_fyear" id="get_fyear" class="form-select" style="width:max-content;display:inline-block" required>
                      <?php if ($fyear == null) { ?>
                        <option value="" disabled selected hidden>Select Year</option>
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
              <?php echo '
          <div class="table-responsive">
                    <table class="table">
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
                        </thead>' ?>
              <?php if ($resultArr != null) {
                echo '<tbody>';
                foreach ($resultArr as $array) {
                  echo '
                                <tr>' ?>
                  <?php
                  echo '

                                <td>' . date("d/m/Y", strtotime($array['timestamp'])) . '</td>
                                <td>' . $array['fullname'] . '<br>' . $array['tel'] . '<br>' . $array['email'] . '</td>
                                <td>' . $array['financial_year'] . '</td>
                                    <td>' . $array['transactionid'] . '</td>
                                    <td>' . $array['currency'] . '&nbsp;' . $array['amount'] . '</td>
                                    <td>' . $array['documenttype'] . '/' . $array['nationalid'] . '</td>
                                    <td>Online</td>' ?>


                  <?php if ($array['donationid'] != null) { ?>
                    <?php
                    echo '<td><a href="/donation_invoice.php?searchField=' . $array['donationid'] . '" target="_blank">' . $array['donationid'] . '</a></span></td>'
                    ?>
                    <?php    } else { ?><?php
                                        echo '<td>' . $array['donationid'] . '</td>' ?>
                  <?php } ?>
                  <?php echo '<td>' . $array['status'] . '</td>' ?>

                  <?php if ($role == 'Admin') { ?>

                    <?php echo '

<td>
<button type="button" href="javascript:void(0)" onclick="showDetails(\'' . $array['donationid'] . '\')" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Details">
<i class="bi bi-box-arrow-up-right" style="font-size: 14px ;color:#777777" title="Show Details" display:inline;></i></button>&nbsp;&nbsp;' ?>
                    <?php if (($array['tel'] != null) && $array['status'] == 'Approved') { ?>

                      <?php
                      $phone = $array['tel'];
                      $fullname = $array['fullname'];
                      $donationid = $array['donationid'];

                      $message = urlencode("Dear $fullname ($phone),\n\nThank you for your generous donation! Your invoice for Donation ID $donationid has been issued and emailed to your registered email address.\n\nWe greatly appreciate your support for our cause. Please feel free to reach out if you have any questions or need assistance.\n\n--RSSI\n\n**This is an automatically generated SMS");

                      $whatsappLink = "https://api.whatsapp.com/send?phone=91$phone&text=$message";
                      ?>
                      <a href="<?php echo $whatsappLink; ?>" target="_blank"><i class="bi bi-whatsapp" style="color:#444444;" title="Send SMS ' . $array['tel'] . '"></i></a>

                    <?php } else { ?>
                      <?php echo '<i class="bi bi-whatsapp" style="color:#A2A2A2;" title="Send SMS"></i>' ?>
                      <?php } ?>&nbsp;&nbsp;

                      <?php if (($array['email'] != null) && $array['status'] == 'Approved') { ?>
                        <?php echo '<form  action="#" name="email-form-' . $array['donationid'] . '" method="POST" style="display: -webkit-inline-box;" >
                          <input type="hidden" name="template" type="text" value="donation_invoice">
                          <input type="hidden" name="data[donationid]" type="text" value="' . $array['donationid'] . '">
                          <input type="hidden" name="data[fullname]" type="text" value="' . $array['fullname'] . '">
                          <input type="hidden" name="email" type="text" value="' . $array['email'] . '">
                          <button  style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;"
                          type="submit"><i class="bi bi-envelope-at" style="color:#444444;" title="Send Email ' . $array['email'] . '"></i></button>
                          </form>' ?>
                      <?php } else { ?>
                        <?php echo '<i class="bi bi-envelope-at" style="color:#A2A2A2;" title="Send Email"></i>' ?>
                      <?php } ?>

                      <?php echo '</td>' ?>
                    <?php } ?>

                    <?php echo '</tr>';
                  }
                } else if ($fyear == null) {
                  echo '<tr>
                            <td colspan="5">Please select Filter value.</td>
                        </tr>';
                } else {
                  echo '<tr>
                        <td colspan="5">No record found for' ?>&nbsp;<?php echo $searchField ?><?php echo $fyear ?>
                  <?php echo '</td>
                    </tr>';
                }
                echo '</tbody>
                     </table>
                     </div>';
                  ?>

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

  <!-- Vendor JS Files -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

  <!-- Template Main JS File -->
  <script src="../assets_new/js/main.js"></script>

  <!--------------- POP-UP BOX --------------->
  <style>
    .modal {
      background-color: rgba(0, 0, 0, 0.4);
      /* Black w/ opacity */
    }
  </style>
  <div class="modal" id="myModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h1 class="modal-title fs-5" id="exampleModalLabel">Donation Review</h1>
          <button type="button" id="closedetails-header" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div style="width: 100%; text-align: right;">
            <p id="status" class="badge" style="display: inline;"><span class="donationid"></span></p>
          </div>

          <form id="donation_review" action="#" method="POST">
            <input type="hidden" name="form-type" value="donation_review" readonly>
            <input type="hidden" name="reviewer_id" value="<?php echo $associatenumber ?>" readonly>
            <input type="hidden" name="donationid" id="donationid" value="" readonly>

            <div class="mb-3">
              <label for="reviewer_status" class="form-label">Status</label>
              <select name="reviewer_status" id="reviewer_status" class="form-select" required>
                <option value="" disabled selected hidden>Status</option>
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

          <div class="modal-footer">
            <button type="button" id="closedetails-footer" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
          <p style="font-size:small; text-align: right; font-style: italic; color:#A2A2A2;">Updated by: <span class="reviewedby"></span> on <span class="reviewedon"></span>
        </div>
      </div>
    </div>
  </div>
  <script>
    var data = <?php echo json_encode($resultArr) ?>

    // Get the modal
    var modal = document.getElementById("myModal");
    // Get the <span> element that closes the modal
    var closedetails = [
      document.getElementById("closedetails-header"),
      document.getElementById("closedetails-footer")
    ];

    function showDetails(id) {
      // console.log(modal)
      // console.log(modal.getElementsByClassName("data"))
      var mydata = undefined
      data.forEach(item => {
        if (item["donationid"] == id) {
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
      if (mydata["status"] === "Approved") {
        status.classList.add("bg-success")
        status.classList.remove("bg-danger")
      } else {
        status.classList.remove("bg-success")
        status.classList.add("bg-danger")
      }
      //class add end

      var profile = document.getElementById("donationid")
      profile.value = mydata["donationid"]
      if (mydata["status"] !== null) {
        profile = document.getElementById("reviewer_status")
        profile.value = mydata["status"]
      }
      if (mydata["status"] !== null) {
        profile = document.getElementById("reviewer_remarks")
        profile.value = mydata["reviewer_remarks"]
      }

      if (mydata["status"] == 'Approved' || mydata["status"] == 'Rejected') {
        document.getElementById("donationupdate").disabled = true;
      } else {
        document.getElementById("donationupdate").disabled = false;
      }
    }
    // When the user clicks the button, open the modal 
    // When the user clicks on <span> (x), close the modal
    closedetails.forEach(function(element) {
      element.addEventListener("click", closeModal);
    });

    function closeModal() {
      var modal1 = document.getElementById("myModal");
      modal1.style.display = "none";
    }
    // When the user clicks anywhere outside of the modal, close it
    // window.onclick = function(event) {
    //     if (event.target == modal) {
    //         modal.style.display = "none";
    //     }
    // }
  </script>
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

</body>

</html>