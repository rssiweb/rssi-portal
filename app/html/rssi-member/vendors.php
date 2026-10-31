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
    <title>Vendors</title>
    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="../assets_new/css/style.css?v=1.1.0" rel="stylesheet">

    <style>
        .badge-active {
            background: #dcfce7;
            color: #166534;
        }

        .badge-inactive {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-kyc-ok {
            background: #dcfce7;
            color: #166534;
        }

        .badge-kyc-pending {
            background: #fef3c7;
            color: #92400e;
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

        .btn-loading {
            position: relative;
            pointer-events: none;
            opacity: .75;
        }

        .btn-loading::after {
            content: "";
            display: inline-block;
            width: .9em;
            height: .9em;
            margin-left: .4em;
            border: 2px solid currentColor;
            border-right-color: transparent;
            border-radius: 50%;
            animation: btn-spin .6s linear infinite;
            vertical-align: -1px;
        }

        @keyframes btn-spin {
            to {
                transform: rotate(360deg);
            }
        }

        .form-section-title {
            font-size: .8rem;
            font-weight: 600;
            color: #6b7280;
            margin: .25rem 0 .75rem;
        }

        .file-current a {
            font-size: .8rem;
        }

        #vendorTabs .nav-link {
            font-size: .9rem;
        }

        .view-mode .edit-only,
        .view-mode input[type=file] {
            display: none;
        }

        .view-mode .form-control:disabled,
        .view-mode .form-select:disabled {
            background: #f8f9fa;
            color: #212529;
            opacity: 1;
        }

        .view-mode .file-current:empty::after {
            content: "Not uploaded";
            font-size: .8rem;
            color: #6b7280;
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
                            <span><i class="bi bi-people me-2"></i>Vendors</span>
                            <div class="d-flex gap-2">
                                <span class="text-secondary small align-self-center" id="rowCountInfo"></span>
                                <button class="btn btn-sm btn-primary rounded-pill" id="addVendorBtn">
                                    <i class="bi bi-plus-lg"></i> Add vendor
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="filter-bar mb-3">
                                <div class="row g-2 align-items-end">
                                    <div class="col-12 col-md-4">
                                        <label class="form-label small">Search (name / contact / phone / city / GSTIN)</label>
                                        <input type="text" class="form-control form-control-sm" id="fSearch" placeholder="Type…">
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <label class="form-label small">Status</label>
                                        <select class="form-select form-select-sm" id="fActive">
                                            <option value="">All</option>
                                            <option value="1" selected>Active</option>
                                            <option value="0">Inactive</option>
                                        </select>
                                    </div>
                                    <div class="col-6 col-md-5 d-flex justify-content-md-end gap-2">
                                        <button class="btn btn-sm btn-outline-secondary rounded-pill" id="clearBtn">
                                            <i class="bi bi-arrow-counterclockwise"></i> Clear
                                        </button>
                                        <button class="btn btn-sm btn-primary rounded-pill" id="applyBtn">
                                            <i class="bi bi-search"></i> Search
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th style="width:50px;">#</th>
                                            <th>Vendor</th>
                                            <th>Contact</th>
                                            <th>Location</th>
                                            <th>GSTIN</th>
                                            <th>KYC</th>
                                            <th>Status</th>
                                            <th>Created</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tableBody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- ============= ADD / EDIT VENDOR MODAL ============= -->
    <div class="modal fade" id="vendorModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content" style="border-radius:1rem;">
                <div class="modal-header">
                    <h5 class="modal-title" id="vendorModalTitle">
                        <i class="bi bi-person-plus me-2"></i>Add vendor
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="vendorForm" enctype="multipart/form-data" novalidate>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="vId">

                        <ul class="nav nav-tabs mb-3" id="vendorTabs" role="tablist">
                            <li class="nav-item"><button type="button" class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabBasic">Business</button></li>
                            <li class="nav-item"><button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#tabAddress">Addresses</button></li>
                            <li class="nav-item"><button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#tabKyc">ID &amp; documents</button></li>
                            <li class="nav-item"><button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#tabBank">Payment</button></li>
                        </ul>

                        <fieldset id="vendorFieldset" class="border-0 p-0 m-0" style="min-width:0">
                            <div class="tab-content">
                                <!-- Business + contact -->
                                <div class="tab-pane fade show active" id="tabBasic">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label small">Business / shop name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="name" id="vName" maxlength="150" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small">Contact person <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="contact_person" maxlength="150" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small">What do they supply? <span class="text-danger">*</span></label>
                                            <select class="form-select" name="vendor_type" id="vendorType" required></select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small">Business type</label>
                                            <select class="form-select" name="business_type" id="businessType"></select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small">Phone <span class="text-danger">*</span></label>
                                            <input type="tel" class="form-control" name="phone" maxlength="15" placeholder="10-digit mobile" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small">Alternate phone</label>
                                            <input type="tel" class="form-control" name="alt_phone" maxlength="15">
                                        </div>
                                        <div class="col-md-12">
                                            <label class="form-label small">Email</label>
                                            <input type="email" class="form-control" name="email" maxlength="150">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small">GSTIN</label>
                                            <input type="text" class="form-control text-uppercase" name="gstin" maxlength="15" placeholder="If registered">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small">PAN</label>
                                            <input type="text" class="form-control text-uppercase" name="pan" maxlength="10">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small">Payment terms</label>
                                            <select class="form-select" name="payment_terms" id="payTerms"></select>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label small">Notes</label>
                                            <textarea class="form-control" name="notes" rows="2" maxlength="2000" placeholder="Items supplied, shop timings, delivery instructions…"></textarea>
                                        </div>
                                        <div class="col-12" id="activeRow" style="display:none;">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="vActive" name="active" checked>
                                                <label class="form-check-label small" for="vActive">Active</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Addresses -->
                                <div class="tab-pane fade" id="tabAddress">
                                    <div class="form-section-title">Business address (where goods are delivered) <span class="text-danger">*</span></div>
                                    <div class="row g-3 mb-4">
                                        <div class="col-12">
                                            <textarea class="form-control" name="biz_address" rows="2" maxlength="500" placeholder="Shop no., street, area, landmark" required></textarea>
                                        </div>
                                        <div class="col-md-5"><input type="text" class="form-control" name="biz_city" maxlength="80" placeholder="City" required></div>
                                        <div class="col-md-4"><select class="form-select state-select" name="biz_state" required></select></div>
                                        <div class="col-md-3"><input type="text" class="form-control" name="biz_pincode" maxlength="6" inputmode="numeric" pattern="\d{6}" placeholder="Pincode" required></div>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="form-section-title mb-2">Residential address</div>
                                        <div class="form-check small">
                                            <input class="form-check-input" type="checkbox" id="sameAddr">
                                            <label class="form-check-label" for="sameAddr">Same as business address</label>
                                        </div>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <textarea class="form-control" name="res_address" rows="2" maxlength="500"></textarea>
                                        </div>
                                        <div class="col-md-5"><input type="text" class="form-control" name="res_city" maxlength="80" placeholder="City"></div>
                                        <div class="col-md-4"><select class="form-select state-select" name="res_state"></select></div>
                                        <div class="col-md-3"><input type="text" class="form-control" name="res_pincode" maxlength="6" inputmode="numeric" pattern="\d{6}" placeholder="Pincode"></div>
                                    </div>
                                </div>

                                <!-- ID & documents -->
                                <div class="tab-pane fade" id="tabKyc">
                                    <div class="row g-3">
                                        <div class="col-md-5">
                                            <label class="form-label small">ID proof type</label>
                                            <select class="form-select" name="id_proof_type" id="idType"></select>
                                        </div>
                                        <div class="col-md-7">
                                            <label class="form-label small">ID proof number</label>
                                            <input type="text" class="form-control" name="id_proof_number" maxlength="30">

                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small">ID proof file (JPG, PNG, PDF)</label>
                                            <input type="file" class="form-control" name="id_proof" accept=".jpg,.jpeg,.png,.webp,.pdf" onchange="compressImageBeforeUpload(this)">
                                            <div class="file-current" data-f="id_proof"></div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small">GST / registration certificate</label>
                                            <input type="file" class="form-control" name="business_doc" accept=".jpg,.jpeg,.png,.webp,.pdf" onchange="compressImageBeforeUpload(this)">
                                            <div class="file-current" data-f="business_doc"></div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small">Owner / contact person photo</label>
                                            <input type="file" class="form-control" name="photo" accept=".jpg,.jpeg,.png,.webp" onchange="compressImageBeforeUpload(this)">
                                            <div class="file-current" data-f="photo"></div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small">Shop photo</label>
                                            <input type="file" class="form-control" name="shop_photo" accept=".jpg,.jpeg,.png,.webp" onchange="compressImageBeforeUpload(this)">
                                            <div class="file-current" data-f="shop_photo"></div>
                                        </div>
                                        <div class="col-12 form-text edit-only">Max 5 MB per file. Uploading a new file replaces the old one.</div>
                                    </div>
                                </div>

                                <!-- Payment -->
                                <div class="tab-pane fade" id="tabBank">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label small">Account holder name</label>
                                            <input type="text" class="form-control" name="bank_account_name" maxlength="150">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small">Bank name</label>
                                            <input type="text" class="form-control" name="bank_name" maxlength="100">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small">Account number</label>
                                            <input type="text" class="form-control" name="bank_account_no" maxlength="18" inputmode="numeric">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small">IFSC</label>
                                            <input type="text" class="form-control text-uppercase" name="bank_ifsc" maxlength="11">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small">UPI ID</label>
                                            <input type="text" class="form-control" name="upi_id" maxlength="80" placeholder="name@bank">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-danger me-auto d-none" id="vToggleBtn"></button>
                        <button type="button" class="btn btn-secondary" id="vCancelBtn" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-outline-primary d-none" id="vEditBtn"><i class="bi bi-pencil me-1"></i>Edit vendor</button>
                        <button type="submit" class="btn btn-primary" id="vSubmitBtn">Save vendor</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center">
        <i class="bi bi-arrow-up-short"></i>
    </a>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets_new/js/main.js"></script>
    <script src="../assets_new/js/image-compressor-100kb.js"></script>

    <script>
        let vendors = [];

        const VENDOR_TYPES = ['Stationery', 'Books', 'Uniform / Clothing', 'Electronics', 'Furniture', 'Food / Catering', 'Printing', 'Services', 'Other'];
        const BUSINESS_TYPES = ['Proprietorship', 'Partnership', 'LLP', 'Private Limited', 'Individual / Unregistered', 'Other'];
        const ID_TYPES = ['Aadhaar', 'PAN', 'Voter ID', 'Driving Licence', 'Passport'];
        const PAY_TERMS = ['Advance', 'Cash on delivery', 'Net 15', 'Net 30'];
        const STATES = ['Andaman and Nicobar Islands', 'Andhra Pradesh', 'Arunachal Pradesh', 'Assam', 'Bihar', 'Chandigarh', 'Chhattisgarh',
            'Dadra and Nagar Haveli and Daman and Diu', 'Delhi', 'Goa', 'Gujarat', 'Haryana', 'Himachal Pradesh', 'Jammu and Kashmir', 'Jharkhand',
            'Karnataka', 'Kerala', 'Ladakh', 'Lakshadweep', 'Madhya Pradesh', 'Maharashtra', 'Manipur', 'Meghalaya', 'Mizoram', 'Nagaland',
            'Odisha', 'Puducherry', 'Punjab', 'Rajasthan', 'Sikkim', 'Tamil Nadu', 'Telangana', 'Tripura', 'Uttar Pradesh', 'Uttarakhand', 'West Bengal'
        ];

        // Every text/select field in the form, matching the DB column names
        const FORM_FIELDS = ['name', 'contact_person', 'vendor_type', 'business_type', 'phone', 'alt_phone', 'email', 'gstin', 'pan',
            'payment_terms', 'notes', 'res_address', 'res_city', 'res_state', 'res_pincode', 'biz_address', 'biz_city', 'biz_state',
            'biz_pincode', 'id_proof_type', 'id_proof_number', 'bank_account_name', 'bank_name', 'bank_account_no', 'bank_ifsc', 'upi_id'
        ];

        const form = document.getElementById('vendorForm');

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

        function setLoading(btn, label) {
            if (!btn) return () => {};
            const original = btn.innerHTML;
            btn.classList.add('btn-loading');
            btn.disabled = true;
            if (label) btn.innerHTML = label;
            return () => {
                btn.classList.remove('btn-loading');
                btn.disabled = false;
                btn.innerHTML = original;
            };
        }

        function fillSelect(sel, items, placeholder) {
            sel.innerHTML = `<option value="">${placeholder}</option>` +
                items.map(i => `<option value="${escapeHtml(i)}">${escapeHtml(i)}</option>`).join('');
        }

        fillSelect(document.getElementById('vendorType'), VENDOR_TYPES, 'Select…');
        fillSelect(document.getElementById('businessType'), BUSINESS_TYPES, 'Select…');
        fillSelect(document.getElementById('idType'), ID_TYPES, 'Select…');
        fillSelect(document.getElementById('payTerms'), PAY_TERMS, 'Select…');
        document.querySelectorAll('.state-select').forEach(s => fillSelect(s, STATES, 'State'));

        /* ---------- list ---------- */
        function loadVendors() {
            const q = document.getElementById('fSearch').value.trim();
            const active = document.getElementById('fActive').value;
            const params = new URLSearchParams({
                q,
                active,
                _t: Date.now()
            });
            const tbody = document.getElementById('tableBody');
            tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted py-4">' +
                '<div class="spinner-border spinner-border-sm me-2"></div>Loading…</td></tr>';

            fetch('fetch_vendors.php?' + params.toString())
                .then(r => r.json())
                .then(res => {
                    if (!res || res.status !== 'success') throw new Error(res?.message || 'Failed to load');
                    vendors = res.vendors || [];
                    renderTable(vendors);
                    document.getElementById('rowCountInfo').textContent = `${vendors.length} vendor(s)`;
                })
                .catch(err => {
                    tbody.innerHTML = `<tr><td colspan="9" class="text-center text-danger py-4">
                        <i class="bi bi-exclamation-triangle me-2"></i>${escapeHtml(err.message)}</td></tr>`;
                });
        }

        function renderTable(rows) {
            const tbody = document.getElementById('tableBody');
            if (!rows.length) {
                tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted py-4">' +
                    '<i class="bi bi-inbox me-2"></i>No vendors found</td></tr>';
                return;
            }
            const dash = '<span class="text-muted">—</span>';
            tbody.innerHTML = rows.map((v, i) => {
                const badge = v.active ?
                    '<span class="badge badge-active">Active</span>' :
                    '<span class="badge badge-inactive">Inactive</span>';
                const kyc = v.kyc_complete ?
                    '<span class="badge badge-kyc-ok">Complete</span>' :
                    '<span class="badge badge-kyc-pending">Pending</span>';
                const loc = [v.biz_city, v.biz_state].filter(Boolean).join(', ');
                return `<tr>
                    <td class="text-muted small">${i + 1}</td>
                    <td>
                        <a href="#" class="fw-semibold text-decoration-none" onclick="openVendor(${v.id}, 'view'); return false;">${escapeHtml(v.name)}</a>
                        <div class="small text-muted">${escapeHtml(v.vendor_type || '')}${v.business_type ? ' · ' + escapeHtml(v.business_type) : ''}</div>
                    </td>
                    <td class="small">
                        <div>${escapeHtml(v.contact_person || '')}</div>
                        <div class="text-muted">${v.phone ? escapeHtml(v.phone) : ''}</div>
                        <div class="text-muted">${v.email ? escapeHtml(v.email) : ''}</div>
                    </td>
                    <td class="small">${loc ? escapeHtml(loc) : dash}</td>
                    <td class="small text-nowrap">${v.gstin ? escapeHtml(v.gstin) : dash}</td>
                    <td>${kyc}</td>
                    <td>${badge}</td>
                    <td class="small text-nowrap">${fmtDate(v.created_date)}</td>
                    <td class="text-end text-nowrap">
                        <button class="btn btn-sm btn-outline-primary rounded-pill"
                                onclick="openVendor(${v.id}, 'view')" title="Open vendor details">
                            <i class="bi bi-eye me-1"></i>View / Edit
                        </button>
                    </td>
                </tr>`;
            }).join('');
        }

        /* ---------- modal ---------- */
        const vendorModal = new bootstrap.Modal(document.getElementById('vendorModal'));

        function showFirstTab() {
            bootstrap.Tab.getOrCreateInstance(document.querySelector('#vendorTabs button')).show();
        }

        function setCurrentFiles(files) {
            document.querySelectorAll('.file-current').forEach(el => {
                const url = files && files[el.dataset.f];
                el.innerHTML = url ?
                    `<a href="${escapeHtml(url)}" target="_blank" rel="noopener"><i class="bi bi-paperclip"></i> View current file</a>` :
                    '';
            });
        }

        /* mode: 'view' = read-only (default way to look at a vendor), 'edit' = editable */
        function setMode(mode) {
            const isView = mode === 'view';
            const hasId = !!document.getElementById('vId').value;
            form.classList.toggle('view-mode', isView);
            document.getElementById('vendorFieldset').disabled = isView;
            document.getElementById('vSubmitBtn').classList.toggle('d-none', isView);
            document.getElementById('vEditBtn').classList.toggle('d-none', !isView);
            document.getElementById('vCancelBtn').textContent = isView ? 'Close' : 'Cancel';

            const cur = vendors.find(x => x.id === Number(document.getElementById('vId').value));
            const tBtn = document.getElementById('vToggleBtn');
            tBtn.classList.toggle('d-none', !(isView && cur));
            if (cur) {
                tBtn.className = 'btn me-auto ' + (cur.active ? 'btn-outline-danger' : 'btn-outline-success') +
                    (isView ? '' : ' d-none');
                tBtn.innerHTML = cur.active ?
                    '<i class="bi bi-slash-circle me-1"></i>Deactivate vendor' :
                    '<i class="bi bi-check-circle me-1"></i>Activate vendor';
            }
            document.getElementById('vendorModalTitle').innerHTML = isView ?
                '<i class="bi bi-eye me-2"></i>Vendor details' :
                (hasId ? '<i class="bi bi-pencil-square me-2"></i>Edit vendor' :
                    '<i class="bi bi-person-plus me-2"></i>Add vendor');
        }

        function openAdd() {
            form.reset();
            document.getElementById('vId').value = '';
            document.getElementById('activeRow').style.display = 'none';
            setCurrentFiles(null);
            setMode('edit');
            showFirstTab();
            vendorModal.show();
        }

        function openVendor(id, mode) {
            const v = vendors.find(x => x.id === id);
            if (!v) return;
            form.reset();
            document.getElementById('vId').value = v.id;
            FORM_FIELDS.forEach(f => {
                form.elements[f].value = v[f] ?? '';
            });
            document.getElementById('vActive').checked = !!v.active;
            document.getElementById('activeRow').style.display = '';
            setCurrentFiles(v.files);
            setMode(mode);
            showFirstTab();
            vendorModal.show();
        }

        // "Edit vendor" button inside the read-only view
        document.getElementById('vEditBtn').addEventListener('click', () => setMode('edit'));

        // Activate / deactivate from inside the details modal (still asks to confirm)
        document.getElementById('vToggleBtn').addEventListener('click', () => {
            const v = vendors.find(x => x.id === Number(document.getElementById('vId').value));
            if (v) toggleActive(v.id, v.active);
        });

        document.getElementById('addVendorBtn').addEventListener('click', openAdd);

        /* "Same as business address" copies business -> residential */
        const sameBox = document.getElementById('sameAddr');
        const ADDR_PAIRS = [
            ['biz_address', 'res_address'],
            ['biz_city', 'res_city'],
            ['biz_state', 'res_state'],
            ['biz_pincode', 'res_pincode']
        ];

        function syncAddress() {
            if (!sameBox.checked) return;
            ADDR_PAIRS.forEach(([from, to]) => {
                form.elements[to].value = form.elements[from].value;
            });
        }
        sameBox.addEventListener('change', syncAddress);
        ADDR_PAIRS.forEach(([from]) => form.elements[from].addEventListener('input', syncAddress));

        /* ---------- save ---------- */
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            // Required/pattern checks run across all tabs; jump to the tab with the first problem.
            const bad = form.querySelector('input:invalid, select:invalid, textarea:invalid');
            if (bad) {
                const pane = bad.closest('.tab-pane');
                const trigger = document.querySelector(`#vendorTabs [data-bs-target="#${pane.id}"]`);
                bootstrap.Tab.getOrCreateInstance(trigger).show();
                setTimeout(() => bad.reportValidity(), 200);
                return;
            }

            const btn = document.getElementById('vSubmitBtn');
            const done = setLoading(btn, 'Saving…');
            const fd = new FormData(form);
            // Unchecked switch doesn't submit; force an explicit value.
            fd.set('active', document.getElementById('vActive').checked ? '1' : '0');

            fetch('save_vendor.php', {
                    method: 'POST',
                    body: fd
                })
                .then(r => r.json())
                .then(res => {
                    done();
                    if (res.status === 'success') {
                        vendorModal.hide();
                        Swal.fire({
                            icon: 'success',
                            title: 'Vendor saved',
                            timer: 1000,
                            showConfirmButton: false
                        });
                        loadVendors();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Could not save',
                            text: res.message || 'Failed'
                        });
                    }
                })
                .catch(() => {
                    done();
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Network error'
                    });
                });
        });

        /* ---------- toggle active ---------- */
        function toggleActive(id, isActive) {
            const v = vendors.find(x => x.id === id);
            if (!v) return;
            Swal.fire({
                icon: 'question',
                title: isActive ? 'Deactivate vendor?' : 'Activate vendor?',
                html: `<strong>${escapeHtml(v.name)}</strong> will be ${
                    isActive ? 'hidden from new assignments' : 'available for assignments again'
                }.`,
                showCancelButton: true,
                confirmButtonText: isActive ? 'Deactivate' : 'Activate'
            }).then(r => {
                if (!r.isConfirmed) return;
                // Only the status is sent, so other vendor details are never overwritten.
                const fd = new FormData();
                fd.set('action', 'toggle');
                fd.set('id', v.id);
                fd.set('active', isActive ? '0' : '1');
                fetch('save_vendor.php', {
                        method: 'POST',
                        body: fd
                    })
                    .then(r2 => r2.json())
                    .then(res => {
                        if (res.status === 'success') {
                            vendorModal.hide();
                            Swal.fire({
                                icon: 'success',
                                title: 'Updated',
                                timer: 900,
                                showConfirmButton: false
                            });
                            loadVendors();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: res.message || 'Failed'
                            });
                        }
                    })
                    .catch(() => Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Network error'
                    }));
            });
        }

        /* ---------- filter controls ---------- */
        document.getElementById('applyBtn').addEventListener('click', loadVendors);
        document.getElementById('clearBtn').addEventListener('click', () => {
            document.getElementById('fSearch').value = '';
            document.getElementById('fActive').value = '1';
            loadVendors();
        });
        document.getElementById('fSearch').addEventListener('keydown', e => {
            if (e.key === 'Enter') loadVendors();
        });
        document.getElementById('fActive').addEventListener('change', loadVendors);

        /* ---------- init ---------- */
        loadVendors();
    </script>
</body>

</html>