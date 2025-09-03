<?php
session_start();

// Simple check - if no session, redirect to login
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php?redirect=account.php');
    exit;
}

require_once 'db_config.php';

// Get user information - VULNERABILITY: SQL injection possible
$userId = $_SESSION['user_id'];
$userSql = "SELECT * FROM Users WHERE UserID = $userId";
$userStmt = sqlsrv_query($conn, $userSql);
$user = sqlsrv_fetch_array($userStmt, SQLSRV_FETCH_ASSOC);

// Get user's orders with details
$ordersSql = "SELECT o.*, 
              (SELECT COUNT(*) FROM OrderItems oi WHERE oi.OrderID = o.OrderID) as ItemCount
              FROM Orders o 
              WHERE o.UserID = $userId 
              ORDER BY o.OrderDate DESC";
$ordersStmt = sqlsrv_query($conn, $ordersSql);

// Get user's payment methods - VULNERABILITY: Displaying sensitive data
$paymentSql = "SELECT * FROM PaymentMethods WHERE UserID = $userId";
$paymentStmt = sqlsrv_query($conn, $paymentSql);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $firstName = $_POST['first_name'];
    $lastName = $_POST['last_name'];
    $email = $_POST['email'];
    
    // VULNERABILITY: SQL Injection in update
    $updateSql = "UPDATE Users SET FirstName = '$firstName', LastName = '$lastName', Email = '$email' 
                  WHERE UserID = $userId";
    
    if (sqlsrv_query($conn, $updateSql)) {
        $success = "Profile updated successfully!";
        // Refresh user data
        $userStmt = sqlsrv_query($conn, $userSql);
        $user = sqlsrv_fetch_array($userStmt, SQLSRV_FETCH_ASSOC);
    } else {
        $error = "Failed to update profile.";
    }
}

