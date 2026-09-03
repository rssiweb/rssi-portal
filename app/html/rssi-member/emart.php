<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}

validation();
?>

<!doctype html>
<html lang="en">

<head>

    <!-- =========================================================
         GOOGLE TAG
    ========================================================== -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=AW-11316670180"></script>

    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }

        gtag('js', new Date());
        gtag('config', 'AW-11316670180');
    </script>


    <!-- =========================================================
         META
    ========================================================== -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <?php include 'includes/meta.php'; ?>

    <link href="../img/favicon.ico" rel="icon">


    <!-- =========================================================
         BOOTSTRAP CSS
    ========================================================== -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65"
        crossorigin="anonymous">


    <!-- =========================================================
         BOOTSTRAP ICONS
    ========================================================== -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">


    <!-- =========================================================
         SELECT2 CSS
    ========================================================== -->
    <link
        href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css"
        rel="stylesheet">


    <!-- =========================================================
         CUSTOM CSS
    ========================================================== -->
    <link
        href="../assets_new/css/style.css?v=1.1.0"
        rel="stylesheet">


    <!-- =========================================================
         JQUERY
    ========================================================== -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>


    <!-- =========================================================
         BOOTSTRAP JS
         IMPORTANT: Must load BEFORE custom JavaScript
    ========================================================== -->
    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
        crossorigin="anonymous">
    </script>


    <!-- =========================================================
         SELECT2 JS
    ========================================================== -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>


    <!-- =========================================================
         GLOW COOKIES
    ========================================================== -->
    <script src="https://cdn.jsdelivr.net/gh/manucaralmo/GlowCookies@3.0.1/src/glowCookies.min.js"></script>

    <script>
        glowCookies.start('en', {
            analytics: 'G-S25QWTFJ2S',
            policyLink: 'https://www.rssi.in/disclaimer'
        });
    </script>


    <!-- =========================================================
         PAGE CSS
    ========================================================== -->
    <style>
        @media (min-width: 767px) {
            .left {
                margin-left: 2%;
            }
        }

        .left-section,
        .right-section {
            background: #ffffff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .product-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .product-card img {
            max-width: 100%;
            border-radius: 8px;
        }

        .btn-quantity {
            display: inline-flex;
            align-items: center;
        }

        .dynamic-price-badge {
            font-size: 0.7rem;
            background-color: #ff9800;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            margin-left: 5px;
        }

        .fixed-price-badge {
            font-size: 0.7rem;
            background-color: #4caf50;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            margin-left: 5px;
        }

        .cart-item-dynamic {
            background-color: #fff3e0;
            border-left: 3px solid #ff9800;
        }

        .cart-item-fixed {
            background-color: #e8f5e9;
            border-left: 3px solid #4caf50;
        }

        .dynamic-item-row {
            background-color: #fff8e1;
        }

        .dynamic-item-row .unit-price-input {
            width: 100px !important;
        }

        .dynamic-item-row .discount-input {
            width: 70px !important;
        }

        .price-to-be-set {
            color: #ff9800;
            font-style: italic;
            font-size: 0.85rem;
        }

        .unit-number {
            font-size: 0.75rem;
            color: #666;
        }

        /*
         * =========================================================
         * SUBMISSION OVERLAY
         *
         * This is intentionally NOT a Bootstrap modal.
         * It appears immediately with its backdrop.
         * =========================================================
         */

        #submissionOverlay {
            position: fixed;
            inset: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.55);
            z-index: 2000;
            display: none;
            align-items: center;
            justify-content: center;
        }

        #submissionOverlay.show {
            display: flex;
        }

        #submissionDialog {
            width: 450px;
            max-width: calc(100% - 30px);
            background: #ffffff;
            border-radius: 8px;
            padding: 28px 25px;
            text-align: center;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.30);
        }

        #submissionDialog .spinner-border {
            width: 3rem;
            height: 3rem;
            margin-bottom: 15px;
        }

        #submissionMessage {
            margin: 0;
            font-size: 16px;
            color: #444;
        }

        /*
         * Make sure normal Bootstrap modal is below our submission overlay
         */
        #orderConfirmationModal {
            z-index: 1055;
        }

        /*
         * Select2 inside Bootstrap modal
         */
        .select2-container {
            width: 100% !important;
        }

        .select2-container--default .select2-selection--multiple {
            min-height: 38px;
            border: 1px solid #ced4da;
        }
    </style>

</head>


