<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();

// PostgreSQL query using $con with parameterized query for security
$query = "SELECT sc.*, 
                 s.studentname, 
                 s.class, 
                 u.fullname as created_by_name,
                 TO_CHAR(sc.effective_from, 'Mon YYYY') as formatted_from_date,
                 CASE WHEN sc.effective_until IS NULL THEN 'Indefinite'
                      ELSE TO_CHAR(sc.effective_until, 'Mon YYYY') 
                 END as formatted_until_date
          FROM student_concessions sc
          JOIN rssimyprofile_student s ON sc.student_id = s.student_id
          JOIN rssimyaccount_members u ON sc.created_by = u.associatenumber
          -- WHERE sc.is_active = TRUE -- (uncomment when you implement this field)
          ORDER BY sc.effective_from DESC, s.studentname";

$result = pg_query($con, $query);

if (!$result) {
    // Handle query error
    die("Database query failed: " . pg_last_error($con));
}

$concessions = pg_fetch_all($result);

// Get students for filter dropdown
$studentsQuery = "SELECT student_id, studentname, class FROM rssimyprofile_student ORDER BY studentname";
$studentsResult = pg_query($con, $studentsQuery);
$students = pg_fetch_all($studentsResult);

// Free the result sets
pg_free_result($result);
pg_free_result($studentsResult);
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

    <title>Student Concessions</title> <!-- Changed from "User log" -->

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
            policyLink: 'https://www.rssi.in/disclaimer'
        });
    </script>
    <style>
        @media (min-width:767px) {
            .left {
                margin-left: 2%;
            }
        }
    </style>
    <!-- CSS Library Files -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
    <!-- JavaScript Library Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>

