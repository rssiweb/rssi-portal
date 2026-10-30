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
                                        <label class="form-label small">Search (name / contact)</label>
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
                                            <th style="width:60px;">#</th>
                                            <th>Name</th>
                                            <th>Contact</th>
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
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:1rem;">
                <div class="modal-header">
                    <h5 class="modal-title" id="vendorModalTitle">
                        <i class="bi bi-person-plus me-2"></i>Add vendor
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="vendorForm">
                    <div class="modal-body">
                        <input type="hidden" name="id" id="vId">
                        <div class="mb-3">
                            <label class="form-label small">Vendor name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" id="vName" maxlength="150" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Contact</label>
                            <input type="text" class="form-control" name="contact" id="vContact" maxlength="100" placeholder="Phone / email / address">
                        </div>
                        <div class="form-check form-switch" id="activeRow" style="display:none;">
                            <input class="form-check-input" type="checkbox" id="vActive" name="active" checked>
                            <label class="form-check-label small" for="vActive">Active</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="vSubmitBtn">Save</button>
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

    <script>
        let vendors = [];

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

        function loadVendors() {
            const q = document.getElementById('fSearch').value.trim();
            const active = document.getElementById('fActive').value;
            const params = new URLSearchParams({
                q,
                active,
                _t: Date.now()
            });
            const tbody = document.getElementById('tableBody');
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">' +
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
                    tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-4">
                        <i class="bi bi-exclamation-triangle me-2"></i>${escapeHtml(err.message)}</td></tr>`;
                });
        }

        function renderTable(rows) {
            const tbody = document.getElementById('tableBody');
            if (!rows.length) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">' +
                    '<i class="bi bi-inbox me-2"></i>No vendors found</td></tr>';
                return;
            }
            tbody.innerHTML = rows.map((v, i) => {
                const badge = v.active ?
                    '<span class="badge badge-active">Active</span>' :
                    '<span class="badge badge-inactive">Inactive</span>';
                return `<tr>
                    <td class="text-muted small">${i + 1}</td>
                    <td><div class="fw-semibold">${escapeHtml(v.name)}</div></td>
                    <td class="small">${v.contact ? escapeHtml(v.contact) : '<span class="text-muted">—</span>'}</td>
                    <td>${badge}</td>
                    <td class="small text-nowrap">${fmtDate(v.created_date)}</td>
                    <td class="text-end text-nowrap">
                        <button class="btn btn-sm btn-outline-primary rounded-pill me-1"
                                onclick="openEdit(${v.id})" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm ${v.active ? 'btn-outline-danger' : 'btn-outline-success'} rounded-pill"
                                onclick="toggleActive(${v.id}, ${v.active ? 'true' : 'false'})"
                                title="${v.active ? 'Deactivate' : 'Activate'}">
                            <i class="bi ${v.active ? 'bi-toggle-on' : 'bi-toggle-off'}"></i>
                        </button>
                    </td>
                </tr>`;
            }).join('');
        }

        /* ---------- modal ---------- */
        const vendorModal = new bootstrap.Modal(document.getElementById('vendorModal'));

        function openAdd() {
            document.getElementById('vendorModalTitle').innerHTML =
                '<i class="bi bi-person-plus me-2"></i>Add vendor';
            document.getElementById('vId').value = '';
            document.getElementById('vName').value = '';
            document.getElementById('vContact').value = '';
            document.getElementById('activeRow').style.display = 'none';
            vendorModal.show();
        }

        function openEdit(id) {
            const v = vendors.find(x => x.id === id);
            if (!v) return;
            document.getElementById('vendorModalTitle').innerHTML =
                '<i class="bi bi-pencil-square me-2"></i>Edit vendor';
            document.getElementById('vId').value = v.id;
            document.getElementById('vName').value = v.name;
            document.getElementById('vContact').value = v.contact || '';
            document.getElementById('vActive').checked = !!v.active;
            document.getElementById('activeRow').style.display = '';
            vendorModal.show();
        }

        document.getElementById('addVendorBtn').addEventListener('click', openAdd);

        document.getElementById('vendorForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('vSubmitBtn');
            const done = setLoading(btn, 'Saving…');
            const fd = new FormData(this);
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
                            title: 'Saved',
                            timer: 1000,
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
                const fd = new FormData();
                fd.set('id', v.id);
                fd.set('name', v.name);
                fd.set('contact', v.contact || '');
                fd.set('active', isActive ? '0' : '1');
                fetch('save_vendor.php', {
                        method: 'POST',
                        body: fd
                    })
                    .then(r2 => r2.json())
                    .then(res => {
                        if (res.status === 'success') {
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