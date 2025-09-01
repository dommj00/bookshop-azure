<?php
// Database configuration - INSECURE: credentials in plain text
$serverName = "bookshop-server-chippy.database.windows.net";
$connectionOptions = array(
    "Database" => "Bookshop-DB",
    "Uid" => "bookshopuser", 
    "PWD" => "boog13D0Wn#", 
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
