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
    <title>eMart</title>
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- In your head section -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">
    <!-- Include jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Include Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
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

        /* Make loading modal appear above order confirmation modal */
        #myModal {
            z-index: 1080 !important;
            /* Higher than order confirmation (1050) */
        }

        /* Make loading modal backdrop appear just below loading modal but above order confirmation */
        #myModal+.modal-backdrop {
            z-index: 1070 !important;
            /* Between loading modal (1080) and order confirmation (1050) */
        }
    </style>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>eMart</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Community Supply</a></li>
                    <li class="breadcrumb-item active">eMart</li>
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
                            <div class="container">
                                <div class="row">
                                    <div class="col-md-12 text-end">
                                        <label for="itemsPerPage" class="form-label mb-1">Items per page:</label>
                                        <select class="form-select d-inline-block w-auto" id="itemsPerPage">
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
                                    <!-- Left Section: Empty for alignment -->
                                    <!-- <div class="col-md-3"></div> -->

                                    <!-- Middle Section: Product List -->
                                    <div class="col-md-6">
                                        <!-- Search Box -->
                                        <div class="row mb-3">
                                            <div class="col-md-12">
                                                <div class="input-group">
                                                    <input type="text" id="searchInput" class="form-control" placeholder="Search products..."
                                                        value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                                                    <button id="searchButton" class="btn btn-primary" type="button">
                                                        <i class="bi bi-search"></i> Search
                                                    </button>
                                                    <?php if (isset($_GET['search']) && !empty($_GET['search'])): ?>
                                                        <button id="clearSearch" class="btn btn-outline-secondary" type="button">
                                                            <i class="bi bi-x"></i> Clear
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Product List Container -->
                                        <div id="productList">
                                            <!-- Products will be loaded here via AJAX -->
                                        </div>

                                        <!-- Pagination Container -->
                                        <div id="paginationContainer"></div>
                                    </div>

                                    <!-- Right Section: Cart Summary -->
                                    <div class="col-md-6">
                                        <div class="right-section">
                                            <h4>Cart Summary</h4>
                                            <ul id="cartList" class="list-group mb-3">
                                                <!-- Dynamic Cart Items -->
                                            </ul>
                                            <h5>Total: <span id="cartTotal" class="text-success">₹0</span></h5>
                                            <button class="btn btn-success w-100 mt-3" onclick="placeOrder()">Place Order</button>
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

    <!-- Order Confirmation Modal -->
    <div class="modal fade" id="orderConfirmationModal" tabindex="-1" aria-labelledby="orderConfirmationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="orderConfirmationModalLabel">Checkout Page</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="orderForm">
                    <div class="modal-body">
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <h5>Order Summary</h5>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Item</th>
                                                <th>Quantity</th>
                                                <th>Unit Price</th>
                                                <th>Total</th>
                                            </tr>
                                        </thead>
                                        <tbody id="orderSummaryBody">
                                            <!-- Order items will be inserted here -->
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th colspan="3" class="text-end">Total:</th>
                                                <th id="orderTotal">₹0</th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Left Side: Beneficiary Selection -->
                            <div class="col-md-6">
                                <label for="beneficiarySelect" class="form-label">Search and Select Beneficiaries</label>
                                <select id="beneficiarySelect" name="beneficiaries" class="form-select js-data-ajax-multiple" multiple="multiple" required>
                                    <!-- Beneficiaries will be loaded via AJAX -->
                                </select>
                                <div class="form-text text-muted">
                                    First-time user? <a href="register_beneficiary.php" target="_blank">Register here</a>
                                </div>
                                <div class="invalid-feedback">Please select at least one beneficiary.</div>
                            </div>

                            <div class="col-md-6">
                                <div id="multipleBeneficiaryWarning" class="alert alert-danger mt-2" style="display: none;">
                                    You have selected multiple beneficiaries. Please collect ₹<span id="totalCollectionAmount">0</span> (₹<span id="orderTotalPerBeneficiary">0</span> × <span id="beneficiaryCount">0</span> beneficiaries).
                                </div>
                                <label for="paymentMode" class="form-label">Payment Mode</label>
                                <select id="paymentMode" class="form-select" required>
                                    <option value="">Select Payment Mode</option>
                                    <option value="cash">Cash</option>
                                    <option value="online">Online Payment</option>
                                    <option value="freebie">Freebies (no payment required)</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-6" id="transactionIdContainer" style="display: none;">
                                <label for="transactionId" class="form-label">Transaction ID</label>
                                <input type="text" id="transactionId" class="form-control" placeholder="Enter transaction ID">
                                <div class="form-text text-muted">
                                    Record payment for all selected purchases in one go. <a href="https://secure.paytmpayments.com/link/paymentForm/47760/LL_790393889" target="_blank">Quick Payment</a>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <label for="remarks" class="form-label">Remarks</label>
                                <textarea id="remarks" class="form-control" rows="2" placeholder="Any additional remarks"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" id="submitOrderBtn" class="btn btn-primary">Checkout</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

    <!-- Bootstrap Modal -->
    <div class="modal fade" id="myModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false" style="display: none;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p id="loadingMessage">Submission in progress. Please do not close or reload this page.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>

    <script>
        // Global variables
        let pageNumber = <?php echo isset($_GET['page']) ? max(1, intval($_GET['page'])) : (isset($_SESSION['emart_page']) ? $_SESSION['emart_page'] : 1); ?>;
        let currentSearchTerm = '<?php echo isset($_GET['search']) ? addslashes($_GET['search']) : (isset($_SESSION['emart_search']) ? addslashes($_SESSION['emart_search']) : ''); ?>';
        let itemsPerPage = <?php echo isset($_GET['itemsPerPage']) ? max(5, min(100, intval($_GET['itemsPerPage']))) : (isset($_SESSION['emart_items_per_page']) ? $_SESSION['emart_items_per_page'] : 5); ?>;
        let totalPages = 1;
        let cart = [];
        let products = []; // Store products for cart operations

        // Initialize when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {

            // Check URL parameters first
            const urlParams = new URLSearchParams(window.location.search);

            if (urlParams.has('itemsPerPage')) {
                itemsPerPage = parseInt(urlParams.get('itemsPerPage'));
                // Update storage to match current URL
                sessionStorage.setItem('emartItemsPerPage', itemsPerPage);
            } else if (sessionStorage.getItem('emartItemsPerPage')) {
                itemsPerPage = parseInt(sessionStorage.getItem('emartItemsPerPage'));
            }

            document.getElementById('itemsPerPage').value = itemsPerPage;

            // Items per page change handler
            document.getElementById('itemsPerPage').addEventListener('change', function() {
                itemsPerPage = parseInt(this.value);

                // Update client-side storage
                sessionStorage.setItem('emartItemsPerPage', itemsPerPage);

                // Update server-side session via AJAX
                fetch('update_session.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `itemsPerPage=${itemsPerPage}`
                });

                loadProducts(1, currentSearchTerm); // Reset to page 1 when changing items per page
            });
            // Load initial products
            loadProducts(pageNumber, currentSearchTerm);

            // Search button click handler
            document.getElementById('searchButton').addEventListener('click', function() {
                const searchTerm = document.getElementById('searchInput').value.trim();
                loadProducts(1, searchTerm);
            });

            // Clear search button
            const clearSearchBtn = document.getElementById('clearSearch');
            if (clearSearchBtn) {
                clearSearchBtn.addEventListener('click', function() {
                    document.getElementById('searchInput').value = '';
                    loadProducts(1, '');
                });
            }

            // Handle Enter key in search input
            document.getElementById('searchInput').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    const searchTerm = this.value.trim();
                    loadProducts(1, searchTerm);
                }
            });

            // Handle browser back/forward buttons
            window.addEventListener('popstate', function() {
                const urlParams = new URLSearchParams(window.location.search);
                const page = urlParams.get('page') || 1;
                const searchTerm = urlParams.get('search') || '';
                document.getElementById('searchInput').value = searchTerm;
                const newItemsPerPage = urlParams.get('itemsPerPage') || 5;
                if (newItemsPerPage != itemsPerPage) {
                    itemsPerPage = newItemsPerPage;
                    document.getElementById('itemsPerPage').value = itemsPerPage;
                }

                document.getElementById('searchInput').value = searchTerm;
                loadProducts(page, searchTerm);
            });
        });

        // Function to load products via AJAX
        function loadProducts(page = 1, searchTerm = '') {
            // Show loading indicator
            const productList = document.getElementById('productList');
            productList.innerHTML = '<div class="text-center my-5"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>';

            // Update URL without reloading
            updateUrl(page, searchTerm);

            // Update current page and search term
            pageNumber = page;
            currentSearchTerm = searchTerm;

            // Return the fetch Promise
            return fetch(`search_products.php?page=${page}&search=${encodeURIComponent(searchTerm)}&itemsPerPage=${itemsPerPage}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    products = data.products;
                    renderProducts(data.products);
                    totalPages = data.totalPages;
                    renderPagination();
                    return data; // Return data for chaining
                })
                .catch(error => {
                    console.error('Error:', error);
                    productList.innerHTML = '<div class="alert alert-danger">Error loading products. Please try again.</div>';
                    throw error; // Re-throw for error handling
                });
        }

        // Update URL without reloading page
        function updateUrl(page, searchTerm) {
            const urlParams = new URLSearchParams();
            if (page > 1) urlParams.set('page', page);
            if (searchTerm) urlParams.set('search', searchTerm);
            if (itemsPerPage != 5) urlParams.set('itemsPerPage', itemsPerPage); // Only include if not default
            history.pushState(null, '', urlParams.toString() ? `?${urlParams}` : window.location.pathname);
        }

        // Render products to the page
        function renderProducts(products) {
            const productList = document.getElementById('productList');
            productList.innerHTML = '';

            if (products.length === 0) {
                productList.innerHTML = `
            <div class="alert alert-info">
                No products found matching your search.
            </div>
        `;
                return;
            }

            products.forEach(product => {
                // Find this product in the cart to get current quantity
                const cartItem = cart.find(item => item.id === product.id);
                const currentQuantity = cartItem ? cartItem.count : 0;

                const hasDiscount = product.discount_percentage > 0;
                const displayPrice = hasDiscount ?
                    (product.original_price * (1 - product.discount_percentage / 100)).toFixed(2) :
                    product.price.toFixed(2);

                const stockStatus = product.in_stock <= 0;
                const lowStock = product.in_stock > 0 && product.in_stock <= 5;

                const productCard = document.createElement('div');
                productCard.className = 'product-card mb-4 p-3 border rounded bg-white';
                productCard.innerHTML = `
            <div class="d-flex">
                <!-- Product Image -->
                <div class="col-6 me-3" style="height: 150px;">
                    <img src="${product.image}" alt="${product.name}" 
                        class="img-fluid h-100 w-100 object-fit-cover rounded">
                </div>
                
                <!-- Product Details -->
                <div class="flex-grow-1">
                    <!-- Product Name -->
                    <h5 class="mb-1">${product.name}</h5>
                    <small class="text-muted">Product Id- ${product.id}</small>
                    
                    <!-- Rating -->
                    ${product.rating > 0 ? `
                    <div class="d-flex align-items-center mb-1">
                        <div class="text-warning">
                            ${'★'.repeat(Math.round(product.rating))}${'☆'.repeat(5 - Math.round(product.rating))}
                        </div>
                        <small class="text-muted ms-2">${product.review_count} reviews</small>
                    </div>
                    ` : ''}
                    
                    <!-- Description -->
                    ${product.description ? `
                    <p class="text-muted small mb-2 text-truncate-2" 
                    style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                    ${product.description}
                    </p>
                    ` : ''}
                    
                    <!-- Pricing -->
                    <div class="mb-2">
                        ${hasDiscount ? `
                            <span class="text-danger fs-5 fw-bold">₹${displayPrice < 0 ? '0.00' : displayPrice}</span>
                            <span class="text-decoration-line-through text-muted ms-2">₹${product.original_price.toFixed(2)}</span>
                            <span class="badge bg-danger ms-2">${product.discount_percentage}% off</span>
                        ` : `
                            <span class="fs-5 fw-bold">₹${displayPrice < 0 ? '0.00' : displayPrice}</span>
                        `}
                        <span class="text-muted">for ${product.unit_quantity} ${product.unit_name}</span>
                    </div>
                    
                    <!-- Stock Status -->
                    ${stockStatus ? `
                        <div class="text-danger mb-2">Out of Stock</div>
                    ` : lowStock ? `
                        <div class="text-danger mb-2">Only ${product.in_stock} left in stock</div>
                        <div class="btn-quantity d-flex align-items-center">
                            <button class="btn btn-sm btn-outline-secondary" onclick="decreaseCount(${product.id})">
                                <i class="bi bi-dash"></i>
                            </button>
                            <input type="number" 
                                id="count${product.id}" 
                                class="form-control mx-2 text-center stock-input" 
                                value="${currentQuantity}" 
                                min="0" 
                                max="${product.in_stock}"
                                onchange="validateQuantityInput(${product.id})"
                                oninput="validateQuantityInput(${product.id})"
                                style="width: 60px;">
                            <button class="btn btn-sm btn-primary" onclick="increaseCount(${product.id})">
                                <i class="bi bi-plus"></i>
                            </button>
                        </div>
                    ` : `
                        <div class="text-success mb-2">In Stock</div>
                        <div class="btn-quantity d-flex align-items-center">
                            <button class="btn btn-sm btn-outline-secondary" onclick="decreaseCount(${product.id})">
                                <i class="bi bi-dash"></i>
                            </button>
                            <input type="number" 
                                id="count${product.id}" 
                                class="form-control mx-2 text-center stock-input" 
                                value="${currentQuantity}" 
                                min="0" 
                                max="${product.in_stock}"
                                onchange="validateQuantityInput(${product.id})"
                                oninput="validateQuantityInput(${product.id})"
                                style="width: 60px;">
                            <button class="btn btn-sm btn-primary" onclick="increaseCount(${product.id})">
                                <i class="bi bi-plus"></i>
                            </button>
                        </div>
                    `}
                </div>
            </div>
        `;
                productList.appendChild(productCard);
            });
        }

        // Render pagination controls
        function renderPagination() {
            const paginationContainer = document.getElementById('paginationContainer');
            paginationContainer.innerHTML = '';

            if (totalPages <= 1) return;

            let paginationHTML = `
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <li class="page-item ${pageNumber === 1 ? 'disabled' : ''}">
                            <a class="page-link" href="#" onclick="loadProducts(${pageNumber - 1}, '${currentSearchTerm}'); return false;" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>`;

            // Show page numbers
            for (let i = 1; i <= totalPages; i++) {
                paginationHTML += `
                    <li class="page-item ${i === pageNumber ? 'active' : ''}">
                        <a class="page-link" href="#" onclick="loadProducts(${i}, '${currentSearchTerm}'); return false;">${i}</a>
                    </li>`;
            }

            paginationHTML += `
                        <li class="page-item ${pageNumber >= totalPages ? 'disabled' : ''}">
                            <a class="page-link" href="#" onclick="loadProducts(${pageNumber + 1}, '${currentSearchTerm}'); return false;" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    </ul>
                </nav>`;

            paginationContainer.innerHTML = paginationHTML;
        }

        // Cart functions
        function updateCart(productId, productName, price, count, unit_name) {
            const existingIndex = cart.findIndex(item => item.id === productId);

            if (count > 0) {
                const cartItem = {
                    id: productId,
                    name: productName,
                    price: price,
                    count: count,
                    unit_name: unit_name
                };

                if (existingIndex >= 0) {
                    cart[existingIndex] = cartItem;
                } else {
                    cart.push(cartItem);
                }
            } else if (existingIndex >= 0) {
                cart.splice(existingIndex, 1);
            }

            renderCart();
            updateCartCount();

            // Trigger AJAX refresh after cart update
            // refreshProductList();
        }

        function renderCart() {
            const cartList = document.getElementById('cartList');
            const cartTotal = document.getElementById('cartTotal');
            cartList.innerHTML = '';
            let total = 0;

            cart.forEach((item, index) => {
                const listItem = document.createElement('li');
                listItem.className = 'list-group-item d-flex justify-content-between align-items-center';

                const itemTotal = item.price * item.count;
                total += itemTotal;

                listItem.innerHTML = `
            <div>
                ${item.name} x ${item.count}
                <span class="text-muted ms-2">(₹${item.price.toFixed(2)}/${item.unit_name})</span>
            </div>
            <div>
                <span class="me-3">₹${itemTotal.toFixed(2)}</span>
                <button class="btn btn-sm btn-outline-danger" onclick="removeFromCart(${index})">
                    <i class="bi bi-x"></i> <!-- Bootstrap Icons cross -->
                </button>
            </div>
        `;

                cartList.appendChild(listItem);
            });

            cartTotal.textContent = `₹${total.toFixed(2)}`;
        }

        // Add this new function to handle item removal
        function removeFromCart(index) {
            if (index >= 0 && index < cart.length) {
                cart.splice(index, 1);
                renderCart();
                updateCartCount();

                // Trigger AJAX refresh
                loadProducts();
            }
        }

        // Add this function to update cart count in navbar or elsewhere
        function updateCartCount() {
            const totalItems = cart.reduce((sum, item) => sum + item.count, 0);
            const cartCountElements = document.querySelectorAll('.cart-count');

            cartCountElements.forEach(element => {
                element.textContent = totalItems;
                element.style.display = totalItems > 0 ? 'inline-block' : 'none';
            });
        }

        function increaseCount(productId) {
            const countInput = document.getElementById(`count${productId}`);
            const currentCount = parseInt(countInput.value) || 0;
            const product = products.find(p => p.id === productId);

            if (product && currentCount < product.in_stock) {
                countInput.value = currentCount + 1;
                updateCart(productId, product.name, product.price, currentCount + 1, product.unit_name);
            } else if (product && currentCount >= product.in_stock) {
                alert(`You cannot order more than ${product.in_stock} items of this product.`);
            }
        }

        function decreaseCount(productId) {
            const countInput = document.getElementById(`count${productId}`);
            const currentCount = parseInt(countInput.value) || 0;
            const product = products.find(p => p.id === productId);

            if (currentCount > 0) {
                countInput.value = currentCount - 1;
                if (product) {
                    updateCart(productId, product.name, product.price, currentCount - 1, product.unit_name);
                }
            }
        }

        function validateQuantityInput(productId) {
            const countInput = document.getElementById(`count${productId}`);
            const product = products.find(p => p.id === productId);

            if (product) {
                let enteredValue = parseInt(countInput.value);

                if (isNaN(enteredValue)) {
                    enteredValue = 0;
                }

                if (enteredValue < 0) {
                    enteredValue = 0;
                } else if (enteredValue > product.in_stock) {
                    enteredValue = product.in_stock;
                    alert(`You cannot order more than ${product.in_stock} items of this product.`);
                }

                countInput.value = enteredValue;
                updateCart(productId, product.name, product.price, enteredValue, product.unit_name);
            }
        }

        function placeOrder() {
            // Get current page and search parameters
            const urlParams = new URLSearchParams(window.location.search);
            const pageNumber = urlParams.get('page') || 1;
            const searchTerm = urlParams.get('search') || '';

            // Store current pagination settings
            sessionStorage.setItem('emartItemsPerPage', itemsPerPage);
            sessionStorage.setItem('emartPage', pageNumber);
            if (currentSearchTerm) {
                sessionStorage.setItem('emartSearch', currentSearchTerm);
            }

            // Store them in session storage to restore after order
            sessionStorage.setItem('emartPage', pageNumber);
            if (searchTerm) {
                sessionStorage.setItem('emartSearch', searchTerm);
            }

            if (cart.length === 0) {
                alert('Your cart is empty!');
                return;
            }

            // Calculate total
            const totalPoints = cart.reduce((sum, item) => sum + item.price * item.count, 0);

            if (totalPoints < 0) {
                alert('Invalid cart total! The total cannot be negative.');
                return;
            }

            // Prepare order summary HTML
            let orderSummary = '';
            cart.forEach(item => {
                orderSummary += `
                    <tr>
                        <td>${item.name}</td>
                        <td>${item.count}</td>
                        <td>₹${item.price.toFixed(2)}</td>
                        <td>₹${(item.price * item.count).toFixed(2)}</td>
                    </tr>
                `;
            });

            // Populate the modal with order details
            document.getElementById('orderSummaryBody').innerHTML = orderSummary;
            document.getElementById('orderTotal').textContent = `₹${totalPoints.toFixed(2)}`;
            updateFreebieOptionBasedOnTotal();

            // Show the order confirmation modal
            const orderModal = new bootstrap.Modal(document.getElementById('orderConfirmationModal'));
            orderModal.show();
        }

        function updateFreebieOptionBasedOnTotal() {
            const totalText = $('#orderTotal').text().replace(/[^\d.]/g, ''); // Remove ₹ or commas
            const total = parseFloat(totalText) || 0;

            const paymentModeSelect = $('#paymentMode');
            const freebieOption = paymentModeSelect.find('option[value="freebie"]');
            const cashOption = paymentModeSelect.find('option[value="cash"]');
            const onlineOption = paymentModeSelect.find('option[value="online"]');

            // Reset selection whenever total changes
            paymentModeSelect.val('');

            if (total > 0) {
                // Enable cash and online, disable freebie
                cashOption.prop('disabled', false);
                onlineOption.prop('disabled', false);
                freebieOption.prop('disabled', true);
            } else {
                // Disable cash and online, enable freebie
                cashOption.prop('disabled', true);
                onlineOption.prop('disabled', true);
                freebieOption.prop('disabled', false);

                // Auto-select freebie when total is zero
                paymentModeSelect.val('freebie');
            }
        }

        // Initialize Select2 and form handling when DOM is loaded
        $(document).ready(function() {
            // Initialize beneficiary select2 when modal is shown
            $('#orderConfirmationModal').on('shown.bs.modal', function() {
                $('#beneficiarySelect').select2({
                    dropdownParent: $(this),
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

                // Clear previous selections
                $('#beneficiarySelect').val(null).trigger('change');
            });

            // Destroy Select2 when modal is closed
            $('#orderConfirmationModal').on('hidden.bs.modal', function() {
                $('#beneficiarySelect').select2('destroy');
            });

            // Handle payment mode change
            $('#paymentMode').change(function() {
                if ($(this).val() === 'online') {
                    $('#transactionIdContainer').show();
                    $('#transactionId').prop('required', true);
                } else {
                    $('#transactionIdContainer').hide();
                    $('#transactionId').prop('required', false);
                }
            });

            // Handle form submission using native validation
            $('#orderForm').on('submit', function(e) {
                e.preventDefault();

                // Native validation
                if (!this.checkValidity()) {
                    e.stopPropagation();
                    this.classList.add('was-validated');
                    return;
                }

                // Custom check for Select2 (which isn't validated natively)
                if (!$('#beneficiarySelect').val() || $('#beneficiarySelect').val().length === 0) {
                    $('#beneficiarySelect').addClass('is-invalid');
                    return;
                } else {
                    $('#beneficiarySelect').removeClass('is-invalid');
                }

                // Show loading modal
                const loadingModal = new bootstrap.Modal(document.getElementById('myModal'), {
                    backdrop: 'static',
                    keyboard: false
                });
                loadingModal.show();

                setTimeout(() => {
                    const backdrops = document.querySelectorAll('.modal-backdrop');
                    if (backdrops.length > 1) {
                        backdrops[backdrops.length - 1].style.zIndex = '1070';
                    }
                }, 10);

                // Disable the button
                $('#submitOrderBtn').prop('disabled', true);

                // Prepare data
                const paymentMode = $('#paymentMode').val();
                const transactionId = paymentMode === 'online' ? $('#transactionId').val() : null;
                const remarks = $('#remarks').val();
                const beneficiaries = $('#beneficiarySelect').val();

                const cartData = cart.map(item => ({
                    productId: item.id,
                    count: item.count,
                    productPoints: item.price * item.count
                }));

                const orderData = new URLSearchParams({
                    'form-type': 'orders',
                    'associatenumber': "<?php echo $associatenumber; ?>",
                    'fullname': "<?php echo $fullname; ?>",
                    'doj': "<?php echo $doj; ?>",
                    'email': "<?php echo $email; ?>",
                    'totalPoints': cart.reduce((sum, item) => sum + item.price * item.count, 0),
                    'cart': JSON.stringify(cartData),
                    'paymentMode': paymentMode,
                    'transactionId': transactionId || '',
                    'remarks': remarks,
                    'beneficiaries': JSON.stringify(beneficiaries)
                });
                fetch('process_order.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: orderData
                    })
                    .then(response => response.json())
                    .then(data => {
                        loadingModal.hide();
                        if (data.status === 'success') {
                            alert(data.message);
                            window.location.href = `order_confirmation.php?id=${data.order_id}`;
                        } else {
                            alert(data.message);
                            $('#submitOrderBtn').prop('disabled', false);
                        }
                    })
                    .catch(error => {
                        loadingModal.hide();
                        console.error('Error:', error);
                        alert('An error occurred while placing the order.');
                        $('#submitOrderBtn').prop('disabled', false);
                    });

                document.addEventListener('keydown', function(event) {
                    if (event.key === 'Escape') {
                        event.preventDefault();
                    }
                });
            });
        });
    </script>
    <script>
        // Add this inside your $(document).ready() function, after the Select2 initialization
        $('#beneficiarySelect').on('change', function() {
            const selectedBeneficiaries = $(this).val() || [];
            const beneficiaryCount = selectedBeneficiaries.length;

            // Get the order total (remove currency symbol and commas)
            const orderTotalText = $('#orderTotal').text().replace(/[^\d.]/g, '');
            const orderTotal = parseFloat(orderTotalText) || 0;

            const warningDiv = $('#multipleBeneficiaryWarning');

            if (beneficiaryCount > 1) {
                const totalCollection = orderTotal * beneficiaryCount;

                // Update the warning message
                $('#totalCollectionAmount').text(totalCollection.toFixed(2));
                $('#orderTotalPerBeneficiary').text(orderTotal.toFixed(2));
                $('#beneficiaryCount').text(beneficiaryCount);

                // Show the warning
                warningDiv.show();
            } else {
                // Hide the warning if only one or zero beneficiaries selected
                warningDiv.hide();
            }
        });

        // Also trigger this when payment mode changes in case it affects the total
        $('#paymentMode').change(function() {
            $('#beneficiarySelect').trigger('change');
        });
    </script>
</body>

</html>