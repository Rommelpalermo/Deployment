<?php
// Utility Functions

// Sanitize input
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// Redirect if not admin
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: dashboard.php');
        exit;
    }
}

// Generate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Format date
function formatDate($date, $format = 'M j, Y') {
    return date($format, strtotime($date));
}

// Format datetime
function formatDateTime($datetime, $format = 'M j, Y g:i A') {
    return date($format, strtotime($datetime));
}

// Get time ago
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31536000) return floor($time/2592000) . ' months ago';
    return floor($time/31536000) . ' years ago';
}

// Generate random string
function generateRandomString($length = 10) {
    return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
}

// Upload file
function uploadFile($file, $destination, $allowedTypes = ['jpg', 'jpeg', 'png', 'gif']) {
    $uploadDir = 'assets/images/uploads/';
    $fileName = basename($file['name']);
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $newFileName = generateRandomString() . '.' . $fileExt;
    $uploadPath = $uploadDir . $newFileName;
    
    // Check if file type is allowed
    if (!in_array($fileExt, $allowedTypes)) {
        return ['success' => false, 'message' => 'File type not allowed.'];
    }
    
    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File size too large.'];
    }
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        return ['success' => true, 'filename' => $newFileName, 'path' => $uploadPath];
    } else {
        return ['success' => false, 'message' => 'Failed to upload file.'];
    }
}

// Set flash message
function setFlashMessage($type, $message) {
    $_SESSION['flash_messages'][] = ['type' => $type, 'message' => $message];
}

// Get and clear flash messages
function getFlashMessages() {
    $messages = isset($_SESSION['flash_messages']) ? $_SESSION['flash_messages'] : [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

// Pagination function
function paginate($totalRecords, $recordsPerPage = 10, $currentPage = 1) {
    $totalPages = ceil($totalRecords / $recordsPerPage);
    $offset = ($currentPage - 1) * $recordsPerPage;
    
    return [
        'total_records' => $totalRecords,
        'records_per_page' => $recordsPerPage,
        'total_pages' => $totalPages,
        'current_page' => $currentPage,
        'offset' => $offset,
        'has_previous' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages
    ];
}

// Log activity
function logActivity($userId, $action, $details = '') {
    global $db;
    
    $data = [
        'user_id' => $userId,
        'action' => $action,
        'details' => $details,
        'ip_address' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT'],
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $db->insert('activity_log', $data);
}

// Send email notification (basic implementation)
function sendEmail($to, $subject, $message) {
    $headers = "From: " . ADMIN_EMAIL . "\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    return mail($to, $subject, $message, $headers);
}

// Get user name by ID
function getUserName($userId) {
    global $db;
    $user = $db->fetch("SELECT first_name, last_name FROM users WHERE id = ?", [$userId]);
    return $user ? $user['first_name'] . ' ' . $user['last_name'] : 'Unknown User';
}

// Get status badge HTML
function getStatusBadge($status) {
    $badges = [
        'active' => '<span class="badge bg-success">Active</span>',
        'inactive' => '<span class="badge bg-secondary">Inactive</span>',
        'deployed' => '<span class="badge bg-primary">Deployed</span>',
        'maintenance' => '<span class="badge bg-warning">Maintenance</span>',
        'retired' => '<span class="badge bg-danger">Retired</span>',
        'available' => '<span class="badge bg-info">Available</span>',
        'pending' => '<span class="badge bg-warning">Pending</span>',
        'completed' => '<span class="badge bg-success">Completed</span>',
        'cancelled' => '<span class="badge bg-danger">Cancelled</span>'
    ];
    
    return isset($badges[$status]) ? $badges[$status] : '<span class="badge bg-secondary">' . ucfirst($status) . '</span>';
}

// Get current user information safely
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return array(
        'id' => isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null,
        'username' => isset($_SESSION['username']) ? $_SESSION['username'] : '',
        'email' => isset($_SESSION['email']) ? $_SESSION['email'] : '',
        'first_name' => isset($_SESSION['first_name']) ? $_SESSION['first_name'] : 'User',
        'last_name' => isset($_SESSION['last_name']) ? $_SESSION['last_name'] : '',
        'role' => isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'user'
    );
}

// Get current user's full name safely
function getCurrentUserName() {
    if (!isLoggedIn()) {
        return 'Guest';
    }
    
    $firstName = isset($_SESSION['first_name']) ? $_SESSION['first_name'] : 'User';
    $lastName = isset($_SESSION['last_name']) ? $_SESSION['last_name'] : '';
    return trim($firstName . ' ' . $lastName);
}

// Get current user ID safely
function getCurrentUserId() {
    return isLoggedIn() ? (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null) : null;
}
?>