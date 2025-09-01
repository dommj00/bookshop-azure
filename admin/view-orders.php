<?php
session_start();

// Check admin access
if (!isset($_SESSION['is_admin'])) {
    header('Location: login.php');
    exit;
}

require_once '../db_config.php';

// Handle order status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $orderId = $_POST['order_id'];
    $newStatus = $_POST['status'];
    
    // VULNERABILITY: SQL Injection
    $updateSql = "UPDATE Orders SET Status = '$newStatus' WHERE OrderID = $orderId";
    
    if (sqlsrv_query($conn, $updateSql)) {
        $message = "Order #$orderId status updated to $newStatus";
        
        // Log action
        $logSql = "INSERT INTO AuditLogs (UserID, Action, TableAffected, UserInput) 
                  VALUES (" . $_SESSION['admin_id'] . ", 'Order Status Updated', 'Orders', 
                  'Updated Order #$orderId to $newStatus')";
        sqlsrv_query($conn, $logSql);
    }
}

// Get filter parameters
$statusFilter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query with filters
$ordersSql = "SELECT o.*, u.Username, u.Email, 
              (SELECT COUNT(*) FROM OrderItems WHERE OrderID = o.OrderID) as ItemCount
              FROM Orders o
              LEFT JOIN Users u ON o.UserID = u.UserID
              WHERE 1=1 ";

if ($statusFilter !== 'all') {
    $ordersSql .= "AND o.Status = '$statusFilter' ";
}

if ($search) {
    // VULNERABILITY: SQL Injection in search
    $ordersSql .= "AND (o.OrderID LIKE '%$search%' OR u.Username LIKE '%$search%' OR u.Email LIKE '%$search%') ";
}

$ordersSql .= "ORDER BY o.OrderDate DESC";
$ordersStmt = sqlsrv_query($conn, $ordersSql);

// Get order statistics
$statsSql = "SELECT 
             COUNT(CASE WHEN Status = 'Pending' THEN 1 END) as pending,
             COUNT(CASE WHEN Status = 'Processing' THEN 1 END) as processing,
             COUNT(CASE WHEN Status = 'Shipped' THEN 1 END) as shipped,
             COUNT(CASE WHEN Status = 'Completed' THEN 1 END) as completed,
             SUM(TotalAmount) as total_revenue
             FROM Orders";
