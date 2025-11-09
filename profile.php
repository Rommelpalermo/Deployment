<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

// Check if user is logged in
requireLogin();

$page_title = 'User Profile';
$user_id = getCurrentUserId();

// Get current user data
$current_user = $db->fetch("SELECT * FROM users WHERE id = ?", array($user_id));

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'update_profile') {
            $email = sanitize($_POST['email']);
            $full_name = sanitize($_POST['full_name']);
            $department = sanitize($_POST['department']);
            $phone = sanitize($_POST['phone']);
            
            // Validation
            $errors = array();
            
            if (empty($email) || empty($full_name)) {
                $errors[] = 'Email and full name are required.';
            }
            
            // Check if email already exists for other users
            $existing_user = $db->fetch("SELECT id FROM users WHERE email = ? AND id != ?", array($email, $user_id));
            
            if ($existing_user) {
                $errors[] = 'Email already exists for another user.';
            }
            
            if (empty($errors)) {
                $data = array(
                    'email' => $email,
                    'full_name' => $full_name,
                    'department' => $department,
                    'phone' => $phone
                );
                
                try {
                    $db->update('users', $data, 'id = ?', array($user_id));
                    logActivity($user_id, 'Profile Updated', 'Updated profile information');
                    setFlashMessage('success', 'Profile updated successfully!');
                    
                    // Refresh user data
                    $current_user = $db->fetch("SELECT * FROM users WHERE id = ?", array($user_id));
                } catch (Exception $e) {
                    setFlashMessage('danger', 'Failed to update profile.');
                }
            } else {
                setFlashMessage('danger', implode('<br>', $errors));
            }
        }
        
        if ($_POST['action'] == 'change_password') {
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];
            
            // Validation
            $errors = array();
            
            if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                $errors[] = 'All password fields are required.';
            }
            
            if (!password_verify($current_password, $current_user['password'])) {
                $errors[] = 'Current password is incorrect.';
            }
            
            if ($new_password !== $confirm_password) {
                $errors[] = 'New passwords do not match.';
            }
            
            if (strlen($new_password) < 6) {
                $errors[] = 'New password must be at least 6 characters long.';
            }
            
            if (empty($errors)) {
                $data = array(
                    'password' => password_hash($new_password, PASSWORD_DEFAULT)
                );
                
                try {
                    $db->update('users', $data, 'id = ?', array($user_id));
                    logActivity($user_id, 'Password Changed', 'Changed account password');
                    setFlashMessage('success', 'Password changed successfully!');
                } catch (Exception $e) {
                    setFlashMessage('danger', 'Failed to change password.');
                }
            } else {
                setFlashMessage('danger', implode('<br>', $errors));
            }
        }
    }
    
    header('Location: profile.php');
    exit;
}