</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Student Concessions</h1> <!-- Changed from "User log" -->
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Work</a></li>
                    <li class="breadcrumb-item active">Student Concessions</li> <!-- Changed from "User log" -->
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
                            <div class="container-fluid">
                                <h2 class="mb-4">Student Concessions Management</h2>

                                <!-- Filter Section -->
                                <div class="card mb-4">
                                    <div class="card-body">
                                        <form id="filterForm">
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <label class="form-label">Student</label>
                                                    <select class="form-select" name="student_id">
                                                        <option value="">All Students</option>
                                                        <?php foreach ($students as $student): ?>
                                                            <option value="<?= htmlspecialchars($student['student_id']) ?>">
                                                                <?= htmlspecialchars($student['studentname']) ?> (<?= htmlspecialchars($student['class']) ?>)
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">Category</label>
                                                    <select class="form-select" name="concession_category">
                                                        <option value="">All Categories</option>
                                                        <option value="admission_month">Admission Month</option>
                                                        <option value="rounding_off">Rounding Off</option>
                                                        <option value="financial_hardship">Financial Hardship</option>
                                                        <option value="sibling">Sibling Concession</option>
                                                        <option value="staff_child">Staff Child</option>
                                                        <option value="special_talent">Special Talent</option>
                                                        <option value="early_bird">Early Bird</option>
                                                        <option value="scholarship">Scholarship</option>
                                                        <option value="referral">Referral</option>
                                                        <option value="special_case">Special Case</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label">Status</label>
                                                    <select class="form-select" name="status">
                                                        <option value="active">Active</option>
                                                        <option value="expired">Expired</option>
                                                        <option value="all">All</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-2 d-flex align-items-end">
                                                    <button type="submit" class="btn btn-primary">Filter</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <!-- Concessions Table -->
                                <div class="card">
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover" id="concessionsTable">
                                                <thead>
                                                    <tr>
                                                        <th>Student</th>
                                                        <th>Class</th>
                                                        <th>Category</th>
                                                        <th>Amount</th>
                                                        <th>Effective From</th>
                                                        <th>Effective Until</th>
                                                        <th>Reason</th>
                                                        <th>Created By</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (!empty($concessions)): ?>
                                                        <?php foreach ($concessions as $concession): ?>
                                                            <tr>
                                                                <td><?= htmlspecialchars($concession['studentname']) ?></td>
                                                                <td><?= htmlspecialchars($concession['class']) ?></td>
                                                                <td><?= ucwords(str_replace('_', ' ', $concession['concession_category'])) ?></td>
                                                                <td><?= number_format($concession['concession_amount'], 2) ?></td>
                                                                <td><?= $concession['formatted_from_date'] ?></td>
                                                                <td><?= $concession['formatted_until_date'] ?></td>
                                                                <td><?= nl2br(htmlspecialchars($concession['reason'])) ?></td>
                                                                <td><?= htmlspecialchars($concession['created_by_name']) ?></td>
                                                                <td>
                                                                    <button class="btn btn-sm btn-primary edit-concession"
                                                                        data-id="<?= $concession['id'] ?>">
                                                                        <i class="bi bi-pencil"></i> Edit
                                                                    </button>
                                                                    <button class="btn btn-sm btn-info history-concession"
                                                                        data-id="<?= $concession['id'] ?>">
                                                                        <i class="bi bi-clock-history"></i> History
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <tr>
                                                            <td colspan="9" class="text-center">No concessions found</td>
                                                        </tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
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

    <!-- Edit Modal -->
    <div class="modal fade" id="editConcessionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <!-- Modal content will be loaded via AJAX -->
            </div>
        </div>
    </div>

    <!-- History Modal -->
    <div class="modal fade" id="historyModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Concession History</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Action</th>
                                <th>Changed By</th>
                                <th>Changes</th>
                            </tr>
                        </thead>
                        <tbody id="historyTableBody">
                            <!-- Will be populated by AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {

            // Initialize DataTable
            $('#concessionsTable').DataTable({
                "order": [], // Disable initial sorting
                "responsive": true
            });

            // Handle Edit button click
            $('body').on('click', '.edit-concession', function() {
                const concessionId = $(this).data('id');
                const modal = $('#editConcessionModal');

                // Show loading placeholder
                modal.find('.modal-content').html(`
                <div class="modal-header">
                    <h5 class="modal-title">Loading Concession...</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            `);
                modal.modal('show');

                // Fetch concession data
                $.get('get_concession.php?id=' + concessionId, function(response) {
                    if (response.success) {
                        // Populate modal with form
                        modal.find('.modal-content').html(`
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Concession</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="editConcessionForm">
                            <div class="modal-body">
                                <input type="hidden" name="id" value="${response.data.id}">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Student</label>
                                        <input type="text" class="form-control" value="${response.data.studentname} (${response.data.class})" readonly>
                                        <input type="hidden" name="student_id" value="${response.data.student_id}">
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-12">
                                        <label for="editConcessionCategory" class="form-label">Concession Category <span class="text-danger">*</span></label>
                                        <select class="form-select" id="editConcessionCategory" name="concession_category" required>
                                            <option value="">-- Select Category --</option>
                                            ${generateOptions(response.data.concession_category)}
                                        </select>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-8">
                                        <label for="editConcessionReason" class="form-label">Reason <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="editConcessionReason" name="reason" rows="3" required>${response.data.reason || ''}</textarea>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label for="editConcessionFromMonth" class="form-label">Effective From (Month) <span class="text-danger">*</span></label>
                                        <input type="month" class="form-control" id="editConcessionFromMonth" value="${response.data.effective_from_month}" required>
                                        <input type="hidden" name="effective_from" id="editEffectiveFrom" value="${response.data.effective_from}">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="editConcessionUntilMonth" class="form-label">Effective Until (Month)</label>
                                        <input type="month" class="form-control" id="editConcessionUntilMonth" value="${response.data.effective_until_month || ''}">
                                        <input type="hidden" name="effective_until" id="editEffectiveUntil" value="${response.data.effective_until || ''}">
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label for="editConcessionAmount" class="form-label">Concession Amount <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="editConcessionAmount" name="concession_amount" value="${response.data.concession_amount}" step="0.01" min="0" required>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                            </div>
                        </form>
                    `);

                        // Bind dynamic date update
                        $('#editConcessionFromMonth, #editConcessionUntilMonth').on('change', function() {
                            const date = new Date($(this).val() + '-01');
                            const formatted = date.toISOString().split('T')[0];
                            if ($(this).attr('id') === 'editConcessionFromMonth') {
                                $('#editEffectiveFrom').val(formatted);
                            } else {
                                $('#editEffectiveUntil').val(formatted);
                            }
                        });
                    } else {
                        showModalError(modal, response.message);
                    }
                }).fail(function() {
                    showModalError(modal, 'Failed to load concession data. Please try again.');
                });
            });

            // Form submission handler
            $(document).on('submit', '#editConcessionForm', function(e) {
                e.preventDefault();
                const form = $(this);
                const submitBtn = form.find('button[type="submit"]');
                const originalText = submitBtn.html();

                submitBtn.prop('disabled', true).html(`
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...
            `);

                $.ajax({
                    url: 'update_concession.php',
                    type: 'POST',
                    data: form.serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $('#editConcessionModal').modal('hide');
                            alert('Concession updated successfully');
                            location.reload();
                        } else {
                            alert(response.message);
                            submitBtn.prop('disabled', false).html(originalText);
                        }
                    },
                    error: function(xhr) {
                        alert('Request failed: ' + (xhr.responseJSON?.message || xhr.statusText || 'Unknown error'));
                        submitBtn.prop('disabled', false).html(originalText);
                    }
                });
            });

            // Helper: show error in modal
            function showModalError(modal, message) {
                modal.find('.modal-content').html(`
                <div class="modal-header">
                    <h5 class="modal-title">Error</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">${message}</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            `);
            }

            // Helper: generate category options
            function generateOptions(selected) {
                const options = [
                    ['admission_month', 'Admission Month Adjustment'],
                    ['rounding_off', 'Rounding Off Adjustment'],
                    ['financial_hardship', 'Financial Hardship / Economic Background'],
                    ['sibling', 'Sibling Concession'],
                    ['staff_child', 'Staff Child Concession'],
                    ['special_talent', 'Special Talent / Merit-Based'],
                    ['early_bird', 'Promotional / Early Bird Offer'],
                    ['scholarship', 'Scholarship-Based'],
                    ['referral', 'Referral / Community Support'],
                    ['special_case', 'Special Cases / Discretionary'],
                ];
                return options.map(opt => `<option value="${opt[0]}" ${selected === opt[0] ? 'selected' : ''}>${opt[1]}</option>`).join('');
            }

            // Handle History button click
            $('body').on('click', '.history-concession', function() {
                const concessionId = $(this).data('id');
                const modal = $('#historyModal');

                // Show loading state
                $('#historyTableBody').html(`
        <tr>
            <td colspan="4" class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </td>
        </tr>
    `);

                modal.modal('show');

                // Fetch concession history
                $.get('get_concession_history.php?id=' + concessionId, function(response) {
                    if (response.success && response.data.length > 0) {
                        let html = '';
                        response.data.forEach(entry => {
                            html += `
                <tr>
                    <td>${entry.formatted_date}</td>
                    <td>${entry.action}</td>
                    <td>${entry.changed_by_name}</td>
                    <td>${formatChanges(entry.old_values, entry.new_values)}</td>
                </tr>
                `;
                        });
                        $('#historyTableBody').html(html);
                    } else {
                        $('#historyTableBody').html(`
                <tr>
                    <td colspan="4" class="text-center">No history found for this concession</td>
                </tr>
            `);
                    }
                }).fail(function() {
                    $('#historyTableBody').html(`
            <tr>
                <td colspan="4" class="text-center text-danger">Failed to load history</td>
            </tr>
        `);
                });
            });

            // Function to format changes for display
            function formatChanges(oldValues, newValues) {
                try {
                    // Parse the JSON if it's a string
                    const oldData = typeof oldValues === 'string' ? JSON.parse(oldValues) : oldValues || {};
                    const newData = typeof newValues === 'string' ? JSON.parse(newValues) : newValues || {};

                    let changes = [];

                    // Check each field for changes
                    const fieldsToCheck = [
                        'concession_category', 'concession_amount',
                        'effective_from', 'effective_until', 'reason'
                    ];

                    fieldsToCheck.forEach(field => {
                        const oldVal = oldData[field];
                        const newVal = newData[field];

                        if (oldVal !== newVal) {
                            // Format field name for display
                            const fieldName = field.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());

                            // Format values for display
                            const displayOldVal = oldVal !== undefined ? oldVal : 'empty';
                            const displayNewVal = newVal !== undefined ? newVal : 'empty';

                            changes.push(`${fieldName}: ${displayOldVal} → ${displayNewVal}`);
                        }
                    });

                    return changes.join('<br>');
                } catch (e) {
                    console.error('Error formatting changes:', e);
                    return 'Changes could not be displayed';
                }
            }

        });
    </script>

</body>

</html>