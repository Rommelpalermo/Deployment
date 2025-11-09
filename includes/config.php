<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'deployment_system');

// Site Configuration
define('SITE_NAME', 'Laboratory Deployment & Inventory System');
define('SITE_URL', 'http://localhost/Deployment/Deployment/');
define('ADMIN_EMAIL', 'admin@labsystem.com');

// Security Configuration
define('PASSWORD_HASH_ALGO', PASSWORD_DEFAULT);
define('SESSION_TIMEOUT', 3600); // 1 hour

// Upload Configuration
define('UPLOAD_DIR', 'assets/images/uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB

// Create database connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Start session
session_start();

// Set timezone
date_default_timezone_set('Asia/Manila');
?>