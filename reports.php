<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

// Check if user is logged in
requireLogin();

$page_title = 'Reports & Analytics';

// Get filter parameters
$report_type = isset($_GET['type']) ? $_GET['type'] : 'inventory';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-t');

// Get report data based on type
$reportData = array();

switch ($report_type) {
    case 'inventory':
        // Inventory status report
        $reportData['summary'] = array(
            'total_items' => $db->count('inventory'),
            'available_items' => $db->count('inventory', 'status = ?', array('available')),
            'deployed_items' => $db->count('inventory', 'status = ?', array('deployed')),
            'maintenance_items' => $db->count('inventory', 'status = ?', array('maintenance')),
            'retired_items' => $db->count('inventory', 'status = ?', array('retired'))
        );
        
        $reportData['by_category'] = $db->fetchAll("
            SELECT c.name as category_name, 
                   COUNT(i.id) as total_count,
                   SUM(CASE WHEN i.status = 'available' THEN 1 ELSE 0 END) as available_count,
                   SUM(CASE WHEN i.status = 'deployed' THEN 1 ELSE 0 END) as deployed_count,
                   SUM(CASE WHEN i.status = 'maintenance' THEN 1 ELSE 0 END) as maintenance_count
            FROM categories c 
            LEFT JOIN inventory i ON c.id = i.category_id 
            WHERE c.type = 'equipment' 
            GROUP BY c.id, c.name
            ORDER BY c.name
        ");
        
        $reportData['recent_additions'] = $db->fetchAll("
            SELECT i.*, c.name as category_name 
            FROM inventory i 
            LEFT JOIN categories c ON i.category_id = c.id 
            WHERE DATE(i.created_at) BETWEEN ? AND ?
            ORDER BY i.created_at DESC
        ", array($date_from, $date_to));
        break;
        
    case 'deployments':
        // Deployment report
        $reportData['summary'] = array(
            'total_deployments' => $db->count('deployments', 'DATE(start_date) BETWEEN ? AND ?', array($date_from, $date_to)),
            'active_deployments' => $db->count('deployments', 'status = ? AND DATE(start_date) BETWEEN ? AND ?', array('active', $date_from, $date_to)),
            'completed_deployments' => $db->count('deployments', 'status = ? AND DATE(start_date) BETWEEN ? AND ?', array('completed', $date_from, $date_to)),
            'pending_deployments' => $db->count('deployments', 'status = ? AND DATE(start_date) BETWEEN ? AND ?', array('pending', $date_from, $date_to))
        );
        
        $reportData['by_location'] = $db->fetchAll("
            SELECT l.name as location_name,
                   COUNT(d.id) as deployment_count,
                   SUM(CASE WHEN d.status = 'completed' THEN 1 ELSE 0 END) as completed_count
            FROM locations l
            LEFT JOIN deployments d ON l.id = d.location_id 
            WHERE DATE(d.start_date) BETWEEN ? AND ?
            GROUP BY l.id, l.name
            ORDER BY deployment_count DESC
        ", array($date_from, $date_to));
        break;
        
    case 'maintenance':
        // Maintenance report
        $reportData['summary'] = array(
            'total_maintenance' => $db->count('maintenance_log', 'DATE(maintenance_date) BETWEEN ? AND ?', array($date_from, $date_to)),
            'preventive_maintenance' => $db->count('maintenance_log', 'maintenance_type = ? AND DATE(maintenance_date) BETWEEN ? AND ?', array('preventive', $date_from, $date_to)),
            'corrective_maintenance' => $db->count('maintenance_log', 'maintenance_type = ? AND DATE(maintenance_date) BETWEEN ? AND ?', array('corrective', $date_from, $date_to)),
            'emergency_maintenance' => $db->count('maintenance_log', 'maintenance_type = ? AND DATE(maintenance_date) BETWEEN ? AND ?', array('emergency', $date_from, $date_to))
        );
        
        $reportData['upcoming'] = $db->fetchAll("
            SELECT i.name, i.item_code, ml.next_maintenance_date, c.name as category_name
            FROM inventory i
            LEFT JOIN maintenance_log ml ON i.id = ml.inventory_id
            LEFT JOIN categories c ON i.category_id = c.id
            WHERE ml.next_maintenance_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
            ORDER BY ml.next_maintenance_date ASC
        ");
        break;
}

include 'includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-chart-bar"></i> Reports & Analytics</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-outline-primary" onclick="window.print()">
                <i class="fas fa-print"></i> Print
            </button>
            <button type="button" class="btn btn-outline-success" onclick="exportToCSV()">
                <i class="fas fa-download"></i> Export CSV
            </button>
        </div>
    </div>
</div>

<!-- Report Filters -->
<div class="card mb-4">
    <div class="card-header">
        <h5><i class="fas fa-filter"></i> Report Filters</h5>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label for="type" class="form-label">Report Type</label>
                <select class="form-select" id="type" name="type">
                    <option value="inventory" <?php echo $report_type == 'inventory' ? 'selected' : ''; ?>>Inventory Report</option>
                    <option value="deployments" <?php echo $report_type == 'deployments' ? 'selected' : ''; ?>>Deployment Report</option>
                    <option value="maintenance" <?php echo $report_type == 'maintenance' ? 'selected' : ''; ?>>Maintenance Report</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label for="date_from" class="form-label">From Date</label>
                <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $date_from; ?>">
            </div>
            
            <div class="col-md-3">
                <label for="date_to" class="form-label">To Date</label>
                <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $date_to; ?>">
            </div>
            
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="fas fa-search"></i> Generate Report
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Report Content -->
<div id="report-content">
    <?php if ($report_type == 'inventory'): ?>
        <!-- Inventory Report -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-boxes"></i> Inventory Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-2 text-center">
                                <h3 class="text-primary"><?php echo number_format($reportData['summary']['total_items']); ?></h3>
                                <small>Total Items</small>
                            </div>
                            <div class="col-md-2 text-center">
                                <h3 class="text-success"><?php echo number_format($reportData['summary']['available_items']); ?></h3>
                                <small>Available</small>
                            </div>
                            <div class="col-md-2 text-center">
                                <h3 class="text-info"><?php echo number_format($reportData['summary']['deployed_items']); ?></h3>
                                <small>Deployed</small>
                            </div>
                            <div class="col-md-2 text-center">
                                <h3 class="text-warning"><?php echo number_format($reportData['summary']['maintenance_items']); ?></h3>
                                <small>Maintenance</small>
                            </div>
                            <div class="col-md-2 text-center">
                                <h3 class="text-danger"><?php echo number_format($reportData['summary']['retired_items']); ?></h3>
                                <small>Retired</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Inventory by Category -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-tags"></i> Inventory by Category</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Category</th>
                                        <th>Total</th>
                                        <th>Available</th>
                                        <th>Deployed</th>
                                        <th>Maintenance</th>
                                        <th>Utilization</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reportData['by_category'] as $category): ?>
                                        <?php $utilization = $category['total_count'] > 0 ? round(($category['deployed_count'] / $category['total_count']) * 100, 1) : 0; ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($category['category_name']); ?></td>
                                            <td><?php echo $category['total_count']; ?></td>
                                            <td><span class="badge bg-success"><?php echo $category['available_count']; ?></span></td>
                                            <td><span class="badge bg-info"><?php echo $category['deployed_count']; ?></span></td>
                                            <td><span class="badge bg-warning"><?php echo $category['maintenance_count']; ?></span></td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar" role="progressbar" style="width: <?php echo $utilization; ?>%" aria-valuenow="<?php echo $utilization; ?>" aria-valuemin="0" aria-valuemax="100">
                                                        <?php echo $utilization; ?>%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
    <?php elseif ($report_type == 'deployments'): ?>
        <!-- Deployment Report -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-truck"></i> Deployment Summary (<?php echo date('M j, Y', strtotime($date_from)); ?> - <?php echo date('M j, Y', strtotime($date_to)); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 text-center">
                                <h3 class="text-primary"><?php echo number_format($reportData['summary']['total_deployments']); ?></h3>
                                <small>Total Deployments</small>
                            </div>
                            <div class="col-md-3 text-center">
                                <h3 class="text-info"><?php echo number_format($reportData['summary']['active_deployments']); ?></h3>
                                <small>Active</small>
                            </div>
                            <div class="col-md-3 text-center">
                                <h3 class="text-success"><?php echo number_format($reportData['summary']['completed_deployments']); ?></h3>
                                <small>Completed</small>
                            </div>
                            <div class="col-md-3 text-center">
                                <h3 class="text-warning"><?php echo number_format($reportData['summary']['pending_deployments']); ?></h3>
                                <small>Pending</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
    <?php elseif ($report_type == 'maintenance'): ?>
        <!-- Maintenance Report -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-wrench"></i> Maintenance Summary (<?php echo date('M j, Y', strtotime($date_from)); ?> - <?php echo date('M j, Y', strtotime($date_to)); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 text-center">
                                <h3 class="text-primary"><?php echo number_format($reportData['summary']['total_maintenance']); ?></h3>
                                <small>Total Maintenance</small>
                            </div>
                            <div class="col-md-3 text-center">
                                <h3 class="text-success"><?php echo number_format($reportData['summary']['preventive_maintenance']); ?></h3>
                                <small>Preventive</small>
                            </div>
                            <div class="col-md-3 text-center">
                                <h3 class="text-warning"><?php echo number_format($reportData['summary']['corrective_maintenance']); ?></h3>
                                <small>Corrective</small>
                            </div>
                            <div class="col-md-3 text-center">
                                <h3 class="text-danger"><?php echo number_format($reportData['summary']['emergency_maintenance']); ?></h3>
                                <small>Emergency</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Upcoming Maintenance -->
        <?php if (!empty($reportData['upcoming'])): ?>
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-calendar-alt"></i> Upcoming Maintenance (Next 30 Days)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Code</th>
                                        <th>Category</th>
                                        <th>Next Maintenance</th>
                                        <th>Days Until</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reportData['upcoming'] as $item): ?>
                                        <?php $daysUntil = ceil((strtotime($item['next_maintenance_date']) - time()) / 86400); ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                                            <td><?php echo htmlspecialchars($item['item_code']); ?></td>
                                            <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                                            <td><?php echo formatDate($item['next_maintenance_date']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $daysUntil <= 7 ? 'danger' : ($daysUntil <= 14 ? 'warning' : 'info'); ?>">
                                                    <?php echo $daysUntil; ?> days
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
    <?php endif; ?>
</div>

<?php
$page_scripts = "
<script>
function exportToCSV() {
    const reportType = '" . $report_type . "';
    const dateFrom = '" . $date_from . "';
    const dateTo = '" . $date_to . "';
    const filename = reportType + '_report_' + dateFrom + '_to_' + dateTo + '.csv';
    
    // Get the main table from the report
    const table = document.querySelector('#report-content table');
    if (!table) {
        alert('No data to export');
        return;
    }
    
    exportTableToCSV(filename);
}

// Auto-refresh report every 5 minutes for real-time data
setTimeout(function() {
    location.reload();
}, 300000);
</script>
";

include 'includes/footer.php';
?>