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

// Redirect to homepage
header('Location: index.html');
exit;
?>
