<?php
session_start();

// VULNERABILITY: Weak authorization check
if (!isset($_SESSION['is_admin'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - BookShop</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .dashboard {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        
        .welcome-banner {
            background: #3498db;
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-card h3 {
            color: #7f8c8d;
            margin-bottom: 10px;
        }
        
        .stat-card .number {
            font-size: 36px;
            color: #2c3e50;
            font-weight: bold;
        }
        
        .admin-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .admin-section h2 {
            color: #2c3e50;
            margin-bottom: 20px;
        }
        
        .logout-btn {
            background: #e74c3c;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            float: right;
        }
    </style>
</head>
<body>
    <header>
        <h1>BookShop Admin</h1>
        <nav>
            <ul class="nav-list">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="manage-books.php">Manage Books</a></li>
                <li><a href="view-orders.php">Orders</a></li>
                <li><a href="users.php">Users</a></li>
                <li><a href="../index.html">View Store</a></li>
            </ul>
        </nav>
    </header>
    
    <main class="dashboard">
        <div class="welcome-banner">
            <h2>Welcome, <?php echo $_SESSION['admin_username']; ?>!</h2>
            <p>Admin Level: <?php echo $_SESSION['admin_level']; ?></p>
            <form method="POST" action="logout.php" style="display: inline;">
                <button type="submit" class="logout-btn">Logout</button>
            </form>
        </div>
        
        <div class="stats-grid">
            <?php
            require_once '../db_config.php';
            
            // Get statistics - VULNERABILITY: No input sanitization in queries
            $stats = [];
            
            // Total Users
            $userCount = sqlsrv_query($conn, "SELECT COUNT(*) as count FROM Users");
            $stats['users'] = sqlsrv_fetch_array($userCount)['count'];
            
            // Total Orders
            $orderCount = sqlsrv_query($conn, "SELECT COUNT(*) as count FROM Orders");
            $stats['orders'] = sqlsrv_fetch_array($orderCount)['count'];
            
            // Total Revenue
            $revenue = sqlsrv_query($conn, "SELECT SUM(TotalAmount) as total FROM Orders");
            $stats['revenue'] = sqlsrv_fetch_array($revenue)['total'] ?? 0;
            
            // Total Books
            $bookCount = sqlsrv_query($conn, "SELECT COUNT(*) as count FROM Products");
            $stats['books'] = sqlsrv_fetch_array($bookCount)['count'];
            ?>
            
            <div class="stat-card">
                <h3>Total Users</h3>
                <div class="number"><?php echo $stats['users']; ?></div>
            </div>
            
            <div class="stat-card">
                <h3>Total Orders</h3>
                <div class="number"><?php echo $stats['orders']; ?></div>
            </div>
            
            <div class="stat-card">
                <h3>Total Revenue</h3>
                <div class="number">$<?php echo number_format($stats['revenue'], 2); ?></div>
            </div>
            
            <div class="stat-card">
                <h3>Total Books</h3>
                <div class="number"><?php echo $stats['books']; ?></div>
            </div>
        </div>
        
        <div class="admin-section">
            <h2>Quick Actions</h2>
            <p>• <a href="manage-books.php">Add New Book</a></p>
            <p>• <a href="view-orders.php">View Pending Orders</a></p>
            <p>• <a href="users.php">Manage Users</a></p>
            <p>• <a href="audit-logs.php">View Security Logs</a></p>
        </div>
        
        <!-- VULNERABILITY: Sensitive information in HTML comments -->
        <!-- Database backup scheduled at 2 AM daily -->
        <!-- API Key: sk_test_4eC39HqLyjWDarjtT1zdp7dc -->
    </main>
</body>
</html>
