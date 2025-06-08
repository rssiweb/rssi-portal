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
<?php
// Fetch products from the database
$products = [];
// $query = "SELECT id, name, price, image_url, sold_out FROM products WHERE is_active=true";
$query = "SELECT
    i.item_id,
    i.item_name,
    i.image_url,
    i.description,  -- Added description
    u.unit_id,
    u.unit_name,
    p.unit_quantity,
     COALESCE((SELECT SUM(quantity_received) 
                  FROM stock_add 
                  WHERE item_id = i.item_id 
                  AND unit_id = u.unit_id), 0) AS total_added_count,
        COALESCE((SELECT SUM(quantity_distributed) 
                  FROM stock_out 
                  WHERE item_distributed = i.item_id 
                  AND unit = u.unit_id), 0) AS total_distributed_count,
        (COALESCE((SELECT SUM(quantity_received) 
                  FROM stock_add 
                  WHERE item_id = i.item_id 
                  AND unit_id = u.unit_id), 0) 
         - 
         COALESCE((SELECT SUM(quantity_distributed) 
                  FROM stock_out 
                  WHERE item_distributed = i.item_id 
                  AND unit = u.unit_id), 0)) AS in_stock,
    p.price_per_unit,
    p.discount_percentage,  -- Added discount
    p.original_price,      -- Added original price
    i.rating,              -- Added rating
    i.review_count,        -- Added review count
    i.is_featured          -- Added featured flag
FROM 
    stock_item i
LEFT JOIN stock_add sa ON i.item_id = sa.item_id
LEFT JOIN stock_out so ON i.item_id = so.item_distributed
JOIN stock_item_unit u ON u.unit_id = sa.unit_id OR u.unit_id = so.unit
LEFT JOIN stock_item_price p ON p.item_id = i.item_id 
    AND p.unit_id = u.unit_id
    AND CURRENT_DATE BETWEEN p.effective_start_date AND COALESCE(p.effective_end_date, CURRENT_DATE)
WHERE 
    i.access_scope = 'public'
GROUP BY 
    i.item_id, i.item_name, i.description, i.rating, i.review_count, i.is_featured,
    u.unit_id, u.unit_name, p.price_per_unit, p.unit_quantity, p.discount_percentage, p.original_price
ORDER BY 
    i.is_featured DESC, i.item_name";

$result = pg_query($con, $query);

