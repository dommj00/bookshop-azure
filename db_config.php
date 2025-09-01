<?php
// Database configuration - INSECURE: credentials in plain text
$serverName = "bookshop-server-chippy.database.windows.net";
$connectionOptions = array(
    "Database" => "BookshopDB",
    "Uid" => "bookshop-admin", 
    "PWD" => "sil3nceAll!", 
    "Encrypt" => true,
    "TrustServerCertificate" => false,
    "LoginTimeout" => 30
);

// Create connection
$conn = sqlsrv_connect($serverName, $connectionOptions);

// Error handling that exposes system info (Security Issue #3)
if (!$conn) {
    die(print_r(sqlsrv_errors(), true)); // Exposed database structure
}
?>
