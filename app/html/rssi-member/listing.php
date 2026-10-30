<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"]        = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}
validation();

// $catRes = pg_query($con, "SELECT id, name FROM product_categories WHERE is_active = TRUE ORDER BY name");
// $categories = pg_fetch_all($catRes) ?: [];

// $metalRes = pg_query($con, "SELECT id, name FROM product_metals WHERE is_active = TRUE ORDER BY name");
// $metals = pg_fetch_all($metalRes) ?: [];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php include 'includes/meta.php' ?>
    <title>List Products</title>
    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="../assets_new/css/style.css?v=1.1.0" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        .barcode-field { background: #fffbeb; border-color: #fcd34d; font-weight: 600; }
        .barcode-field:focus { background:#fef3c7; border-color:#f59e0b; box-shadow:0 0 0 .2rem rgba(245,158,11,.25); }
        .txn-row { background:#fff; border-radius:.75rem; padding:.75rem .5rem; transition:background .15s; }
        .txn-row:hover { background:#fafbfc; }
        .remove-row-btn, .copy-row-btn { width:34px; height:34px; padding:0; line-height:1; }
        .form-label { font-size:.8rem; font-weight:500; color:#374151; margin-bottom:.15rem; }
        .break-even-hint { font-size:.72rem; color:#6b7280; }
        .break-even-hint.loss { color:#b91c1c; font-weight:600; }
        .qty-input { max-width:80px; }
        @media (max-width: 767px) {
            .txn-row { border:1px solid #e5e7eb; padding:1rem .75rem; margin-bottom:.75rem; }
            .form-label { font-size:.85rem; }
            .form-control, .form-select { font-size:16px !important; } /* prevents iOS zoom */
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
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <span><i class="bi bi-upc-scan me-2"></i>Add products (bulk)</span>
                        <span class="badge bg-light text-dark rounded-pill px-3 py-2" id="rowSummary">
                            0 rows
                        </span>
                    </div>
                    <div class="card-body">
                        <form id="listingForm" enctype="multipart/form-data">
                            <!-- Desktop header row -->
                            <div class="row g-2 align-items-end mb-2 d-none d-lg-flex px-2 small fw-semibold text-secondary">
                                <div class="col-lg-2">Serial / Barcode</div>
                                <div class="col-lg-2">Item name</div>
                                <div class="col-lg-1">Qty</div>
                                <div class="col-lg-1">Cost (₹)</div>
                                <div class="col-lg-1">Making (₹)</div>
                                <div class="col-lg-1">Sell (₹)</div>
                                <div class="col-lg-1">Disc (₹)</div>
                                <div class="col-lg-1">Image</div>
                                <div class="col-lg-2 text-end">Actions</div>
                            </div>

                            <div id="txnRows"></div>

                            <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                                <button type="button" class="btn btn-outline-primary btn-sm rounded-pill" id="addRowBtn">
                                    <i class="bi bi-plus-circle me-1"></i>Add row
                                </button>
                                <button type="submit" class="btn btn-primary" id="submitBtn">
                                    <i class="bi bi-check-lg me-1"></i>Save all
                                </button>
                            </div>
                        </form>
                        <div id="formMessage" class="mt-3"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<!-- =============== ROW TEMPLATE =============== -->
<template id="rowTemplate">
    <div class="row g-2 align-items-end txn-row mb-2 pb-2 border-bottom">
        <div class="col-lg-2 col-12">
            <label class="form-label d-lg-none">Serial / Barcode</label>
            <input type="text" class="form-control form-control-sm barcode-field"
                   name="rows[__idx__][serial_number]" placeholder="Scan here" required>
        </div>
        <div class="col-lg-2 col-6">
            <label class="form-label d-lg-none">Item name</label>
            <input type="text" class="form-control form-control-sm"
                   name="rows[__idx__][item_name]" placeholder="e.g. Gold Ring" required>
        </div>
        <div class="col-lg-1 col-6">
            <label class="form-label d-lg-none">Qty</label>
            <input type="number" class="form-control form-control-sm qty-input"
                   name="rows[__idx__][quantity]" value="1" min="1" required>
        </div>
        <div class="col-lg-1 col-6">
            <label class="form-label d-lg-none">Cost ₹</label>
            <input type="number" step="0.01" min="0" class="form-control form-control-sm cost-input"
                   name="rows[__idx__][cost_price]" placeholder="0.00" required>
        </div>
        <div class="col-lg-1 col-6">
            <label class="form-label d-lg-none">Making ₹</label>
            <input type="number" step="0.01" min="0" class="form-control form-control-sm making-input"
                   name="rows[__idx__][making_charges]" placeholder="0">
        </div>
        <div class="col-lg-1 col-6">
            <label class="form-label d-lg-none">Sell ₹</label>
            <input type="number" step="0.01" min="0" class="form-control form-control-sm sell-input"
                   name="rows[__idx__][selling_price]" placeholder="0.00" required>
        </div>
        <div class="col-lg-1 col-6">
            <label class="form-label d-lg-none">Discount ₹</label>
            <input type="number" step="0.01" min="0" class="form-control form-control-sm"
                   name="rows[__idx__][discount]" placeholder="0">
        </div>
        <div class="col-lg-1 col-6">
            <label class="form-label d-lg-none">Image</label>
            <input type="file" class="form-control form-control-sm"
                   name="rows[__idx__][image]" accept="image/jpeg,image/png,image/webp">
        </div>
        <div class="col-lg-2 col-12">
            <div class="d-flex justify-content-end gap-1">
                <button type="button" class="btn btn-sm btn-outline-secondary copy-row-btn" title="Copy row">
                    <i class="bi bi-files"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger remove-row-btn" title="Remove">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="break-even-hint text-end mt-1"></div>
        </div>
        <!-- Optional hidden advanced fields (metal/purity etc) - can be toggled -->
        <input type="hidden" name="rows[__idx__][category]" value="">
        <input type="hidden" name="rows[__idx__][notes]" value="">
    </div>
</template>

<a href="#" class="back-to-top d-flex align-items-center justify-content-center">
    <i class="bi bi-arrow-up-short"></i>
</a>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets_new/js/main.js"></script>

<script>
let rowCounter = 0;

function addRow(values = {}) {
    const tpl  = document.getElementById('rowTemplate');
    const html = tpl.innerHTML.replace(/__idx__/g, rowCounter++);
    const wrap = document.createElement('div');
    wrap.innerHTML = html;
    const row = wrap.firstElementChild;

    for (const k in values) {
        const el = row.querySelector(`[name$="[${k}]"]`);
        if (el) el.value = values[k];
    }

    bindRowEvents(row);
    document.getElementById('txnRows').appendChild(row);
    updateRemoveButtons();
    updateRowSummary();
    return row;
}

function bindRowEvents(row) {
    // Copy button
    row.querySelector('.copy-row-btn').addEventListener('click', () => {
        const vals = {};
        row.querySelectorAll('input[name^="rows"]').forEach(inp => {
            if (inp.type === 'file') return;
            const key = inp.name.match(/\[(\w+)\]$/)[1];
            vals[key] = inp.value;
        });
        vals.serial_number = '';          // clear so scanner can fill
        const newRow = addRow(vals);
        newRow.querySelector('.barcode-field').focus();
    });

    // Live break-even check
    const costIn  = row.querySelector('.cost-input');
    const makeIn  = row.querySelector('.making-input');
    const sellIn  = row.querySelector('.sell-input');
    const qtyIn   = row.querySelector('.qty-input');
    const hint    = row.querySelector('.break-even-hint');

    function recalc() {
        const cost  = parseFloat(costIn.value)  || 0;
        const make  = parseFloat(makeIn.value)  || 0;
        const sell  = parseFloat(sellIn.value)  || 0;
        const qty   = parseInt(qtyIn.value)     || 1;
        const breakEven = (cost + make) / qty;
        hint.classList.remove('loss');
        if (sell > 0 && sell < breakEven) {
            hint.classList.add('loss');
            hint.textContent = `⚠ below break-even ₹${breakEven.toFixed(2)}`;
        } else if (sell > 0) {
            hint.textContent = `break-even ₹${breakEven.toFixed(2)}`;
        } else {
            hint.textContent = '';
        }
    }
    [costIn, makeIn, sellIn, qtyIn].forEach(el => el.addEventListener('input', recalc));
    recalc();

    // Serial field: Enter jumps to item name (barcode scanners send Enter)
    row.querySelector('.barcode-field').addEventListener('keydown', e => {
        if (e.key === 'Enter') {
            e.preventDefault();
            row.querySelector('[name$="[item_name]"]').focus();
        }
    });
}

function updateRemoveButtons() {
    const rows = document.querySelectorAll('.txn-row');
    rows.forEach(r => {
        const btn = r.querySelector('.remove-row-btn');
        btn.disabled = rows.length === 1;
        btn.style.opacity = rows.length === 1 ? '0.4' : '1';
    });
}

function updateRowSummary() {
    const n = document.querySelectorAll('.txn-row').length;
    document.getElementById('rowSummary').textContent = n + ' row' + (n !== 1 ? 's' : '');
}

document.getElementById('addRowBtn').addEventListener('click', () => {
    const row = addRow();
    row.querySelector('.barcode-field').focus();
});

document.getElementById('txnRows').addEventListener('click', e => {
    const btn = e.target.closest('.remove-row-btn');
    if (!btn || btn.disabled) return;
    btn.closest('.txn-row').remove();
    updateRemoveButtons();
    updateRowSummary();
});

// Submit
document.getElementById('listingForm').addEventListener('submit', function (e) {
    e.preventDefault();
    const btn = document.getElementById('submitBtn');
    const rows = document.querySelectorAll('.txn-row');

    // Client-side validation
    for (let i = 0; i < rows.length; i++) {
        const r = rows[i];
        const serial = r.querySelector('[name$="[serial_number]"]').value.trim();
        const name   = r.querySelector('[name$="[item_name]"]').value.trim();
        const cost   = parseFloat(r.querySelector('[name$="[cost_price]"]').value);
        const sell   = parseFloat(r.querySelector('[name$="[selling_price]"]').value);
        if (!serial || !name || !(cost >= 0) || !(sell >= 0)) {
            Swal.fire('Incomplete row', 'Fill serial, item name, cost, and sell price in row ' + (i+1), 'warning');
            return;
        }
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving...';

    fetch('add_products.php', {
        method: 'POST',
        body: new FormData(this),
        cache: 'no-store'
    })
    .then(r => r.json())
    .then(res => {
        if (!res.success) throw new Error(res.error || 'Failed');
        Swal.fire({
            icon: 'success',
            title: 'Saved',
            html: `<strong>${res.inserted}</strong> product(s) listed.`,
            confirmButtonColor: '#1e3c72'
        }).then(() => window.location.href = 'listed_products.php');
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Save all';
        Swal.fire('Error', err.message, 'error');
    });
});

// Init
document.addEventListener('DOMContentLoaded', () => {
    const row = addRow();
    row.querySelector('.barcode-field').focus();
});
</script>
</body>
</html>