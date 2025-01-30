<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util_iexplore.php");
include("../../util/email.php");
include("../../util/drive.php");

if (!isLoggedIn("aid")) {
  $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
  header("Location: index.php");
  exit;
}

validation();

$query = "
SELECT 
    e.id AS exam_id,
    e.name AS exam_name,
    e.total_duration AS duration,
    e.total_questions AS total_questions,
    e.created_at AS created_date,
    STRING_AGG(c.name, ', ') AS categories
FROM 
    test_exams e
LEFT JOIN test_exam_categories ec ON ec.exam_id = e.id 
LEFT JOIN test_categories c ON c.id = ec.category_id
WHERE e.is_active=true
GROUP BY 
    e.id, e.name, e.total_duration, e.total_questions, e.created_at
ORDER BY 
    e.created_at DESC;
";

$result = pg_query($con, $query);

if (!$result) {
  echo "Error fetching exams.";
  exit;
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

  <title>iExplore Edge-Home</title>

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
  <!-- CSS Library Files -->
  <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
  <!-- JavaScript Library Files -->
  <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
  <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
  <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>

</head>


<body>

  <?php include 'header.php'; ?>
  <?php include 'inactive_session_expire_check.php'; ?>

  <main id="main" class="main">
    <div class="pagetitle">
      <h1>Home</h1>
    </div>

    <section class="section dashboard">
      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-body">
              <br>
              <div class="container">
                <div class="table-responsive">
                  <table class="table mt-3" id="table-id">
                    <thead>
                      <tr>
                        <th>Exam ID</th>
                        <th>Exam Name</th>
                        <th>Categories</th>
                        <th>Duration (Minutes)</th>
                        <th>Total Questions</th>
                        <th>Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php while ($row = pg_fetch_assoc($result)) : ?>
                        <tr>
                          <td><?php echo $row['exam_id']; ?></td>
                          <td><?php echo $row['exam_name']; ?></td>
                          <td><?php echo $row['categories']; ?></td>
                          <td><?php echo $row['duration']; ?></td>
                          <td><?php echo $row['total_questions']; ?></td>
                          <td>
                            <!-- Button trigger modal -->
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#examModal<?php echo $row['exam_id']; ?>">
                              View & Start
                            </button>

                            <!-- Modal -->
                            <div class="modal fade" id="examModal<?php echo $row['exam_id']; ?>" tabindex="-1" aria-labelledby="examModalLabel<?php echo $row['exam_id']; ?>" aria-hidden="true">
                              <div class="modal-dialog">
                                <div class="modal-content">
                                  <div class="modal-header">
                                    <h5 class="modal-title" id="examModalLabel<?php echo $row['exam_id']; ?>">Exam Details</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                  </div>
                                  <div class="modal-body">
                                    <p><strong>Exam Name:</strong> <?php echo $row['exam_name']; ?></p>
                                    <p><strong>Categories:</strong> <?php echo $row['categories']; ?></p>
                                    <p><strong>Duration:</strong> <?php echo $row['duration']; ?> minutes</p>
                                    <p><strong>Total Questions:</strong> <?php echo $row['total_questions']; ?></p>
                                    <hr>
                                    <h6>Instructions & Prerequisites:</h6>
                                    <ul>
                                      <li>Ensure a stable internet connection.</li>
                                      <li>Do not refresh the page during the exam.</li>
                                      <li>Read each question carefully before answering.</li>
                                    </ul>
                                    <div class="form-check mt-3">
                                      <input class="form-check-input" type="checkbox" id="agreeCheck<?php echo $row['exam_id']; ?>">
                                      <label class="form-check-label" for="agreeCheck<?php echo $row['exam_id']; ?>">
                                        I understand and agree to proceed with the exam.
                                      </label>
                                    </div>
                                  </div>
                                  <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button type="button" class="btn btn-primary proceed-btn" data-exam-id="<?php echo $row['exam_id']; ?>" disabled>Proceed to Exam</button>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </td>
                        </tr>
                      <?php endwhile; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.addEventListener("DOMContentLoaded", function () {
      document.querySelectorAll(".form-check-input").forEach(function (checkbox) {
        checkbox.addEventListener("change", function () {
          const proceedButton = this.closest(".modal-content").querySelector(".proceed-btn");
          proceedButton.disabled = !this.checked;
        });
      });

      document.querySelectorAll(".proceed-btn").forEach(function (button) {
        button.addEventListener("click", function () {
          const examId = this.dataset.examId;
          window.location.href = "testc.php?exam_id=" + examId;
        });
      });
    });
  </script>
  <script>
    $(document).ready(function() {
      // Check if resultArr is empty
      <?php if (!empty($result)) : ?>
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
