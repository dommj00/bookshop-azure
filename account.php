<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit;
}

require_once 'db_config.php';

// Get user information - VULNERABILITY: SQL injection possible
$userId = $_SESSION['user_id'];
$userSql = "SELECT * FROM Users WHERE UserID = $userId";
$userStmt = sqlsrv_query($conn, $userSql);
$user = sqlsrv_fetch_array($userStmt, SQLSRV_FETCH_ASSOC);

// Get user's orders
$ordersSql = "SELECT * FROM Orders WHERE UserID = $userId ORDER BY OrderDate DESC";
$ordersStmt = sqlsrv_query($conn, $ordersSql);

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
        
        .logout-btn {
            background: #e74c3c;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
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
                    <a href="#profile" class="active">Profile Settings</a>
                    <a href="#orders">Order History</a>
                    <a href="#security">Security Settings</a>
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
                
                <section class="section" id="profile">
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
                
                <section class="section" id="orders">
                    <h2>Order History</h2>
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Date</th>
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
                                    echo '<tr>';
                                    echo '<td>#' . str_pad($order['OrderID'], 5, '0', STR_PAD_LEFT) . '</td>';
                                    echo '<td>' . $order['OrderDate']->format('M d, Y') . '</td>';
                                    echo '<td>$' . number_format($order['TotalAmount'], 2) . '</td>';
                                    echo '<td class="' . $statusClass . '">' . $order['Status'] . '</td>';
                                    // VULNERABILITY: XSS - displaying address without proper sanitization
                                    echo '<td>' . $order['ShippingAddress'] . '</td>';
                                    echo '</tr>';
                                }
                                
                                if (!$hasOrders) {
                                    echo '<tr><td colspan="5" style="text-align: center; color: #7f8c8d;">No orders found</td></tr>';
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </section>
                
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
</body>
</html>
