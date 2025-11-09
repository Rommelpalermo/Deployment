<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

// Check if user is logged in and is admin
requireLogin();
if (!isAdmin()) {
    header('Location: dashboard.php');
    exit;
}

$page_title = 'User Management';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'add') {
        $username = sanitize($_POST['username']);
        $email = sanitize($_POST['email']);
        $full_name = sanitize($_POST['full_name']);
        $role = $_POST['role'];
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $department = sanitize($_POST['department']);
        $phone = sanitize($_POST['phone']);
        
        // Validation
        $errors = array();
        
        if (empty($username) || empty($email) || empty($full_name) || empty($role) || empty($password)) {
            $errors[] = 'Please fill in all required fields.';
        }
        
        if ($password !== $confirm_password) {
            $errors[] = 'Passwords do not match.';
        }
        
        if (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters long.';
        }
        
        // Check if username or email already exists
        if ($db->exists('users', 'username = ?', array($username))) {
            $errors[] = 'Username already exists.';
        }
        
        if ($db->exists('users', 'email = ?', array($email))) {
            $errors[] = 'Email already exists.';
        }
        
        if (empty($errors)) {
            $data = array(
                'username' => $username,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'full_name' => $full_name,
                'role' => $role,
                'department' => $department,
                'phone' => $phone,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s')
            );
            
            try {
                $db->insert('users', $data);
                logActivity(getCurrentUserId(), 'User Created', "Created user: {$username}");
                setFlashMessage('success', 'User created successfully!');
            } catch (Exception $e) {
                setFlashMessage('danger', 'Failed to create user.');
            }
        } else {
            setFlashMessage('danger', implode('<br>', $errors));
        }
    }
    
    if ($_POST['action'] == 'edit') {
        $user_id = (int)$_POST['user_id'];
        $email = sanitize($_POST['email']);
        $full_name = sanitize($_POST['full_name']);
        $role = $_POST['role'];
        $department = sanitize($_POST['department']);
        $phone = sanitize($_POST['phone']);
        $status = $_POST['status'];
        
        if (!empty($email) && !empty($full_name) && !empty($role)) {
            // Check if email already exists for other users
            $existing_user = $db->fetch("SELECT id FROM users WHERE email = ? AND id != ?", array($email, $user_id));
            
            if (!$existing_user) {
                $data = array(
                    'email' => $email,
                    'full_name' => $full_name,
                    'role' => $role,
                    'department' => $department,
                    'phone' => $phone,
                    'status' => $status
                );
                
                try {
                    $db->update('users', $data, 'id = ?', array($user_id));
                    logActivity(getCurrentUserId(), 'User Updated', "Updated user ID: {$user_id}");
                    setFlashMessage('success', 'User updated successfully!');
                } catch (Exception $e) {
                    setFlashMessage('danger', 'Failed to update user.');
                }
            } else {
                setFlashMessage('danger', 'Email already exists for another user.');
            }
        } else {
            setFlashMessage('danger', 'Please fill in all required fields.');
        }
    }
    
    if ($_POST['action'] == 'reset_password') {
        $user_id = (int)$_POST['user_id'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if ($new_password === $confirm_password && strlen($new_password) >= 6) {
            $data = array(
                'password' => password_hash($new_password, PASSWORD_DEFAULT)
            );
            
            try {
                $db->update('users', $data, 'id = ?', array($user_id));
                logActivity(getCurrentUserId(), 'Password Reset', "Reset password for user ID: {$user_id}");
                setFlashMessage('success', 'Password reset successfully!');
            } catch (Exception $e) {
                setFlashMessage('danger', 'Failed to reset password.');
            }
        } else {
            setFlashMessage('danger', 'Invalid password. Must be at least 6 characters and passwords must match.');
        }
    }
    
    header('Location: users.php');
    exit;
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Don't allow deletion of current user
    if ($id != getCurrentUserId()) {
        try {
            $user = $db->fetch("SELECT username FROM users WHERE id = ?", array($id));
            $db->delete('users', 'id = ?', array($id));
            logActivity(getCurrentUserId(), 'User Deleted', "Deleted user: {$user['username']}");
            setFlashMessage('success', 'User deleted successfully!');
        } catch (Exception $e) {
            setFlashMessage('danger', 'Failed to delete user.');
        }
    } else {
        setFlashMessage('warning', 'Cannot delete your own account.');
    }
    
    header('Location: users.php');
    exit;
}