// Get user activity statistics
$activity_stats = $db->fetch("
    SELECT 
        COUNT(*) as total_activities,
        MAX(timestamp) as last_activity,
        DATE(MIN(timestamp)) as first_activity
    FROM activity_log 
    WHERE user_id = ?
", array($user_id));

// Get recent activities
$recent_activities = $db->fetchAll("
    SELECT * FROM activity_log 
    WHERE user_id = ? 
    ORDER BY timestamp DESC 
    LIMIT 10
", array($user_id));

include 'includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-user"></i> My Profile</h1>
</div>

<div class="row">
    <!-- Profile Information -->
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5><i class="fas fa-user-edit"></i> Profile Information</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" 
                                       value="<?php echo htmlspecialchars($current_user['username']); ?>" readonly>
                                <small class="text-muted">Username cannot be changed</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="role" class="form-label">Role</label>
                                <input type="text" class="form-control" id="role" 
                                       value="<?php echo ucfirst($current_user['role']); ?>" readonly>
                                <small class="text-muted">Contact admin to change role</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($current_user['email']); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="full_name" class="form-label">Full Name *</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" 
                                       value="<?php echo htmlspecialchars($current_user['full_name']); ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="department" class="form-label">Department</label>
                                <input type="text" class="form-control" id="department" name="department" 
                                       value="<?php echo htmlspecialchars($current_user['department']); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="text" class="form-control" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($current_user['phone']); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Change Password -->
        <div class="card mb-4">
            <div class="card-header">
                <h5><i class="fas fa-key"></i> Change Password</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="change_password">
                    
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password *</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password *</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                                <small class="text-muted">Minimum 6 characters</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password *</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-key"></i> Change Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Recent Activity -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-history"></i> Recent Activity</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($recent_activities)): ?>
                    <div class="timeline">
                        <?php foreach ($recent_activities as $activity): ?>
                        <div class="timeline-item mb-3">
                            <div class="d-flex">
                                <div class="timeline-marker me-3">
                                    <i class="fas fa-circle text-primary"></i>
                                </div>
                                <div class="timeline-content">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($activity['action']); ?></h6>
                                    <p class="mb-1 text-muted"><?php echo htmlspecialchars($activity['details']); ?></p>
                                    <small class="text-muted">
                                        <i class="fas fa-clock"></i> 
                                        <?php echo date('M d, Y H:i', strtotime($activity['timestamp'])); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No recent activity found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Profile Sidebar -->
    <div class="col-md-4">
        <!-- User Info Card -->
        <div class="card mb-4">
            <div class="card-body text-center">
                <div class="avatar-large mb-3">
                    <i class="fas fa-user fa-4x text-primary"></i>
                </div>
                <h5><?php echo htmlspecialchars($current_user['full_name']); ?></h5>
                <p class="text-muted">@<?php echo htmlspecialchars($current_user['username']); ?></p>
                <span class="badge bg-<?php 
                    echo $current_user['role'] == 'admin' ? 'danger' : 
                         ($current_user['role'] == 'manager' ? 'warning' : 
                         ($current_user['role'] == 'technician' ? 'info' : 'success')); 
                ?>">
                    <?php echo ucfirst($current_user['role']); ?>
                </span>
            </div>
        </div>
        
        <!-- Account Statistics -->
        <div class="card mb-4">
            <div class="card-header">
                <h6><i class="fas fa-chart-line"></i> Account Statistics</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Total Activities</span>
                        <span class="badge bg-info"><?php echo $activity_stats['total_activities']; ?></span>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Member Since</span>
                        <span class="text-muted"><?php echo date('M Y', strtotime($current_user['created_at'])); ?></span>
                    </div>
                </div>
                <?php if ($activity_stats['last_activity']): ?>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Last Activity</span>
                        <span class="text-muted"><?php echo date('M d, H:i', strtotime($activity_stats['last_activity'])); ?></span>
                    </div>
                </div>
                <?php endif; ?>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Status</span>
                        <span class="badge bg-<?php echo $current_user['status'] == 'active' ? 'success' : 'secondary'; ?>">
                            <?php echo ucfirst($current_user['status']); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h6><i class="fas fa-bolt"></i> Quick Actions</h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="dashboard.php" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a href="inventory.php" class="btn btn-outline-info btn-sm">
                        <i class="fas fa-boxes"></i> View Inventory
                    </a>
                    <a href="deployments.php" class="btn btn-outline-warning btn-sm">
                        <i class="fas fa-shipping-fast"></i> My Deployments
                    </a>
                    <a href="reports.php" class="btn btn-outline-success btn-sm">
                        <i class="fas fa-chart-bar"></i> Reports
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.avatar-large {
    width: 80px;
    height: 80px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: center;
}

.timeline {
    position: relative;
}

.timeline-item {
    position: relative;
}

.timeline-marker {
    width: 20px;
    text-align: center;
    margin-top: 2px;
}

.timeline-content {
    flex: 1;
    padding: 10px 15px;
    background-color: #f8f9fa;
    border-radius: 8px;
    border-left: 3px solid #007bff;
}
</style>

<?php include 'includes/footer.php'; ?>