if ($result) {
    while ($row = pg_fetch_assoc($result)) {
        $products[] = [
            'id' => (int)$row['item_id'],
            'name' => $row['item_name'],
            'price' => (float)$row['price_per_unit'],
            'original_price' => isset($row['original_price']) ? (float)$row['original_price'] : (float)$row['price_per_unit'],
            'image' => $row['image_url'],
            'description' => $row['description'] ?? '',
            'unit_name' => $row['unit_name'],
            'unit_quantity' => $row['unit_quantity'] ?? 1,
            'in_stock' => $row['in_stock'],
            'soldOut' => $row['in_stock'] <= 0,
            'discount_percentage' => (float)$row['discount_percentage'] ?? 0,
            'rating' => (float)$row['rating'] ?? 0,
            'review_count' => (int)$row['review_count'] ?? 0,
            'is_featured' => $row['is_featured'] ?? false
        ];
    }
}
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
            //facebookPixel: '',
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
    </style>
    <style>
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
                    <li class="breadcrumb-item"><a href="#">Stock Management</a></li>
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
                            <div class="container py-5">
                                <div class="row">
                                    <!-- Left Section: Total Points -->
                                    <div class="col-md-3">

                                    </div>

                                    <!-- Middle Section: Product List -->
                                    <div class="col-md-6">
                                        <div id="productList">
                                            <script>
                                                const products = <?php echo json_encode($products); ?>;

                                                function renderProducts() {
                                                    const productList = document.getElementById('productList');
                                                    productList.innerHTML = '';

                                                    if (products.length === 0) {
                                                        productList.innerHTML = `
                                                            <div class="alert alert-info">
                                                                No products available at the moment.
                                                            </div>
                                                        `;
                                                        return;
                                                    }

                                                    products.forEach(product => {
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
                            <div class="me-3" style="width: 150px; height: 150px;">
                                <img src="${product.image}" alt="${product.name}" 
                                     class="img-fluid h-100 w-100 object-fit-cover rounded">
                            </div>
                            
                            <!-- Product Details -->
                            <div class="flex-grow-1">
                                <!-- Product Name -->
                                <h5 class="mb-1">${product.name}</h5>
                                
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
                                        <span class="text-danger fs-5 fw-bold">₹${displayPrice}</span>
                                        <span class="text-decoration-line-through text-muted ms-2">₹${product.original_price.toFixed(2)}</span>
                                        <span class="badge bg-danger ms-2">${product.discount_percentage}% off</span>
                                    ` : `
                                        <span class="fs-5 fw-bold">₹${displayPrice}</span>
                                    `}
                                    <span class="text-muted">for ${product.unit_quantity} ${product.unit_name}</span>
                                </div>
                                <!--<div class="text-muted small mb-2 text-truncate-2">Only ${product.in_stock} left in stock</div>-->
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
                                        value="0" 
                                        min="0" 
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
                                        value="0" 
                                        min="0" 
                                        onchange="validateQuantityInput(${product.id})"
                                        oninput="validateQuantityInput(${product.id})"
                                        style="width: 60px;">
                                    <button class="btn btn-sm btn-primary" onclick="increaseCount(${product.id})">
                                        <i class="bi bi-plus"></i>
                                    </button>
                                </div>
                                `}
                                
                                <!--${product.is_featured ? `<span class="badge bg-info mt-2">Featured</span>` : ''}-->
                            </div>
                        </div>
                    `;
                                                        productList.appendChild(productCard);
                                                    });
                                                }

                                                renderProducts();
                                            </script>
                                        </div>
                                    </div>

                                    <!-- Right Section: Cart Summary -->
                                    <div class="col-md-3">
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
                    <h5 class="modal-title" id="orderConfirmationModalLabel">Order Confirmation</h5>
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
                        <button type="submit" id="submitOrderBtn" class="btn btn-primary">Confirm Order</button>
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
                        <p id="loadingMessage">Submission in progress.
                            Please do not close or reload this page.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Create a new Bootstrap modal instance with backdrop: 'static' and keyboard: false options
        const myModal = new bootstrap.Modal(document.getElementById("myModal"), {
            backdrop: 'static',
            keyboard: false
        });
        // Add event listener to intercept Escape key press
        document.body.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                // Prevent default behavior of Escape key
                event.preventDefault();
            }
        });
    </script>

    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>

    <script>
        let cart = [];

        function updateCart(productId, productName, price, count) {
            const existingIndex = cart.findIndex(item => item.id === productId);

            if (count > 0) {
                const cartItem = {
                    id: productId,
                    name: productName,
                    price: price,
                    count: count
                };

                if (existingIndex >= 0) {
                    // Update existing item
                    cart[existingIndex] = cartItem;
                } else {
                    // Add new item
                    cart.push(cartItem);
                }
            } else if (existingIndex >= 0) {
                // Remove item from cart if count is 0
                cart.splice(existingIndex, 1);
            }

            renderCart();
        }

        function renderCart() {
            const cartList = document.getElementById('cartList');
            const cartTotal = document.getElementById('cartTotal');
            cartList.innerHTML = '';
            let total = 0;

            cart.forEach(item => {
                const listItem = document.createElement('li');
                listItem.className = 'list-group-item d-flex justify-content-between align-items-center';
                listItem.textContent = `${item.name} x ${item.count}`;
                const itemTotal = item.price * item.count;
                total += itemTotal;
                listItem.innerHTML += `<span>₹${itemTotal}</span>`;
                cartList.appendChild(listItem);
            });

            cartTotal.textContent = `₹${total}`;
        }

        function increaseCount(productId) {
            const countInput = document.getElementById(`count${productId}`);
            const currentCount = parseInt(countInput.value);
            const product = products.find(p => p.id === productId);

            if (product && currentCount < product.in_stock) {
                countInput.value = currentCount + 1;
                updateCart(productId, product.name, product.price, currentCount + 1);
            } else if (product && currentCount >= product.in_stock) {
                alert(`You cannot order more than ${product.in_stock} items of this product.`);
            }
        }

        function decreaseCount(productId) {
            const countInput = document.getElementById(`count${productId}`);
            const currentCount = parseInt(countInput.value);
            const product = products.find(p => p.id === productId);

            if (currentCount > 0) {
                countInput.value = currentCount - 1;
                if (product) {
                    updateCart(productId, product.name, product.price, currentCount - 1);
                }
            }
        }

        // Add input validation to prevent manual entry above stock limit
        function validateQuantityInput(productId) {
            const countInput = document.getElementById(`count${productId}`);
            const product = products.find(p => p.id === productId);

            if (product) {
                let enteredValue = parseInt(countInput.value);

                // Handle NaN cases (when input is cleared)
                if (isNaN(enteredValue)) {
                    enteredValue = 0;
                }

                // Ensure value is within bounds
                if (enteredValue < 0) {
                    enteredValue = 0;
                } else if (enteredValue > product.in_stock) {
                    enteredValue = product.in_stock;
                    alert(`You cannot order more than ${product.in_stock} items of this product.`);
                }

                countInput.value = enteredValue;

                // Update cart with validated quantity
                updateCart(productId, product.name, product.price, enteredValue);
            }
        }

        function placeOrder() {
            if (cart.length === 0) {
                alert('Your cart is empty!');
                return;
            }

            // Calculate total
            const totalPoints = cart.reduce((sum, item) => sum + item.price * item.count, 0);

            // Prepare order summary HTML
            let orderSummary = '';
            cart.forEach(item => {
                orderSummary += `
            <tr>
                <td>${item.name}</td>
                <td>${item.count}</td>
                <td>₹${item.price}</td>
                <td>₹${item.price * item.count}</td>
            </tr>
        `;
            });

            // Populate the modal with order details
            document.getElementById('orderSummaryBody').innerHTML = orderSummary;
            document.getElementById('orderTotal').textContent = `₹${totalPoints}`;
            updateFreebieOptionBasedOnTotal();

            // Show the order confirmation modal
            const orderModal = new bootstrap.Modal(document.getElementById('orderConfirmationModal'));
            orderModal.show();
        }

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
                    minimumInputLength: 1,
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

                // Submit data
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
                            window.location.href = window.location.href;
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

                // Prevent Escape key from closing modal
                document.addEventListener('keydown', function(event) {
                    if (event.key === 'Escape') {
                        event.preventDefault();
                    }
                });
            });
        });
    </script>
    <script>
        function updateFreebieOptionBasedOnTotal() {
            const totalText = $('#orderTotal').text().replace(/[^\d.]/g, ''); // Remove ₹ or commas
            const total = parseFloat(totalText) || 0;

            const freebieOption = $('#paymentMode option[value="freebie"]');

            if (total > 0) {
                freebieOption.prop('disabled', true);

                // If currently selected, reset to blank
                if ($('#paymentMode').val() === 'freebie') {
                    $('#paymentMode').val('');
                }
            } else {
                freebieOption.prop('disabled', false);
            }
        }
    </script>
</body>

</html>