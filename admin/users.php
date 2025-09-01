<?php
session_start();

// Check admin access
if (!isset($_SESSION['is_admin'])) {
    header('Location: login.php');
    exit;
}

require_once '../db_config.php';

$message = '';
$error = '';

// Handle user actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $userId = $_GET['id'];
    $action = $_GET['action'];
    
    if ($action === 'deactivate') {
        // VULNERABILITY: SQL Injection
        $updateSql = "UPDATE Users SET IsActive = 0 WHERE UserID = $userId";
        if (sqlsrv_query($conn, $updateSql)) {
            $message = "User deactivated successfully!";
        }
    } elseif ($action === 'activate') {
        $updateSql = "UPDATE Users SET IsActive = 1 WHERE UserID = $userId";
        if (sqlsrv_query($conn, $updateSql)) {
            $message = "User activated successfully!";
        }
    } elseif ($action === 'delete') {
        // VULNERABILITY: No confirmation or CSRF protection
        $deleteSql = "DELETE FROM Users WHERE UserID = $userId";
        if (sqlsrv_query($conn, $deleteSql)) {
            $message = "User deleted successfully!";
        } else {
            $error = "Cannot delete user with existing orders!";
        }
    }
}

// Get search parameters
$search = $_GET['search'] ?? '';

// Build query
$usersSql = "SELECT u.*, 
             (SELECT COUNT(*) FROM Orders WHERE UserID = u.UserID) as OrderCount,
             (SELECT SUM(TotalAmount) FROM Orders WHERE UserID = u.UserID) as TotalSpent
             FROM Users u
             WHERE 1=1 ";

if ($search) {
    // VULNERABILITY: SQL Injection in search
    $usersSql .= "AND (u.Username LIKE '%$search%' OR u.Email LIKE '%$search%' 
                  OR u.FirstName LIKE '%$search%' OR u.LastName LIKE '%$search%') ";
}

$usersSql .= "ORDER BY u.CreatedDate DESC";
$usersStmt = sqlsrv_query($conn, $usersSql);

// Get user statistics
$statsSql = "SELECT 
             COUNT(*) as total_users,
             COUNT(CASE WHEN IsActive = 1 THEN 1 END) as active_users,
             COUNT(CASE WHEN CreatedDate >= DATEADD(day, -30, GETDATE()) THEN 1 END) as new_users
             FROM Users";
$statsStmt = sqlsrv_query($conn, $statsSql);
$stats = sqlsrv_fetch_array($statsStmt, SQLSRV_FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - BookShop Admin</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .users-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        
        .stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
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
            font-size: 32px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .search-bar {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .search-bar form {
            display: flex;
            gap: 10px;
        }
        
        .search-bar input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .search-bar button {
            padding: 10px 20px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .users-table {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .users-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .users-table th {
            background: #2c3e50;
            color: white;
            padding: 15px;
            text-align: left;
        }
        
        .users-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .users-table tr:hover {
            background: #f8f9fa;
        }
        
        .status-active {
            color: #27ae60;
            font-weight: bold;
        }
        
        .status-inactive {
            color: #e74c3c;
            font-weight: bold;
        }
        
        .action-links {
            display: flex;
            gap: 10px;
        }
        
        .action-links a {
            color: #3498db;
            text-decoration: none;
            font-size: 14px;
        }
        
        .action-links a:hover {
            text-decoration: underline;
        }
        
        .action-links a.delete {
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
        
        .vip-badge {
            background: #f39c12;
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
            margin-left: 10px;
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
                <li><a href="users.php" class="active">Users</a></li>
                <li><a href="audit-logs.php">Audit Logs</a></li>
                <li><a href="../index.html">View Store</a></li>
            </ul>
        </nav>
    </header>
    
    <main class="users-container">
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="stats-row">
            <div class="stat-card">
                <h3>Total Users</h3>
                <div class="number"><?php echo $stats['total_users']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Active Users</h3>
                <div class="number"><?php echo $stats['active_users']; ?></div>
            </div>
            <div class="stat-card">
                <h3>New Users (30 days)</h3>
                <div class="number"><?php echo $stats['new_users']; ?></div>
            </div>
        </div>
        
        <div class="search-bar">
            <form method="GET" action="users.php">
                <input type="text" name="search" placeholder="Search by username, email, or name..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit">Search Users</button>
            </form>
        </div>
        
        <div class="users-table">
            <table>
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Name</th>
                        <th>Joined</th>
                        <th>Orders</th>
                        <th>Total Spent</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($usersStmt) {
                        while ($user = sqlsrv_fetch_array($usersStmt, SQLSRV_FETCH_ASSOC)) {
                            $statusClass = $user['IsActive'] ? 'status-active' : 'status-inactive';
                            $statusText = $user['IsActive'] ? 'Active' : 'Inactive';
                            $totalSpent = $user['TotalSpent'] ?? 0;
                            
                            echo '<tr>';
                            echo '<td>' . $user['UserID'] . '</td>';
                            echo '<td>' . htmlspecialchars($user['Username']);
                            if ($totalSpent > 100) {
                                echo '<span class="vip-badge">VIP</span>';
                            }
                            echo '</td>';
                            echo '<td>' . htmlspecialchars($user['Email']) . '</td>';
                            echo '<td>' . htmlspecialchars($user['FirstName'] . ' ' . $user['LastName']) . '</td>';
                            echo '<td>' . $user['CreatedDate']->format('M d, Y') . '</td>';
                            echo '<td>' . $user['OrderCount'] . '</td>';
                            echo '<td>$' . number_format($totalSpent, 2) . '</td>';
                            echo '<td class="' . $statusClass . '">' . $statusText . '</td>';
                            echo '<td class="action-links">';
                            
                            if ($user['IsActive']) {
                                echo '<a href="users.php?action=deactivate&id=' . $user['UserID'] . '">Deactivate</a>';
                            } else {
                                echo '<a href="users.php?action=activate&id=' . $user['UserID'] . '">Activate</a>';
                            }
                            
                            // VULNERABILITY: Exposing user password reset without proper authorization
                            echo '<a href="reset-password.php?user=' . $user['UserID'] . '">Reset Password</a>';
                            
                            if ($user['OrderCount'] == 0) {
                                echo '<a href="users.php?action=delete&id=' . $user['UserID'] . '" class="delete">Delete</a>';
                            }
                            
                            echo '</td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="9" style="text-align: center;">No users found</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
        
        <!-- VULNERABILITY: Exposing user database structure in comments -->
        <!-- User table columns: UserID, Username, Email, PasswordHash, FirstName, LastName, IsActive, CreatedDate, LastLoginDate -->
    </main>
</body>
</html>
