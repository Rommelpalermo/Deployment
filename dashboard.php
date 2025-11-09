<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

// Check if user is logged in
requireLogin();

$page_title = 'Dashboard';

// Get dashboard statistics
$stats = [];

// Total inventory items
$stats['total_inventory'] = $db->count('inventory');
$stats['available_inventory'] = $db->count('inventory', 'status = ?', ['available']);
$stats['deployed_inventory'] = $db->count('inventory', 'status = ?', ['deployed']);
$stats['maintenance_inventory'] = $db->count('inventory', 'status = ?', ['maintenance']);

// Total deployments
$stats['total_deployments'] = $db->count('deployments');
$stats['active_deployments'] = $db->count('deployments', 'status = ?', ['active']);
$stats['pending_deployments'] = $db->count('deployments', 'status = ?', ['pending']);
$stats['completed_deployments'] = $db->count('deployments', 'status = ?', ['completed']);

// Total users
$stats['total_users'] = $db->count('users', 'status = ?', ['active']);

// Recent activities
$recentActivities = $db->fetchAll(
    "SELECT al.*, CONCAT(u.first_name, ' ', u.last_name) as user_name 
     FROM activity_log al 
     LEFT JOIN users u ON al.user_id = u.id 
     ORDER BY al.created_at DESC 
     LIMIT 10"
);

// Inventory by category
$inventoryByCategory = $db->fetchAll(
    "SELECT c.name, COUNT(i.id) as count 
     FROM categories c 
     LEFT JOIN inventory i ON c.id = i.category_id 
     WHERE c.type = 'equipment' 
     GROUP BY c.id, c.name"
);

// Deployments by status
$deploymentsByStatus = $db->fetchAll(
    "SELECT status, COUNT(*) as count 
     FROM deployments 
     GROUP BY status"
);

// Items requiring maintenance soon
$maintenanceItems = $db->fetchAll(
    "SELECT i.*, c.name as category_name 
     FROM inventory i 
     LEFT JOIN categories c ON i.category_id = c.id 
     WHERE i.status = 'maintenance' 
     ORDER BY i.updated_at DESC 
     LIMIT 5"
);

// Recent deployments
$recentDeployments = $db->fetchAll(
    "SELECT d.*, l.name as location_name, CONCAT(u.first_name, ' ', u.last_name) as assigned_user 
     FROM deployments d 
     LEFT JOIN locations l ON d.location_id = l.id 
     LEFT JOIN users u ON d.assigned_to = u.id 
     ORDER BY d.created_at DESC 
     LIMIT 5"
);

// Low stock items (items with only 1 or 2 available)
$lowStockItems = $db->fetchAll(
    "SELECT i.*, c.name as category_name,
            (SELECT COUNT(*) FROM inventory i2 WHERE i2.name = i.name AND i2.status = 'available') as available_count
     FROM inventory i 
     LEFT JOIN categories c ON i.category_id = c.id 
     GROUP BY i.name 
     HAVING available_count <= 2 
     ORDER BY available_count ASC 
     LIMIT 5"
);

include 'includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="location.reload()">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card text-white h-100">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-uppercase mb-1">Total Inventory</div>
                        <div class="h5 mb-0 font-weight-bold"><?php echo number_format($stats['total_inventory']); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-boxes fa-2x"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="inventory.php" class="text-white text-decoration-none">
                    <small>View Details <i class="fas fa-arrow-right"></i></small>
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card-success text-white h-100">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-uppercase mb-1">Active Deployments</div>
                        <div class="h5 mb-0 font-weight-bold"><?php echo number_format($stats['active_deployments']); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-truck fa-2x"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="deployments.php?status=active" class="text-white text-decoration-none">
                    <small>View Details <i class="fas fa-arrow-right"></i></small>
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card-warning text-white h-100">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-uppercase mb-1">Available Items</div>
                        <div class="h5 mb-0 font-weight-bold"><?php echo number_format($stats['available_inventory']); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-check-circle fa-2x"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="inventory.php?status=available" class="text-white text-decoration-none">
                    <small>View Details <i class="fas fa-arrow-right"></i></small>
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card-info text-white h-100">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-uppercase mb-1">System Users</div>
                        <div class="h5 mb-0 font-weight-bold"><?php echo number_format($stats['total_users']); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-users fa-2x"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <?php if (isAdmin()): ?>
                <a href="users.php" class="text-white text-decoration-none">
                    <small>View Details <i class="fas fa-arrow-right"></i></small>
                </a>
                <?php else: ?>
                <small>Active users in system</small>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row mb-4">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-chart-pie"></i> Inventory by Category</h5>
            </div>
            <div class="card-body">
                <canvas id="inventoryChart" width="400" height="200"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-chart-bar"></i> Deployments by Status</h5>
            </div>
            <div class="card-body">
                <canvas id="deploymentChart" width="400" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Content Row -->