<body>

    <?php include 'includes/header.php'; ?>

    <?php include 'inactive_session_expire_check.php'; ?>


    <!-- =========================================================
         MAIN
    ========================================================== -->

    <main id="main" class="main">

        <div class="pagetitle">

            <h1>
                <?php echo getPageTitle(); ?>
            </h1>

            <?php echo generateDynamicBreadcrumb(); ?>

        </div>


        <section class="section dashboard">

            <div class="row">

                <div class="col-12">

                    <div class="card">

                        <div class="card-body">

                            <br>


                            <!-- =================================================
                                 ITEMS PER PAGE
                            ================================================== -->

                            <div class="container">

                                <div class="row">

                                    <div class="col-md-12 text-end">

                                        <label
                                            for="itemsPerPage"
                                            class="form-label mb-1">

                                            Items per page:

                                        </label>


                                        <select
                                            class="form-select d-inline-block w-auto"
                                            id="itemsPerPage">

                                            <option value="5">5</option>
                                            <option value="10">10</option>
                                            <option value="20">20</option>
                                            <option value="50">50</option>
                                            <option value="100">100</option>

                                        </select>

                                    </div>

                                </div>

                            </div>


                            <div class="container py-5">

                                <div class="row">


                                    <!-- =================================================
                                         PRODUCTS
                                    ================================================== -->

                                    <div class="col-md-6">

                                        <div class="row mb-3">

                                            <div class="col-md-12">

                                                <div class="input-group">

                                                    <input
                                                        type="text"
                                                        id="searchInput"
                                                        class="form-control"
                                                        placeholder="Search products..."
                                                        value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">


                                                    <button
                                                        id="searchButton"
                                                        class="btn btn-primary"
                                                        type="button">

                                                        <i class="bi bi-search"></i>
                                                        Search

                                                    </button>


                                                    <?php if (isset($_GET['search']) && !empty($_GET['search'])): ?>

                                                        <button
                                                            id="clearSearch"
                                                            class="btn btn-outline-secondary"
                                                            type="button">

                                                            <i class="bi bi-x"></i>
                                                            Clear

                                                        </button>

                                                    <?php endif; ?>

                                                </div>

                                            </div>

                                        </div>


                                        <div id="productList">
                                        </div>


                                        <div id="paginationContainer">
                                        </div>

                                    </div>


                                    <!-- =================================================
                                         CART
                                    ================================================== -->

                                    <div class="col-md-6">

                                        <div class="right-section">

                                            <h4>
                                                Cart Summary
                                            </h4>


                                            <ul
                                                id="cartList"
                                                class="list-group mb-3">
                                            </ul>


                                            <h5>

                                                Total:

                                                <span
                                                    id="cartTotal"
                                                    class="text-success">

                                                    ₹0

                                                </span>

                                            </h5>


                                            <button
                                                type="button"
                                                class="btn btn-success w-100 mt-3"
                                                onclick="placeOrder()">

                                                Place Order

                                            </button>

                                        </div>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </section>

    </main>


    <!-- =========================================================
         CHECKOUT MODAL
    ========================================================== -->

    <div
        class="modal fade"
        id="orderConfirmationModal"
        tabindex="-1"
        aria-labelledby="orderConfirmationModalLabel"
        aria-hidden="true"
        data-bs-backdrop="static"
        data-bs-keyboard="false">

        <div class="modal-dialog modal-lg">

            <div class="modal-content">


                <div class="modal-header">

                    <h5
                        class="modal-title"
                        id="orderConfirmationModalLabel">

                        Checkout Page

                    </h5>


                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close">
                    </button>

                </div>


                <form id="orderForm">

                    <div class="modal-body">


                        <!-- =================================================
                             ORDER SUMMARY
                        ================================================== -->

                        <div class="row mb-4">

                            <div class="col-md-12">

                                <h5>
                                    Order Summary
                                </h5>


                                <div class="table-responsive">

                                    <table class="table table-bordered">

                                        <thead>

                                            <tr>

                                                <th>
                                                    Item
                                                </th>

                                                <th>
                                                    Unit #
                                                </th>

                                                <th>
                                                    Unit Price
                                                </th>

                                                <th>
                                                    Discount %
                                                </th>

                                                <th>
                                                    Total
                                                </th>

                                                <th>
                                                    Action
                                                </th>

                                            </tr>

                                        </thead>


                                        <tbody id="orderSummaryBody">
                                        </tbody>


                                        <tfoot>

                                            <tr>

                                                <th
                                                    colspan="4"
                                                    class="text-end">

                                                    Total:

                                                </th>


                                                <th id="orderTotal">
                                                    ₹0.00
                                                </th>


                                                <th></th>

                                            </tr>

                                        </tfoot>

                                    </table>

                                </div>

                            </div>

                        </div>


                        <!-- =================================================
                             BENEFICIARY + PAYMENT
                        ================================================== -->

                        <div class="row">

                            <div class="col-md-6">

                                <label
                                    for="beneficiarySelect"
                                    class="form-label">

                                    Search and Select Beneficiaries

                                </label>


                                <select
                                    id="beneficiarySelect"
                                    name="beneficiaries"
                                    class="form-select js-data-ajax-multiple"
                                    multiple="multiple"
                                    required>
                                </select>


                                <div class="form-text text-muted">

                                    First-time user?

                                    <a
                                        href="register_beneficiary.php"
                                        target="_blank">

                                        Register here

                                    </a>

                                </div>


                                <div class="invalid-feedback">

                                    Please select at least one beneficiary.

                                </div>

                            </div>


                            <div class="col-md-6">

                                <div
                                    id="multipleBeneficiaryWarning"
                                    class="alert alert-danger mt-2"
                                    style="display:none;">

                                    You have selected multiple beneficiaries.

                                    Please collect ₹
                                    <span id="totalCollectionAmount">0</span>

                                    (₹
                                    <span id="orderTotalPerBeneficiary">0</span>
                                    ×
                                    <span id="beneficiaryCount">0</span>
                                    beneficiaries).

                                </div>


                                <label
                                    for="paymentMode"
                                    class="form-label">

                                    Payment Mode

                                </label>


                                <select
                                    id="paymentMode"
                                    class="form-select"
                                    required>

                                    <option value="">
                                        Select Payment Mode
                                    </option>

                                    <option value="cash">
                                        Cash
                                    </option>

                                    <option value="online">
                                        Online Payment
                                    </option>

                                    <option value="freebie">
                                        Freebies (no payment required)
                                    </option>

                                </select>

                            </div>

                        </div>


                        <!-- =================================================
                             TRANSACTION ID
                        ================================================== -->

                        <div class="row mt-3">

                            <div
                                class="col-md-6"
                                id="transactionIdContainer"
                                style="display:none;">

                                <label
                                    for="transactionId"
                                    class="form-label">

                                    Transaction ID

                                </label>


                                <input
                                    type="text"
                                    id="transactionId"
                                    class="form-control"
                                    placeholder="Enter transaction ID">


                                <div class="form-text text-muted">

                                    Record payment for all selected purchases in one go.

                                    <a
                                        href="https://secure.paytmpayments.com/link/paymentForm/47760/LL_790393889"
                                        target="_blank">

                                        Quick Payment

                                    </a>

                                </div>

                            </div>

                        </div>


                        <!-- =================================================
                             REMARKS
                        ================================================== -->

                        <div class="row mt-3">

                            <div class="col-md-12">

                                <label
                                    for="remarks"
                                    class="form-label">

                                    Remarks

                                </label>


                                <textarea
                                    id="remarks"
                                    class="form-control"
                                    rows="2"
                                    placeholder="Any additional remarks"></textarea>

                            </div>

                        </div>

                    </div>


                    <!-- =================================================
                         FOOTER
                    ================================================== -->

                    <div class="modal-footer">

                        <button
                            type="button"
                            class="btn btn-secondary"
                            data-bs-dismiss="modal">

                            Cancel

                        </button>


                        <button
                            type="submit"
                            id="submitOrderBtn"
                            class="btn btn-primary">

                            Checkout

                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>


    <!-- =========================================================
         SUBMISSION OVERLAY

         IMPORTANT:
         This is NOT a Bootstrap modal.

         It gives immediate:
         BACKDROP + SPINNER + MESSAGE
    ========================================================== -->

    <div
        id="submissionOverlay"
        aria-hidden="true">

        <div id="submissionDialog">

            <div
                class="spinner-border"
                role="status">

                <span class="visually-hidden">
                    Loading...
                </span>

            </div>


            <p id="submissionMessage">

                Submission in progress.
                Please do not close or reload this page.

            </p>

        </div>

    </div>


    <!-- =========================================================
         BACK TO TOP
    ========================================================== -->

    <a
        href="#"
        class="back-to-top d-flex align-items-center justify-content-center">

        <i class="bi bi-arrow-up-short"></i>

    </a>


    <!-- =========================================================
         MAIN JS
         Include ONLY ONCE
    ========================================================== -->

    <script src="../assets_new/js/main.js"></script>


    <!-- =========================================================
         CUSTOM JAVASCRIPT
    ========================================================== -->

    <script>
        /* =========================================================
           GLOBAL VARIABLES
        ========================================================== */

        let pageNumber =
            <?php
            echo isset($_GET['page'])
                ? max(1, intval($_GET['page']))
                : (isset($_SESSION['emart_page'])
                    ? $_SESSION['emart_page']
                    : 1);
            ?>;


        let currentSearchTerm =
            <?php
            echo json_encode(
                isset($_GET['search'])
                    ? $_GET['search']
                    : (isset($_SESSION['emart_search'])
                        ? $_SESSION['emart_search']
                        : '')
            );
            ?>;


        let itemsPerPage =
            <?php
            echo isset($_GET['itemsPerPage'])
                ? max(5, min(100, intval($_GET['itemsPerPage'])))
                : (isset($_SESSION['emart_items_per_page'])
                    ? $_SESSION['emart_items_per_page']
                    : 5);
            ?>;


        let totalPages = 1;

        let cart = [];

        let products = [];

        let isCheckoutModalOpen = false;


        /* =========================================================
           DOM READY
        ========================================================== */

        document.addEventListener('DOMContentLoaded', function() {

            const urlParams =
                new URLSearchParams(window.location.search);


            if (urlParams.has('itemsPerPage')) {

                itemsPerPage =
                    parseInt(
                        urlParams.get('itemsPerPage')
                    );

                sessionStorage.setItem(
                    'emartItemsPerPage',
                    itemsPerPage
                );

            } else if (
                sessionStorage.getItem('emartItemsPerPage')
            ) {

                itemsPerPage =
                    parseInt(
                        sessionStorage.getItem(
                            'emartItemsPerPage'
                        )
                    );
            }


            document.getElementById(
                'itemsPerPage'
            ).value = itemsPerPage;


            /* =====================================================
               ITEMS PER PAGE
            ====================================================== */

            document
                .getElementById('itemsPerPage')
                .addEventListener('change', function() {

                    itemsPerPage =
                        parseInt(this.value);


                    sessionStorage.setItem(
                        'emartItemsPerPage',
                        itemsPerPage
                    );


                    fetch('update_session.php', {

                        method: 'POST',

                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },

                        body: `itemsPerPage=${itemsPerPage}`

                    });


                    loadProducts(
                        1,
                        currentSearchTerm
                    );

                });


            /* =====================================================
               INITIAL PRODUCTS
            ====================================================== */

            loadProducts(
                pageNumber,
                currentSearchTerm
            );


            /* =====================================================
               SEARCH
            ====================================================== */

            document
                .getElementById('searchButton')
                .addEventListener('click', function() {

                    const searchTerm =
                        document
                        .getElementById('searchInput')
                        .value
                        .trim();


                    loadProducts(
                        1,
                        searchTerm
                    );

                });


            /* =====================================================
               CLEAR SEARCH
            ====================================================== */

            const clearSearchBtn =
                document.getElementById(
                    'clearSearch'
                );


            if (clearSearchBtn) {

                clearSearchBtn.addEventListener(
                    'click',
                    function() {

                        document
                            .getElementById(
                                'searchInput'
                            )
                            .value = '';


                        loadProducts(
                            1,
                            ''
                        );

                    }
                );
            }


            /* =====================================================
               SEARCH ENTER
            ====================================================== */

            document
                .getElementById('searchInput')
                .addEventListener(
                    'keypress',
                    function(e) {

                        if (e.key === 'Enter') {

                            const searchTerm =
                                this.value.trim();


                            loadProducts(
                                1,
                                searchTerm
                            );
                        }

                    }
                );


            /* =====================================================
               BROWSER BACK / FORWARD
            ====================================================== */

            window.addEventListener(
                'popstate',
                function() {

                    const params =
                        new URLSearchParams(
                            window.location.search
                        );


                    const page =
                        parseInt(
                            params.get('page')
                        ) || 1;


                    const searchTerm =
                        params.get('search') || '';


                    const newItemsPerPage =
                        parseInt(
                            params.get('itemsPerPage')
                        ) || 5;


                    itemsPerPage =
                        newItemsPerPage;


                    document
                        .getElementById(
                            'itemsPerPage'
                        )
                        .value =
                        itemsPerPage;


                    document
                        .getElementById(
                            'searchInput'
                        )
                        .value =
                        searchTerm;


                    loadProducts(
                        page,
                        searchTerm
                    );

                }
            );


            /* =====================================================
               CHECKOUT MODAL EVENTS
            ====================================================== */

            const checkoutModalElement =
                document.getElementById(
                    'orderConfirmationModal'
                );


            if (checkoutModalElement) {

                checkoutModalElement.addEventListener(
                    'shown.bs.modal',
                    function() {

                        isCheckoutModalOpen = true;

                        initialiseBeneficiarySelect();

                    }
                );


                checkoutModalElement.addEventListener(
                    'hidden.bs.modal',
                    function() {

                        isCheckoutModalOpen = false;


                        const select =
                            $('#beneficiarySelect');


                        if (
                            select.hasClass(
                                'select2-hidden-accessible'
                            )
                        ) {

                            select.select2(
                                'destroy'
                            );
                        }


                        document
                            .getElementById(
                                'orderForm'
                            )
                            .classList
                            .remove(
                                'was-validated'
                            );

                    }
                );

            }


            /* =====================================================
               PAYMENT MODE
            ====================================================== */

            $('#paymentMode').on(
                'change',
                function() {

                    const paymentMode =
                        $(this).val();


                    if (
                        paymentMode === 'online'
                    ) {

                        $('#transactionIdContainer')
                            .show();


                        $('#transactionId')
                            .prop(
                                'required',
                                true
                            );

                    } else {

                        $('#transactionIdContainer')
                            .hide();


                        $('#transactionId')
                            .prop(
                                'required',
                                false
                            );


                        $('#transactionId')
                            .val('');

                    }


                    updateBeneficiaryWarning();

                }
            );


            /* =====================================================
               BENEFICIARY CHANGE
            ====================================================== */

            $('#beneficiarySelect').on(
                'change',
                function() {

                    updateBeneficiaryWarning();

                }
            );


            /* =====================================================
               ORDER FORM SUBMIT
            ====================================================== */

            $('#orderForm').on(
                'submit',
                function(e) {

                    e.preventDefault();


                    const form = this;


                    /* Browser validation */

                    if (!form.checkValidity()) {

                        e.stopPropagation();

                        form.classList.add(
                            'was-validated'
                        );

                        return;
                    }


                    /* Beneficiary validation */

                    const beneficiaries =
                        $('#beneficiarySelect').val() ||
                        [];


                    if (
                        beneficiaries.length === 0
                    ) {

                        $('#beneficiarySelect')
                            .addClass(
                                'is-invalid'
                            );

                        return;

                    }


                    $('#beneficiarySelect')
                        .removeClass(
                            'is-invalid'
                        );


                    /* Cart validation */

                    if (
                        cart.length === 0
                    ) {

                        alert(
                            'Your cart is empty.'
                        );

                        closeCheckoutModal();

                        return;

                    }


                    /* Disable button immediately */

                    $('#submitOrderBtn')
                        .prop(
                            'disabled',
                            true
                        );


                    /*
                     * =================================================
                     * SHOW SUBMISSION OVERLAY IMMEDIATELY
                     *
                     * No Bootstrap modal is used here.
                     *
                     * Therefore:
                     * backdrop + spinner + message
                     * appear together immediately.
                     * =================================================
                     */

                    showSubmissionOverlay();


                    /* =================================================
                       PAYMENT DATA
                    ================================================== */

                    const paymentMode =
                        $('#paymentMode').val();


                    const transactionId =
                        paymentMode === 'online' ?
                        $('#transactionId')
                        .val()
                        .trim() :
                        '';


                    const remarks =
                        $('#remarks')
                        .val()
                        .trim();


                    /* =================================================
                       BUILD CART DATA
                    ================================================== */

                    const cartData = [];


                    cart.forEach(function(item) {

                        if (item.isFixedPrice) {

                            cartData.push({

                                productId: item.id,

                                count: item.count,

                                productPoints: (
                                    item.price *
                                    item.count
                                ),

                                customPrice: null,

                                discount: 0,

                                isFixedPrice: true

                            });

                        } else {

                            /*
                             * Dynamic products:
                             * EACH UNIT is submitted separately.
                             */

                            const units =
                                item.units || [];


                            units.forEach(
                                function(unitData, index) {

                                    const price =
                                        parseFloat(
                                            unitData.price
                                        ) || 0;


                                    const discount =
                                        parseFloat(
                                            unitData.discount
                                        ) || 0;


                                    const finalPrice =
                                        price *
                                        (
                                            1 -
                                            discount / 100
                                        );


                                    cartData.push({

                                        productId: item.id,

                                        count: 1,

                                        productPoints: finalPrice,

                                        customPrice: price,

                                        discount: discount,

                                        isFixedPrice: false,

                                        unitNumber: index + 1

                                    });

                                }
                            );

                        }

                    });


                    /* =================================================
                       TOTAL
                    ================================================== */

                    const totalPoints =
                        cartData.reduce(
                            function(sum, item) {

                                return sum +
                                    (
                                        parseFloat(
                                            item.productPoints
                                        ) || 0
                                    );

                            },
                            0
                        );


                    /* =================================================
                       ORDER DATA
                    ================================================== */

                    const orderData =
                        new URLSearchParams({

                            'form-type': 'orders',

                            'associatenumber': "<?php echo htmlspecialchars($associatenumber, ENT_QUOTES); ?>",

                            'fullname': "<?php echo htmlspecialchars($fullname, ENT_QUOTES); ?>",

                            'doj': "<?php echo htmlspecialchars($doj, ENT_QUOTES); ?>",

                            'email': "<?php echo htmlspecialchars($email, ENT_QUOTES); ?>",

                            'totalPoints': totalPoints.toFixed(2),

                            'cart': JSON.stringify(
                                cartData
                            ),

                            'paymentMode': paymentMode,

                            'transactionId': transactionId,

                            'remarks': remarks,

                            'beneficiaries': JSON.stringify(
                                beneficiaries
                            )

                        });


                    /* =================================================
                       SEND ORDER
                    ================================================== */

                    fetch(
                            'process_order.php', {

                                method: 'POST',

                                headers: {

                                    'Content-Type': 'application/x-www-form-urlencoded'

                                },

                                body: orderData

                            }
                        )

                        .then(
                            function(response) {

                                if (!response.ok) {

                                    throw new Error(
                                        'Server returned HTTP ' +
                                        response.status
                                    );

                                }


                                return response.json();

                            }
                        )

                        .then(
                            function(data) {

                                console.log(
                                    'Order response:',
                                    data
                                );


                                if (
                                    data.status ===
                                    'success'
                                ) {

                                    /*
                                     * =================================================
                                     * SUCCESS
                                     *
                                     * DO NOT REMOVE OVERLAY YET.
                                     *
                                     * First show:
                                     *
                                     * "Order placed successfully"
                                     *
                                     * User clicks OK.
                                     *
                                     * ONLY THEN:
                                     * 1. Remove submission overlay
                                     * 2. Remove checkout modal
                                     * 3. Remove Bootstrap backdrop
                                     * 4. Redirect
                                     * =================================================
                                     */

                                    const successMessage =
                                        data.message ||
                                        'Order placed successfully.';


                                    alert(
                                        successMessage
                                    );


                                    /*
                                     * User clicked OK.
                                     * Now remove everything.
                                     */

                                    hideSubmissionOverlay();


                                    closeCheckoutModal(
                                        function() {

                                            /*
                                             * Extra cleanup only after
                                             * Bootstrap has finished hiding.
                                             */

                                            cleanupModalArtifacts();


                                            /*
                                             * Redirect AFTER cleanup.
                                             */

                                            window.location.href =
                                                'order_confirmation.php?id=' +
                                                encodeURIComponent(
                                                    data.order_id
                                                );

                                        }
                                    );


                                } else {

                                    /*
                                     * Server returned an error.
                                     * Remove only submission overlay.
                                     * Keep checkout modal open so user
                                     * can correct/retry.
                                     */

                                    hideSubmissionOverlay();


                                    $('#submitOrderBtn')
                                        .prop(
                                            'disabled',
                                            false
                                        );


                                    alert(
                                        data.message ||
                                        'Unable to place the order.'
                                    );

                                }

                            }
                        )

                        .catch(
                            function(error) {

                                console.error(
                                    'Order submission error:',
                                    error
                                );


                                /*
                                 * Remove submission overlay
                                 * but keep checkout modal open.
                                 */

                                hideSubmissionOverlay();


                                $('#submitOrderBtn')
                                    .prop(
                                        'disabled',
                                        false
                                    );


                                alert(
                                    'An error occurred while placing the order. Please try again.'
                                );

                            }
                        );

                }
            );


            /* =====================================================
               INITIAL CART
            ====================================================== */

            renderCart();

            updateCartCount();

        });


        /* =========================================================
           SUBMISSION OVERLAY
        ========================================================== */

        function showSubmissionOverlay() {

            const overlay =
                document.getElementById(
                    'submissionOverlay'
                );


            if (!overlay) {
                return;
            }


            overlay.classList.add('show');

            overlay.setAttribute(
                'aria-hidden',
                'false'
            );


            /*
             * Prevent background scrolling.
             */

            document.body.style.overflow =
                'hidden';

        }


        function hideSubmissionOverlay() {

            const overlay =
                document.getElementById(
                    'submissionOverlay'
                );


            if (!overlay) {
                return;
            }


            overlay.classList.remove('show');

            overlay.setAttribute(
                'aria-hidden',
                'true'
            );


            /*
             * Do NOT remove body modal state here.
             * Bootstrap checkout modal may still be open.
             */

        }


        /* =========================================================
           LOAD PRODUCTS
        ========================================================== */

        function loadProducts(
            page = 1,
            searchTerm = ''
        ) {

            const productList =
                document.getElementById(
                    'productList'
                );


            productList.innerHTML = `

                <div class="text-center my-5">

                    <div
                        class="spinner-border"
                        role="status">

                        <span class="visually-hidden">
                            Loading...
                        </span>

                    </div>

                </div>

            `;


            updateUrl(
                page,
                searchTerm
            );


            pageNumber =
                parseInt(page);


            currentSearchTerm =
                searchTerm;


            return fetch(
                    `search_products.php?page=${page}` +
                    `&search=${encodeURIComponent(searchTerm)}` +
                    `&itemsPerPage=${itemsPerPage}`
                )

                .then(
                    function(response) {

                        if (!response.ok) {

                            throw new Error(
                                'Network response was not ok'
                            );

                        }


                        return response.json();

                    }
                )

                .then(
                    function(data) {

                        products =
                            data.products || [];


                        totalPages =
                            parseInt(
                                data.totalPages
                            ) || 1;


                        renderProducts(
                            products
                        );


                        renderPagination();


                        return data;

                    }
                )

                .catch(
                    function(error) {

                        console.error(
                            'Error loading products:',
                            error
                        );


                        productList.innerHTML = `

                        <div class="alert alert-danger">

                            Error loading products.
                            Please try again.

                        </div>

                    `;


                        throw error;

                    }
                );

        }


        /* =========================================================
           UPDATE URL
        ========================================================== */

        function updateUrl(
            page,
            searchTerm
        ) {

            const urlParams =
                new URLSearchParams();


            if (page > 1) {

                urlParams.set(
                    'page',
                    page
                );

            }


            if (searchTerm) {

                urlParams.set(
                    'search',
                    searchTerm
                );

            }


            if (itemsPerPage != 5) {

                urlParams.set(
                    'itemsPerPage',
                    itemsPerPage
                );

            }


            history.pushState(
                null,
                '',
                urlParams.toString() ?
                `?${urlParams}` :
                window.location.pathname
            );

        }


        /* =========================================================
           RENDER PRODUCTS
        ========================================================== */

        function renderProducts(
            productsList
        ) {

            const productList =
                document.getElementById(
                    'productList'
                );


            productList.innerHTML = '';


            if (!productsList.length) {

                productList.innerHTML = `

                    <div class="alert alert-info">

                        No products found matching your search.

                    </div>

                `;

                return;

            }


            productsList.forEach(
                function(product) {

                    const cartItem =
                        cart.find(
                            item =>
                            item.id == product.id
                        );


                    const currentQuantity =
                        cartItem ?
                        cartItem.count :
                        0;


                    const hasDiscount =
                        parseFloat(
                            product.discount_percentage
                        ) > 0;


                    const originalPrice =
                        parseFloat(
                            product.original_price
                        ) || 0;


                    const productPrice =
                        parseFloat(
                            product.price
                        ) || 0;


                    const discountPercentage =
                        parseFloat(
                            product.discount_percentage
                        ) || 0;


                    const displayPrice =
                        hasDiscount ?
                        (
                            originalPrice *
                            (
                                1 -
                                discountPercentage / 100
                            )
                        ).toFixed(2) :
                        productPrice.toFixed(2);


                    const stock =
                        parseInt(
                            product.in_stock
                        ) || 0;


                    const stockStatus =
                        stock <= 0;


                    const lowStock =
                        stock > 0 &&
                        stock <= 5;


                    const isFixed =
                        product.is_fixed_price == true ||
                        product.is_fixed_price == 1;


                    const priceTypeBadge =
                        isFixed

                        ?
                        `
                                <span class="fixed-price-badge">
                                    Fixed Price
                                </span>
                              `

                        :
                        `
                                <span class="dynamic-price-badge">
                                    Dynamic Price
                                </span>
                              `;


                    const productCard =
                        document.createElement(
                            'div'
                        );


                    productCard.className =
                        'product-card mb-4 p-3 border rounded bg-white';


                    productCard.innerHTML = `

                        <div class="d-flex">

                            <div
                                class="col-6 me-3"
                                style="height:150px;">

                                <img
                                    src="${escapeHtml(product.image)}"
                                    alt="${escapeHtml(product.name)}"
                                    class="img-fluid h-100 w-100 object-fit-cover rounded">

                            </div>


                            <div class="flex-grow-1">

                                <h5 class="mb-1">

                                    ${escapeHtml(product.name)}

                                </h5>


                                <small class="text-muted">

                                    Product Id- ${product.id}

                                </small>


                                ${priceTypeBadge}


                                ${
                                    parseFloat(product.rating) > 0

                                    ? `

                                        <div class="d-flex align-items-center mb-1">

                                            <div class="text-warning">

                                                ${
                                                    '★'.repeat(
                                                        Math.round(
                                                            parseFloat(
                                                                product.rating
                                                            )
                                                        )
                                                    )
                                                }

                                                ${
                                                    '☆'.repeat(
                                                        5 -
                                                        Math.round(
                                                            parseFloat(
                                                                product.rating
                                                            )
                                                        )
                                                    )
                                                }

                                            </div>


                                            <small class="text-muted ms-2">

                                                ${product.review_count || 0}
                                                reviews

                                            </small>

                                        </div>

                                      `

                                    : ''
                                }


                                ${
                                    product.description

                                    ? `

                                        <p
                                            class="text-muted small mb-2"
                                            style="
                                                display:-webkit-box;
                                                -webkit-line-clamp:2;
                                                -webkit-box-orient:vertical;
                                                overflow:hidden;
                                            ">

                                            ${escapeHtml(
                                                product.description
                                            )}

                                        </p>

                                      `

                                    : ''
                                }


                                <div class="mb-2">

                                    ${
                                        hasDiscount

                                        ? `

                                            <span class="text-danger fs-5 fw-bold">

                                                ₹${displayPrice}

                                            </span>


                                            <span class="text-decoration-line-through text-muted ms-2">

                                                ₹${originalPrice.toFixed(2)}

                                            </span>


                                            <span class="badge bg-danger ms-2">

                                                ${discountPercentage}% off

                                            </span>

                                          `

                                        : `

                                            <span class="fs-5 fw-bold">

                                                ₹${displayPrice}

                                            </span>

                                          `
                                    }


                                    <span class="text-muted">

                                        for ${product.unit_quantity}
                                        ${escapeHtml(product.unit_name)}

                                    </span>


                                    ${
                                        !isFixed

                                        ? `

                                            <br>

                                            <small class="text-warning">

                                                Price can be set at checkout

                                            </small>

                                          `

                                        : ''
                                    }

                                </div>


                                ${
                                    stockStatus

                                    ? `

                                        <div class="text-danger mb-2">

                                            Out of Stock

                                        </div>

                                      `

                                    : lowStock

                                    ? `

                                        <div class="text-danger mb-2">

                                            Only ${stock} left in stock

                                        </div>


                                        ${quantityControl(
                                            product,
                                            currentQuantity,
                                            stock
                                        )}

                                      `

                                    : `

                                        <div class="text-success mb-2">

                                            In Stock (${stock} available)

                                        </div>


                                        ${quantityControl(
                                            product,
                                            currentQuantity,
                                            stock
                                        )}

                                      `
                                }

                            </div>

                        </div>

                    `;


                    productList.appendChild(
                        productCard
                    );

                }
            );

        }


        /* =========================================================
           QUANTITY CONTROL
        ========================================================== */

        function quantityControl(
            product,
            currentQuantity,
            stock
        ) {

            return `

                <div class="btn-quantity d-flex align-items-center">

                    <button
                        class="btn btn-sm btn-outline-secondary"
                        type="button"
                        onclick="decreaseCount(${product.id})">

                        <i class="bi bi-dash"></i>

                    </button>


                    <input
                        type="number"
                        id="count${product.id}"
                        class="form-control mx-2 text-center stock-input"
                        value="${currentQuantity}"
                        min="0"
                        max="${stock}"
                        onchange="validateQuantityInput(${product.id})"
                        oninput="validateQuantityInput(${product.id})"
                        style="width:60px;">


                    <button
                        class="btn btn-sm btn-primary"
                        type="button"
                        onclick="increaseCount(${product.id})">

                        <i class="bi bi-plus"></i>

                    </button>

                </div>

            `;

        }


        /* =========================================================
           PAGINATION
        ========================================================== */

        function renderPagination() {

            const container =
                document.getElementById(
                    'paginationContainer'
                );


            container.innerHTML = '';


            if (totalPages <= 1) {
                return;
            }


            let html = `

                <nav aria-label="Page navigation">

                    <ul class="pagination justify-content-center">

                        <li class="page-item ${
                            pageNumber === 1
                                ? 'disabled'
                                : ''
                        }">

                            <a
                                class="page-link"
                                href="#"
                                onclick="
                                    loadProducts(
                                        ${pageNumber - 1},
                                        currentSearchTerm
                                    );
                                    return false;
                                ">

                                &laquo;

                            </a>

                        </li>

            `;


            for (
                let i = 1; i <= totalPages; i++
            ) {

                html += `

                    <li class="page-item ${
                        i === pageNumber
                            ? 'active'
                            : ''
                    }">

                        <a
                            class="page-link"
                            href="#"
                            onclick="
                                loadProducts(
                                    ${i},
                                    currentSearchTerm
                                );
                                return false;
                            ">

                            ${i}

                        </a>

                    </li>

                `;

            }


            html += `

                        <li class="page-item ${
                            pageNumber >= totalPages
                                ? 'disabled'
                                : ''
                        }">

                            <a
                                class="page-link"
                                href="#"
                                onclick="
                                    loadProducts(
                                        ${pageNumber + 1},
                                        currentSearchTerm
                                    );
                                    return false;
                                ">

                                &raquo;

                            </a>

                        </li>

                    </ul>

                </nav>

            `;


            container.innerHTML =
                html;

        }


        /* =========================================================
           CART UPDATE
        ========================================================== */

        function updateCart(
            productId,
            productName,
            price,
            count,
            unitName,
            isFixedPrice
        ) {

            const id =
                parseInt(productId);


            const fixed =
                isFixedPrice == true ||
                isFixedPrice == 1;


            const existingIndex =
                cart.findIndex(
                    item =>
                    item.id == id
                );


            if (count > 0) {

                let units = [];


                /*
                 * Preserve already entered dynamic
                 * prices when quantity changes.
                 */

                if (
                    !fixed &&
                    existingIndex >= 0
                ) {

                    const oldUnits =
                        cart[existingIndex].units || [];


                    for (
                        let i = 0; i < count; i++
                    ) {

                        if (oldUnits[i]) {

                            units.push({
                                price: parseFloat(
                                    oldUnits[i].price
                                ) || 0,

                                discount: parseFloat(
                                    oldUnits[i].discount
                                ) || 0
                            });

                        } else {

                            units.push({

                                price: parseFloat(price) || 0,

                                discount: 0

                            });

                        }

                    }

                } else if (!fixed) {

                    for (
                        let i = 0; i < count; i++
                    ) {

                        units.push({

                            price: parseFloat(price) || 0,

                            discount: 0

                        });

                    }

                }


                const cartItem = {

                    id: id,

                    name: productName,

                    price: parseFloat(price) || 0,

                    count: parseInt(count),

                    unitName: unitName,

                    isFixedPrice: fixed,

                    units: units

                };


                if (
                    existingIndex >= 0
                ) {

                    cart[existingIndex] =
                        cartItem;

                } else {

                    cart.push(
                        cartItem
                    );

                }

            } else {

                if (
                    existingIndex >= 0
                ) {

                    cart.splice(
                        existingIndex,
                        1
                    );

                }

            }


            renderCart();

            updateCartCount();

        }


        /* =========================================================
           RENDER CART
        ========================================================== */

        function renderCart() {

            const cartList =
                document.getElementById(
                    'cartList'
                );


            const cartTotal =
                document.getElementById(
                    'cartTotal'
                );


            cartList.innerHTML = '';


            let total = 0;

            let hasDynamicItems = false;


            cart.forEach(
                function(item) {

                    const isFixed =
                        item.isFixedPrice;


                    let itemTotal = 0;

                    let priceDisplay = '';


                    if (isFixed) {

                        itemTotal =
                            item.price *
                            item.count;


                        total +=
                            itemTotal;


                        priceDisplay =
                            `₹${item.price.toFixed(2)}`;

                    } else {

                        hasDynamicItems =
                            true;


                        priceDisplay = `

                            <span class="price-to-be-set">

                                Price to be set at checkout

                            </span>

                        `;

                    }


                    const listItem =
                        document.createElement(
                            'li'
                        );


                    listItem.className =
                        `list-group-item
                         d-flex
                         justify-content-between
                         align-items-center
                         ${
                            isFixed
                                ? 'cart-item-fixed'
                                : 'cart-item-dynamic'
                         }`;


                    listItem.innerHTML = `

                        <div>

                            <div>

                                ${escapeHtml(item.name)}

                            </div>


                            <small class="text-muted">

                                ${item.count}
                                ×
                                ${priceDisplay}


                                ${
                                    !isFixed

                                    ? `

                                        <span class="badge bg-warning text-dark">

                                            Dynamic

                                        </span>

                                      `

                                    : ''
                                }

                            </small>

                        </div>


                        <div>

                            ${
                                isFixed

                                ? `

                                    <span class="me-3">

                                        ₹${itemTotal.toFixed(2)}

                                    </span>

                                  `

                                : ''
                            }


                            <button
                                type="button"
                                class="btn btn-sm btn-outline-danger"
                                onclick="removeFromCart(${item.id})">

                                <i class="bi bi-x"></i>

                            </button>

                        </div>

                    `;


                    cartList.appendChild(
                        listItem
                    );

                }
            );


            if (hasDynamicItems) {

                const noteItem =
                    document.createElement(
                        'li'
                    );


                noteItem.className =
                    'list-group-item text-warning bg-light';


                noteItem.innerHTML = `

                    <small>

                        <i class="bi bi-info-circle"></i>

                        Dynamic item prices will be set
                        at checkout.

                    </small>

                `;


                cartList.appendChild(
                    noteItem
                );

            }


            cartTotal.textContent =
                `₹${total.toFixed(2)}`;

        }


        /* =========================================================
           REMOVE FROM MAIN CART
        ========================================================== */

        function removeFromCart(
            productId
        ) {

            const id =
                parseInt(productId);


            const index =
                cart.findIndex(
                    item =>
                    item.id == id
                );


            if (index === -1) {
                return;
            }


            cart.splice(
                index,
                1
            );


            renderCart();

            updateCartCount();

            renderProducts(products);

        }


        /* =========================================================
           CART COUNT
        ========================================================== */

        function updateCartCount() {

            const totalItems =
                cart.reduce(
                    function(sum, item) {

                        return sum +
                            item.count;

                    },
                    0
                );


            document
                .querySelectorAll(
                    '.cart-count'
                )
                .forEach(
                    function(element) {

                        element.textContent =
                            totalItems;


                        element.style.display =
                            totalItems > 0 ?
                            'inline-block' :
                            'none';

                    }
                );

        }


        /* =========================================================
           INCREASE
        ========================================================== */

        function increaseCount(
            productId
        ) {

            const input =
                document.getElementById(
                    `count${productId}`
                );


            if (!input) {
                return;
            }


            const currentCount =
                parseInt(
                    input.value
                ) || 0;


            const product =
                products.find(
                    p =>
                    p.id == productId
                );


            if (!product) {
                return;
            }


            const stock =
                parseInt(
                    product.in_stock
                ) || 0;


            if (
                currentCount < stock
            ) {

                const newCount =
                    currentCount + 1;


                input.value =
                    newCount;


                updateCart(
                    product.id,
                    product.name,
                    parseFloat(
                        product.price
                    ) || 0,
                    newCount,
                    product.unit_name,
                    product.is_fixed_price
                );

            } else {

                alert(
                    `You cannot order more than ${stock} items of this product.`
                );

            }

        }


        /* =========================================================
           DECREASE
        ========================================================== */

        function decreaseCount(
            productId
        ) {

            const input =
                document.getElementById(
                    `count${productId}`
                );


            if (!input) {
                return;
            }


            const currentCount =
                parseInt(
                    input.value
                ) || 0;


            const product =
                products.find(
                    p =>
                    p.id == productId
                );


            if (!product) {
                return;
            }


            if (
                currentCount > 0
            ) {

                const newCount =
                    currentCount - 1;


                input.value =
                    newCount;


                updateCart(
                    product.id,
                    product.name,
                    parseFloat(
                        product.price
                    ) || 0,
                    newCount,
                    product.unit_name,
                    product.is_fixed_price
                );

            }

        }


        /* =========================================================
           VALIDATE QUANTITY
        ========================================================== */

        function validateQuantityInput(
            productId
        ) {

            const input =
                document.getElementById(
                    `count${productId}`
                );


            const product =
                products.find(
                    p =>
                    p.id == productId
                );


            if (
                !input ||
                !product
            ) {
                return;
            }


            let value =
                parseInt(
                    input.value
                );


            if (isNaN(value)) {
                value = 0;
            }


            const stock =
                parseInt(
                    product.in_stock
                ) || 0;


            if (value < 0) {
                value = 0;
            }


            if (value > stock) {

                value = stock;


                alert(
                    `You cannot order more than ${stock} items of this product.`
                );

            }


            input.value =
                value;


            updateCart(
                product.id,
                product.name,
                parseFloat(
                    product.price
                ) || 0,
                value,
                product.unit_name,
                product.is_fixed_price
            );

        }


        /* =========================================================
           PLACE ORDER
        ========================================================== */

        function placeOrder() {

            console.log(
                'Place Order clicked. Cart:',
                cart
            );


            if (
                !cart ||
                cart.length === 0
            ) {

                alert(
                    'Your cart is empty!'
                );

                return;

            }


            /*
             * Render checkout.
             */

            renderCheckoutSummary();


            /*
             * Reset checkout form.
             */

            $('#beneficiarySelect')
                .val(null)
                .trigger('change');


            $('#paymentMode')
                .val('');


            $('#transactionId')
                .val('');


            $('#transactionIdContainer')
                .hide();


            $('#remarks')
                .val('');


            $('#submitOrderBtn')
                .prop(
                    'disabled',
                    false
                );


            document
                .getElementById(
                    'orderForm'
                )
                .classList
                .remove(
                    'was-validated'
                );


            /*
             * Open Bootstrap checkout modal.
             */

            const modalElement =
                document.getElementById(
                    'orderConfirmationModal'
                );


            if (!modalElement) {

                console.error(
                    'orderConfirmationModal not found.'
                );

                return;

            }


            try {

                const checkoutModal =
                    bootstrap.Modal.getOrCreateInstance(
                        modalElement, {
                            backdrop: 'static',
                            keyboard: false
                        }
                    );


                isCheckoutModalOpen =
                    true;


                checkoutModal.show();


                console.log(
                    'Checkout modal opened.'
                );

            } catch (error) {

                console.error(
                    'Unable to open checkout modal:',
                    error
                );


                alert(
                    'Unable to open checkout page. Please refresh the page and try again.'
                );

            }

        }


        /* =========================================================
           RENDER CHECKOUT SUMMARY
        ========================================================== */

        function renderCheckoutSummary() {

            const body =
                document.getElementById(
                    'orderSummaryBody'
                );


            body.innerHTML = '';


            cart.forEach(
                function(item) {

                    if (item.isFixedPrice) {

                        const itemTotal =
                            item.price *
                            item.count;


                        const row =
                            document.createElement(
                                'tr'
                            );


                        row.className =
                            'fixed-item-row';


                        row.innerHTML = `

                            <td>

                                ${escapeHtml(item.name)}

                            </td>


                            <td>

                                ${item.count}

                            </td>


                            <td>

                                <span class="fw-bold">

                                    ₹${item.price.toFixed(2)}

                                </span>

                            </td>


                            <td>

                                <span class="text-muted">

                                    0%

                                </span>

                            </td>


                            <td class="item-total">

                                ₹${itemTotal.toFixed(2)}

                            </td>


                            <td>

                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-danger"
                                    onclick="removeCartItemFromCheckout(${item.id})">

                                    <i class="bi bi-x"></i>

                                </button>

                            </td>

                        `;


                        body.appendChild(
                            row
                        );

                    } else {

                        /*
                         * Dynamic products:
                         * create one row for EACH unit.
                         */

                        const units =
                            item.units || [];


                        units.forEach(
                            function(unitData, unitIndex) {

                                const price =
                                    parseFloat(
                                        unitData.price
                                    ) || 0;


                                const discount =
                                    parseFloat(
                                        unitData.discount
                                    ) || 0;


                                const finalPrice =
                                    price *
                                    (
                                        1 -
                                        discount / 100
                                    );


                                const row =
                                    document.createElement(
                                        'tr'
                                    );


                                row.className =
                                    'dynamic-item-row';


                                row.setAttribute(
                                    'data-product-id',
                                    item.id
                                );


                                row.setAttribute(
                                    'data-unit-index',
                                    unitIndex
                                );


                                row.innerHTML = `

                                    <td>

                                        ${escapeHtml(item.name)}

                                    </td>


                                    <td>

                                        <span class="unit-number">

                                            Unit #${unitIndex + 1}

                                        </span>

                                    </td>


                                    <td>

                                        <input
                                            type="number"
                                            class="form-control form-control-sm unit-price-input"
                                            value="${price}"
                                            min="0"
                                            step="0.01"
                                            data-product-id="${item.id}"
                                            data-unit-index="${unitIndex}"
                                            oninput="updateDynamicPrice(${item.id}, ${unitIndex})">

                                    </td>


                                    <td>

                                        <input
                                            type="number"
                                            class="form-control form-control-sm discount-input"
                                            value="${discount}"
                                            min="0"
                                            max="100"
                                            step="0.01"
                                            data-product-id="${item.id}"
                                            data-unit-index="${unitIndex}"
                                            oninput="updateDynamicPrice(${item.id}, ${unitIndex})">

                                    </td>


                                    <td class="item-total">

                                        ₹${finalPrice.toFixed(2)}

                                    </td>


                                    <td>

                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-danger"
                                            title="Remove this unit"
                                            onclick="removeDynamicUnitFromCheckout(${item.id}, ${unitIndex})">

                                            <i class="bi bi-x"></i>

                                        </button>

                                    </td>

                                `;


                                body.appendChild(
                                    row
                                );

                            }
                        );

                    }

                }
            );


            updateOrderTotal();

        }


        /* =========================================================
           REMOVE COMPLETE CART ITEM FROM CHECKOUT
        ========================================================== */

        function removeCartItemFromCheckout(
            productId
        ) {

            const id =
                parseInt(productId);


            const cartIndex =
                cart.findIndex(
                    item =>
                    item.id == id
                );


            if (cartIndex === -1) {
                return;
            }


            /*
             * Remove the COMPLETE product.
             * This is used for fixed-price products.
             */

            cart.splice(
                cartIndex,
                1
            );


            renderCart();

            updateCartCount();

            renderProducts(products);


            if (cart.length === 0) {

                closeCheckoutModal();

                return;

            }


            /*
             * Re-render checkout WITHOUT closing
             * and reopening the Bootstrap modal.
             *
             * This prevents backdrop problems.
             */

            renderCheckoutSummary();

        }


        /* =========================================================
           REMOVE ONLY ONE DYNAMIC UNIT
        ========================================================== */

        function removeDynamicUnitFromCheckout(
            productId,
            unitIndex
        ) {

            const id =
                parseInt(productId);


            const cartIndex =
                cart.findIndex(
                    item =>
                    item.id == id
                );


            if (cartIndex === -1) {
                return;
            }


            const item =
                cart[cartIndex];


            if (
                !item.units ||
                !item.units[unitIndex]
            ) {

                return;

            }


            /*
             * THIS IS THE IMPORTANT FIX.
             *
             * Remove ONLY the clicked unit.
             */

            item.units.splice(
                unitIndex,
                1
            );


            /*
             * Update product quantity.
             */

            item.count =
                item.units.length;


            /*
             * If no units remain,
             * remove the whole product.
             */

            if (
                item.count <= 0
            ) {

                cart.splice(
                    cartIndex,
                    1
                );

            }


            renderCart();

            updateCartCount();

            renderProducts(products);


            if (
                cart.length === 0
            ) {

                closeCheckoutModal();

                return;

            }


            /*
             * Rebuild only the checkout table.
             * The modal itself stays open.
             */

            renderCheckoutSummary();

        }


        /* =========================================================
           UPDATE DYNAMIC PRICE
        ========================================================== */

        function updateDynamicPrice(
            productId,
            unitIndex
        ) {

            const id =
                parseInt(productId);


            const item =
                cart.find(
                    cartItem =>
                    cartItem.id == id
                );


            if (
                !item ||
                !item.units ||
                !item.units[unitIndex]
            ) {

                return;

            }


            const row =
                document.querySelector(
                    `.dynamic-item-row[data-product-id="${id}"][data-unit-index="${unitIndex}"]`
                );


            if (!row) {
                return;
            }


            const priceInput =
                row.querySelector(
                    '.unit-price-input'
                );


            const discountInput =
                row.querySelector(
                    '.discount-input'
                );


            let price =
                parseFloat(
                    priceInput.value
                ) || 0;


            let discount =
                parseFloat(
                    discountInput.value
                ) || 0;


            if (price < 0) {
                price = 0;
            }


            if (discount < 0) {
                discount = 0;
            }


            if (discount > 100) {
                discount = 100;
            }


            priceInput.value =
                price;


            discountInput.value =
                discount;


            /*
             * Save price directly inside cart.
             */

            item.units[unitIndex] = {

                price: price,

                discount: discount

            };


            const finalPrice =
                price *
                (
                    1 -
                    discount / 100
                );


            const totalCell =
                row.querySelector(
                    '.item-total'
                );


            if (totalCell) {

                totalCell.textContent =
                    `₹${finalPrice.toFixed(2)}`;

            }


            updateOrderTotal();

        }


        /* =========================================================
           CALCULATE ORDER TOTAL
        ========================================================== */

        function calculateOrderTotal() {

            let total = 0;


            cart.forEach(
                function(item) {

                    if (
                        item.isFixedPrice
                    ) {

                        total +=
                            item.price *
                            item.count;

                    } else {

                        const units =
                            item.units || [];


                        units.forEach(
                            function(unit) {

                                const price =
                                    parseFloat(
                                        unit.price
                                    ) || 0;


                                const discount =
                                    parseFloat(
                                        unit.discount
                                    ) || 0;


                                total +=
                                    price *
                                    (
                                        1 -
                                        discount / 100
                                    );

                            }
                        );

                    }

                }
            );


            return total;

        }


        /* =========================================================
           UPDATE ORDER TOTAL
        ========================================================== */

        function updateOrderTotal() {

            const total =
                calculateOrderTotal();


            document.getElementById(
                    'orderTotal'
                ).textContent =
                `₹${total.toFixed(2)}`;


            updateFreebieOptionBasedOnTotal();

            updateBeneficiaryWarning();

        }


        /* =========================================================
           PAYMENT OPTIONS
        ========================================================== */

        function updateFreebieOptionBasedOnTotal() {

            const total =
                calculateOrderTotal();


            const paymentModeSelect =
                $('#paymentMode');


            const freebieOption =
                paymentModeSelect.find(
                    'option[value="freebie"]'
                );


            const cashOption =
                paymentModeSelect.find(
                    'option[value="cash"]'
                );


            const onlineOption =
                paymentModeSelect.find(
                    'option[value="online"]'
                );


            if (total > 0) {

                cashOption.prop(
                    'disabled',
                    false
                );


                onlineOption.prop(
                    'disabled',
                    false
                );


                freebieOption.prop(
                    'disabled',
                    true
                );


                if (
                    paymentModeSelect.val() ===
                    'freebie'
                ) {

                    paymentModeSelect.val('');

                }

            } else {

                cashOption.prop(
                    'disabled',
                    true
                );


                onlineOption.prop(
                    'disabled',
                    true
                );


                freebieOption.prop(
                    'disabled',
                    false
                );


                paymentModeSelect.val(
                    'freebie'
                );

            }

        }


        /* =========================================================
           INITIALISE BENEFICIARY SELECT2
        ========================================================== */

        function initialiseBeneficiarySelect() {

            const select =
                $('#beneficiarySelect');


            /*
             * Destroy existing Select2 first.
             */

            if (
                select.hasClass(
                    'select2-hidden-accessible'
                )
            ) {

                select.select2(
                    'destroy'
                );

            }


            select.select2({

                dropdownParent: $('#orderConfirmationModal'),

                ajax: {

                    url: 'search_beneficiaries.php',

                    dataType: 'json',

                    delay: 250,

                    data: function(params) {

                        return {
                            q: params.term
                        };

                    },

                    processResults: function(data) {

                        return {

                            results: data.results || []

                        };

                    }

                },

                minimumInputLength: 2,

                placeholder: 'Search by name, ID, or contact',

                allowClear: false,

                closeOnSelect: true,

                width: '100%'

            });


            select.val(null)
                .trigger('change');

        }


        /* =========================================================
           BENEFICIARY WARNING
        ========================================================== */

        function updateBeneficiaryWarning() {

            const selectedBeneficiaries =
                $('#beneficiarySelect')
                .val() || [];


            const beneficiaryCount =
                selectedBeneficiaries.length;


            const orderTotal =
                calculateOrderTotal();


            const warningDiv =
                $('#multipleBeneficiaryWarning');


            if (
                beneficiaryCount > 1
            ) {

                const totalCollection =
                    orderTotal *
                    beneficiaryCount;


                $('#totalCollectionAmount')
                    .text(
                        totalCollection.toFixed(2)
                    );


                $('#orderTotalPerBeneficiary')
                    .text(
                        orderTotal.toFixed(2)
                    );


                $('#beneficiaryCount')
                    .text(
                        beneficiaryCount
                    );


                warningDiv.show();

            } else {

                warningDiv.hide();

            }

        }


        /* =========================================================
           CLOSE CHECKOUT MODAL
        ========================================================== */

        function closeCheckoutModal(
            callback
        ) {

            const modalElement =
                document.getElementById(
                    'orderConfirmationModal'
                );


            if (!modalElement) {

                if (callback) {
                    callback();
                }

                return;

            }


            const modalInstance =
                bootstrap.Modal.getInstance(
                    modalElement
                );


            /*
             * If not open.
             */

            if (
                !modalInstance ||
                !modalElement.classList.contains(
                    'show'
                )
            ) {

                isCheckoutModalOpen =
                    false;


                if (callback) {
                    callback();
                }

                return;

            }


            /*
             * Wait for Bootstrap's hidden event.
             */

            const handler =
                function() {

                    isCheckoutModalOpen =
                        false;


                    if (callback) {
                        callback();
                    }

                };


            modalElement.addEventListener(
                'hidden.bs.modal',
                handler, {
                    once: true
                }
            );


            modalInstance.hide();

        }


        /* =========================================================
           CLEAN BOOTSTRAP MODAL ARTIFACTS
        ========================================================== */

        function cleanupModalArtifacts() {

            /*
             * Only run after Bootstrap has finished hiding
             * the checkout modal.
             */

            document
                .querySelectorAll(
                    '.modal-backdrop'
                )
                .forEach(
                    function(backdrop) {

                        backdrop.remove();

                    }
                );


            document.body.classList.remove(
                'modal-open'
            );


            document.body.style.overflow =
                '';


            document.body.style.paddingRight =
                '';


            document.body.style.removeProperty(
                'padding-right'
            );


            hideSubmissionOverlay();

        }


        /* =========================================================
           HTML ESCAPE
        ========================================================== */

        function escapeHtml(
            value
        ) {

            if (
                value === null ||
                value === undefined
            ) {

                return '';

            }


            return String(value)

                .replace(
                    /&/g,
                    '&amp;'
                )

                .replace(
                    /</g,
                    '&lt;'
                )

                .replace(
                    />/g,
                    '&gt;'
                )

                .replace(
                    /"/g,
                    '&quot;'
                )

                .replace(
                    /'/g,
                    '&#039;'
                );

        }
    </script>

</body>

</html>