$statsStmt = sqlsrv_query($conn, $statsSql);
$stats = sqlsrv_fetch_array($statsStmt, SQLSRV_FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Orders - BookShop Admin</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .orders-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        
        .stats-row {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
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
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .stat-card .number {
            font-size: 28px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .stat-card.pending { border-top: 3px solid #f39c12; }
        .stat-card.processing { border-top: 3px solid #3498db; }
        .stat-card.shipped { border-top: 3px solid #9b59b6; }
        .stat-card.completed { border-top: 3px solid #27ae60; }
        .stat-card.revenue { border-top: 3px solid #e74c3c; }
        
        .filters {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            gap: 20px;
            align-items: center;
        }
        
        .filters select,
        .filters input {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .filters button {
            padding: 10px 20px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .orders-table {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .orders-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .orders-table th {
            background: #2c3e50;
            color: white;
            padding: 15px;
            text-align: left;
        }
        
        .orders-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .orders-table tr:hover {
            background: #f8f9fa;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-pending { background: #fff3cd; color: #856404; }
        .status-processing { background: #cce5ff; color: #004085; }
        .status-shipped { background: #e2d5f1; color: #6f42c1; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        
        .action-form {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .action-form select {
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .action-form button {
            padding: 5px 10px;
            background: #27ae60;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .view-details {
            color: #3498db;
            text-decoration: none;
        }
        
        .view-details:hover {
            text-decoration: underline;
        }
        
        .alert {
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
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
                <li><a href="view-orders.php" class="active">Orders</a></li>
                <li><a href="users.php">Users</a></li>
                <li><a href="audit-logs.php">Audit Logs</a></li>
                <li><a href="../index.html">View Store</a></li>
            </ul>
        </nav>
    </header>
    
    <main class="orders-container">
        <?php if (isset($message)): ?>
            <div class="alert"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <div class="stats-row">
            <div class="stat-card pending">
                <h3>Pending Orders</h3>
                <div class="number"><?php echo $stats['pending'] ?? 0; ?></div>
            </div>
            <div class="stat-card processing">
                <h3>Processing</h3>
                <div class="number"><?php echo $stats['processing'] ?? 0; ?></div>
            </div>
            <div class="stat-card shipped">
                <h3>Shipped</h3>
                <div class="number"><?php echo $stats['shipped'] ?? 0; ?></div>
            </div>
            <div class="stat-card completed">
                <h3>Completed</h3>
                <div class="number"><?php echo $stats['completed'] ?? 0; ?></div>
            </div>
            <div class="stat-card revenue">
                <h3>Total Revenue</h3>
                <div class="number">$<?php echo number_format($stats['total_revenue'] ?? 0, 2); ?></div>
            </div>
        </div>
        
        <div class="filters">
            <form method="GET" action="view-orders.php" style="display: flex; gap: 20px; width: 100%;">
                <select name="status">
                    <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Orders</option>
                    <option value="Pending" <?php echo $statusFilter === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="Processing" <?php echo $statusFilter === 'Processing' ? 'selected' : ''; ?>>Processing</option>
                    <option value="Shipped" <?php echo $statusFilter === 'Shipped' ? 'selected' : ''; ?>>Shipped</option>
                    <option value="Completed" <?php echo $statusFilter === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                </select>
                
                <input type="text" name="search" placeholder="Search by Order ID, Username, or Email" 
                       value="<?php echo htmlspecialchars($search); ?>" style="flex: 1;">
                
                <button type="submit">Filter</button>
            </form>
        </div>
        
        <div class="orders-table">
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($ordersStmt) {
                        while ($order = sqlsrv_fetch_array($ordersStmt, SQLSRV_FETCH_ASSOC)) {
                            $statusClass = 'status-' . strtolower($order['Status']);
                            
                            echo '<tr>';
                            echo '<td>#' . str_pad($order['OrderID'], 5, '0', STR_PAD_LEFT) . '</td>';
                            echo '<td>';
                            echo htmlspecialchars($order['Username'] ?? 'Guest') . '<br>';
                            echo '<small>' . htmlspecialchars($order['Email'] ?? '') . '</small>';
                            echo '</td>';
                            echo '<td>' . $order['OrderDate']->format('M d, Y H:i') . '</td>';
                            echo '<td>' . $order['ItemCount'] . ' items</td>';
                            echo '<td>$' . number_format($order['TotalAmount'], 2) . '</td>';
                            echo '<td><span class="status-badge ' . $statusClass . '">' . $order['Status'] . '</span></td>';
                            echo '<td>';
                            echo '<form method="POST" action="view-orders.php" class="action-form">';
                            echo '<input type="hidden" name="order_id" value="' . $order['OrderID'] . '">';
                            echo '<select name="status">';
                            echo '<option value="Pending"' . ($order['Status'] === 'Pending' ? ' selected' : '') . '>Pending</option>';
                            echo '<option value="Processing"' . ($order['Status'] === 'Processing' ? ' selected' : '') . '>Processing</option>';
                            echo '<option value="Shipped"' . ($order['Status'] === 'Shipped' ? ' selected' : '') . '>Shipped</option>';
                            echo '<option value="Completed"' . ($order['Status'] === 'Completed' ? ' selected' : '') . '>Completed</option>';
                            echo '<option value="Cancelled"' . ($order['Status'] === 'Cancelled' ? ' selected' : '') . '>Cancelled</option>';
                            echo '</select>';
                            echo '<button type="submit" name="update_status">Update</button>';
                            echo '</form>';
                            echo '</td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="7" style="text-align: center;">No orders found</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
