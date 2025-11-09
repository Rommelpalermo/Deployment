<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

// Log activity before logout
if (isLoggedIn()) {
    logActivity(getCurrentUserId(), 'User Logout', 'User logged out');
}

// Destroy session
session_destroy();

// Redirect to login page
header('Location: login.php');
exit;
?>