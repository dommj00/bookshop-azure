<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - BookShop</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .cart-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .cart-item {
            display: flex;
            align-items: center;
            background: white;
            padding: 20px;
            margin-bottom: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .cart-item img {
            width: 80px;
            height: 120px;
            object-fit: cover;
            margin-right: 20px;
            border-radius: 4px;
        }
        
        .item-details {
            flex-grow: 1;
        }
        
        .item-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .item-price {
            color: #e74c3c;
            font-size: 16px;
        }
        
        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-right: 20px;
        }
        
        .quantity-btn {
            background: #3498db;
            color: white;
            border: none;
            width: 30px;
            height: 30px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 18px;
        }
        
        .quantity {
            font-size: 16px;
            min-width: 30px;
            text-align: center;
        }
        
        .remove-btn {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .cart-summary {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
        
        .total {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            text-align: right;
            margin: 20px 0;
        }
        
        .checkout-btn {
            background: #27ae60;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 18px;
            width: 100%;
        }
        
        .checkout-btn:hover {
            background: #229954;
        }
        
        .empty-cart {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
        }
    </style>
</head>
<body>
    <header>
        <h1>BookShop</h1>
        <nav>
            <ul class="nav-list">
                <li><a href="index.html">Home</a></li>
                <li><a href="books.php">Books</a></li>
                <li><a href="cart.php">Cart</a></li>
                <li><a href="login.php">Login</a></li>
                <li><a href="admin/login.php">Admin</a></li>
            </ul>
        </nav>
    </header>
    
    <main class="cart-container">
        <h2>Your Shopping Cart</h2>
        
        <div id="cart-items"></div>
        
        <div class="cart-summary" id="cart-summary" style="display: none;">
            <div class="total" id="cart-total">Total: $0.00</div>
            <button class="checkout-btn" onclick="proceedToCheckout()">Proceed to Checkout</button>
        </div>
    </main>
    
    <?php
    // Image mapping array (same as in books.php)
    $imageMapJson = json_encode(array(
        'The Midnight Garden' => 'midnight-garden.jpg',
        'Echoes of Tomorrow' => 'echoes-tomorrow.jpg',
        'The Last Bookkeeper' => 'last-bookkeeper.jpg',
        'Crimson Tides' => 'crimson-tides.jpg',
        'The Coffee Shop Chronicles' => 'coffee-shop.jpg',
        'The Digital Revolution' => 'digital-revolution.jpg',
        'Mindful Living' => 'mindful-living.jpg',
        'The Economics of Everything' => 'economics-everything.jpg',
        'Climate Action Now' => 'climate-action.jpg',
        'Mathematics Made Simple' => 'math-simple.jpg',
        'Introduction to Psychology' => 'intro-psychology.jpg',
        'World History: A Complete Guide' => 'world-history.jpg',
        'Programming Fundamentals' => 'programming-fundamentals.jpg',
        'Journey to the Stars' => 'journey-stars.jpg',
        'From Poverty to Purpose' => 'poverty-purpose.jpg',
        'The Innovator\'s Mind' => 'innovators-mind.jpg',
        'Breaking Barriers' => 'breaking-barriers.jpg'
    ));
    ?>
    
    <script>
        const imageMap = <?php echo $imageMapJson; ?>;
        
        function loadCart() {
            const cart = JSON.parse(localStorage.getItem('cart') || '[]');
            const cartItemsDiv = document.getElementById('cart-items');
            const cartSummary = document.getElementById('cart-summary');
            
            if (cart.length === 0) {
                cartItemsDiv.innerHTML = '<div class="empty-cart"><p>Your cart is empty</p><a href="books.php">Continue Shopping</a></div>';
                cartSummary.style.display = 'none';
                return;
            }
            
            // Group items by title to handle quantities
            const groupedCart = {};
            cart.forEach(item => {
                if (groupedCart[item.title]) {
                    groupedCart[item.title].quantity++;
                } else {
                    groupedCart[item.title] = {...item, quantity: 1};
                }
            });
            
            let html = '';
            let total = 0;
            
            Object.values(groupedCart).forEach(item => {
                const itemTotal = item.price * item.quantity;
                total += itemTotal;
                const image = imageMap[item.title] || 'default-book.jpg';
                
                html += `
                    <div class="cart-item">
                        <img src="images/${image}" alt="${item.title}">
                        <div class="item-details">
                            <div class="item-title">${item.title}</div>
                            <div class="item-price">$${item.price.toFixed(2)}</div>
                        </div>
                        <div class="quantity-controls">
                            <button class="quantity-btn" onclick="updateQuantity('${item.title}', -1)">-</button>
                            <span class="quantity">${item.quantity}</span>
                            <button class="quantity-btn" onclick="updateQuantity('${item.title}', 1)">+</button>
                        </div>
                        <div class="item-total">$${itemTotal.toFixed(2)}</div>
                        <button class="remove-btn" onclick="removeItem('${item.title}')">Remove</button>
                    </div>
                `;
            });
            
            cartItemsDiv.innerHTML = html;
            document.getElementById('cart-total').textContent = `Total: $${total.toFixed(2)}`;
            cartSummary.style.display = 'block';
        }
        
        function updateQuantity(title, change) {
            let cart = JSON.parse(localStorage.getItem('cart') || '[]');
            
            if (change > 0) {
                // Find the first item with this title to get the price
                const item = cart.find(i => i.title === title);
                if (item) {
                    cart.push({title: item.title, price: item.price});
                }
            } else {
                // Remove one instance
                const index = cart.findIndex(i => i.title === title);
                if (index > -1) {
                    cart.splice(index, 1);
                }
            }
            
            localStorage.setItem('cart', JSON.stringify(cart));
            loadCart();
        }
        
        function removeItem(title) {
            let cart = JSON.parse(localStorage.getItem('cart') || '[]');
            cart = cart.filter(item => item.title !== title);
            localStorage.setItem('cart', JSON.stringify(cart));
            loadCart();
        }
        
        function proceedToCheckout() {
            window.location.href = 'checkout.php';
        }
        
        // Load cart on page load
        loadCart();
    </script>
</body>
</html>
