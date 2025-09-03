<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - BookShop</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .checkout-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .checkout-form {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .form-row {
            display: flex;
            gap: 20px;
        }
        
        .form-row .form-group {
            flex: 1;
        }
        
        .order-summary {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .error {
            color: #e74c3c;
            font-size: 14px;
            margin-top: 5px;
        }
        
        .success-message {
            background: #27ae60;
            color: white;
            padding: 30px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .success-message a {
            color: white;
            text-decoration: underline;
        }
        
        .submit-btn {
            background: #27ae60;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 18px;
            width: 100%;
        }
        
        .submit-btn:hover {
            background: #229954;
        }
        
        .order-number-highlight {
            background: rgba(255,255,255,0.2);
            padding: 15px;
            border-radius: 4px;
            margin: 15px 0;
            border: 2px solid white;
        }
        
        .countdown-timer {
            background: rgba(255,255,255,0.1);
            padding: 10px;
            border-radius: 4px;
            margin: 15px 0;
        }
        
        .action-buttons {
            margin-top: 25px;
        }
        
        .print-btn {
            background: #34495e;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 10px;
            font-size: 16px;
        }
        
        .track-btn {
            background: white;
            color: #27ae60;
            text-decoration: none;
            padding: 12px 20px;
            border-radius: 4px;
            margin-right: 10px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <header>
        <h1>BookShop</h1>
        <nav>
            <ul class="nav-list" id="main-nav">
                <li><a href="index.html">Home</a></li>
                <li><a href="books.php">Books</a></li>
                <li><a href="cart.php">Cart</a></li>
                <li><a href="orders.php">Orders</a></li>
                <li><a href="login.php" id="login-link">Login</a></li>
                <li><a href="account.php" id="account-link" style="display: none;">My Account</a></li>
                <li><a href="logout.php" id="logout-link" style="display: none;">Logout</a></li>
                <li id="welcome-user" style="display: none; color: #27ae60;"></li>
                <li><a href="admin/login.php">Admin</a></li>
            </ul>
        </nav>
    </header>
    
    <main class="checkout-container">
        <h2>Checkout</h2>
        
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Process the order (with security vulnerabilities!)
            require_once 'db_config.php';
            
            // Get form data - NO VALIDATION (Security Issue!)
            $name = $_POST['name'];
            $email = $_POST['email'];
            $cardNumber = $_POST['cardNumber'];
            $cvv = $_POST['cvv'];
            $address = $_POST['address'];
            $city = $_POST['city'];
            $state = $_POST['state'];
            $zip = $_POST['zip'];
            
            // Get cart from POST data
            $cart = json_decode($_POST['cart'], true);
            $total = 0;
            foreach ($cart as $item) {
                $total += $item['price'] * ($item['quantity'] ?? 1);
            }
            
            // Generate unique order number
            $orderNumber = 'ORD-' . date('Y') . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
            
            // Insert order - SQL INJECTION VULNERABILITY!
            // Store actual form data instead of UserID lookup
            $orderSql = "INSERT INTO Orders (UserID, OrderDate, TotalAmount, Status, ShippingAddress, OrderNumber, CustomerEmail, CustomerName) 
                         VALUES (NULL, GETDATE(), $total, 'Pending', '$address, $city, $state $zip', '$orderNumber', '$email', '$name')";
            
            $orderResult = sqlsrv_query($conn, $orderSql);
            
            if ($orderResult) {
                // Get the inserted order ID
                $orderIdSql = "SELECT @@IDENTITY as OrderID";
                $orderIdResult = sqlsrv_query($conn, $orderIdSql);
                $orderIdRow = sqlsrv_fetch_array($orderIdResult);
                $newOrderId = $orderIdRow['OrderID'];
                
                // Store payment info - STORING SENSITIVE DATA IN PLAIN TEXT!
                $paymentSql = "INSERT INTO PaymentMethods (UserID, CardNumber, CardholderName, CVV) 
                              VALUES (NULL, '$cardNumber', '$name', '$cvv')";
                sqlsrv_query($conn, $paymentSql);
                
                // Log the transaction - XSS VULNERABILITY!
                $logSql = "INSERT INTO AuditLogs (UserID, Action, TableAffected, UserInput) 
                          VALUES (NULL, 'Order Placed', 'Orders', '$name placed order for $$total')";
                sqlsrv_query($conn, $logSql);
                
                echo '<div class="success-message" id="order-confirmation">
                        <h3>Order Placed Successfully!</h3>
                        <div class="order-number-highlight">
                            <p style="font-size: 24px; margin: 10px 0;"><strong>Order Number: ' . $orderNumber . '</strong></p>
                            <p style="font-size: 14px; margin: 5px 0;">Please save this order number for your records</p>
                        </div>
                        <p><strong>Customer:</strong> ' . htmlspecialchars($name) . '</p>
                        <p><strong>Email:</strong> ' . htmlspecialchars($email) . '</p>
                        <p><strong>Order Total:</strong> $' . number_format($total, 2) . '</p>
                        <p>A confirmation has been sent to ' . htmlspecialchars($email) . '</p>
                        
                        <div id="countdown-timer" class="countdown-timer">
                            This confirmation will remain visible for <span id="countdown">60</span> seconds, or until you navigate away.
                        </div>
                        
                        <div class="action-buttons">
                            <button onclick="window.print()" class="print-btn">
                                Print Confirmation
                            </button>
                            <a href="orders.php?search_query=' . urlencode($orderNumber) . '" class="track-btn">
                                Track This Order
                            </a>
                            <a href="books.php" style="color: white; text-decoration: underline; margin-right: 20px; font-size: 16px;">Continue Shopping</a>
                            <a href="index.html" style="color: white; text-decoration: underline; font-size: 16px;">Return to Home</a>
                        </div>
                      </div>';
                
                // Hide the checkout form and add countdown timer
                echo '<script>
                    // Clear the cart
                    localStorage.removeItem("cart");
                    
                    // Hide the order summary and checkout form
                    const orderSummary = document.querySelector(".order-summary");
                    const checkoutForm = document.querySelector(".checkout-form");
                    
                    if (orderSummary) orderSummary.style.display = "none";
                    if (checkoutForm) checkoutForm.style.display = "none";
                    
                    // Scroll to top to ensure success message is visible
                    window.scrollTo(0, 0);
                    
                    // Add 60-second countdown timer
                    let timeLeft = 60;
                    const countdownElement = document.getElementById("countdown");
                    const timerElement = document.getElementById("countdown-timer");
                    
                    const timer = setInterval(function() {
                        timeLeft--;
                        countdownElement.textContent = timeLeft;
                        
                        if (timeLeft <= 10) {
                            timerElement.style.background = "rgba(231, 76, 60, 0.2)";
                            timerElement.style.color = "#e74c3c";
                        }
                        
                        if (timeLeft <= 0) {
                            clearInterval(timer);
                            timerElement.innerHTML = "Timer expired - confirmation will remain until you navigate away";
                            timerElement.style.background = "rgba(149, 165, 166, 0.2)";
                            timerElement.style.color = "#95a5a6";
                        }
                    }, 1000);
                </script>';
            } else {
                echo '<div class="error">Error processing order. Please try again.</div>';
            }
            
            sqlsrv_close($conn);
        } else {
        ?>
        
        <div class="order-summary">
            <h3>Order Summary</h3>
            <div id="order-items"></div>
            <div style="font-size: 20px; font-weight: bold; margin-top: 10px;">
                Total: $<span id="order-total">0.00</span>
            </div>
        </div>
        
        <form method="POST" action="checkout.php" class="checkout-form" onsubmit="return validateAndSubmit()">
            <input type="hidden" name="cart" id="cart-data">
            
            <h3>Billing Information</h3>
            
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" required>
            </div>
            
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label>Card Number</label>
                <input type="text" name="cardNumber" placeholder="1234 5678 9012 3456" 
                       maxlength="16" pattern="[0-9]{16}" required>
                <span class="error" id="card-error"></span>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Expiry Date</label>
                    <input type="text" name="expiry" placeholder="MM/YY" pattern="[0-9]{2}/[0-9]{2}" required>
                </div>
                <div class="form-group">
                    <label>CVV</label>
                    <input type="text" name="cvv" placeholder="123" maxlength="3" pattern="[0-9]{3}" required>
                </div>
            </div>
            
            <h3>Shipping Address</h3>
            
            <div class="form-group">
                <label>Street Address</label>
                <input type="text" name="address" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>City</label>
                    <input type="text" name="city" required>
                </div>
                <div class="form-group">
                    <label>State</label>
                    <input type="text" name="state" maxlength="2" required>
                </div>
                <div class="form-group">
                    <label>ZIP Code</label>
                    <input type="text" name="zip" pattern="[0-9]{5}" required>
                </div>
            </div>
            
            <button type="submit" class="submit-btn">Place Order</button>
        </form>
        
        <?php } ?>
    </main>
    
    <script>
        function loadOrderSummary() {
            const cart = JSON.parse(localStorage.getItem('cart') || '[]');
            const orderItemsDiv = document.getElementById('order-items');
            
            if (cart.length === 0) {
                window.location.href = 'cart.php';
                return;
            }
            
            // Group items by title
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
                html += `<div>${item.title} x ${item.quantity} - $${itemTotal.toFixed(2)}</div>`;
            });
            
            orderItemsDiv.innerHTML = html;
            document.getElementById('order-total').textContent = total.toFixed(2);
            
            // Store cart data for form submission
            document.getElementById('cart-data').value = JSON.stringify(Object.values(groupedCart));
        }
        
        function validateAndSubmit() {
            const cardNumber = document.querySelector('input[name="cardNumber"]').value;
            
            // Basic validation - accepts any 16 digits
            if (cardNumber.length !== 16 || !/^\d+$/.test(cardNumber)) {
                document.getElementById('card-error').textContent = 'Please enter a valid 16-digit card number';
                return false;
            }
            
            return true;
        }
        
        function updateNavigation() {
            const isLoggedIn = localStorage.getItem('userLoggedIn');
            const username = localStorage.getItem('username');
            
            if (isLoggedIn === 'true' && username) {
                document.getElementById('login-link').style.display = 'none';
                document.getElementById('account-link').style.display = 'block';
                document.getElementById('logout-link').style.display = 'block';
                document.getElementById('welcome-user').style.display = 'block';
                document.getElementById('welcome-user').textContent = 'Welcome, ' + username + '!';
            } else {
                document.getElementById('login-link').style.display = 'block';
                document.getElementById('account-link').style.display = 'none';
                document.getElementById('logout-link').style.display = 'none';
                document.getElementById('welcome-user').style.display = 'none';
            }
        }
        
        // Load order summary and update navigation on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateNavigation();
            loadOrderSummary();
        });
    </script>
</body>
</html>