// Get users with activity info
$users = $db->fetchAll("
    SELECT u.*, 
           al.last_activity,
           COUNT(DISTINCT al2.id) as total_activities
    FROM users u 
    LEFT JOIN (
        SELECT user_id, MAX(timestamp) as last_activity 
        FROM activity_log 
        GROUP BY user_id
    ) al ON u.id = al.user_id
    LEFT JOIN activity_log al2 ON u.id = al2.user_id
    GROUP BY u.id 
    ORDER BY u.created_at DESC
");

include 'includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-users"></i> User Management</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="fas fa-user-plus"></i> Add User
        </button>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <?php 
    $stats = array(
        'admin' => array('count' => 0, 'icon' => 'user-shield', 'color' => 'danger'),
        'manager' => array('count' => 0, 'icon' => 'user-tie', 'color' => 'warning'),
        'technician' => array('count' => 0, 'icon' => 'user-cog', 'color' => 'info'),
        'user' => array('count' => 0, 'icon' => 'user', 'color' => 'success')
    );
    
    $active_count = 0;
    $total_count = count($users);
    
    foreach ($users as $user) {
        if (isset($stats[$user['role']])) {
            $stats[$user['role']]['count']++;
        }
        if ($user['status'] == 'active') {
            $active_count++;
        }
    }
    ?>
    
    <?php foreach ($stats as $role => $stat): ?>
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card border-<?php echo $stat['color']; ?>">
            <div class="card-body text-center">
                <div class="text-<?php echo $stat['color']; ?>">
                    <i class="fas fa-<?php echo $stat['icon']; ?> fa-2x mb-2"></i>
                </div>
                <h5 class="card-title"><?php echo ucfirst($role); ?>s</h5>
                <h2 class="text-<?php echo $stat['color']; ?>"><?php echo $stat['count']; ?></h2>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Additional Stats -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card bg-light">
            <div class="card-body text-center">
                <h5>Active Users</h5>
                <h3 class="text-success"><?php echo $active_count; ?> / <?php echo $total_count; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card bg-light">
            <div class="card-body text-center">
                <h5>System Usage</h5>
                <h3 class="text-info"><?php echo round(($active_count / $total_count) * 100); ?>%</h3>
            </div>
        </div>
    </div>
</div>

<!-- Users Table -->
<div class="card">
    <div class="card-header">
        <h5>All Users (<?php echo count($users); ?> total)</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover data-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Role</th>
                        <th>Department</th>
                        <th>Contact</th>
                        <th>Status</th>
                        <th>Last Activity</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar-placeholder me-2">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div>
                                    <strong><?php echo htmlspecialchars($user['full_name']); ?></strong><br>
                                    <small class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php 
                            $role_colors = array(
                                'admin' => 'danger',
                                'manager' => 'warning',
                                'technician' => 'info',
                                'user' => 'success'
                            );
                            $color = isset($role_colors[$user['role']]) ? $role_colors[$user['role']] : 'secondary';
                            ?>
                            <span class="badge bg-<?php echo $color; ?>">
                                <?php echo ucfirst($user['role']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($user['department'] ? $user['department'] : 'Not specified'); ?></td>
                        <td>
                            <small><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></small><br>
                            <?php if (!empty($user['phone'])): ?>
                                <small><i class="fas fa-phone"></i> <?php echo htmlspecialchars($user['phone']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo getStatusBadge($user['status']); ?></td>
                        <td>
                            <?php if ($user['last_activity']): ?>
                                <small><?php echo date('M d, Y H:i', strtotime($user['last_activity'])); ?></small><br>
                                <span class="badge bg-secondary"><?php echo $user['total_activities']; ?> activities</span>
                            <?php else: ?>
                                <span class="text-muted">Never</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm btn-outline-info" 
                                        data-bs-toggle="modal" data-bs-target="#viewUserModal"
                                        onclick="viewUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                        data-bs-toggle="modal" data-bs-target="#editUserModal"
                                        onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-warning"
                                        data-bs-toggle="modal" data-bs-target="#resetPasswordModal"
                                        onclick="resetPassword(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">
                                    <i class="fas fa-key"></i>
                                </button>
                                <?php if ($user['id'] != getCurrentUserId()): ?>
                                <a href="?delete=<?php echo $user['id']; ?>" 
                                   class="btn btn-sm btn-outline-danger delete-btn" 
                                   data-name="<?php echo htmlspecialchars($user['username']); ?>">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username *</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="full_name" class="form-label">Full Name *</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="role" class="form-label">Role *</label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="">Select Role</option>
                                    <option value="user">User</option>
                                    <option value="technician">Technician</option>
                                    <option value="manager">Manager</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="department" class="form-label">Department</label>
                                <input type="text" class="form-control" id="department" name="department">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="text" class="form-control" id="phone" name="phone">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="password" class="form-label">Password *</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <small class="text-muted">Minimum 6 characters</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password *</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="user_id" id="edit_user_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="edit_email" name="email" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_full_name" class="form-label">Full Name *</label>
                                <input type="text" class="form-control" id="edit_full_name" name="full_name" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_role" class="form-label">Role *</label>
                                <select class="form-select" id="edit_role" name="role" required>
                                    <option value="user">User</option>
                                    <option value="technician">Technician</option>
                                    <option value="manager">Manager</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_status" class="form-label">Status *</label>
                                <select class="form-select" id="edit_status" name="status" required>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="suspended">Suspended</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_department" class="form-label">Department</label>
                                <input type="text" class="form-control" id="edit_department" name="department">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_phone" class="form-label">Phone</label>
                                <input type="text" class="form-control" id="edit_phone" name="phone">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reset Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="user_id" id="reset_user_id">
                <div class="modal-body">
                    <p>Reset password for user: <strong id="reset_username"></strong></p>
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password *</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                        <small class="text-muted">Minimum 6 characters</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_new_password" class="form-label">Confirm New Password *</label>
                        <input type="password" class="form-control" id="confirm_new_password" name="confirm_password" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Reset Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View User Modal -->
<div class="modal fade" id="viewUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">User Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="userDetails">
                <!-- Content will be populated by JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function editUser(user) {
    document.getElementById('edit_user_id').value = user.id;
    document.getElementById('edit_email').value = user.email;
    document.getElementById('edit_full_name').value = user.full_name;
    document.getElementById('edit_role').value = user.role;
    document.getElementById('edit_status').value = user.status;
    document.getElementById('edit_department').value = user.department || '';
    document.getElementById('edit_phone').value = user.phone || '';
}

function resetPassword(userId, username) {
    document.getElementById('reset_user_id').value = userId;
    document.getElementById('reset_username').textContent = username;
    document.getElementById('new_password').value = '';
    document.getElementById('confirm_new_password').value = '';
}

function viewUser(user) {
    const details = document.getElementById('userDetails');
    const role_colors = {
        'admin': 'danger',
        'manager': 'warning',
        'technician': 'info',
        'user': 'success'
    };
    const color = role_colors[user.role] || 'secondary';
    
    details.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <h6>Basic Information</h6>
                <p><strong>Username:</strong> ${user.username}</p>
                <p><strong>Full Name:</strong> ${user.full_name}</p>
                <p><strong>Email:</strong> ${user.email}</p>
                <p><strong>Role:</strong> <span class="badge bg-${color}">${user.role}</span></p>
                <p><strong>Status:</strong> ${getStatusBadgeHTML(user.status)}</p>
            </div>
            <div class="col-md-6">
                <h6>Contact & Department</h6>
                <p><strong>Department:</strong> ${user.department || 'Not specified'}</p>
                <p><strong>Phone:</strong> ${user.phone || 'Not specified'}</p>
                <p><strong>Created:</strong> ${new Date(user.created_at).toLocaleDateString()}</p>
                ${user.last_activity ? `<p><strong>Last Activity:</strong> ${new Date(user.last_activity).toLocaleString()}</p>` : '<p><strong>Last Activity:</strong> Never</p>'}
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <h6>Activity Statistics</h6>
                <div class="row">
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h5 class="text-info">${user.total_activities || 0}</h5>
                                <small>Total Activities</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h5 class="text-success">${user.status === 'active' ? 'Active' : 'Inactive'}</h5>
                                <small>Current Status</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function getStatusBadgeHTML(status) {
    const statusColors = {
        'active': 'success',
        'inactive': 'secondary',
        'suspended': 'danger'
    };
    return `<span class="badge bg-${statusColors[status] || 'secondary'}">${status.toUpperCase()}</span>`;
}
</script>

<style>
.avatar-placeholder {
    width: 40px;
    height: 40px;
    background-color: #6c757d;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}
</style>

<?php include 'includes/footer.php'; ?>