<?php
session_start();

// Log the logout action if user was logged in
if (isset($_SESSION['user_id'])) {
    require_once 'db_config.php';
    
    $userId = $_SESSION['user_id'];
    $username = $_SESSION['username'] ?? 'Unknown';
    
    // Log logout action
    $logSql = "INSERT INTO AuditLogs (UserID, Action, TableAffected, UserInput, IPAddress) 
              VALUES ($userId, 'User Logout', 'Users', 'User $username logged out', '" . $_SERVER['REMOTE_ADDR'] . "')";
    sqlsrv_query($conn, $logSql);
    sqlsrv_close($conn);
}

// Destroy session
session_destroy();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logging Out - BookShop</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>BookShop</h1>
    </header>
    <main style="text-align: center; padding: 50px;">
        <h2>Logging you out...</h2>
        <p>You have been successfully logged out.</p>
    </main>
    
    <script>
        // Clear localStorage
        localStorage.removeItem("userLoggedIn");
        localStorage.removeItem("username");
        localStorage.removeItem("userId");
        
        // Redirect to homepage after a brief delay
        setTimeout(function() {
            window.location.href = 'index.html';
        }, 2000);
    </script>
</body>
</html>
