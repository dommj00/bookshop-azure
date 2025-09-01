<?php
session_start();

// Check admin access
if (!isset($_SESSION['is_admin'])) {
    header('Location: login.php');
    exit;
}

require_once '../db_config.php';

// VULNERABILITY: No input sanitization for search
$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? 'all';

// Build query with vulnerabilities
$sql = "SELECT TOP 100 * FROM AuditLogs ";
if ($search) {
    $sql .= "WHERE Action LIKE '%$search%' OR UserInput LIKE '%$search%' ";
}
$sql .= "ORDER BY Timestamp DESC";

$stmt = sqlsrv_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs - BookShop Admin</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .audit-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        
        .audit-header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .search-bar {
            display: flex;
            gap: 10px;
            margin-top: 20px;
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
        
        .logs-table {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: #2c3e50;
            color: white;
            padding: 15px;
            text-align: left;
        }
        
        td {
            padding: 12px 15px;
            border-bottom: 1px solid #ecf0f1;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .action-login {
            color: #3498db;
        }
        
        .action-order {
            color: #27ae60;
        }
        
        .action-error {
            color: #e74c3c;
        }
        
        .user-input {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .ip-address {
            font-family: monospace;
            background: #ecf0f1;
            padding: 2px 6px;
            border-radius: 3px;
        }
        
        .export-btn {
            float: right;
            background: #27ae60;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
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
                <li><a href="audit-logs.php" class="active">Audit Logs</a></li>
                <li><a href="../index.html">View Store</a></li>
            </ul>
        </nav>
    </header>
    
    <main class="audit-container">
        <div class="audit-header">
            <h2>Security Audit Logs</h2>
            <a href="export-logs.php" class="export-btn">Export Logs</a>
            
            <form method="GET" action="audit-logs.php" class="search-bar">
                <input type="text" name="search" placeholder="Search logs..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit">Search</button>
            </form>
        </div>
        
        <div class="logs-table">
            <table>
                <thead>
                    <tr>
                        <th>Log ID</th>
                        <th>User ID</th>
                        <th>Action</th>
                        <th>Table Affected</th>
                        <th>User Input</th>
                        <th>IP Address</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($stmt) {
                        while ($log = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                            // Determine action class for styling
                            $actionClass = '';
                            if (strpos($log['Action'], 'Login') !== false) $actionClass = 'action-login';
                            elseif (strpos($log['Action'], 'Order') !== false) $actionClass = 'action-order';
                            elseif (strpos($log['Action'], 'Error') !== false) $actionClass = 'action-error';
                            
                            echo '<tr>';
                            echo '<td>' . $log['LogID'] . '</td>';
                            echo '<td>' . ($log['UserID'] ?? 'Guest') . '</td>';
                            echo '<td class="' . $actionClass . '">' . $log['Action'] . '</td>';
                            echo '<td>' . ($log['TableAffected'] ?? '-') . '</td>';
                            // VULNERABILITY: XSS - displaying user input without sanitization
                            echo '<td class="user-input" title="' . $log['UserInput'] . '">' . $log['UserInput'] . '</td>';
                            echo '<td><span class="ip-address">' . ($log['IPAddress'] ?? 'Unknown') . '</span></td>';
                            echo '<td>' . $log['Timestamp']->format('Y-m-d H:i:s') . '</td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="7">No logs found</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
        
        <!-- VULNERABILITY: Sensitive information in comments -->
        <!-- TODO: Add pagination, currently showing last 100 logs only -->
        <!-- Log retention: 90 days, stored in AuditLogs table -->
    </main>
</body>
</html>
