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

// -------- Balance (initial paint) --------
$balRes  = pg_query($con, "SELECT * FROM cashflow_balance");
$balance = pg_fetch_assoc($balRes) ?: [
    'total_earnings'  => 0,
    'total_expenses'  => 0,
    'current_balance' => 0
];

// -------- Flash message from a successful POST (set before redirect) --------
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
        * {
            font-family: 'Inter', sans-serif;
        }

        body {
            background: #f5f7fb;
        }

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

        /* Filter bar styling */
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

        /* Custom small pagination */
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

                            <!-- Add transaction -->
                            <div class="card mb-4">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <span><i class="bi bi-pencil-square me-2"></i>Add transaction</span>
                                    <span class="badge bg-light text-dark rounded-pill px-3 py-2">
                                        Balance: <span id="quickBalanceHint">₹<?php echo number_format($balance['current_balance'], 2); ?></span>
                                    </span>
                                </div>
                                <div class="card-body">
                                    <form id="transactionForm" enctype="multipart/form-data" class="row g-3 align-items-end">
                                        <div class="col-md-2">
                                            <label class="form-label">Type *</label>
                                            <select class="form-select" id="transType" name="type" required>
                                                <option value="earning">Earning</option>
                                                <option value="expense">Expense</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Amount (₹) *</label>
                                            <input type="number" step="0.01" min="0.01" class="form-control"
                                                id="transAmount" name="amount" placeholder="0.00" required>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Category *</label>
                                            <select class="form-select" id="transCategory" name="category_name" required>
                                                <option value="">Select</option>
                                                <?php foreach ($categories as $c): ?>
                                                    <option value="<?php echo htmlspecialchars($c['name']); ?>"
                                                        data-type="<?php echo $c['type']; ?>">
                                                        <?php echo htmlspecialchars($c['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Date *</label>
                                            <input type="date" class="form-control" id="transDate"
                                                name="transaction_date" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Notes / Description</label>
                                            <input type="text" class="form-control" id="transNotes"
                                                name="notes" placeholder="e.g. Bill no, donor name">
                                        </div>
                                        <div class="col-md-8">
                                            <label class="form-label">Upload bill / receipt (PDF/JPG/PNG, max 5MB)</label>
                                            <input type="file" class="form-control" id="transReceipt"
                                                name="receipt"
                                                accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                                                onchange="validateReceipt(this)">
                                        </div>
                                        <div class="col-md-4 d-flex align-items-end">
                                            <button type="submit" class="btn btn-primary w-100" id="submitTransactionBtn">
                                                <i class="bi bi-check-lg me-1"></i>Record transaction
                                            </button>
                                        </div>
                                    </form>
                                    <div id="formMessage" class="mt-3"></div>
                                </div>
                            </div>

                            <!-- Transaction history (with merged filter + export) -->
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <span><i class="bi bi-table me-2"></i>Transaction history</span>
                                    <span class="text-secondary small" id="rowCountInfo"></span>
                                </div>

                                <div class="card-body">
                                    <!-- Filter bar -->
                                    <div class="filter-bar mb-3">
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
                                                </tr>
                                            </thead>
                                            <tbody id="tableBody">
                                                <tr>
                                                    <td colspan="6" class="text-center text-muted py-4">
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

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center">
        <i class="bi bi-arrow-up-short"></i>
    </a>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets_new/js/main.js"></script>

    <script>
        // ---------- helpers ----------
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

        // ---------- filters state ----------
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

        // Date helpers (local-time safe, no UTC off-by-one)
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

        // Enforce the 90-day window between #fFrom and #fTo and clamp values
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

            // Neither side set → clear bounds
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

                // Can't be after today
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

                // Pull To into range if out of window
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

                // Can't be after today
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

                // Pull From into range if out of window
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

            // Final range indicator
            if (fromEl.value && toEl.value) {
                const days = Math.round(
                    (ymdToDate(toEl.value) - ymdToDate(fromEl.value)) / (1000 * 60 * 60 * 24)
                ) + 1;
                fb.textContent = `Range: ${days} day${days > 1 ? 's' : ''}.`;
            }
        }

        // ---------- date range validation (pre-fetch / pre-export sanity check) ----------
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

        // ---------- load ----------
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
            tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4">
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
                    tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-4">
                        ${escapeHtml(err.message)}
                    </td></tr>`;
                });
        }

        function renderTable(rows) {
            const tbody = document.getElementById('tableBody');
            tbody.innerHTML = '';

            if (!rows.length) {
                tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4">
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
                    <td class="text-nowrap">${escapeHtml(t.transaction_date)}</td>
                    <td>${typeBadge}</td>
                    <td>${escapeHtml(t.category_name)}</td>
                    <td><span class="note-badge">${escapeHtml(t.notes || '—')}</span></td>
                    <td>${receipt}</td>
                    <td class="text-end fw-semibold ${amtClass} text-nowrap">
                        ${sign} ${fmtMoney(t.amount)}
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

            // Show up to 5 page numbers around current
            let startPage = Math.max(1, page - 2);
            let endPage = Math.min(total_pages, startPage + 4);
            startPage = Math.max(1, endPage - 4);

            for (let p = startPage; p <= endPage; p++) {
                ul.appendChild(mkLi(p, p, false, p === page));
            }

            ul.appendChild(mkLi('<i class="bi bi-chevron-right"></i>', page + 1, page === total_pages));
            ul.appendChild(mkLi('<i class="bi bi-chevron-double-right"></i>', total_pages, page === total_pages));
        }

        // ---------- balance ----------
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

        // ---------- form submit (with reload + sweetalert) ----------
        document.getElementById('transactionForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const form = e.target;
            const fd = new FormData(form);
            const btn = document.getElementById('submitTransactionBtn');

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Recording...';

            fetch('add_transaction.php', {
                    method: 'POST',
                    body: fd,
                    cache: 'no-store'
                })
                .then(r => r.json())
                .then(res => {
                    if (!res.success) throw new Error(res.error || 'Failed');

                    // SweetAlert then full page reload
                    Swal.fire({
                        icon: 'success',
                        title: 'Transaction recorded',
                        text: 'The entry has been saved successfully.',
                        timer: 1400,
                        timerProgressBar: true,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.reload();
                    });
                })
                .catch(err => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Record transaction';
                    Swal.fire({
                        icon: 'error',
                        title: 'Could not record',
                        text: err.message || 'Something went wrong.',
                        confirmButtonColor: '#1e3c72'
                    });
                });
        });

        // ---------- filter buttons ----------
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

        // Enforce 90-day window whenever either side changes
        document.getElementById('fFrom').addEventListener('change', () => {
            enforceDateWindow('from');
            validateDateRange();
        });
        document.getElementById('fTo').addEventListener('change', () => {
            enforceDateWindow('to');
            validateDateRange();
        });

        // Enter key in search box triggers search
        document.getElementById('fSearch').addEventListener('keydown', e => {
            if (e.key === 'Enter') {
                e.preventDefault();
                state.page = 1;
                loadTransactions();
            }
        });

        // ---------- export ----------
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

        // ---------- init ----------
        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('transDate').value = new Date().toISOString().slice(0, 10);

            // Prevent future dates in the filter inputs on first load
            const todayYmd = dateToYmd(new Date());
            document.getElementById('fFrom').max = todayYmd;
            document.getElementById('fTo').max = todayYmd;

            loadTransactions();
            refreshBalance();

            // Show flash from a previous request (if any)
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