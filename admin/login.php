<?php
session_start();

// Process login BEFORE any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../db_config.php';
    
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // VULNERABILITY 1: Plain text password comparison
    $sql = "SELECT * FROM AdminUsers WHERE Username = '$username' AND Password = '$password' AND IsActive = 1";
    
    $stmt = sqlsrv_query($conn, $sql);
    
    if ($stmt === false) {
        $error = "System Error: Unable to process login";
    } else {
        $admin = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        
        if ($admin) {
            // VULNERABILITY 2: Predictable session values
            $_SESSION['admin_id'] = $admin['AdminID'];
            $_SESSION['admin_username'] = $admin['Username'];
            $_SESSION['is_admin'] = true;
            $_SESSION['admin_level'] = 'super_admin'; // VULNERABILITY 3: Hardcoded privilege
            
            // Update last login
            $updateSql = "UPDATE AdminUsers SET LastLoginDate = GETDATE() WHERE AdminID = " . $admin['AdminID'];
            sqlsrv_query($conn, $updateSql);
            
            sqlsrv_close($conn);
            
            // VULNERABILITY 5: Redirect with sensitive info in URL
            header('Location: dashboard.php?admin=' . $admin['Username']);
            exit();
            
        } else {
            // Check for disabled accounts (information disclosure)
            $checkDisabled = "SELECT Username, IsActive FROM AdminUsers WHERE Username = '$username'";
            $checkStmt = sqlsrv_query($conn, $checkDisabled);
            $account = sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC);
            
            if ($account && $account['IsActive'] == 0) {
                $error = "Account " . $username . " is disabled. Contact system administrator.";
            } else {
                $error = "Invalid admin credentials";
            }
        }
    }
    
    sqlsrv_close($conn);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - BookShop</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        body {
            background-color: #2c3e50;
        }
        
        .admin-login-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 20px;
        }
        
        .admin-login-form {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.2);
        }
        
        .admin-login-form h2 {
            text-align: center;
            color: #e74c3c;
            margin-bottom: 30px;
            font-size: 28px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: bold;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .admin-login-btn {
            width: 100%;
            padding: 14px;
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 18px;
            cursor: pointer;
            font-weight: bold;
        }
        
        .admin-login-btn:hover {
            background: #c0392b;
        }
        
        .error-message {
            background: #e74c3c;
            color: white;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-link a {
            color: #3498db;
            text-decoration: none;
        }
        
        /* VULNERABILITY: Visible comments in source */
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
    <!-- Admin Login Page - Default credentials: admin/admin123 -->
    <main class="admin-login-container">
        <div class="admin-login-form">
            <h2>🔒 Admin Portal</h2>
            
            <?php if (isset($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="login.php" autocomplete="off">
                <div class="form-group">
                    <label for="username">Admin Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Admin Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="admin-login-btn">Login to Admin Panel</button>
            </form>
            
            <div class="back-link">
                <a href="../index.html">← Back to Store</a>
            </div>
        </div>
    </main>
    
    <!-- TODO: Implement 2FA for admin accounts -->
    <!-- Default test account: admin/admin123 -->
</body>
</html>
