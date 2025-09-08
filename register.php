<?php
session_start();

// Process registration BEFORE any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'db_config.php';
    
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $firstName = $_POST['first_name'];
    $lastName = $_POST['last_name'];
    
    // VULNERABILITY 1: Weak password validation
    if ($password !== $confirmPassword) {
        $error = "Passwords do not match!";
    } elseif (strlen($password) < 3) { // Very weak requirement!
        $error = "Password must be at least 3 characters!";
    } else {
        // VULNERABILITY 2: Check if username exists (username enumeration)
        $checkSql = "SELECT Username FROM Users WHERE Username = '$username' OR Email = '$email'";
        $checkStmt = sqlsrv_query($conn, $checkSql);
        
        if ($checkStmt && sqlsrv_fetch_array($checkStmt)) {
            $error = "Username or email already exists!";
        } else {
            // VULNERABILITY 3: SQL Injection - direct concatenation
            // VULNERABILITY 4: Storing password as plain text (like admin table)
            $insertSql = "INSERT INTO Users (Username, Email, PasswordHash, FirstName, LastName, CreatedDate) 
                         VALUES ('$username', '$email', '$password', '$firstName', '$lastName', GETDATE())";
            
            $result = sqlsrv_query($conn, $insertSql);
            
            if ($result) {
                // VULNERABILITY 5: Auto-login without email verification
                $_SESSION['user_id'] = sqlsrv_get_field(sqlsrv_query($conn, "SELECT @@IDENTITY"), 0);
                $_SESSION['username'] = $username;
                $_SESSION['logged_in'] = true;
                
                // Log registration - potential XSS
                $logSql = "INSERT INTO AuditLogs (UserID, Action, TableAffected, UserInput, IPAddress) 
                          VALUES (" . $_SESSION['user_id'] . ", 'User Registration', 'Users', 
                          'New user: $username from email: $email', '" . $_SERVER['REMOTE_ADDR'] . "')";
                sqlsrv_query($conn, $logSql);
                
                $success = true;
            } else {
                $error = "Registration failed. Please try again.";
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
    <title>Register - BookShop</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .register-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 20px;
        }
        
        .register-form {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .register-form h2 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 30px;
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
        
        .register-btn {
            width: 100%;
            padding: 12px;
            background: #27ae60;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
        }
        
        .register-btn:hover {
            background: #229954;
        }
        
        .error-message {
            background: #e74c3c;
            color: white;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .success-message {
            background: #27ae60;
            color: white;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .password-requirements {
            font-size: 12px;
            color: #7f8c8d;
            margin-top: 5px;
        }
        
        .security-notice {
            background: #f39c12;
            color: white;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
        }
        
        .critical-warning {
            background: #e74c3c;
            color: white;
            padding: 20px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
            border: 3px solid #c0392b;
        }
        
        .warning-icon {
            font-size: 24px;
            margin-bottom: 10px;
            display: block;
        }
    </style>
</head>
<body>
    <!-- Security Warning Banner -->
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
            <ul class="nav-list">
                <li><a href="index.html">Home</a></li>
                <li><a href="books.php">Books</a></li>
                <li><a href="cart.php">Cart</a></li>
                <li><a href="login.php">Login</a></li>
                <li><a href="admin/login.php">Admin</a></li>
            </ul>
        </nav>
    </header>
    
    <main class="register-container">
        <div class="register-form">
            <h2>Create Your Account</h2>
            
            <div class="critical-warning">
                <span class="warning-icon">🚫</span>
                <strong>DO NOT USE REAL INFORMATION</strong><br>
                This is a demonstration website with intentional security vulnerabilities.<br>
                Use only fictional names, fake email addresses, and test passwords.<br>
                Your information is NOT secure on this site.
            </div>
            
            <?php if (isset($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (isset($success) && $success): ?>
                <div class="success-message">
                    Registration successful! Welcome <?php echo htmlspecialchars($username); ?>!<br>
                    Redirecting to homepage...
                </div>
                <script>
                    setTimeout(function() {
                        window.location.href = 'index.html';
                    }, 3000);
                </script>
            <?php else: ?>
            
            <div class="security-notice">
                📝 DEMO ACCOUNT CREATION - Use fictional information only for testing purposes
            </div>
            
            <form method="POST" action="register.php">
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name (Use fake name)</label>
                        <input type="text" id="first_name" name="first_name" required
                               placeholder="e.g., John"
                               value="<?php echo $_POST['first_name'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name (Use fake name)</label>
                        <input type="text" id="last_name" name="last_name" required
                               placeholder="e.g., Doe"
                               value="<?php echo $_POST['last_name'] ?? ''; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="username">Username (Fictional only)</label>
                    <input type="text" id="username" name="username" required
                           placeholder="e.g., testuser123"
                           value="<?php echo $_POST['username'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address (Use fake email)</label>
                    <input type="email" id="email" name="email" required
                           placeholder="e.g., fake@example.com"
                           value="<?php echo $_POST['email'] ?? ''; ?>">
                    <div class="password-requirements">
                        ⚠️ Do not use your real email address
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password (Test password only)</label>
                    <input type="password" id="password" name="password" required
                           placeholder="Simple test password">
                    <div class="password-requirements">
                        Minimum 3 characters (intentionally weak for demo)
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required
                           placeholder="Repeat test password">
                </div>
                
                <button type="submit" class="register-btn">Create Demo Account</button>
            </form>
            
            <div class="login-link">
                <p>Already have an account? <a href="login.php">Login here</a></p>
            </div>
            
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
