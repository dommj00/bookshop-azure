<?php
session_start();

// Destroy all session data
session_destroy();

// Redirect to admin login
header('Location: login.php');
exit;
?>
