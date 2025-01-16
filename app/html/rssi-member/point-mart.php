<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];

    header("Location: index.php");
    exit;
}
validation();
// $associatenumber = 'ILKO22063';
?>
<?php
// Fetch products from the database
$products = [];
$query = "SELECT id, name, price, image_url, sold_out FROM products";
$result = pg_query($con, $query);

if ($result) {
    while ($row = pg_fetch_assoc($result)) {
        $products[] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'price' => (int)$row['price'],
            'image' => $row['image_url'],
            'soldOut' => $row['sold_out'] === 't' ? true : false, // Convert 't'/'f' to boolean
        ];
    }
}
?>
<?php
// Get total gems redeemed and received for a specific user ($associatenumber)
$query_totalgemsredeem = pg_query($con, "SELECT COALESCE(SUM(redeem_gems_point), 0) FROM gems WHERE user_id = '$associatenumber' AND (reviewer_status IS NULL OR reviewer_status != 'Rejected')");
$query_totalgemsreceived = pg_query($con, "SELECT COALESCE(SUM(gems), 0) FROM certificate WHERE awarded_to_id = '$associatenumber'");

// Fetch results
$totalgemsredeem = pg_fetch_result($query_totalgemsredeem, 0, 0);
$totalgemsreceived = pg_fetch_result($query_totalgemsreceived, 0, 0);
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

    <title>PointMart</title>

    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">

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
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>PointMart</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Rewards & Recognition</a></li>
                    <li class="breadcrumb-item active">PointMart</li>
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
                                        <div class="left-section text-center">
                                            <h4>Total Points</h4>
                                            <div id="totalPoints" class="display-4 text-primary"><?php echo ($totalgemsreceived - $totalgemsredeem) ?></div>
                                        </div>
                                    </div>

                                    <!-- Middle Section: Product List -->
                                    <div class="col-md-6">
                                        <div id="productList">
                                            <!-- Product Template -->
                                            <script>
                                                const products = <?php echo json_encode($products); ?>;

                                                function renderProducts() {
                                                    const productList = document.getElementById('productList');
                                                    productList.innerHTML = '';
                                                    products.forEach(product => {
                                                        const productCard = document.createElement('div');
                                                        productCard.className = 'product-card d-flex align-items-center';
                                                        productCard.innerHTML = `
                        <img src="${product.image}" alt="${product.name}" class="me-3" width="50%">
                        <div>
                            <h5>${product.name}</h5>
                            <p>Price: <strong>${product.price} Points</strong></p>
                            ${product.soldOut ? 
                                '<span class="text-danger">Sold Out</span>' : 
                                `<div class="btn-quantity">
                                    <button class="btn btn-sm btn-secondary" onclick="decreaseCount(${product.id})">-</button>
                                    <input type="number" id="count${product.id}" class="form-control mx-2 text-center" value="0" min="0" style="width: 60px;">
                                    <button class="btn btn-sm btn-primary" onclick="increaseCount(${product.id})">+</button>
                                </div>`
                            }
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
                                            <h5>Total: <span id="cartTotal" class="text-success">0 Points</span></h5>
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

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>
    <script>
        let totalPoints = 1000;
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
                listItem.innerHTML += `<span>${itemTotal} Points</span>`;
                cartList.appendChild(listItem);
            });

            cartTotal.textContent = `${total} Points`;
        }

        function decreaseCount(productId) {
            const countInput = document.getElementById(`count${productId}`);
            const currentCount = parseInt(countInput.value);
            if (currentCount > 0) {
                countInput.value = currentCount - 1;
                const product = products.find(p => p.id === productId); // Get product by ID
                if (product) {
                    updateCart(productId, product.name, product.price, currentCount - 1);
                }
            }
        }

        function increaseCount(productId) {
            const countInput = document.getElementById(`count${productId}`);
            const currentCount = parseInt(countInput.value);
            countInput.value = currentCount + 1;
            const product = products.find(p => p.id === productId); // Get product by ID
            if (product) {
                updateCart(productId, product.name, product.price, currentCount + 1);
            }
        }

        function placeOrder() {
            if (cart.length === 0) {
                alert('Your cart is empty!');
                return;
            }
            // Disable the button
            const placeOrderButton = document.querySelector('.btn-success');
            placeOrderButton.disabled = true; // Disable the button
            // Show loader
            $('#myModal').modal('show');
            const totalPoints = cart.reduce((sum, item) => sum + item.price * item.count, 0);

            // Prepare the cart data as a JSON string
            const cartData = cart.map(item => ({
                productId: item.id,
                count: item.count
            }));

            // Prepare the order data as URLSearchParams
            const orderData = new URLSearchParams({
                'form-type': 'orders', // Form type
                'associatenumber': "<?php echo $associatenumber; ?>", // Associate number
                'maxlimit': "<?php echo ($totalgemsreceived - $totalgemsredeem); ?>", // Associate number
                'totalPoints': totalPoints, // Total points
                'cart': JSON.stringify(cartData) // Cart data as a string
            });

            // Send the data to the server
            fetch('payment-api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: orderData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert(data.message); // Show success message from PHP response
                        location.reload(); // Reload the page to prevent multiple submissions
                    } else {
                        alert(data.message); // Show error message from PHP response
                        placeOrderButton.disabled = false; // Re-enable the button
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while placing the order.');
                    placeOrderButton.disabled = false; // Re-enable the button
                })
                .finally(() => {
                    $('#myModal').modal('hide'); // Hide loader regardless of success or failure
                });

        }
    </script>
    <!-- Add this script at the end of the HTML body -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <!-- Bootstrap Modal -->
    <div class="modal fade" id="myModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
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
</body>

</html>