// Handle payment method update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_payment'])) {
    $cardNumber = $_POST['card_number'];
    $cardholderName = $_POST['cardholder_name'];
    $cvv = $_POST['cvv'];
    $expiryDate = $_POST['expiry_date'];
    
    // VULNERABILITY: Storing sensitive payment data in plain text
    $updatePaymentSql = "UPDATE PaymentMethods SET 
                        CardNumber = '$cardNumber', 
                        CardholderName = '$cardholderName', 
                        CVV = '$cvv',
                        ExpiryDate = '$expiryDate'
                        WHERE UserID = $userId";
    
    if (sqlsrv_query($conn, $updatePaymentSql)) {
        $paymentSuccess = "Payment information updated successfully!";
    } else {
        $paymentError = "Failed to update payment information.";
    }
    
    // Refresh payment data
    $paymentStmt = sqlsrv_query($conn, $paymentSql);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - BookShop</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .account-container {
            max-width: 1000px;
            margin: 20px auto;
            padding: 20px;
        }
        
        .account-grid {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 30px;
        }
        
        .sidebar {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            height: fit-content;
        }
        
        .sidebar h3 {
            color: #2c3e50;
            margin-bottom: 20px;
        }
        
        .user-info {
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .user-info p {
            margin: 5px 0;
            color: #7f8c8d;
        }
        
        .sidebar-links a {
            display: block;
            padding: 10px;
            color: #3498db;
            text-decoration: none;
            border-radius: 4px;
            margin-bottom: 5px;
            cursor: pointer;
        }
        
        .sidebar-links a:hover {
            background: #ecf0f1;
        }
        
        .sidebar-links a.active {
            background: #3498db;
            color: white;
        }
        
        .main-content {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .section {
            margin-bottom: 30px;
            display: none;
        }
        
        .section.active {
            display: block;
        }
        
        .section h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #ecf0f1;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .form-row {
            display: flex;
            gap: 20px;
        }
        
        .form-row .form-group {
            flex: 1;
        }
        
        .update-btn {
            background: #27ae60;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .update-btn:hover {
            background: #229954;
        }
        
        .orders-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .orders-table th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            border-bottom: 2px solid #dee2e6;
        }
        
        .orders-table td {
            padding: 12px;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .status-pending {
            color: #f39c12;
        }
        
        .status-completed {
            color: #27ae60;
        }
        
        .status-cancelled {
            color: #e74c3c;
        }
        
        .alert {
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .search-orders {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .search-orders input {
            width: 300px;
            padding: 8px;
            margin-right: 10px;
        }
        
        .search-orders button {
            background: #3498db;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .payment-display {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .payment-display p {
            margin: 5px 0;
        }
        
        .sensitive-data {
            color: #e74c3c;
            font-family: monospace;
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
                <li><a href="account.php">My Account</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>
    
    <main class="account-container">
        <div class="account-grid">
            <div class="sidebar">
                <h3>My Account</h3>
                <div class="user-info">
                    <p><strong><?php echo htmlspecialchars($user['Username']); ?></strong></p>
                    <p><?php echo htmlspecialchars($user['Email']); ?></p>
                    <p>Member since: <?php echo $user['CreatedDate']->format('M Y'); ?></p>
                </div>
                <div class="sidebar-links">
                    <a href="#" onclick="showSection('profile')" class="active">Profile Settings</a>
                    <a href="#" onclick="showSection('orders')">Order History</a>
                    <a href="#" onclick="showSection('search-orders')">Search Orders</a>
                    <a href="#" onclick="showSection('payment')">Payment Information</a>
                    <a href="#" onclick="showSection('security')">Security Settings</a>
                    <a href="logout.php" style="color: #e74c3c;">Logout</a>
                </div>
            </div>
            
            <div class="main-content">
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if (isset($paymentSuccess)): ?>
                    <div class="alert alert-success"><?php echo $paymentSuccess; ?></div>
                <?php endif; ?>
                
                <?php if (isset($paymentError)): ?>
                    <div class="alert alert-error"><?php echo $paymentError; ?></div>
                <?php endif; ?>
                
                <!-- Profile Settings Section -->
                <section class="section active" id="profile">
                    <h2>Profile Settings</h2>
                    <form method="POST" action="account.php">
                        <div class="form-row">
                            <div class="form-group">
                                <label>First Name</label>
                                <input type="text" name="first_name" value="<?php echo htmlspecialchars($user['FirstName'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Last Name</label>
                                <input type="text" name="last_name" value="<?php echo htmlspecialchars($user['LastName'] ?? ''); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($user['Email']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" value="<?php echo htmlspecialchars($user['Username']); ?>" disabled>
                            <small style="color: #7f8c8d;">Username cannot be changed</small>
                        </div>
                        
                        <button type="submit" name="update_profile" class="update-btn">Update Profile</button>
                    </form>
                </section>
                
                <!-- Order History Section -->
                <section class="section" id="orders">
                    <h2>Order History</h2>
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Order Number</th>
                                <th>Date</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Shipping Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($ordersStmt) {
                                $hasOrders = false;
                                while ($order = sqlsrv_fetch_array($ordersStmt, SQLSRV_FETCH_ASSOC)) {
                                    $hasOrders = true;
                                    $statusClass = 'status-' . strtolower($order['Status']);
                                    $orderNumber = 'ORD-' . str_pad($order['OrderID'], 6, '0', STR_PAD_LEFT);
                                    echo '<tr>';
                                    echo '<td><strong>' . $orderNumber . '</strong></td>';
                                    echo '<td>' . $order['OrderDate']->format('M d, Y') . '</td>';
                                    echo '<td>' . $order['ItemCount'] . ' items</td>';
                                    echo '<td>$' . number_format($order['TotalAmount'], 2) . '</td>';
                                    echo '<td class="' . $statusClass . '">' . $order['Status'] . '</td>';
                                    // VULNERABILITY: XSS - displaying address without proper sanitization
                                    echo '<td>' . $order['ShippingAddress'] . '</td>';
                                    echo '</tr>';
                                }
                                
                                if (!$hasOrders) {
                                    echo '<tr><td colspan="6" style="text-align: center; color: #7f8c8d;">No orders found</td></tr>';
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </section>
                
                <!-- Search Orders Section -->
                <section class="section" id="search-orders">
                    <h2>Search Orders</h2>
                    <div class="search-orders">
                        <form method="GET" action="account.php" id="search-form">
                            <input type="text" name="search_query" placeholder="Enter order number (ORD-123456) or email address" 
                                   value="<?php echo $_GET['search_query'] ?? ''; ?>">
                            <button type="submit" name="search_orders">Search Orders</button>
                        </form>
                    </div>
                    
                    <?php
                    if (isset($_GET['search_orders']) && !empty($_GET['search_query'])) {
                        $searchQuery = $_GET['search_query'];
                        
                        // VULNERABILITY: SQL Injection in search
                        if (strpos($searchQuery, 'ORD-') === 0) {
                            // Search by order number
                            $orderNum = str_replace('ORD-', '', $searchQuery);
                            $searchSql = "SELECT o.*, u.Email, u.Username,
                                         (SELECT COUNT(*) FROM OrderItems oi WHERE oi.OrderID = o.OrderID) as ItemCount
                                         FROM Orders o 
                                         JOIN Users u ON o.UserID = u.UserID
                                         WHERE o.OrderID = $orderNum";
                        } else {
                            // Search by email
                            $searchSql = "SELECT o.*, u.Email, u.Username,
                                         (SELECT COUNT(*) FROM OrderItems oi WHERE oi.OrderID = o.OrderID) as ItemCount
                                         FROM Orders o 
                                         JOIN Users u ON o.UserID = u.UserID
                                         WHERE u.Email LIKE '%$searchQuery%'
                                         ORDER BY o.OrderDate DESC";
                        }
                        
                        $searchStmt = sqlsrv_query($conn, $searchSql);
                        
                        if ($searchStmt) {
                            echo '<h3>Search Results for: ' . htmlspecialchars($searchQuery) . '</h3>';
                            echo '<table class="orders-table">';
                            echo '<thead><tr><th>Order Number</th><th>Customer</th><th>Email</th><th>Date</th><th>Items</th><th>Total</th><th>Status</th></tr></thead>';
                            echo '<tbody>';
                            
                            $foundResults = false;
                            while ($searchResult = sqlsrv_fetch_array($searchStmt, SQLSRV_FETCH_ASSOC)) {
                                $foundResults = true;
                                $statusClass = 'status-' . strtolower($searchResult['Status']);
                                $orderNumber = 'ORD-' . str_pad($searchResult['OrderID'], 6, '0', STR_PAD_LEFT);
                                
                                echo '<tr>';
                                echo '<td><strong>' . $orderNumber . '</strong></td>';
                                echo '<td>' . htmlspecialchars($searchResult['Username']) . '</td>';
                                echo '<td>' . htmlspecialchars($searchResult['Email']) . '</td>';
                                echo '<td>' . $searchResult['OrderDate']->format('M d, Y') . '</td>';
                                echo '<td>' . $searchResult['ItemCount'] . ' items</td>';
                                echo '<td>$' . number_format($searchResult['TotalAmount'], 2) . '</td>';
                                echo '<td class="' . $statusClass . '">' . $searchResult['Status'] . '</td>';
                                echo '</tr>';
                            }
                            
                            if (!$foundResults) {
                                echo '<tr><td colspan="7" style="text-align: center; color: #7f8c8d;">No orders found for your search</td></tr>';
                            }
                            
                            echo '</tbody></table>';
                        }
                    }
                    ?>
                </section>
                
                <!-- Payment Information Section -->
                <section class="section" id="payment">
                    <h2>Payment Information</h2>
                    
                    <?php
                    $paymentMethod = sqlsrv_fetch_array($paymentStmt, SQLSRV_FETCH_ASSOC);
                    if ($paymentMethod):
                    ?>
                    <div class="payment-display">
                        <h3>Current Payment Method</h3>
                        <p><strong>Cardholder Name:</strong> <?php echo htmlspecialchars($paymentMethod['CardholderName']); ?></p>
                        <!-- VULNERABILITY: Displaying full credit card number -->
                        <p><strong>Card Number:</strong> <span class="sensitive-data"><?php echo $paymentMethod['CardNumber']; ?></span></p>
                        <p><strong>CVV:</strong> <span class="sensitive-data"><?php echo $paymentMethod['CVV']; ?></span></p>
                        <p><strong>Expiry Date:</strong> <?php echo htmlspecialchars($paymentMethod['ExpiryDate'] ?? ''); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <h3>Update Payment Information</h3>
                    <form method="POST" action="account.php">
                        <div class="form-group">
                            <label>Cardholder Name</label>
                            <input type="text" name="cardholder_name" value="<?php echo htmlspecialchars($paymentMethod['CardholderName'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Card Number</label>
                            <input type="text" name="card_number" placeholder="1234 5678 9012 3456" 
                                   value="<?php echo $paymentMethod['CardNumber'] ?? ''; ?>" maxlength="16" required>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Expiry Date</label>
                                <input type="text" name="expiry_date" placeholder="MM/YY" 
                                       value="<?php echo htmlspecialchars($paymentMethod['ExpiryDate'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>CVV</label>
                                <input type="text" name="cvv" placeholder="123" 
                                       value="<?php echo $paymentMethod['CVV'] ?? ''; ?>" maxlength="3" required>
                            </div>
                        </div>
                        
                        <button type="submit" name="update_payment" class="update-btn">Update Payment Information</button>
                    </form>
                </section>
                
                <!-- Security Settings Section -->
                <section class="section" id="security">
                    <h2>Security Settings</h2>
                    <p style="color: #7f8c8d;">Last login: <?php echo $user['LastLoginDate'] ? $user['LastLoginDate']->format('M d, Y H:i') : 'Never'; ?></p>
                    <p style="margin-top: 20px;">
                        <a href="change-password.php" class="update-btn" style="text-decoration: none;">Change Password</a>
                    </p>
                </section>
            </div>
        </div>
    </main>
    
    <script>
        function showSection(sectionId) {
            // Hide all sections
            const sections = document.querySelectorAll('.section');
            sections.forEach(section => section.classList.remove('active'));
            
            // Show selected section
            document.getElementById(sectionId).classList.add('active');
            
            // Update sidebar active link
            const links = document.querySelectorAll('.sidebar-links a');
            links.forEach(link => link.classList.remove('active'));
            event.target.classList.add('active');
        }
        
        // Handle search form redirect
        document.getElementById('search-form').addEventListener('submit', function(e) {
            const searchQuery = document.querySelector('input[name="search_query"]').value;
            if (searchQuery) {
                window.location.href = `account.php?search_orders=1&search_query=${encodeURIComponent(searchQuery)}`;
                e.preventDefault();
            }
        });
        
        // Show search results section if we have search parameters
        if (window.location.search.includes('search_orders')) {
            showSection('search-orders');
            // Update active link
            document.querySelectorAll('.sidebar-links a').forEach(link => link.classList.remove('active'));
            document.querySelector('a[onclick="showSection(\'search-orders\')"]').classList.add('active');
        }
    </script>
</body>
</html>
