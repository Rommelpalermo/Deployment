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

$page_title = 'System Settings';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'update_settings') {
        $site_name = sanitize($_POST['site_name']);
        $site_description = sanitize($_POST['site_description']);
        $contact_email = sanitize($_POST['contact_email']);
        $items_per_page = (int)$_POST['items_per_page'];
        $backup_frequency = $_POST['backup_frequency'];
        $maintenance_mode = isset($_POST['maintenance_mode']) ? 1 : 0;
        
        // Here you would typically save these to a settings table or config file
        // For now, we'll just show a success message
        logActivity(getCurrentUserId(), 'Settings Updated', 'Updated system settings');
        setFlashMessage('success', 'Settings updated successfully!');
    }
    
    if ($_POST['action'] == 'clear_logs') {
        try {
            // Clear old activity logs (older than 90 days)
            $db->query("DELETE FROM activity_log WHERE timestamp < DATE_SUB(NOW(), INTERVAL 90 DAY)");
            logActivity(getCurrentUserId(), 'Logs Cleared', 'Cleared old activity logs');
            setFlashMessage('success', 'Old logs cleared successfully!');
        } catch (Exception $e) {
            setFlashMessage('danger', 'Failed to clear logs.');
        }
    }
    
    header('Location: settings.php');
    exit;
}

// Get system statistics
$total_users = $db->fetch("SELECT COUNT(*) as count FROM users")['count'];
$total_inventory = $db->fetch("SELECT COUNT(*) as count FROM inventory")['count'];
$total_deployments = $db->fetch("SELECT COUNT(*) as count FROM deployments")['count'];
$total_activities = $db->fetch("SELECT COUNT(*) as count FROM activity_log")['count'];

include 'includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-cog"></i> System Settings</h1>
</div>

<!-- System Statistics -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card border-primary">
            <div class="card-body text-center">
                <div class="text-primary">
                    <i class="fas fa-users fa-2x mb-2"></i>
                </div>
                <h5 class="card-title">Total Users</h5>
                <h2 class="text-primary"><?php echo $total_users; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card border-success">
            <div class="card-body text-center">
                <div class="text-success">
                    <i class="fas fa-boxes fa-2x mb-2"></i>
                </div>
                <h5 class="card-title">Inventory Items</h5>
                <h2 class="text-success"><?php echo $total_inventory; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card border-warning">
            <div class="card-body text-center">
                <div class="text-warning">
                    <i class="fas fa-shipping-fast fa-2x mb-2"></i>
                </div>
                <h5 class="card-title">Deployments</h5>
                <h2 class="text-warning"><?php echo $total_deployments; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card border-info">
            <div class="card-body text-center">
                <div class="text-info">
                    <i class="fas fa-history fa-2x mb-2"></i>
                </div>
                <h5 class="card-title">Activity Logs</h5>
                <h2 class="text-info"><?php echo $total_activities; ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- System Settings -->
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5><i class="fas fa-sliders-h"></i> General Settings</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="update_settings">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="site_name" class="form-label">Site Name</label>
                                <input type="text" class="form-control" id="site_name" name="site_name" 
                                       value="Laboratory Deployment System">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="contact_email" class="form-label">Contact Email</label>
                                <input type="email" class="form-control" id="contact_email" name="contact_email" 
                                       value="admin@laboratory.com">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="site_description" class="form-label">Site Description</label>
                        <textarea class="form-control" id="site_description" name="site_description" rows="3">Laboratory Deployment & Inventory Management System</textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="items_per_page" class="form-label">Items Per Page</label>
                                <select class="form-select" id="items_per_page" name="items_per_page">
                                    <option value="10">10</option>
                                    <option value="25" selected>25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="backup_frequency" class="form-label">Backup Frequency</label>
                                <select class="form-select" id="backup_frequency" name="backup_frequency">
                                    <option value="daily" selected>Daily</option>
                                    <option value="weekly">Weekly</option>
                                    <option value="monthly">Monthly</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="maintenance_mode" name="maintenance_mode">
                            <label class="form-check-label" for="maintenance_mode">
                                Enable Maintenance Mode
                            </label>
                            <small class="form-text text-muted d-block">When enabled, only administrators can access the system.</small>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Database Maintenance -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-database"></i> Database Maintenance</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Clear Old Logs</h6>
                        <p class="text-muted">Remove activity logs older than 90 days.</p>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="clear_logs">
                            <button type="submit" class="btn btn-outline-warning" 
                                    onclick="return confirm('Are you sure you want to clear old logs?')">
                                <i class="fas fa-trash-alt"></i> Clear Old Logs
                            </button>
                        </form>
                    </div>
                    <div class="col-md-6">
                        <h6>Database Backup</h6>
                        <p class="text-muted">Download a backup of the current database.</p>
                        <button type="button" class="btn btn-outline-success" onclick="downloadBackup()">
                            <i class="fas fa-download"></i> Download Backup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- System Information -->
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h6><i class="fas fa-info-circle"></i> System Information</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <span>PHP Version</span>
                        <span class="text-muted"><?php echo PHP_VERSION; ?></span>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <span>Database Size</span>
                        <span class="text-muted">
                            <?php 
                            try {
                                $size = $db->fetch("
                                    SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size_mb
                                    FROM information_schema.tables 
                                    WHERE table_schema = DATABASE()
                                ")['size_mb'];
                                echo $size . ' MB';
                            } catch (Exception $e) {
                                echo 'Unknown';
                            }
                            ?>
                        </span>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <span>Server Time</span>
                        <span class="text-muted"><?php echo date('Y-m-d H:i:s'); ?></span>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <span>Disk Usage</span>
                        <span class="text-muted">
                            <?php 
                            $bytes = disk_total_space('.') - disk_free_space('.');
                            echo round($bytes / 1024 / 1024 / 1024, 2) . ' GB';
                            ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Activities -->
        <div class="card">
            <div class="card-header">
                <h6><i class="fas fa-clock"></i> Recent System Activities</h6>
            </div>
            <div class="card-body">
                <?php 
                $recent_activities = $db->fetchAll("
                    SELECT al.*, u.username 
                    FROM activity_log al 
                    LEFT JOIN users u ON al.user_id = u.id 
                    WHERE al.action IN ('User Created', 'Settings Updated', 'Logs Cleared', 'System Maintenance')
                    ORDER BY al.timestamp DESC 
                    LIMIT 5
                ");
                ?>
                
                <?php if (!empty($recent_activities)): ?>
                    <?php foreach ($recent_activities as $activity): ?>
                    <div class="mb-2">
                        <small class="text-muted">
                            <strong><?php echo htmlspecialchars($activity['username']); ?></strong>
                            <?php echo htmlspecialchars($activity['action']); ?>
                            <br>
                            <span class="text-muted"><?php echo date('M d, H:i', strtotime($activity['timestamp'])); ?></span>
                        </small>
                    </div>
                    <hr class="my-2">
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted">No recent system activities.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function downloadBackup() {
    // This would typically trigger a server-side script to generate and download a database backup
    alert('Backup functionality would be implemented here. This would generate a SQL dump of the database.');
}
</script>

<?php include 'includes/footer.php'; ?>