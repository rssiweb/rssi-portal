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
                        const products = [{
                                id: 1,
                                name: "Amazon India E-Gift Voucher",
                                price: 100,
                                image: "https://fulfilmentstorage.blob.core.windows.net/images/3e43eff3-b235-4087-8477-173fb9acbbc2_ASV.jpg",
                                soldOut: false
                            },
                            {
                                id: 2,
                                name: "Big Basket Gift Voucher",
                                price: 100,
                                image: "https://5.imimg.com/data5/SELLER/Default/2024/3/396938205/YY/CT/RK/211087561/big-basket-gift-voucher.png",
                                soldOut: false
                            },
                            {
                                id: 3,
                                name: "Amazon India E-Gift Voucher",
                                price: 1000,
                                image: "https://vouchervia.com/wp-content/uploads/2024/04/gift_voucher-3.png",
                                soldOut: true
                            },
                            {
                                id: 4,
                                name: "Nykaa Gift Card",
                                price: 100,
                                image: "https://res.cloudinary.com/dcm/image/upload/v1700040552/prod/n/nykaa.webp",
                                soldOut: false
                            },
                            {
                                id: 5,
                                name: "Zomato E-Gift Voucher",
                                price: 100,
                                image: "https://m.media-amazon.com/images/I/41aLwzw85iL.jpg",
                                soldOut: true
                            }
                        ];

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
        let cart = {};

        function updateCart(productId, productName, price, count) {
            if (count > 0) {
                cart[productId] = {
                    name: productName,
                    price: price,
                    count: count
                };
            } else {
                delete cart[productId];
            }
            renderCart();
        }

        function renderCart() {
            const cartList = document.getElementById('cartList');
            const cartTotal = document.getElementById('cartTotal');
            cartList.innerHTML = '';
            let total = 0;

            for (const [productId, item] of Object.entries(cart)) {
                const listItem = document.createElement('li');
                listItem.className = 'list-group-item d-flex justify-content-between align-items-center';
                listItem.textContent = `${item.name} x ${item.count}`;
                const itemTotal = item.price * item.count;
                total += itemTotal;
                listItem.innerHTML += `<span>${itemTotal} Points</span>`;
                cartList.appendChild(listItem);
            }
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
            if (Object.keys(cart).length === 0) {
                alert('Your cart is empty!');
            } else {
                alert('Order placed successfully!');
                cart = {};
                renderCart();
            }
        }
    </script>
</body>

</html>