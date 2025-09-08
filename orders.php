<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Lookup - BookShop</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .orders-container {
            max-width: 1000px;
            margin: 20px auto;
            padding: 20px;
        }
        
        .search-section {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .search-section h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .search-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        
        .form-group input {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .search-options {
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        
        .search-btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        
        .search-btn:hover {
            background: #2980b9;
        }
        
        .results-section {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .results-section h3 {
            color: #2c3e50;
            margin-bottom: 20px;
        }
        
        .orders-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .orders-table th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            border-bottom: 2px solid #dee2e6;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .orders-table td {
            padding: 15px;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .orders-table tr:hover {
            background: #f8f9fa;
        }
        
        .status-pending {
            color: #f39c12;
            font-weight: bold;
        }
        
        .status-processing {
            color: #3498db;
            font-weight: bold;
        }
        
        .status-shipped {
            color: #9b59b6;
            font-weight: bold;
        }
        
        .status-completed {
            color: #27ae60;
            font-weight: bold;
        }
        
        .status-cancelled {
            color: #e74c3c;
            font-weight: bold;
        }
        
        .no-results {
            text-align: center;
            color: #7f8c8d;
            padding: 40px;
            font-style: italic;
        }
        
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .search-tips {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 4px;
            margin-top: 20px;
        }
        
        .search-tips h4 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .search-tips ul {
            color: #6c757d;
            margin-left: 20px;
        }
        
        .order-details {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            margin: 5px 0;
        }
        
        .order-number {
            font-family: monospace;
            font-weight: bold;
            color: #2c3e50;
        }
    </style>
</head>
<body>
<div class="security-warning-banner">
    <span class="warning-icon">⚠️</span>
    <span class="warning-text">
        <strong>SECURITY DEMONSTRATION SITE</strong> - This website is intentionally vulnerable and contains fictional data. 
        DO NOT enter real personal or financial information. This is an educational security testing environment.
    </span>
</div>
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
    
    <main class="orders-container">
        <div class="search-section">
            <h2>Order Lookup</h2>
            
            <div class="alert alert-info">
                <strong>Find Your Order:</strong> Enter your order number (received after checkout) or the email address used when placing your order.
            </div>
            
            <form method="GET" action="orders.php" class="search-form">
                <div class="form-group">
                    <label for="search_query">Order Number or Email Address</label>
                    <input type="text" 
                           id="search_query" 
                           name="search_query" 
                           placeholder="Enter order number (e.g., ORD-2024123456) or email address" 
                           value="<?php echo htmlspecialchars($_GET['search_query'] ?? ''); ?>"
                           required>
                </div>
                
                <div class="search-options">
                    <button type="submit" name="search_orders" class="search-btn">Search Orders</button>
                </div>
            </form>
            
            <div class="search-tips">
                <h4>Search Tips:</h4>
                <ul>
                    <li><strong>Order Number:</strong> Format is ORD-YYYY###### (e.g., ORD-2024123456)</li>
                    <li><strong>Email:</strong> Use the exact email address from your order confirmation</li>
                    <li><strong>Case Sensitive:</strong> Email searches are not case-sensitive</li>
                    <li><strong>Guest Orders:</strong> No account required - all orders can be tracked</li>
                </ul>
            </div>
        </div>
        
        <?php
        if (isset($_GET['search_orders']) && !empty($_GET['search_query'])) {
            require_once 'db_config.php';
            
            $searchQuery = $_GET['search_query'];
            $results = [];
            
            echo '<div class="results-section">';
            echo '<h3>Search Results for: <span class="order-number">' . htmlspecialchars($searchQuery) . '</span></h3>';
            
            // VULNERABILITY: SQL Injection in search
            if (strpos(strtoupper($searchQuery), 'ORD-') === 0) {
                // Search by order number
                $searchSql = "SELECT o.*, o.CustomerName as CustomerDisplayName, o.CustomerEmail as DisplayEmail,
                             (SELECT COUNT(*) FROM OrderItems oi WHERE oi.OrderID = o.OrderID) as ItemCount
                             FROM Orders o 
                             WHERE o.OrderNumber = '$searchQuery'";
            } else {
                // Search by email
                $searchSql = "SELECT o.*, o.CustomerName as CustomerDisplayName, o.CustomerEmail as DisplayEmail,
                             (SELECT COUNT(*) FROM OrderItems oi WHERE oi.OrderID = o.OrderID) as ItemCount
                             FROM Orders o 
                             WHERE o.CustomerEmail LIKE '%$searchQuery%'
                             ORDER BY o.OrderDate DESC";
            }
            
            $searchStmt = sqlsrv_query($conn, $searchSql);
            
            if ($searchStmt) {
                $foundResults = false;
                echo '<table class="orders-table">';
                echo '<thead>';
                echo '<tr>';
                echo '<th>Order Number</th>';
                echo '<th>Customer Name</th>';
                echo '<th>Email Address</th>';
                echo '<th>Order Date</th>';
                echo '<th>Items</th>';
                echo '<th>Total Amount</th>';
                echo '<th>Status</th>';
                echo '<th>Shipping Address</th>';
                echo '</tr>';
                echo '</thead>';
                echo '<tbody>';
                
                while ($searchResult = sqlsrv_fetch_array($searchStmt, SQLSRV_FETCH_ASSOC)) {
                    $foundResults = true;
                    $statusClass = 'status-' . strtolower($searchResult['Status']);
                    $orderNumber = $searchResult['OrderNumber'] ?? ('ORD-' . str_pad($searchResult['OrderID'], 6, '0', STR_PAD_LEFT));
                    
                    echo '<tr>';
                    echo '<td><span class="order-number">' . htmlspecialchars($orderNumber) . '</span></td>';
                    echo '<td>' . htmlspecialchars($searchResult['CustomerDisplayName'] ?? 'Guest Customer') . '</td>';
                    echo '<td>' . htmlspecialchars($searchResult['DisplayEmail'] ?? 'No Email') . '</td>';
                    echo '<td>' . $searchResult['OrderDate']->format('M d, Y H:i') . '</td>';
                    echo '<td>' . $searchResult['ItemCount'] . ' items</td>';
                    echo '<td>$' . number_format($searchResult['TotalAmount'], 2) . '</td>';
                    echo '<td><span class="' . $statusClass . '">' . $searchResult['Status'] . '</span></td>';
                    // VULNERABILITY: XSS - displaying address without proper sanitization
                    echo '<td>' . $searchResult['ShippingAddress'] . '</td>';
                    echo '</tr>';
                }
                
                echo '</tbody>';
                echo '</table>';
                
                if (!$foundResults) {
                    echo '<div class="no-results">';
                    echo '<p>No orders found matching your search criteria.</p>';
                    echo '<p>Please check your order number or email address and try again.</p>';
                    echo '<div style="margin-top: 20px;">';
                    echo '<strong>Common Issues:</strong>';
                    echo '<ul style="text-align: left; display: inline-block; margin-top: 10px;">';
                    echo '<li>Make sure to include "ORD-" in your order number</li>';
                    echo '<li>Check for typos in your email address</li>';
                    echo '<li>Orders may take a few minutes to appear in our system</li>';
                    echo '</ul>';
                    echo '</div>';
                    echo '</div>';
                }
            } else {
                echo '<div class="alert alert-error">';
                echo '<strong>Search Error:</strong> Unable to search orders at this time. Please try again later.';
                echo '</div>';
            }
            
            echo '</div>';
            sqlsrv_close($conn);
        }
        ?>
    </main>
    
    <script>
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
        
        document.addEventListener('DOMContentLoaded', updateNavigation);
    </script>
</body>
</html>