<div class="row">
    <!-- Recent Deployments -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-truck"></i> Recent Deployments</h5>
                <a href="deployments.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (!empty($recentDeployments)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Location</th>
                                    <th>Status</th>
                                    <th>Assigned To</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentDeployments as $deployment): ?>
                                <tr>
                                    <td>
                                        <a href="deployment_view.php?id=<?php echo $deployment['id']; ?>" class="text-decoration-none">
                                            <?php echo htmlspecialchars($deployment['title']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($deployment['location_name']); ?></td>
                                    <td><?php echo getStatusBadge($deployment['status']); ?></td>
                                    <td><?php echo htmlspecialchars($deployment['assigned_user'] ?: 'Unassigned'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center">No recent deployments found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Maintenance Items -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-wrench"></i> Maintenance Required</h5>
                <a href="maintenance.php" class="btn btn-sm btn-outline-warning">View All</a>
            </div>
            <div class="card-body">
                <?php if (!empty($maintenanceItems)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($maintenanceItems as $item): ?>
                                <tr>
                                    <td>
                                        <a href="inventory_view.php?id=<?php echo $item['id']; ?>" class="text-decoration-none">
                                            <?php echo htmlspecialchars($item['name']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                                    <td><?php echo getStatusBadge($item['status']); ?></td>
                                    <td>
                                        <a href="maintenance_add.php?item_id=<?php echo $item['id']; ?>" 
                                           class="btn btn-sm btn-warning">Schedule</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center">No items requiring maintenance.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activities -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-history"></i> Recent Activities</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($recentActivities)): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Details</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentActivities as $activity): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($activity['user_name'] ?: 'System'); ?></td>
                                    <td><?php echo htmlspecialchars($activity['action']); ?></td>
                                    <td><?php echo htmlspecialchars($activity['details']); ?></td>
                                    <td><?php echo timeAgo($activity['created_at']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center">No recent activities.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$page_scripts = "
<script>
// Inventory Chart
const inventoryCtx = document.getElementById('inventoryChart').getContext('2d');
const inventoryChart = new Chart(inventoryCtx, {
    type: 'doughnut',
    data: {
        labels: [" . implode(',', array_map(function($item) { return "'" . addslashes($item['name']) . "'"; }, $inventoryByCategory)) . "],
        datasets: [{
            data: [" . implode(',', array_column($inventoryByCategory, 'count')) . "],
            backgroundColor: [
                '#FF6384',
                '#36A2EB',
                '#FFCE56',
                '#4BC0C0',
                '#9966FF',
                '#FF9F40',
                '#FF6384',
                '#C9CBCF'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// Deployment Chart
const deploymentCtx = document.getElementById('deploymentChart').getContext('2d');
const deploymentChart = new Chart(deploymentCtx, {
    type: 'bar',
    data: {
        labels: [" . implode(',', array_map(function($item) { return "'" . ucfirst($item['status']) . "'"; }, $deploymentsByStatus)) . "],
        datasets: [{
            label: 'Deployments',
            data: [" . implode(',', array_column($deploymentsByStatus, 'count')) . "],
            backgroundColor: [
                'rgba(255, 99, 132, 0.8)',
                'rgba(54, 162, 235, 0.8)',
                'rgba(255, 206, 86, 0.8)',
                'rgba(75, 192, 192, 0.8)'
            ],
            borderColor: [
                'rgba(255, 99, 132, 1)',
                'rgba(54, 162, 235, 1)',
                'rgba(255, 206, 86, 1)',
                'rgba(75, 192, 192, 1)'
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        },
        plugins: {
            legend: {
                display: false
            }
        }
    }
});

// Auto refresh dashboard every 5 minutes
setTimeout(function() {
    location.reload();
}, 300000);
</script>
";

include 'includes/footer.php';
?>