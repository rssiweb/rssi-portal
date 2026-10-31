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
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php include 'includes/meta.php' ?>
    <title>Listed Products</title>
    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="../assets_new/css/style.css?v=1.1.0" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">

    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

    <style>
        .badge-available {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge-partial {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-sold {
            background: #fee2e2;
            color: #991b1b;
        }

        .filter-bar {
            background: #f9fafb;
            border-radius: 1rem;
            padding: 1rem 1.25rem;
            border: 1px solid #eef0f3;
        }

        .table thead th {
            font-size: .78rem;
            text-transform: uppercase;
            color: #6b7280;
            font-weight: 600;
            white-space: nowrap;
        }

        .thumb {
            width: 42px;
            height: 42px;
            object-fit: cover;
            border-radius: .5rem;
            cursor: pointer;
        }

        .bulk-bar {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: .75rem;
            padding: .5rem .75rem;
        }

        .row-check,
        #checkAll {
            cursor: pointer;
        }

        .form-check-input:disabled {
            cursor: not-allowed;
        }

        @media (max-width:767px) {

            .form-control,
            .form-select {
                font-size: 16px !important;
            }

            .table {
                font-size: .85rem;
            }
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
                            <span><i class="bi bi-box-seam me-2"></i>Listed products</span>
                            <span class="text-secondary small" id="rowCountInfo"></span>
                        </div>
                        <div class="card-body">
                            <!-- Filters -->
                            <div class="filter-bar mb-3">
                                <div class="row g-2 align-items-end">
                                    <div class="col-6 col-md-2">
                                        <label class="form-label small">Listed from</label>
                                        <input type="date" class="form-control form-control-sm" id="fFrom">
                                    </div>
                                    <div class="col-6 col-md-2">
                                        <label class="form-label small">Listed to</label>
                                        <input type="date" class="form-control form-control-sm" id="fTo">
                                    </div>
                                    <div class="col-6 col-md-2">
                                        <label class="form-label small">Sold from</label>
                                        <input type="date" class="form-control form-control-sm" id="fSoldFrom">
                                    </div>
                                    <div class="col-6 col-md-2">
                                        <label class="form-label small">Sold to</label>
                                        <input type="date" class="form-control form-control-sm" id="fSoldTo">
                                    </div>
                                    <div class="col-6 col-md-2">
                                        <label class="form-label small">Status</label>
                                        <select class="form-select form-select-sm" id="fStatus">
                                            <option value="">All</option>
                                            <option value="available">Available</option>
                                            <option value="partial">Partial</option>
                                            <option value="sold">Sold</option>
                                        </select>
                                    </div>
                                    <div class="col-6 col-md-2">
                                        <label class="form-label small">Vendor</label>
                                        <select class="form-select form-select-sm" id="fVendor"></select>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small">Search (serial / item)</label>
                                        <div class="input-group input-group-sm">
                                            <input type="text" class="form-control" id="fSearch" placeholder="Scan or type…">
                                            <button class="btn btn-outline-secondary" type="button" id="scanBtn" title="Scan barcode">
                                                <i class="bi bi-upc-scan"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6 d-flex justify-content-md-end gap-2">
                                        <button class="btn btn-sm btn-outline-secondary rounded-pill" id="clearBtn">
                                            <i class="bi bi-arrow-counterclockwise"></i> Clear
                                        </button>
                                        <button class="btn btn-sm btn-primary rounded-pill" id="applyBtn">
                                            <i class="bi bi-search"></i> Search
                                        </button>
                                        <button class="btn btn-sm btn-success rounded-pill" id="exportBtn">
                                            <i class="bi bi-download"></i> Export
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Bulk action toolbar -->
                            <div class="d-flex align-items-center gap-2 mb-2 bulk-bar" id="bulkBar" style="display:none;">
                                <span class="small text-secondary"><strong id="selCount">0</strong> selected</span>
                                <button class="btn btn-sm btn-success rounded-pill" id="bulkSoldBtn">
                                    <i class="bi bi-bag-check"></i> Mark selected sold
                                </button>
                                <button class="btn btn-sm btn-outline-primary rounded-pill" id="bulkAssignBtn">
                                    <i class="bi bi-person-plus"></i> Assign vendor
                                </button>
                                <button class="btn btn-sm btn-outline-secondary rounded-pill" id="clearSelBtn">
                                    Clear selection
                                </button>
                            </div>

                            <!-- Table -->
                            <div class="table-responsive">
                                <table class="table table-hover align-middle" id="productsTable">
                                    <thead>
                                        <tr>
                                            <th style="width:32px;"><input type="checkbox" class="form-check-input" id="checkAll"></th>
                                            <th>Image</th>
                                            <th>Serial</th>
                                            <th>Item</th>
                                            <th>Qty</th>
                                            <th>Sold</th>
                                            <th class="text-end">Cost</th>
                                            <th class="text-end">Sell</th>
                                            <th class="text-end">Discount</th>
                                            <th>Status</th>
                                            <th>Vendor</th>
                                            <th>Listed</th>
                                            <th>Sold on</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tableBody"></tbody>
                                </table>
                            </div>

                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">
                                <div class="small text-secondary" id="pageInfo"></div>
                                <nav>
                                    <ul class="pagination pagination-sm mb-0" id="pagination"></ul>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- ============= UPDATE PRICE MODAL ============= -->
    <div class="modal fade" id="updateModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:1rem;">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Update product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="updateForm">
                    <div class="modal-body">
                        <input type="hidden" name="id" id="uId">
                        <div class="mb-2"><strong id="uLabel"></strong></div>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label small">Selling price ₹</label>
                                <input type="number" step="0.01" class="form-control" name="selling_price" id="uSell" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small">Discount ₹</label>
                                <input type="number" step="0.01" class="form-control" name="discount" id="uDisc">
                            </div>
                            <div class="col-6">
                                <label class="form-label small">Quantity</label>
                                <input type="number" min="1" class="form-control" name="quantity" id="uQty" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small">Notes</label>
                                <input type="text" class="form-control" name="notes" id="uNotes">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ============= MARK SOLD MODAL ============= -->
    <div class="modal fade" id="soldModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:1rem;">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-bag-check me-2"></i>Mark as sold</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="soldForm">
                    <div class="modal-body">
                        <input type="hidden" name="id" id="sId">
                        <div class="mb-2"><strong id="sLabel"></strong></div>
                        <div class="mb-2 small text-muted" id="sAvailable"></div>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label small">Qty to sell</label>
                                <input type="number" min="1" class="form-control" name="qty_sold" id="sQty" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small">Sell price / unit ₹</label>
                                <input type="number" step="0.01" class="form-control" name="sell_price" id="sPrice" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small">Discount ₹</label>
                                <input type="number" step="0.01" class="form-control" name="discount" id="sDisc">
                            </div>
                            <div class="col-6">
                                <label class="form-label small">Buyer name</label>
                                <input type="text" class="form-control" name="buyer_name" id="sBuyer">
                            </div>
                            <div class="col-12">
                                <label class="form-label small">Buyer contact</label>
                                <input type="text" class="form-control" name="buyer_contact" id="sContact">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Confirm sale</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ============= BULK SOLD MODAL ============= -->
    <div class="modal fade" id="bulkSoldModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:1rem;">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-bag-check me-2"></i>Mark selected as sold</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="bulkSoldForm">
                    <div class="modal-body">
                        <div class="small text-muted mb-2" id="bulkSoldLabel"></div>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label small">Buyer name</label>
                                <input type="text" class="form-control" name="buyer_name" id="bsBuyer">
                            </div>
                            <div class="col-6">
                                <label class="form-label small">Buyer contact</label>
                                <input type="text" class="form-control" name="buyer_contact" id="bsContact">
                            </div>
                        </div>
                        <p class="small text-muted mt-3 mb-0">
                            Each item will be sold at its current listing price and full available quantity.
                            To change price or quantity, sell items individually instead.
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Confirm</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ============= ASSIGN VENDOR MODAL ============= -->
    <div class="modal fade" id="assignModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:1rem;">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Assign to vendor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="assignForm">
                    <div class="modal-body">
                        <div class="small text-muted mb-2" id="assignLabel"></div>
                        <label class="form-label small">Vendor</label>
                        <select class="form-select" id="assignVendor"></select>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Apply</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ============= SCAN MODAL ============= -->
    <div class="modal fade" id="scanModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:1rem;">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-upc-scan me-2"></i>Scan barcode</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="scanReader" style="width:100%;"></div>
                    <div class="small text-muted mt-2 text-center" id="scanMsg">Point the camera at the barcode</div>
                </div>
            </div>
        </div>
    </div>

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center">
        <i class="bi bi-arrow-up-short"></i>
    </a>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets_new/js/main.js"></script>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

    <script>
        const state = {
            from: '',
            to: '',
            sold_from: '',
            sold_to: '',
            status: '',
            vendor_id: '',
            q: '',
            page: 1,
            pageSize: 15
        };

        // Selection survives pagination. id -> full row object.
        const selected = new Map();
        // Rows currently displayed, keyed lookup for checkbox handler.
        let pageRows = [];

        /* ---------- helpers ---------- */
        function fmtMoney(n) {
            return '₹' + Number(n || 0).toLocaleString('en-IN', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function escapeHtml(s) {
            return String(s ?? '').replace(/[&<>"']/g, c => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            } [c]));
        }

        function fmtDate(ymd) {
            if (!ymd) return '';
            const [y, m, d] = String(ymd).split('-').map(Number);
            return new Date(y, m - 1, d).toLocaleDateString('en-IN', {
                day: '2-digit',
                month: 'short',
                year: 'numeric'
            });
        }

        /* ---------- button loading state ---------- */
        function setBtnLoading(btn, loading, loadingText) {
            if (!btn) return;
            if (loading) {
                btn.dataset.originalHtml = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = `<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>${loadingText || 'Please wait…'}`;
            } else {
                btn.disabled = false;
                if (btn.dataset.originalHtml) btn.innerHTML = btn.dataset.originalHtml;
            }
        }

        /* ---------- Select2 with remote vendor search ---------- */
        function makeVendorSelect2($el, sentinelLabel, placeholder, dropdownParent) {
            $el.select2({
                placeholder,
                allowClear: true,
                width: '100%',
                dropdownParent: dropdownParent || $('body'),
                ajax: {
                    url: 'vendors_list.php',
                    dataType: 'json',
                    delay: 250,
                    cache: true,
                    data: params => ({
                        q: params.term || '',
                        active: '1'
                    }),
                    processResults: (data, params) => {
                        const out = (data && data.vendors ? data.vendors : []).map(v => ({
                            id: v.id,
                            text: v.contact ? `${v.name} (${v.contact})` : v.name
                        }));
                        if (!params.term) {
                            out.unshift({
                                id: '0',
                                text: sentinelLabel
                            });
                        }
                        return {
                            results: out
                        };
                    }
                },
                language: {
                    inputTooShort: () => 'Type to search vendors…',
                    noResults: () => 'No vendors found',
                    searching: () => 'Searching…'
                }
            });
        }

        makeVendorSelect2($('#fVendor'), 'Unassigned', 'All vendors');
        makeVendorSelect2($('#assignVendor'), '— Unassign —', 'Search vendor…', $('#assignModal'));

        // Re-add the selected option so the visible label persists after AJAX refresh
        $('#fVendor').on('select2:select', e => {
            const d = e.params.data;
            if (!$('#fVendor option[value="' + d.id + '"]').length) {
                $('#fVendor').append(new Option(d.text, d.id, true, true));
            }
        });
        /* ---------- load ---------- */
        function loadProducts() {
            state.from = document.getElementById('fFrom').value;
            state.to = document.getElementById('fTo').value;
            state.sold_from = document.getElementById('fSoldFrom').value;
            state.sold_to = document.getElementById('fSoldTo').value;
            state.status = document.getElementById('fStatus').value;
            state.vendor_id = $('#fVendor').val() || '';
            state.q = document.getElementById('fSearch').value.trim();

            const params = new URLSearchParams({
                ...state,
                _t: Date.now()
            });
            const tbody = document.getElementById('tableBody');
            tbody.innerHTML = '<tr><td colspan="14" class="text-center text-muted py-4">' +
                '<div class="spinner-border spinner-border-sm me-2"></div>Loading…</td></tr>';

            fetch('fetch_listed_products.php?' + params.toString())
                .then(r => r.json())
                .then(res => {
                    if (!res || res.status !== 'success') throw new Error(res?.message || 'Failed to load');
                    state.page = res.page;
                    state.pageSize = res.pageSize;
                    pageRows = res.rows || [];
                    // Drop selections that have since become fully sold
                    for (const [id] of [...selected]) {
                        const fresh = pageRows.find(x => x.id === id);
                        if (fresh) {
                            const avail = Math.max(0, Number(fresh.quantity || 0) - Number(fresh.sold || 0));
                            if (avail <= 0) selected.delete(id);
                        }
                    }
                    refreshBulkBar();
                    renderTable(pageRows);
                    renderPagination(res.total || 0, res.page, res.pageSize);
                    document.getElementById('rowCountInfo').textContent =
                        `${res.total || 0} product(s)`;
                    syncCheckAll();
                })
                .catch(err => {
                    tbody.innerHTML = `<tr><td colspan="14" class="text-center text-danger py-4">
                        <i class="bi bi-exclamation-triangle me-2"></i>${escapeHtml(err.message)}</td></tr>`;
                });
        }

        /* ---------- render ---------- */
        function statusBadge(qty, sold) {
            const q = Number(qty || 0),
                s = Number(sold || 0);
            if (s >= q && q > 0) return '<span class="badge badge-sold">Sold</span>';
            if (s > 0) return '<span class="badge badge-partial">Partial</span>';
            return '<span class="badge badge-available">Available</span>';
        }

        function renderTable(rows) {
            const tbody = document.getElementById('tableBody');
            if (!rows.length) {
                tbody.innerHTML = '<tr><td colspan="14" class="text-center text-muted py-4">' +
                    '<i class="bi bi-inbox me-2"></i>No products found</td></tr>';
                return;
            }
            tbody.innerHTML = rows.map(r => {
                const img = r.image_url ?
                    `<img src="${escapeHtml(r.image_url)}" class="thumb" alt=""
                         onclick="previewImage('${escapeHtml(r.image_url)}')">` :
                    '<span class="text-muted">—</span>';
                const available = Math.max(0, Number(r.quantity || 0) - Number(r.sold || 0));
                const checked = selected.has(r.id) ? 'checked' : '';
                const vendorCell = r.vendor_name ?
                    `<span class="small">${escapeHtml(r.vendor_name)}</span>` :
                    '<span class="small text-muted">—</span>';
                const fullySold = available <= 0;
                const checkboxAttrs = fullySold ? 'disabled title="Sold out"' : '';
                return `<tr>
                    <td><input type="checkbox" class="form-check-input row-check" data-id="${r.id}" ${checked} ${checkboxAttrs}></td>
                    <td>${img}</td>
                    <td><code class="small">${escapeHtml(r.serial || '')}</code></td>
                    <td>
                        <div class="fw-semibold">${escapeHtml(r.item_name || '')}</div>
                        ${r.notes ? `<div class="small text-muted">${escapeHtml(r.notes)}</div>` : ''}
                    </td>
                    <td>${Number(r.quantity || 0)}</td>
                    <td>${Number(r.sold || 0)}</td>
                    <td class="text-end">${fmtMoney(r.cost_price)}</td>
                    <td class="text-end">${fmtMoney(r.selling_price)}</td>
                    <td class="text-end">${fmtMoney(r.discount)}</td>
                    <td>${statusBadge(r.quantity, r.sold)}</td>
                    <td>${vendorCell}</td>
                    <td class="small text-nowrap">${fmtDate(r.listed_date)}</td>
                    <td class="small text-nowrap">${r.sold_date ? fmtDate(r.sold_date) : '<span class="text-muted">—</span>'}</td>
                                        <td class="text-end text-nowrap">
                        ${available > 0 ? `
                        <button class="btn btn-sm btn-outline-primary rounded-pill me-1"
                                onclick="openUpdate(${r.id})" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-success rounded-pill"
                                onclick="openSold(${r.id})" title="Mark sold">
                            <i class="bi bi-bag-check"></i>
                        </button>` : `<span class="small text-muted">Fully sold</span>`}
                    </td>
                </tr>`;
            }).join('');
        }

        function renderPagination(total, page, pageSize) {
            const pages = Math.max(1, Math.ceil(total / pageSize));
            const ul = document.getElementById('pagination');
            const info = document.getElementById('pageInfo');
            info.textContent = total ?
                `Showing ${(page - 1) * pageSize + 1}–${Math.min(page * pageSize, total)} of ${total}` :
                'No records';

            let html = '';
            const btn = (label, p, disabled, active) =>
                `<li class="page-item ${disabled ? 'disabled' : ''} ${active ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="gotoPage(${p});return false;">${label}</a>
                 </li>`;

            html += btn('&laquo;', Math.max(1, page - 1), page <= 1, false);
            const start = Math.max(1, page - 2),
                end = Math.min(pages, page + 2);
            if (start > 1) html += btn('1', 1, false, page === 1);
            if (start > 2) html += '<li class="page-item disabled"><span class="page-link">…</span></li>';
            for (let p = start; p <= end; p++) html += btn(p, p, false, p === page);
            if (end < pages - 1) html += '<li class="page-item disabled"><span class="page-link">…</span></li>';
            if (end < pages) html += btn(pages, pages, false, page === pages);
            html += btn('&raquo;', Math.min(pages, page + 1), page >= pages, false);
            ul.innerHTML = html;
        }

        function gotoPage(p) {
            state.page = p;
            loadProducts();
        }

        function previewImage(url) {
            Swal.fire({
                imageUrl: url,
                imageAlt: 'Product image',
                showConfirmButton: false,
                showCloseButton: true,
                width: 'auto'
            });
        }

        /* ---------- selection ---------- */
        function refreshBulkBar() {
            const n = selected.size;
            document.getElementById('bulkBar').style.display = n ? 'flex' : 'none';
            document.getElementById('selCount').textContent = n;
        }

        function syncCheckAll() {
            const all = document.querySelectorAll('.row-check:not(:disabled)');
            const checked = document.querySelectorAll('.row-check:not(:disabled):checked');
            const ca = document.getElementById('checkAll');
            ca.checked = all.length > 0 && all.length === checked.length;
            ca.indeterminate = checked.length > 0 && checked.length < all.length;
        }

        document.getElementById('tableBody').addEventListener('change', e => {
            if (!e.target.classList.contains('row-check')) return;
            const id = Number(e.target.dataset.id);
            if (e.target.checked) {
                const row = pageRows.find(x => x.id === id);
                if (row) selected.set(id, row);
            } else {
                selected.delete(id);
            }
            refreshBulkBar();
            syncCheckAll();
        });

        document.getElementById('checkAll').addEventListener('change', e => {
            const on = e.target.checked;
            document.querySelectorAll('.row-check:not(:disabled)').forEach(cb => {
                cb.checked = on;
                const id = Number(cb.dataset.id);
                if (on) {
                    const row = pageRows.find(x => x.id === id);
                    if (row) selected.set(id, row);
                } else {
                    selected.delete(id);
                }
            });
            refreshBulkBar();
        });

        document.getElementById('clearSelBtn').addEventListener('click', () => {
            selected.clear();
            document.querySelectorAll('.row-check, #checkAll').forEach(cb => cb.checked = false);
            document.getElementById('checkAll').indeterminate = false;
            refreshBulkBar();
        });

        /* ---------- update modal ---------- */
        const updateModal = new bootstrap.Modal(document.getElementById('updateModal'));

        function openUpdate(id) {
            const r = pageRows.find(x => x.id === id);
            if (!r) return;
            document.getElementById('uId').value = r.id;
            document.getElementById('uLabel').textContent = `${r.serial || ''} — ${r.item_name || ''}`;
            document.getElementById('uSell').value = r.selling_price ?? '';
            document.getElementById('uDisc').value = r.discount ?? '';
            document.getElementById('uQty').value = r.quantity ?? 1;
            document.getElementById('uNotes').value = r.notes ?? '';
            updateModal.show();
        }

        document.getElementById('updateForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const fd = new FormData(this);
            const btn = this.querySelector('button[type="submit"]');
            setBtnLoading(btn, true, 'Saving…');
            fetch('update_listed_product.php', {
                    method: 'POST',
                    body: fd
                })
                .then(r => r.json())
                .then(res => {
                    setBtnLoading(btn, false);
                    if (res.status === 'success') {
                        updateModal.hide();
                        Swal.fire({
                            icon: 'success',
                            title: 'Updated',
                            timer: 1200,
                            showConfirmButton: false
                        });
                        loadProducts();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: res.message || 'Update failed'
                        });
                    }
                })
                .catch(() => {
                    setBtnLoading(btn, false);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Network error'
                    });
                });
        });

        /* ---------- single sold modal ---------- */
        const soldModal = new bootstrap.Modal(document.getElementById('soldModal'));
        let soldAvailable = 0;

        function openSold(id) {
            const r = pageRows.find(x => x.id === id);
            if (!r) return;
            soldAvailable = Math.max(0, Number(r.quantity || 0) - Number(r.sold || 0));
            document.getElementById('sId').value = r.id;
            document.getElementById('sLabel').textContent = `${r.serial || ''} — ${r.item_name || ''}`;
            document.getElementById('sAvailable').textContent = `Available: ${soldAvailable} of ${r.quantity}`;
            document.getElementById('sQty').value = 1;
            document.getElementById('sQty').max = soldAvailable;
            document.getElementById('sPrice').value = r.selling_price ?? '';
            document.getElementById('sDisc').value = r.discount ?? '';
            document.getElementById('sBuyer').value = '';
            document.getElementById('sContact').value = '';
            soldModal.show();
        }

        document.getElementById('soldForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const qty = Number(document.getElementById('sQty').value);
            if (qty <= 0 || qty > soldAvailable) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Invalid quantity',
                    text: `Only ${soldAvailable} unit(s) available.`
                });
                return;
            }
            const fd = new FormData(this);
            const btn = this.querySelector('button[type="submit"]');
            setBtnLoading(btn, true, 'Recording…');
            fetch('mark_product_sold.php', {
                    method: 'POST',
                    body: fd
                })
                .then(r => r.json())
                .then(res => {
                    setBtnLoading(btn, false);
                    if (res.status === 'success') {
                        soldModal.hide();
                        Swal.fire({
                            icon: 'success',
                            title: 'Sale recorded',
                            timer: 1200,
                            showConfirmButton: false
                        });
                        loadProducts();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: res.message || 'Failed to record sale'
                        });
                    }
                })
                .catch(() => {
                    setBtnLoading(btn, false);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Network error'
                    });
                });
        });

        /* ---------- bulk sold ---------- */
        const bulkSoldModal = new bootstrap.Modal(document.getElementById('bulkSoldModal'));

        document.getElementById('bulkSoldBtn').addEventListener('click', () => {
            if (!selected.size) return;
            document.getElementById('bulkSoldLabel').textContent =
                `${selected.size} item(s) will be marked sold at current price and full available qty.`;
            document.getElementById('bsBuyer').value = '';
            document.getElementById('bsContact').value = '';
            bulkSoldModal.show();
        });

        document.getElementById('bulkSoldForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const items = [...selected.values()].map(r => ({
                id: r.id,
                qty_sold: Math.max(1, Number(r.quantity || 0) - Number(r.sold || 0)),
                sell_price: Number(r.selling_price || 0),
                discount: Number(r.discount || 0)
            }));
            if (!items.length) return;
            const btn = this.querySelector('button[type="submit"]');
            setBtnLoading(btn, true, 'Processing…');
            fetch('bulk_mark_sold.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        items,
                        buyer_name: document.getElementById('bsBuyer').value.trim(),
                        buyer_contact: document.getElementById('bsContact').value.trim()
                    })
                })
                .then(r => r.json())
                .then(res => {
                    setBtnLoading(btn, false);
                    if (res.status === 'success') {
                        bulkSoldModal.hide();
                        selected.clear();
                        refreshBulkBar();
                        Swal.fire({
                            icon: 'success',
                            title: `${res.sold} item(s) sold`,
                            timer: 1200,
                            showConfirmButton: false
                        });
                        loadProducts();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: res.message || 'Failed'
                        });
                    }
                })
                .catch(() => {
                    setBtnLoading(btn, false);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Network error'
                    });
                });
        });

        /* ---------- assign vendor ---------- */
        const assignModal = new bootstrap.Modal(document.getElementById('assignModal'));

        document.getElementById('bulkAssignBtn').addEventListener('click', () => {
            if (!selected.size) return;
            document.getElementById('assignLabel').textContent = `${selected.size} item(s) selected`;
            $('#assignVendor').val(null).trigger('change');
            assignModal.show();
        });

        document.getElementById('assignForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const raw = document.getElementById('assignVendor').value;
            if (raw === '') return;
            const vid = Number(raw);
            const ids = [...selected.keys()];
            const btn = this.querySelector('button[type="submit"]');
            setBtnLoading(btn, true, 'Applying…');
            fetch('assign_vendor.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        ids,
                        vendor_id: vid
                    })
                })
                .then(r => r.json())
                .then(res => {
                    setBtnLoading(btn, false);
                    if (res.status === 'success') {
                        assignModal.hide();
                        selected.clear();
                        refreshBulkBar();
                        Swal.fire({
                            icon: 'success',
                            title: 'Updated',
                            timer: 1000,
                            showConfirmButton: false
                        });
                        loadProducts();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: res.message || 'Failed'
                        });
                    }
                })
                .catch(() => {
                    setBtnLoading(btn, false);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Network error'
                    });
                });
        });

        /* ---------- filter controls ---------- */
        document.getElementById('applyBtn').addEventListener('click', () => {
            state.page = 1;
            loadProducts();
        });

        document.getElementById('clearBtn').addEventListener('click', () => {
            document.getElementById('fFrom').value = '';
            document.getElementById('fTo').value = '';
            document.getElementById('fSoldFrom').value = '';
            document.getElementById('fSoldTo').value = '';
            document.getElementById('fStatus').value = '';
            $('#fVendor').val(null).trigger('change');
            document.getElementById('fSearch').value = '';
            state.page = 1;
            loadProducts();
        });

        document.getElementById('fSearch').addEventListener('keydown', e => {
            if (e.key === 'Enter') {
                state.page = 1;
                loadProducts();
            }
        });

        document.getElementById('fStatus').addEventListener('change', () => {
            state.page = 1;
            loadProducts();
        });

        let vendorSelect2Ready = false;
        $('#fVendor').on('change', () => {
            // Skip the synthetic change Select2 fires on init
            if (!vendorSelect2Ready) {
                vendorSelect2Ready = true;
                return;
            }
            state.page = 1;
            loadProducts();
        });

        /* ---------- export ---------- */
        document.getElementById('exportBtn').addEventListener('click', () => {
            const params = new URLSearchParams({
                from: state.from,
                to: state.to,
                sold_from: state.sold_from,
                sold_to: state.sold_to,
                status: state.status,
                vendor_id: state.vendor_id,
                q: state.q
            });
            window.location = 'export_listed_products.php?' + params.toString();
        });

        /* ---------- barcode scanner ---------- */
        const scanModalEl = document.getElementById('scanModal');
        const scanModal = new bootstrap.Modal(scanModalEl);
        const scanMsg = document.getElementById('scanMsg');
        let scanner = null;
        let scanDone = false;

        document.getElementById('scanBtn').addEventListener('click', () => scanModal.show());

        scanModalEl.addEventListener('shown.bs.modal', async () => {
            scanDone = false;
            scanMsg.textContent = 'Starting camera…';
            scanner = new Html5Qrcode('scanReader', {
                verbose: false,
                useBarCodeDetectorIfSupported: true,
                formatsToSupport: [
                    Html5QrcodeSupportedFormats.CODE_128,
                    Html5QrcodeSupportedFormats.CODE_39,
                    Html5QrcodeSupportedFormats.EAN_13,
                    Html5QrcodeSupportedFormats.EAN_8,
                    Html5QrcodeSupportedFormats.UPC_A,
                    Html5QrcodeSupportedFormats.UPC_E,
                    Html5QrcodeSupportedFormats.QR_CODE
                ]
            });
            try {
                await scanner.start({
                        facingMode: 'environment'
                    }, {
                        fps: 10,
                        qrbox: {
                            width: 260,
                            height: 140
                        }
                    },
                    onScanSuccess,
                    () => {} // ignore per-frame "not found" errors
                );
                scanMsg.textContent = 'Point the camera at the barcode';
            } catch (err) {
                scanMsg.textContent = 'Camera error: ' + (err?.message || err) +
                    '. Allow camera permission and make sure the site is on HTTPS.';
            }
        });

        scanModalEl.addEventListener('hidden.bs.modal', stopScanner);

        async function stopScanner() {
            if (!scanner) return;
            try {
                await scanner.stop();
            } catch (e) {}
            try {
                scanner.clear();
            } catch (e) {}
            scanner = null;
        }

        function onScanSuccess(code) {
            if (scanDone) return; // ignore repeat reads
            scanDone = true;
            if (navigator.vibrate) navigator.vibrate(100);

            document.getElementById('fSearch').value = code;
            scanModal.hide(); // camera stops in 'hidden.bs.modal'
            state.page = 1;
            loadProducts();
        }

        /* ---------- init ---------- */
        loadProducts();
    </script>
</body>

</html>