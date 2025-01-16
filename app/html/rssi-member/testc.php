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
<!-- <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $orderData = json_decode(file_get_contents('php://input'), true);

            // Insert into orders table
            $totalPoints = $orderData['totalPoints'];
            $query = "INSERT INTO orders (total_points) VALUES ($totalPoints) RETURNING id";
            $result = pg_query($con, $query);
            $orderId = pg_fetch_result($result, 0, 'id');

            // Insert into order_items table
            foreach ($orderData['cart'] as $item) {
                $productId = $item['productId'];
                $quantity = $item['count'];
                pg_query($con, "INSERT INTO order_items (order_id, product_id, quantity) VALUES ($orderId, $productId, $quantity)");
            }

            echo json_encode(['status' => 'success']);
        }
        ?> -->

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart UI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
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
    <div class="container py-5">
        <div class="row">
            <!-- Left Section: Total Points -->
            <div class="col-md-3">
                <div class="left-section text-center">
                    <h4>Total Points</h4>
                    <div id="totalPoints" class="display-4 text-primary">1000</div>
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
                        alert('Order placed successfully!');
                        location.reload(); // Reload the page to prevent multiple submissions
                    } else {
                        alert('Failed to place the order. Please try again.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while placing the order.');
                });
        }
    </script>
</body>

</html>