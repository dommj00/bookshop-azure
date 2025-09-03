<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - BookShop</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .login-container {
            max-width: 400px;
            margin: 50px auto;
            padding: 20px;
        }
        
        .login-form {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .login-form h2 {
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
        
        .login-btn {
            width: 100%;
            padding: 12px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
        }
        
        .login-btn:hover {
            background: #2980b9;
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
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .register-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .debug-info {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 10px;
            margin-top: 20px;
            font-family: monospace;
            font-size: 12px;
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
                <li><a href="login.php" id="login-link">Login</a></li>
                <li><a href="account.php" id="account-link" style="display: none;">My Account</a></li>
                <li><a href="logout.php" id="logout-link" style="display: none;">Logout</a></li>
                <li id="welcome-user" style="display: none; color: #27ae60;"></li>
                <li><a href="admin/login.php">Admin</a></li>
            </ul>
        </nav>
    </header>
    
    <main class="login-container">
        <div class="login-form">
            <h2>Customer Login</h2>
            
            <?php
            session_start();
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                require_once 'db_config.php';
                
                $username = $_POST['username'];
                $password = $_POST['password'];
                
                // VULNERABILITY 1: SQL Injection - Direct concatenation
                $sql = "SELECT * FROM Users WHERE Username = '$username' AND PasswordHash = '$password'";
                
                // VULNERABILITY 2: Verbose error messages
                echo "<div class='debug-info'>Debug SQL: $sql</div>";
                
                $stmt = sqlsrv_query($conn, $sql);
                
                if ($stmt === false) {
                    // VULNERABILITY 3: Detailed error disclosure
                    echo '<div class="error-message">Database Error: ' . print_r(sqlsrv_errors(), true) . '</div>';
                } else {
                    $user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
                    
                    if ($user) {
                        // VULNERABILITY 4: Weak session management
                        $_SESSION['user_id'] = $user['UserID'];
                        $_SESSION['username'] = $user['Username'];
                        $_SESSION['logged_in'] = true;
                        
                        // VULNERABILITY 5: Session fixation - not regenerating session ID
                        
                        // Update last login without prepared statement
                        $updateSql = "UPDATE Users SET LastLoginDate = GETDATE() WHERE UserID = " . $user['UserID'];
                        sqlsrv_query($conn, $updateSql);
                        
                        // Determine redirect destination
                        $redirectTo = 'index.html';
                        if (isset($_GET['redirect'])) {
                            $redirectTo = $_GET['redirect'];
                        }
                        
                        echo '<div class="success-message">Login successful! Welcome ' . htmlspecialchars($user['Username']) . '</div>';
                        echo '<script>
                            localStorage.setItem("userLoggedIn", "true");
                            localStorage.setItem("username", "' . htmlspecialchars($user['Username']) . '");
                            localStorage.setItem("userId", "' . $user['UserID'] . '");
                            setTimeout(function(){ 
                                window.location.href = "' . $redirectTo . '"; 
                            }, 1000);
                        </script>';
                        
                    } else {
                        // VULNERABILITY 6: Username enumeration
                        $checkUser = "SELECT Username FROM Users WHERE Username = '$username'";
                        $checkStmt = sqlsrv_query($conn, $checkUser);
                        $exists = sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC);
                        
                        if ($exists) {
                            echo '<div class="error-message">Invalid password for user: ' . $username . '</div>';
                        } else {
                            echo '<div class="error-message">Username ' . $username . ' does not exist</div>';
                        }
                    }
                }
                
                sqlsrv_close($conn);
            }
            ?>
            
            <form method="POST" action="login.php<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required 
                           value="<?php echo $_POST['username'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="login-btn">Login</button>
            </form>
            
            <div class="register-link">
                <p>Don't have an account? <a href="register.php">Register here</a></p>
                <p style="color: #7f8c8d; font-size: 14px; margin-top: 10px;">
                    Test credentials: testuser1 / password123
                </p>
            </div>
        </div>
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
