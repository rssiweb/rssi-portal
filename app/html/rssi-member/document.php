<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();
?>

<!DOCTYPE html>
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

    <title>My Documents | RSSI</title>

    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap">

    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">

    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #4895ef;
            --secondary: #3a0ca3;
            --accent: #f72585;
            --light-bg: #f8f9fa;
            --dark-text: #212529;
            --card-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
            --transition: all 0.3s ease;
        }

        .main-container {
            padding: 0;
        }

        .nav-tabs {
            border-bottom: 1px solid #e9ecef;
            margin-bottom: 1.5rem;
        }

        .nav-tabs .nav-link {
            border: none;
            padding: 1rem 1.5rem;
            color: #6c757d;
            font-weight: 500;
            border-radius: 0;
            position: relative;
        }

        .nav-tabs .nav-link::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 3px;
            background: var(--primary);
            transition: var(--transition);
        }

        .nav-tabs .nav-link:hover {
            color: var(--primary);
            background: transparent;
        }

        .nav-tabs .nav-link.active {
            color: var(--primary);
            background: transparent;
            border: none;
        }

        .nav-tabs .nav-link.active::before {
            width: 100%;
        }

        .document-card {
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 1.5rem;
            transition: var(--transition);
        }

        .document-card:hover {
            transform: translateY(-3px);
        }

        .document-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            flex-shrink: 0;
        }

        .official {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary);
        }

        .payslip {
            background-color: rgba(247, 37, 133, 0.1);
            color: var(--accent);
        }

        .misc {
            background-color: rgba(58, 12, 163, 0.1);
            color: var(--secondary);
        }

        .document-content h5 {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .document-content p {
            color: #6c757d;
            margin-bottom: 0;
            font-size: 0.875rem;
        }

        .document-link {
            text-decoration: none;
            color: inherit;
            display: flex;
            padding: 1.25rem;
            align-items: center;
        }

        .document-link:hover {
            color: inherit;
        }

        .badge-new {
            background-color: var(--accent);
            color: white;
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
            border-radius: 50px;
            margin-left: 0.5rem;
        }

        @media (max-width: 768px) {
            .document-icon {
                width: 50px;
                height: 50px;
                margin-right: 0.75rem;
            }

            .document-link {
                padding: 1rem;
            }

            .nav-tabs .nav-link {
                padding: 0.75rem 1rem;
                font-size: 0.9rem;
            }
        }
    </style>

    <script src="https://cdn.jsdelivr.net/gh/manucaralmo/GlowCookies@3.0.1/src/glowCookies.min.js"></script>
    <!-- Glow Cookies v3.0.1 -->
    <script>
        glowCookies.start('en', {
            analytics: 'G-S25QWTFJ2S',
            //facebookPixel: '',
            policyLink: 'https://www.rssi.in/disclaimer'
        });
    </script>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>My Documents</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">My Services</a></li>
                    <li class="breadcrumb-item active">My Documents</li>
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

                            <main class="main">
                                <div class="container main-container">
                                    <section class="section">
                                        <div class="row">
                                            <div class="col-12">
                                                <div class="card">
                                                    <div class="card-body">
                                                        <ul class="nav nav-tabs" id="myTab" role="tablist">
                                                            <li class="nav-item" role="presentation">
                                                                <button class="nav-link active" id="official-tab" data-bs-toggle="tab" data-bs-target="#official" type="button" role="tab" aria-controls="official" aria-selected="true">
                                                                    <i class="bi bi-file-text me-2"></i>Official Letters
                                                                </button>
                                                            </li>
                                                            <li class="nav-item" role="presentation">
                                                                <button class="nav-link" id="payslip-tab" data-bs-toggle="tab" data-bs-target="#payslip" type="button" role="tab" aria-controls="payslip" aria-selected="false">
                                                                    <i class="bi bi-cash-coin me-2"></i>Payslips
                                                                </button>
                                                            </li>
                                                            <li class="nav-item" role="presentation">
                                                                <button class="nav-link" id="misc-tab" data-bs-toggle="tab" data-bs-target="#misc" type="button" role="tab" aria-controls="misc" aria-selected="false">
                                                                    <i class="bi bi-folder2-open me-2"></i>Miscellaneous
                                                                </button>
                                                            </li>
                                                        </ul>

                                                        <div class="tab-content mt-4" id="myTabContent">
                                                            <div class="tab-pane fade show active" id="official" role="tabpanel" aria-labelledby="official-tab">
                                                                <div class="document-card">
                                                                    <a href="digital_archive.php" class="document-link">
                                                                        <div class="document-icon official">
                                                                            <i class="bi bi-archive fs-4"></i>
                                                                        </div>
                                                                        <div class="document-content">
                                                                            <h5>Digital Archive</h5>
                                                                            <p>Access your official documents and letters</p>
                                                                        </div>
                                                                    </a>
                                                                </div>

                                                                <div class="document-card">
                                                                    <a href="bankdetails.php" class="document-link">
                                                                        <div class="document-icon official">
                                                                            <i class="bi bi-bank fs-4"></i>
                                                                        </div>
                                                                        <div class="document-content">
                                                                            <h5>My Bank Details</h5>
                                                                            <p>View and update your banking information</p>
                                                                        </div>
                                                                    </a>
                                                                </div>

                                                                <div class="document-card">
                                                                    <a href="my_certificate.php" class="document-link">
                                                                        <div class="document-icon official">
                                                                            <i class="bi bi-award fs-4"></i>
                                                                        </div>
                                                                        <div class="document-content">
                                                                            <h5>My Certificates</h5>
                                                                            <p>Access your professional certificates</p>
                                                                        </div>
                                                                    </a>
                                                                </div>
                                                            </div>

                                                            <div class="tab-pane fade" id="payslip" role="tabpanel" aria-labelledby="payslip-tab">
                                                                <div class="document-card">
                                                                    <a href="pay_details.php" target="_self" class="document-link">
                                                                        <div class="document-icon payslip">
                                                                            <i class="bi bi-wallet2 fs-4"></i>
                                                                        </div>
                                                                        <div class="document-content">
                                                                            <h5>Pay Details</h5>
                                                                            <p>View your current salary information</p>
                                                                        </div>
                                                                    </a>
                                                                </div>

                                                                <div class="document-card">
                                                                    <a href="old_payslip.php" target="_self" class="document-link">
                                                                        <div class="document-icon payslip">
                                                                            <i class="bi bi-file-earmark-text fs-4"></i>
                                                                        </div>
                                                                        <div class="document-content">
                                                                            <h5>Old Pay Details</h5>
                                                                            <p>Access your historical payslips (till May 2023)</p>
                                                                            <span class="badge-new">Archive</span>
                                                                        </div>
                                                                    </a>
                                                                </div>
                                                            </div>

                                                            <div class="tab-pane fade" id="misc" role="tabpanel" aria-labelledby="misc-tab">
                                                                <div class="document-card">
                                                                    <a href="idcard.php" target="_blank" class="document-link">
                                                                        <div class="document-icon misc">
                                                                            <i class="bi bi-person-badge fs-4"></i>
                                                                        </div>
                                                                        <div class="document-content">
                                                                            <h5>RSSI Identity Card</h5>
                                                                            <p>Access your digital identity card</p>
                                                                        </div>
                                                                    </a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </section>
                                </div>
                            </main>

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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Function to update URL with tab parameter
            function updateUrlWithTab(tabName) {
                const url = new URL(window.location);
                url.searchParams.set('tab', tabName);
                window.history.replaceState({}, '', url);
            }

            // Function to activate tab based on URL parameter
            function activateTabFromUrl() {
                const urlParams = new URLSearchParams(window.location.search);
                const tabParam = urlParams.get('tab');

                if (tabParam) {
                    const tabTrigger = document.querySelector(`button[data-bs-target="#${tabParam}"]`);
                    if (tabTrigger) {
                        new bootstrap.Tab(tabTrigger).show();
                    }
                }
            }

            // Set up tab change listeners
            const tabEls = document.querySelectorAll('button[data-bs-toggle="tab"]');
            tabEls.forEach(tabEl => {
                tabEl.addEventListener('shown.bs.tab', function(event) {
                    const targetId = event.target.getAttribute('data-bs-target').substring(1);
                    updateUrlWithTab(targetId);
                });
            });

            // Activate tab from URL on page load
            activateTabFromUrl();
        });
    </script>
</body>

</html>