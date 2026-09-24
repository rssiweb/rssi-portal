<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

ob_start();

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"]        = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}

validation();

$aid = $_SESSION['aid'] ?? ($associatenumber ?? 'unknown');

// -------- Categories --------
$catRes = pg_query($con, "SELECT id, name, type FROM cashflow_categories
                          WHERE is_active = TRUE ORDER BY type, name");
$categories = pg_fetch_all($catRes) ?: [];

// -------- Balance (initial paint) — computed inline from transactions --------
$balRes = pg_query($con, "
    SELECT
        COALESCE(SUM(CASE WHEN type = 'earning' THEN amount ELSE 0 END), 0)  AS total_earnings,
        COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0)  AS total_expenses,
        COALESCE(SUM(CASE WHEN type = 'earning' THEN amount ELSE -amount END), 0) AS current_balance
    FROM cashflow_transactions
");
$balance = pg_fetch_assoc($balRes) ?: [
    'total_earnings'  => 0,
    'total_expenses'  => 0,
    'current_balance' => 0
];

// -------- Flash messages --------
$flashSuccess = $_SESSION['cashflow_flash_success'] ?? null;
unset($_SESSION['cashflow_flash_success']);

$flashError = $_SESSION['cashflow_flash_error'] ?? null;
unset($_SESSION['cashflow_flash_error']);
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php include 'includes/meta.php' ?>
    <title>Cashflow Portal</title>

    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="../assets_new/css/style.css?v=1.1.0" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        .balance-card {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            border-radius: 1.5rem;
            padding: 1.5rem 2rem;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15);
        }

        .balance-card .balance-amount {
            font-size: 2.75rem;
            font-weight: 700;
        }

        .summary-chip {
            background: rgba(255, 255, 255, 0.15);
            border-radius: 2rem;
            padding: 0.5rem 1.2rem;
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }

        .table thead th {
            font-size: 0.8rem;
            text-transform: uppercase;
            color: #6b7280;
            font-weight: 600;
            white-space: nowrap;
        }

        .badge-earning {
            background: #d1fae5;
            color: #065f46;
            font-weight: 500;
            padding: 0.35rem 0.75rem;
            border-radius: 2rem;
        }

        .badge-expense {
            background: #fee2e2;
            color: #991b1b;
            font-weight: 500;
            padding: 0.35rem 0.75rem;
            border-radius: 2rem;
        }

        .form-label {
            font-size: 0.85rem;
            font-weight: 500;
            color: #374151;
        }

        .note-badge {
            background: #f3f4f6;
            color: #374151;
            font-size: 0.75rem;
            padding: 0.2rem 0.6rem;
            border-radius: 1rem;
        }

        .negative-balance {
            color: #fecaca !important;
        }

        .filter-bar {
            background: #f9fafb;
            border-radius: 1rem;
            padding: 1rem 1.25rem;
            border: 1px solid #eef0f3;
        }

        .filter-bar .form-label {
            margin-bottom: 0.25rem;
        }

        .filter-bar .form-control,
        .filter-bar .form-select {
            font-size: 0.875rem;
        }

        .search-feedback {
            font-size: 0.78rem;
            color: #6b7280;
            margin-top: 0.35rem;
            min-height: 1.1em;
        }

        .search-feedback.error {
            color: #b91c1c;
        }

        .table-hover tbody tr:hover {
            background-color: #f9fafb;
        }

        .cf-pagination .page-link {
            border-radius: 0.5rem;
            margin: 0 0.15rem;
            color: #1e3c72;
            border-color: #e5e7eb;
            font-size: 0.85rem;
        }

        .cf-pagination .page-item.active .page-link {
            background: #1e3c72;
            border-color: #1e3c72;
            color: #fff;
        }

        .cf-pagination .page-item.disabled .page-link {
            color: #cbd5e1;
        }

        /* Multi-row transaction grid */
        .txn-row {
            background: #fff;
            border-radius: 0.75rem;
            padding: 0.75rem 0.5rem;
            transition: background 0.15s;
        }

        .txn-row:hover {
            background: #fafbfc;
        }

        .remove-row-btn {
            width: 34px;
            height: 34px;
            padding: 0;
            line-height: 1;
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'inactive_session_expire_check.php'; ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1><?php echo getPageTitle(); ?></h1>
            <?php echo generateDynamicBreadcrumb(); ?>
        </div>

        <section class="section dashboard">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <br>

                            <!-- Balance summary -->
                            <div class="row g-4 mb-4">
                                <div class="col-12">
                                    <div class="balance-card d-flex flex-column justify-content-between">
                                        <div>
                                            <div class="d-flex justify-content-between align-items-start">
                                                <span class="opacity-75"><i class="bi bi-piggy-bank me-1"></i>Current balance</span>
                                                <span class="summary-chip"><i class="bi bi-arrow-repeat"></i> Live</span>
                                            </div>
                                            <div class="balance-amount mt-3" id="currentBalanceDisplay">
                                                ₹<?php echo number_format($balance['current_balance'], 2); ?>
                                            </div>
                                            <div class="d-flex gap-4 mt-3 opacity-75 small">
                                                <span>
                                                    <i class="bi bi-arrow-down-circle me-1"></i>Earnings:
                                                    <span id="totalEarningsDisplay">₹<?php echo number_format($balance['total_earnings'], 2); ?></span>
                                                </span>
                                                <span>
                                                    <i class="bi bi-arrow-up-circle me-1"></i>Expenses:
                                                    <span id="totalExpensesDisplay">₹<?php echo number_format($balance['total_expenses'], 2); ?></span>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Add transactions (multi-row) -->
                            <div class="card mb-4">
                                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <span><i class="bi bi-pencil-square me-2"></i>Add transactions</span>
                                    <span class="badge bg-light text-dark rounded-pill px-3 py-2">
                                        Balance: <span id="quickBalanceHint">₹<?php echo number_format($balance['current_balance'], 2); ?></span>
                                    </span>
                                </div>
                                <div class="card-body">
                                    <form id="transactionForm" enctype="multipart/form-data">
                                        <!-- Column headers (desktop only) -->
                                        <div class="row g-2 align-items-center mb-2 d-none d-lg-flex px-2">
                                            <div class="col-lg-1"><label class="form-label small mb-0">Type</label></div>
                                            <div class="col-lg-2"><label class="form-label small mb-0">Amount (₹)</label></div>
                                            <div class="col-lg-2"><label class="form-label small mb-0">Category</label></div>
                                            <div class="col-lg-2"><label class="form-label small mb-0">Date</label></div>
                                            <div class="col-lg-3"><label class="form-label small mb-0">Notes / Description</label></div>
                                            <div class="col-lg-2"><label class="form-label small mb-0">Receipt</label></div>
                                        </div>

                                        <div id="txnRows"></div>

                                        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                                            <button type="button" class="btn btn-outline-primary btn-sm rounded-pill" id="addRowBtn">
                                                <i class="bi bi-plus-circle me-1"></i>Add row
                                            </button>
                                            <button type="submit" class="btn btn-primary" id="submitTransactionBtn">
                                                <i class="bi bi-check-lg me-1"></i>Save all
                                            </button>
                                        </div>
                                    </form>
                                    <div id="formMessage" class="mt-3"></div>
                                </div>
                            </div>

                            <!-- Transaction history -->
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <span><i class="bi bi-table me-2"></i>Transaction history</span>
                                    <span class="text-secondary small" id="rowCountInfo"></span>
                                </div>

                                <div class="card-body">
                                    <!-- Filter bar -->
                                    <div class="filter-bar mb-3">
                                        <!-- Quick range chips -->
                                        <div class="d-flex flex-wrap gap-2 mb-2">
                                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill" id="quickLast7Btn">
                                                <i class="bi bi-calendar-week me-1"></i>Last 7 days
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill" id="quickThisMonthBtn">
                                                <i class="bi bi-calendar-month me-1"></i>This month
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill" id="quickLast90Btn">
                                                <i class="bi bi-calendar-range me-1"></i>Last 90 days
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill ms-auto" id="viewDetailsBtn">
                                                <i class="bi bi-bar-chart-line me-1"></i>View details
                                            </button>
                                        </div>

                                        <div class="row g-2 align-items-end">
                                            <div class="col-6 col-md-2">
                                                <label class="form-label small">From date</label>
                                                <input type="date" class="form-control form-control-sm" id="fFrom">
                                            </div>
                                            <div class="col-6 col-md-2">
                                                <label class="form-label small">To date</label>
                                                <input type="date" class="form-control form-control-sm" id="fTo">
                                            </div>
                                            <div class="col-6 col-md-2">
                                                <label class="form-label small">Category</label>
                                                <select class="form-select form-select-sm" id="fCategory">
                                                    <option value="">All</option>
                                                    <?php foreach ($categories as $c): ?>
                                                        <option value="<?php echo htmlspecialchars($c['name']); ?>">
                                                            <?php echo htmlspecialchars($c['name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-6 col-md-2">
                                                <label class="form-label small">Type</label>
                                                <select class="form-select form-select-sm" id="fType">
                                                    <option value="">All</option>
                                                    <option value="earning">Earning</option>
                                                    <option value="expense">Expense</option>
                                                </select>
                                            </div>
                                            <div class="col-12 col-md-4">
                                                <label class="form-label small">Search (notes / category / amount)</label>
                                                <input type="text" class="form-control form-control-sm" id="fSearch"
                                                    placeholder="e.g. electricity bill, donation, 5000">
                                            </div>
                                        </div>

                                        <div class="row g-2 align-items-center mt-2">
                                            <div class="col-12 col-md-7">
                                                <div class="search-feedback" id="dateFeedback"></div>
                                            </div>
                                            <div class="col-12 col-md-5 d-flex justify-content-md-end gap-2">
                                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" id="clearFiltersBtn">
                                                    <i class="bi bi-arrow-counterclockwise me-1"></i>Clear
                                                </button>
                                                <button type="button" class="btn btn-sm btn-primary rounded-pill" id="applyFiltersBtn">
                                                    <i class="bi bi-search me-1"></i>Search
                                                </button>
                                                <button type="button" class="btn btn-sm btn-success rounded-pill" id="exportCsvBtn">
                                                    <i class="bi bi-download me-1"></i>Export
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Table -->
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0" id="cashflowTable">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Type</th>
                                                    <th>Category</th>
                                                    <th>Notes</th>
                                                    <th>Receipt</th>
                                                    <th class="text-end">Amount (₹)</th>
                                                    <th>Created By</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tableBody">
                                                <tr>
                                                    <td colspan="7" class="text-center text-muted py-4">
                                                        <span class="spinner-border spinner-border-sm me-2"></span>Loading…
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Pagination -->
                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">
                                        <div class="small text-secondary" id="pageInfo"></div>
                                        <nav>
                                            <ul class="pagination pagination-sm cf-pagination mb-0" id="pagination"></ul>
                                        </nav>
                                    </div>
                                </div>
                            </div>

                            <p class="text-muted small mt-3">
                                <i class="bi bi-info-circle me-1"></i>Expenses cannot exceed current balance.
                                Receipts are stored in Google Drive.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- ============================= -->
    <!-- Details Modal                 -->
    <!-- ============================= -->
    <div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content" style="border-radius:1rem;">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailsModalLabel">
                        <i class="bi bi-bar-chart-line me-2"></i>Financial summary
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="small text-secondary mb-3" id="detailsRangeInfo"></div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="card bg-light border-0 h-100">
                                <div class="card-body text-center">
                                    <div class="small text-muted mb-1">Total earnings</div>
                                    <div class="h4 fw-bold text-success mb-0" id="detTotalEarnings">₹0.00</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light border-0 h-100">
                                <div class="card-body text-center">
                                    <div class="small text-muted mb-1">Total expenses</div>
                                    <div class="h4 fw-bold text-danger mb-0" id="detTotalExpenses">₹0.00</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light border-0 h-100">
                                <div class="card-body text-center">
                                    <div class="small text-muted mb-1">Net balance</div>
                                    <div class="h4 fw-bold mb-0" id="detNetBalance">₹0.00</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body">
                                    <div class="small fw-semibold mb-2">Earnings vs Expenses</div>
                                    <canvas id="chartTotals" height="180"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body">
                                    <div class="small fw-semibold mb-2">Category-wise split</div>
                                    <canvas id="chartCategories" height="180"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <h6 class="fw-semibold text-success">
                                <i class="bi bi-arrow-down-circle me-1"></i>Earnings by category
                            </h6>
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <thead>
                                        <tr>
                                            <th>Category</th>
                                            <th class="text-end">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody id="detEarningTable"></tbody>
                                </table>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-semibold text-danger">
                                <i class="bi bi-arrow-up-circle me-1"></i>Expenses by category
                            </h6>
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <thead>
                                        <tr>
                                            <th>Category</th>
                                            <th class="text-end">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody id="detExpenseTable"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Template for one transaction row -->
    <template id="txnRowTemplate">
        <div class="row g-2 align-items-end txn-row mb-2 pb-2 border-bottom">
            <div class="col-lg-1 col-6">
                <label class="form-label small d-lg-none">Type</label>
                <select class="form-select form-select-sm" name="rows[__idx__][type]" required>
                    <option value="earning">Earning</option>
                    <option value="expense">Expense</option>
                </select>
            </div>
            <div class="col-lg-2 col-6">
                <label class="form-label small d-lg-none">Amount (₹)</label>
                <input type="number" step="0.01" min="0.01" class="form-control form-control-sm"
                    name="rows[__idx__][amount]" placeholder="0.00" required>
            </div>
            <div class="col-lg-2 col-6">
                <label class="form-label small d-lg-none">Category</label>
                <select class="form-select form-select-sm" name="rows[__idx__][category_name]" required>
                    <option value="">Select</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?php echo htmlspecialchars($c['name']); ?>"
                            data-type="<?php echo $c['type']; ?>">
                            <?php echo htmlspecialchars($c['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-2 col-6">
                <label class="form-label small d-lg-none">Date</label>
                <input type="date" class="form-control form-control-sm"
                    name="rows[__idx__][transaction_date]" required>
            </div>
            <div class="col-lg-3 col-6">
                <label class="form-label small d-lg-none">Notes</label>
                <input type="text" class="form-control form-control-sm"
                    name="rows[__idx__][notes]" placeholder="e.g. Bill no, donor">
            </div>
            <div class="col-lg-2 col-4">
                <label class="form-label small d-lg-none">Receipt</label>
                <input type="file" class="form-control form-control-sm"
                    name="rows[__idx__][receipt]"
                    accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                    onchange="validateReceipt(this)">
            </div>
            <div class="col-lg-auto col-2 text-end">
                <button type="button" class="btn btn-sm btn-outline-danger remove-row-btn"
                    title="Remove row">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        </div>
    </template>

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center">
        <i class="bi bi-arrow-up-short"></i>
    </a>

    <!-- Vendor JS -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets_new/js/main.js"></script>

    <script>
        function formatDate(ymd) {
            if (!ymd) return '';
            const [y, m, d] = String(ymd).split('-').map(Number);
            if (!y || !m || !d) return ymd;
            const date = new Date(y, m - 1, d);
            return date.toLocaleDateString('en-IN', {
                day: '2-digit',
                month: 'short',
                year: 'numeric'
            });
        }

        function formatDateTime(dt) {
            if (!dt) return '';
            // Postgres timestamps come as "YYYY-MM-DD HH:MM:SS"; replace space with T
            const d = new Date(String(dt).replace(' ', 'T'));
            if (isNaN(d)) return dt;
            return d.toLocaleString('en-IN', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        // =========================================================
        //  Helpers
        // =========================================================
        function escapeHtml(s) {
            return String(s ?? '').replace(/[&<>"']/g, c => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            } [c]));
        }

        function fmtMoney(n) {
            const num = parseFloat(n || 0);
            return '₹' + num.toLocaleString('en-IN', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function validateReceipt(input) {
            const file = input.files[0];
            if (!file) return;
            const allowed = ['application/pdf', 'image/jpeg', 'image/png'];
            if (!allowed.includes(file.type)) {
                Swal.fire('Invalid file', 'Only PDF, JPG, or PNG files are allowed.', 'warning');
                input.value = '';
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                Swal.fire('File too large', 'Maximum allowed size is 5 MB.', 'warning');
                input.value = '';
            }
        }

        // =========================================================
        //  Filter categories by transaction type
        // =========================================================
        function applyCategoryFilterToRow(row) {
            const typeSelect = row.querySelector('[name$="[type]"]');
            const catSelect = row.querySelector('[name$="[category_name]"]');
            if (!typeSelect || !catSelect) return;

            const selectedType = typeSelect.value; // 'earning' | 'expense'
            const currentCat = catSelect.value;

            // Walk the options and toggle visibility
            Array.from(catSelect.options).forEach(opt => {
                if (opt.value === '') return; // keep the placeholder
                const optType = opt.dataset.type || 'both';
                const matches = (optType === 'both') || (optType === selectedType);
                opt.hidden = !matches;
                opt.disabled = !matches;
            });

            // If the previously selected category is no longer valid, reset it
            const current = catSelect.querySelector(`option[value="${CSS.escape(currentCat)}"]`);
            if (current && (current.hidden || current.disabled)) {
                catSelect.value = '';
            }
        }

        // Wire the change handler whenever a row's Type dropdown changes
        function bindCategoryFilter(row) {
            const typeSelect = row.querySelector('[name$="[type]"]');
            if (!typeSelect) return;
            typeSelect.addEventListener('change', () => applyCategoryFilterToRow(row));
            // Apply once so the row starts with the correct filtered list
            applyCategoryFilterToRow(row);
        }

        // =========================================================
        //  State + date helpers
        // =========================================================
        const state = {
            from: '',
            to: '',
            category: '',
            type: '',
            q: '',
            page: 1,
            pageSize: 10
        };

        const MAX_RANGE_DAYS = 90;

        function ymdToDate(ymd) {
            if (!ymd) return null;
            const [y, m, d] = ymd.split('-').map(Number);
            return new Date(y, m - 1, d);
        }

        function dateToYmd(d) {
            const y = d.getFullYear();
            const m = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            return `${y}-${m}-${day}`;
        }

        function addDays(d, n) {
            const nd = new Date(d);
            nd.setDate(nd.getDate() + n);
            return nd;
        }

        // =========================================================
        //  Multi-row transaction entry
        // =========================================================
        let rowCounter = 0;

        function addTransactionRow(values = {}) {
            const tpl = document.getElementById('txnRowTemplate');
            const html = tpl.innerHTML.replace(/__idx__/g, rowCounter++);

            const wrapper = document.createElement('div');
            wrapper.innerHTML = html;
            const row = wrapper.firstElementChild;

            if (values.type) row.querySelector('[name$="[type]"]').value = values.type;
            if (values.amount) row.querySelector('[name$="[amount]"]').value = values.amount;
            if (values.category_name) row.querySelector('[name$="[category_name]"]').value = values.category_name;
            if (values.transaction_date) row.querySelector('[name$="[transaction_date]"]').value = values.transaction_date;
            if (values.notes) row.querySelector('[name$="[notes]"]').value = values.notes;

            // NEW — filter the category dropdown for this row
            bindCategoryFilter(row);

            document.getElementById('txnRows').appendChild(row);
            updateRemoveButtons();
            return row;
        }

        function updateRemoveButtons() {
            const rows = document.querySelectorAll('.txn-row');
            rows.forEach(r => {
                const btn = r.querySelector('.remove-row-btn');
                btn.disabled = (rows.length === 1);
                btn.style.opacity = (rows.length === 1) ? '0.4' : '1';
            });
        }

        document.getElementById('addRowBtn').addEventListener('click', () => {
            const row = addTransactionRow({
                transaction_date: new Date().toISOString().slice(0, 10)
            });
            row.querySelector('[name$="[amount]"]').focus();
        });

        document.getElementById('txnRows').addEventListener('click', e => {
            const btn = e.target.closest('.remove-row-btn');
            if (!btn || btn.disabled) return;
            btn.closest('.txn-row').remove();
            updateRemoveButtons();
        });

        // =========================================================
        //  90-day window enforcement
        // =========================================================
        function enforceDateWindow(changedSide) {
            const fromEl = document.getElementById('fFrom');
            const toEl = document.getElementById('fTo');
            const fb = document.getElementById('dateFeedback');
            fb.classList.remove('error');
            fb.textContent = '';

            let fromYmd = fromEl.value;
            let toYmd = toEl.value;

            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const todayYmd = dateToYmd(today);

            if (!fromYmd && !toYmd) {
                ['min', 'max'].forEach(a => {
                    fromEl.removeAttribute(a);
                    toEl.removeAttribute(a);
                });
                return;
            }

            // ----- FROM just changed -----
            if (changedSide === 'from' && fromYmd) {
                const fromD = ymdToDate(fromYmd);

                if (fromD > today) {
                    fromEl.value = todayYmd;
                    fromYmd = todayYmd;
                }

                const fromD2 = ymdToDate(fromYmd);
                const maxTo = addDays(fromD2, MAX_RANGE_DAYS - 1);
                const minFrom = addDays(fromD2, -(MAX_RANGE_DAYS - 1));

                fromEl.min = dateToYmd(minFrom);
                fromEl.max = todayYmd;

                toEl.min = fromYmd;
                toEl.max = dateToYmd(maxTo > today ? today : maxTo);

                if (toYmd) {
                    const toD = ymdToDate(toYmd);
                    if (toD < fromD2) {
                        toEl.value = fromYmd;
                        toYmd = fromYmd;
                    } else if (toD > maxTo) {
                        const clamped = maxTo > today ? today : maxTo;
                        toEl.value = dateToYmd(clamped);
                        toYmd = toEl.value;
                        fb.textContent = `"To" adjusted to stay within ${MAX_RANGE_DAYS} days.`;
                    }
                }
            }

            // ----- TO just changed -----
            if (changedSide === 'to' && toYmd) {
                const toD = ymdToDate(toYmd);

                if (toD > today) {
                    toEl.value = todayYmd;
                    toYmd = todayYmd;
                }

                const toD2 = ymdToDate(toYmd);
                const minFrom = addDays(toD2, -(MAX_RANGE_DAYS - 1));

                toEl.min = dateToYmd(minFrom);
                toEl.max = todayYmd;

                fromEl.min = dateToYmd(minFrom);
                fromEl.max = toYmd;

                if (fromYmd) {
                    const fromD = ymdToDate(fromYmd);
                    if (fromD > toD2) {
                        fromEl.value = toYmd;
                        fromYmd = toYmd;
                    } else if (fromD < minFrom) {
                        fromEl.value = dateToYmd(minFrom);
                        fromYmd = fromEl.value;
                        fb.textContent = `"From" adjusted to stay within ${MAX_RANGE_DAYS} days.`;
                    }
                }
            }

            if (fromEl.value && toEl.value) {
                const days = Math.round(
                    (ymdToDate(toEl.value) - ymdToDate(fromEl.value)) / (1000 * 60 * 60 * 24)
                ) + 1;
                fb.textContent = `Range: ${days} day${days > 1 ? 's' : ''}.`;
            }
        }

        function validateDateRange() {
            const fromVal = document.getElementById('fFrom').value;
            const toVal = document.getElementById('fTo').value;
            const fb = document.getElementById('dateFeedback');
            fb.classList.remove('error');

            if (!fromVal && !toVal) return true;

            if (fromVal && toVal) {
                const from = ymdToDate(fromVal);
                const to = ymdToDate(toVal);
                const days = Math.round((to - from) / (1000 * 60 * 60 * 24)) + 1;

                if (days < 1) {
                    fb.classList.add('error');
                    fb.textContent = '"From" date must be before "To" date.';
                    return false;
                }
                if (days > MAX_RANGE_DAYS) {
                    fb.classList.add('error');
                    fb.textContent = `Date range cannot exceed ${MAX_RANGE_DAYS} days.`;
                    return false;
                }
                fb.textContent = `Range: ${days} day${days > 1 ? 's' : ''}.`;
            } else {
                fb.textContent = fromVal ?
                    'Showing from selected date.' :
                    'Showing up to selected date.';
            }
            return true;
        }

        // =========================================================
        //  Load transactions
        // =========================================================
        function loadTransactions() {
            if (!validateDateRange()) return;

            state.from = document.getElementById('fFrom').value;
            state.to = document.getElementById('fTo').value;
            state.category = document.getElementById('fCategory').value;
            state.type = document.getElementById('fType').value;
            state.q = document.getElementById('fSearch').value.trim();

            const params = new URLSearchParams({
                from: state.from,
                to: state.to,
                category: state.category,
                type: state.type,
                q: state.q,
                page: state.page,
                page_size: state.pageSize,
                _t: Date.now()
            });

            const tbody = document.getElementById('tableBody');
            tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted py-4">
            <span class="spinner-border spinner-border-sm me-2"></span>Loading…
        </td></tr>`;

            fetch('get_transactions.php?' + params.toString(), {
                    cache: 'no-store'
                })
                .then(r => r.json())
                .then(res => {
                    if (!res.success) throw new Error(res.error || 'Failed to load');
                    renderTable(res.data);
                    renderPagination(res.pagination);
                })
                .catch(err => {
                    tbody.innerHTML = `<tr><td colspan="7" class="text-center text-danger py-4">
                    ${escapeHtml(err.message)}
                </td></tr>`;
                });
        }

        function renderTable(rows) {
            const tbody = document.getElementById('tableBody');
            tbody.innerHTML = '';

            if (!rows.length) {
                tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted py-4">
                <i class="bi bi-inbox fs-4 d-block mb-1"></i>No transactions found for the selected filters.
            </td></tr>`;
                document.getElementById('rowCountInfo').textContent = '';
                return;
            }

            rows.forEach(t => {
                const typeBadge = t.type === 'earning' ?
                    '<span class="badge-earning"><i class="bi bi-arrow-down-circle me-1"></i>Earning</span>' :
                    '<span class="badge-expense"><i class="bi bi-arrow-up-circle me-1"></i>Expense</span>';

                const receipt = t.receipt_drive_url ?
                    `<a href="${t.receipt_drive_url}" target="_blank" class="small text-decoration-none">
                       <i class="bi bi-paperclip"></i> View</a>` :
                    '<span class="text-muted small">—</span>';

                const amtClass = t.type === 'earning' ? 'text-success' : 'text-danger';
                const sign = t.type === 'earning' ? '+' : '−';

                const row = document.createElement('tr');
                row.innerHTML = `
    <td class="text-nowrap">${escapeHtml(formatDate(t.transaction_date))}</td>
    <td>${typeBadge}</td>
    <td>${escapeHtml(t.category_name)}</td>
    <td><span class="note-badge">${escapeHtml(t.notes || '—')}</span></td>
    <td>${receipt}</td>
    <td class="text-end fw-semibold ${amtClass} text-nowrap">
        ${sign} ${fmtMoney(t.amount)}
    </td>
    <td class="text-nowrap">
        <div>${escapeHtml(t.created_by_name || t.created_by || '—')}</div>
        <small class="text-muted">${escapeHtml(formatDateTime(t.created_at))}</small>
    </td>`;
                tbody.appendChild(row);
            });
        }

        function renderPagination(pg) {
            const info = document.getElementById('pageInfo');
            const ul = document.getElementById('pagination');
            ul.innerHTML = '';

            if (!pg) {
                info.textContent = '';
                return;
            }

            const {
                page,
                page_size,
                total,
                total_pages
            } = pg;
            const start = total === 0 ? 0 : (page - 1) * page_size + 1;
            const end = Math.min(page * page_size, total);

            info.textContent = total === 0 ?
                'No records' :
                `Showing ${start}–${end} of ${total}`;

            document.getElementById('rowCountInfo').textContent =
                total + ' transaction' + (total !== 1 ? 's' : '');

            if (total_pages <= 1) return;

            const mkLi = (label, targetPage, disabled = false, active = false) => {
                const li = document.createElement('li');
                li.className = 'page-item' + (disabled ? ' disabled' : '') + (active ? ' active' : '');
                const a = document.createElement('a');
                a.className = 'page-link';
                a.href = '#';
                a.innerHTML = label;
                a.addEventListener('click', e => {
                    e.preventDefault();
                    if (disabled || active) return;
                    state.page = targetPage;
                    loadTransactions();
                });
                li.appendChild(a);
                return li;
            };

            ul.appendChild(mkLi('<i class="bi bi-chevron-double-left"></i>', 1, page === 1));
            ul.appendChild(mkLi('<i class="bi bi-chevron-left"></i>', page - 1, page === 1));

            let startPage = Math.max(1, page - 2);
            let endPage = Math.min(total_pages, startPage + 4);
            startPage = Math.max(1, endPage - 4);

            for (let p = startPage; p <= endPage; p++) {
                ul.appendChild(mkLi(p, p, false, p === page));
            }

            ul.appendChild(mkLi('<i class="bi bi-chevron-right"></i>', page + 1, page === total_pages));
            ul.appendChild(mkLi('<i class="bi bi-chevron-double-right"></i>', total_pages, page === total_pages));
        }

        // =========================================================
        //  Balance
        // =========================================================
        function refreshBalance() {
            fetch('get_balance.php?_t=' + Date.now(), {
                    cache: 'no-store'
                })
                .then(r => r.json())
                .then(res => {
                    if (!res.success) return;
                    const b = res.balance;
                    document.getElementById('currentBalanceDisplay').textContent = fmtMoney(b.current_balance);
                    document.getElementById('totalEarningsDisplay').textContent = fmtMoney(b.total_earnings);
                    document.getElementById('totalExpensesDisplay').textContent = fmtMoney(b.total_expenses);
                    document.getElementById('quickBalanceHint').textContent = fmtMoney(b.current_balance);

                    document.getElementById('currentBalanceDisplay')
                        .classList.toggle('negative-balance', parseFloat(b.current_balance) < 0);
                });
        }

        // =========================================================
        //  Form submit — batch of rows
        // =========================================================
        document.getElementById('transactionForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const form = e.target;
            const btn = document.getElementById('submitTransactionBtn');
            const msg = document.getElementById('formMessage');
            msg.innerHTML = '';

            const rows = form.querySelectorAll('.txn-row');
            if (!rows.length) {
                Swal.fire('No rows', 'Add at least one transaction.', 'warning');
                return;
            }

            // Check every row has the required basics
            let invalid = null;
            rows.forEach((r, i) => {
                const amount = r.querySelector('[name$="[amount]"]').value;
                const category = r.querySelector('[name$="[category_name]"]').value;
                const date = r.querySelector('[name$="[transaction_date]"]').value;
                if ((!amount || parseFloat(amount) <= 0) || !category || !date) {
                    invalid = i + 1;
                }
            });
            if (invalid !== null) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Incomplete row',
                    text: 'Please fill amount, category, and date in row ' + invalid + '.'
                });
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving...';

            const fd = new FormData(form);

            fetch('add_transaction.php', {
                    method: 'POST',
                    body: fd,
                    cache: 'no-store'
                })
                .then(r => r.json())
                .then(res => {
                    if (!res.success) throw new Error(res.error || 'Failed');

                    Swal.fire({
                        icon: 'success',
                        title: 'Saved',
                        html: `<strong>${res.inserted}</strong> transaction(s) recorded successfully.`,
                        timer: 1400,
                        timerProgressBar: true,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.reload();
                    });
                })
                .catch(err => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Save all';
                    Swal.fire({
                        icon: 'error',
                        title: 'Could not save',
                        text: err.message || 'Something went wrong.',
                        confirmButtonColor: '#1e3c72'
                    });
                });
        });

        // =========================================================
        //  Filter buttons
        // =========================================================
        document.getElementById('applyFiltersBtn').addEventListener('click', () => {
            state.page = 1;
            loadTransactions();
        });

        document.getElementById('clearFiltersBtn').addEventListener('click', () => {
            const fromEl = document.getElementById('fFrom');
            const toEl = document.getElementById('fTo');

            fromEl.value = '';
            toEl.value = '';
            ['min', 'max'].forEach(a => {
                fromEl.removeAttribute(a);
                toEl.removeAttribute(a);
            });

            document.getElementById('fCategory').value = '';
            document.getElementById('fType').value = '';
            document.getElementById('fSearch').value = '';
            document.getElementById('dateFeedback').textContent = '';
            document.getElementById('dateFeedback').classList.remove('error');
            state.page = 1;
            loadTransactions();
        });

        document.getElementById('fFrom').addEventListener('change', () => {
            enforceDateWindow('from');
            validateDateRange();
        });
        document.getElementById('fTo').addEventListener('change', () => {
            enforceDateWindow('to');
            validateDateRange();
        });

        document.getElementById('fSearch').addEventListener('keydown', e => {
            if (e.key === 'Enter') {
                e.preventDefault();
                state.page = 1;
                loadTransactions();
            }
        });

        // =========================================================
        //  Quick range chips
        // =========================================================
        function applyQuickRange(kind) {
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            let fromD, toD = today;

            if (kind === 'last7') {
                fromD = addDays(today, -6);
            } else if (kind === 'thisMonth') {
                fromD = new Date(today.getFullYear(), today.getMonth(), 1);
            } else if (kind === 'last90') {
                fromD = addDays(today, -89);
            }

            const fromEl = document.getElementById('fFrom');
            const toEl = document.getElementById('fTo');

            fromEl.value = dateToYmd(fromD);
            toEl.value = dateToYmd(toD);

            enforceDateWindow('from');
            validateDateRange();

            state.page = 1;
            loadTransactions();
        }

        document.getElementById('quickLast7Btn').addEventListener('click', () => applyQuickRange('last7'));
        document.getElementById('quickThisMonthBtn').addEventListener('click', () => applyQuickRange('thisMonth'));
        document.getElementById('quickLast90Btn').addEventListener('click', () => applyQuickRange('last90'));

        // =========================================================
        //  Export
        // =========================================================
        document.getElementById('exportCsvBtn').addEventListener('click', () => {
            const fromVal = document.getElementById('fFrom').value;
            const toVal = document.getElementById('fTo').value;

            if (!fromVal || !toVal) {
                Swal.fire({
                    icon: 'info',
                    title: 'Pick a date range first',
                    text: 'Please select both "From" and "To" dates before exporting.',
                    confirmButtonColor: '#1e3c72'
                });
                return;
            }
            if (!validateDateRange()) {
                Swal.fire('Invalid date range', 'Please fix the date range before exporting.', 'warning');
                return;
            }

            const params = new URLSearchParams({
                from: fromVal,
                to: toVal,
                category: document.getElementById('fCategory').value,
                type: document.getElementById('fType').value,
                q: document.getElementById('fSearch').value.trim()
            });
            window.location.href = 'export_csv.php?' + params.toString();
        });

        // =========================================================
        //  Details modal
        // =========================================================
        let chartTotals = null;
        let chartCategories = null;
        let detailsModalInstance = null;

        document.getElementById('viewDetailsBtn').addEventListener('click', () => {
            const fromVal = document.getElementById('fFrom').value;
            const toVal = document.getElementById('fTo').value;

            if (!fromVal || !toVal) {
                Swal.fire({
                    icon: 'info',
                    title: 'No date range selected',
                    html: 'The details view needs a date range.<br>Showing the <strong>last 90 days</strong> for you now.',
                    confirmButtonColor: '#1e3c72'
                }).then(() => {
                    applyQuickRange('last90');
                    setTimeout(openDetailsModal, 350);
                });
                return;
            }
            openDetailsModal();
        });

        function openDetailsModal() {
            const fromVal = document.getElementById('fFrom').value;
            const toVal = document.getElementById('fTo').value;

            document.getElementById('detailsRangeInfo').innerHTML =
                '<span class="spinner-border spinner-border-sm me-2"></span>Loading summary…';
            document.getElementById('detTotalEarnings').textContent = '₹0.00';
            document.getElementById('detTotalExpenses').textContent = '₹0.00';
            document.getElementById('detNetBalance').textContent = '₹0.00';
            document.getElementById('detEarningTable').innerHTML =
                '<tr><td colspan="2" class="text-center text-muted small">Loading…</td></tr>';
            document.getElementById('detExpenseTable').innerHTML =
                '<tr><td colspan="2" class="text-center text-muted small">Loading…</td></tr>';

            if (chartTotals) {
                chartTotals.destroy();
                chartTotals = null;
            }
            if (chartCategories) {
                chartCategories.destroy();
                chartCategories = null;
            }

            if (!detailsModalInstance) {
                detailsModalInstance = new bootstrap.Modal(document.getElementById('detailsModal'));
            }
            detailsModalInstance.show();

            const params = new URLSearchParams({
                from: fromVal,
                to: toVal,
                category: document.getElementById('fCategory').value,
                type: document.getElementById('fType').value,
                q: document.getElementById('fSearch').value.trim(),
                _t: Date.now()
            });

            fetch('get_summary.php?' + params.toString(), {
                    cache: 'no-store'
                })
                .then(r => r.json())
                .then(res => {
                    if (!res.success) throw new Error(res.error || 'Failed to load summary');
                    renderDetails(res);
                })
                .catch(err => {
                    document.getElementById('detailsRangeInfo').innerHTML =
                        `<span class="text-danger">${escapeHtml(err.message)}</span>`;
                });
        }

        function renderDetails(res) {
            const t = res.totals;
            const cats = res.categories;

            document.getElementById('detailsRangeInfo').innerHTML =
                `<i class="bi bi-calendar-range me-1"></i>${escapeHtml(res.meta.from)} → ${escapeHtml(res.meta.to)}` +
                ` &nbsp;<span class="badge bg-light text-dark">${res.meta.days} day${res.meta.days > 1 ? 's' : ''}</span>`;

            document.getElementById('detTotalEarnings').textContent = fmtMoney(t.earnings);
            document.getElementById('detTotalExpenses').textContent = fmtMoney(t.expenses);
            const netEl = document.getElementById('detNetBalance');
            netEl.textContent = fmtMoney(t.net);
            netEl.classList.remove('text-success', 'text-danger');
            netEl.classList.add(t.net >= 0 ? 'text-success' : 'text-danger');

            const earnBody = document.getElementById('detEarningTable');
            if (cats.earnings.length) {
                earnBody.innerHTML = cats.earnings.map(r =>
                        `<tr><td>${escapeHtml(r.category)}</td><td class="text-end">${fmtMoney(r.total)}</td></tr>`
                    ).join('') +
                    `<tr class="fw-semibold border-top">
                <td>Total</td><td class="text-end">${fmtMoney(t.earnings)}</td>
            </tr>`;
            } else {
                earnBody.innerHTML =
                    '<tr><td colspan="2" class="text-muted small">No earnings in this range.</td></tr>';
            }

            const expBody = document.getElementById('detExpenseTable');
            if (cats.expenses.length) {
                expBody.innerHTML = cats.expenses.map(r =>
                        `<tr><td>${escapeHtml(r.category)}</td><td class="text-end">${fmtMoney(r.total)}</td></tr>`
                    ).join('') +
                    `<tr class="fw-semibold border-top">
                <td>Total</td><td class="text-end">${fmtMoney(t.expenses)}</td>
            </tr>`;
            } else {
                expBody.innerHTML =
                    '<tr><td colspan="2" class="text-muted small">No expenses in this range.</td></tr>';
            }

            // Chart 1 — totals bar
            const ctxTotals = document.getElementById('chartTotals').getContext('2d');
            chartTotals = new Chart(ctxTotals, {
                type: 'bar',
                data: {
                    labels: ['Earnings', 'Expenses'],
                    datasets: [{
                        data: [t.earnings, t.expenses],
                        backgroundColor: ['#10b981', '#ef4444'],
                        borderRadius: 8,
                        barThickness: 60
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: v => '₹' + Number(v).toLocaleString('en-IN')
                            }
                        }
                    }
                }
            });

            // Chart 2 — category doughnut
            const labels = [
                ...cats.earnings.map(r => 'Earning · ' + r.category),
                ...cats.expenses.map(r => 'Expense · ' + r.category)
            ];
            const values = [
                ...cats.earnings.map(r => parseFloat(r.total)),
                ...cats.expenses.map(r => parseFloat(r.total))
            ];
            const colors = [
                ...cats.earnings.map((_, i) => `hsl(${150 + i * 20}, 65%, ${55 - i * 3}%)`),
                ...cats.expenses.map((_, i) => `hsl(${0 + i * 20}, 70%, ${60 - i * 3}%)`)
            ];

            const ctxCat = document.getElementById('chartCategories').getContext('2d');
            chartCategories = new Chart(ctxCat, {
                type: 'doughnut',
                data: {
                    labels: labels.length ? labels : ['No data'],
                    datasets: [{
                        data: values.length ? values : [1],
                        backgroundColor: colors.length ? colors : ['#e5e7eb'],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    cutout: '60%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 10,
                                font: {
                                    size: 11
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: ctx => {
                                    if (!values.length) return 'No data';
                                    return ctx.label + ': ₹' + Number(ctx.raw).toLocaleString('en-IN');
                                }
                            }
                        }
                    }
                }
            });
        }

        // =========================================================
        //  Init
        // =========================================================
        document.addEventListener('DOMContentLoaded', () => {
            // Seed the first transaction row with today's date
            addTransactionRow({
                transaction_date: new Date().toISOString().slice(0, 10)
            });

            const todayYmd = dateToYmd(new Date());
            document.getElementById('fFrom').max = todayYmd;
            document.getElementById('fTo').max = todayYmd;

            loadTransactions();
            refreshBalance();

            <?php if ($flashSuccess): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Transaction recorded',
                    text: <?php echo json_encode($flashSuccess); ?>,
                    timer: 1600,
                    timerProgressBar: true,
                    showConfirmButton: false
                });
            <?php endif; ?>

            <?php if ($flashError): ?>
                Swal.fire({
                    icon: 'error',
                    title: 'Could not record',
                    text: <?php echo json_encode($flashError); ?>,
                    confirmButtonColor: '#1e3c72'
                });
            <?php endif; ?>
        });
    </script>
</body>

</html>