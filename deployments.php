<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

// Check if user is logged in
requireLogin();

$page_title = 'Deployment Management';

// Get filters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$location_filter = isset($_GET['location']) ? $_GET['location'] : '';
$assigned_filter = isset($_GET['assigned']) ? $_GET['assigned'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build query conditions
$conditions = ['1=1'];
$params = [];

if ($status_filter) {
    $conditions[] = 'd.status = ?';
    $params[] = $status_filter;
}

if ($location_filter) {
    $conditions[] = 'd.location_id = ?';
    $params[] = $location_filter;
}

if ($assigned_filter) {
    $conditions[] = 'd.assigned_to = ?';
    $params[] = $assigned_filter;
}

if ($search) {
    $conditions[] = '(d.title LIKE ? OR d.deployment_code LIKE ? OR d.description LIKE ?)';
    $searchTerm = '%' . $search . '%';
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
}

$whereClause = implode(' AND ', $conditions);

// Get deployments
$deployments = $db->fetchAll("
    SELECT d.*, c.name as category_name, l.name as location_name,
           CONCAT(u1.first_name, ' ', u1.last_name) as assigned_user,
           CONCAT(u2.first_name, ' ', u2.last_name) as created_by_name,
           COUNT(di.id) as items_count
    FROM deployments d 
    LEFT JOIN categories c ON d.category_id = c.id 
    LEFT JOIN locations l ON d.location_id = l.id 
    LEFT JOIN users u1 ON d.assigned_to = u1.id 
    LEFT JOIN users u2 ON d.created_by = u2.id 
    LEFT JOIN deployment_items di ON d.id = di.deployment_id
    WHERE {$whereClause}
    GROUP BY d.id
    ORDER BY d.created_at DESC
", $params);

// Get filter options
$locations = $db->fetchAll("SELECT * FROM locations WHERE status = 'active' ORDER BY name");
$users = $db->fetchAll("SELECT id, CONCAT(first_name, ' ', last_name) as name FROM users WHERE status = 'active' ORDER BY first_name, last_name");

include 'includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-truck"></i> Deployment Management</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="deployment_add.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> New Deployment
            </a>
            <button type="button" class="btn btn-outline-secondary" onclick="exportTableToCSV('deployments.csv')">
                <i class="fas fa-download"></i> Export
            </button>
        </div>
    </div>
</div>

<!-- Quick Stats -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Active</h6>
                        <h4><?php echo count(array_filter($deployments, function($d) { return $d['status'] == 'active'; })); ?></h4>
                    </div>
                    <i class="fas fa-play-circle fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Pending</h6>
                        <h4><?php echo count(array_filter($deployments, function($d) { return $d['status'] == 'pending'; })); ?></h4>
                    </div>
                    <i class="fas fa-clock fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Completed</h6>
                        <h4><?php echo count(array_filter($deployments, function($d) { return $d['status'] == 'completed'; })); ?></h4>
                    </div>
                    <i class="fas fa-check-circle fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-danger text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Cancelled</h6>
                        <h4><?php echo count(array_filter($deployments, function($d) { return $d['status'] == 'cancelled'; })); ?></h4>
                    </div>
                    <i class="fas fa-times-circle fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label for="search" class="form-label">Search</label>
                <input type="text" class="form-control" id="search" name="search" 
                       value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Search deployments...">
            </div>
            
            <div class="col-md-2">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Status</option>
                    <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label for="location" class="form-label">Location</label>
                <select class="form-select" id="location" name="location">
                    <option value="">All Locations</option>
                    <?php foreach ($locations as $location): ?>
                        <option value="<?php echo $location['id']; ?>" 
                                <?php echo $location_filter == $location['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($location['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-3">
                <label for="assigned" class="form-label">Assigned To</label>
                <select class="form-select" id="assigned" name="assigned">
                    <option value="">All Users</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user['id']; ?>" 
                                <?php echo $assigned_filter == $user['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($user['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-1 d-flex align-items-end">
                <button type="submit" class="btn btn-outline-primary me-2">
                    <i class="fas fa-search"></i>
                </button>
                <a href="deployments.php" class="btn btn-outline-secondary">
                    <i class="fas fa-times"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Deployments Table -->
<div class="card">
    <div class="card-header">
        <h5>Deployments (<?php echo count($deployments); ?> total)</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover data-table" id="deploymentsTable">
                <thead>
                    <tr>
                        <th>Deployment Code</th>
                        <th>Title</th>
                        <th>Location</th>
                        <th>Assigned To</th>
                        <th>Status</th>
                        <th>Priority</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Items</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($deployments as $deployment): ?>
                    <tr class="<?php echo $deployment['status'] == 'active' ? 'table-primary' : ''; ?>">
                        <td>
                            <strong><?php echo htmlspecialchars($deployment['deployment_code']); ?></strong>
                        </td>
                        <td>
                            <div>
                                <strong><?php echo htmlspecialchars($deployment['title']); ?></strong>
                                <?php if ($deployment['description']): ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars(substr($deployment['description'], 0, 50)) . (strlen($deployment['description']) > 50 ? '...' : ''); ?></small>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($deployment['location_name'] ? $deployment['location_name'] : 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($deployment['assigned_user'] ? $deployment['assigned_user'] : 'Unassigned'); ?></td>
                        <td><?php echo getStatusBadge($deployment['status']); ?></td>
                        <td>
                            <?php
                            $priorityColors = [
                                'low' => 'success',
                                'medium' => 'warning',
                                'high' => 'danger',
                                'critical' => 'dark'
                            ];
                            $color = isset($priorityColors[$deployment['priority']]) ? $priorityColors[$deployment['priority']] : 'secondary';
                            ?>
                            <span class="badge bg-<?php echo $color; ?>">
                                <?php echo ucfirst($deployment['priority']); ?>
                            </span>
                        </td>
                        <td><?php echo $deployment['start_date'] ? formatDate($deployment['start_date']) : 'N/A'; ?></td>
                        <td><?php echo $deployment['end_date'] ? formatDate($deployment['end_date']) : 'Ongoing'; ?></td>
                        <td>
                            <span class="badge bg-info"><?php echo $deployment['items_count']; ?> items</span>
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <a href="deployment_view.php?id=<?php echo $deployment['id']; ?>" 
                                   class="btn btn-sm btn-outline-info" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="deployment_edit.php?id=<?php echo $deployment['id']; ?>" 
                                   class="btn btn-sm btn-outline-primary" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if ($deployment['status'] == 'pending'): ?>
                                <a href="deployment_activate.php?id=<?php echo $deployment['id']; ?>" 
                                   class="btn btn-sm btn-success" title="Activate">
                                    <i class="fas fa-play"></i>
                                </a>
                                <?php endif; ?>
                                <?php if ($deployment['status'] == 'active'): ?>
                                <a href="deployment_complete.php?id=<?php echo $deployment['id']; ?>" 
                                   class="btn btn-sm btn-warning" title="Complete">
                                    <i class="fas fa-check"></i>
                                </a>
                                <?php endif; ?>
                                <?php if (isAdmin()): ?>
                                <a href="deployment_delete.php?id=<?php echo $deployment['id']; ?>" 
                                   class="btn btn-sm btn-outline-danger delete-btn" 
                                   title="Delete" data-name="<?php echo htmlspecialchars($deployment['title']); ?>">
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

<!-- Quick Actions Modal -->
<div class="modal fade" id="quickActionsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Quick Actions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-grid gap-2">
                    <a href="deployment_add.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> New Deployment
                    </a>
                    <a href="locations.php" class="btn btn-outline-secondary">
                        <i class="fas fa-map-marker-alt"></i> Manage Locations
                    </a>
                    <a href="categories.php" class="btn btn-outline-secondary">
                        <i class="fas fa-tags"></i> Manage Categories
                    </a>
                    <a href="reports.php?type=deployments" class="btn btn-outline-secondary">
                        <i class="fas fa-chart-bar"></i> Deployment Reports
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Floating Action Button -->
<button type="button" class="btn btn-primary rounded-circle position-fixed" 
        style="bottom: 30px; right: 30px; width: 60px; height: 60px; z-index: 1000;"
        data-bs-toggle="modal" data-bs-target="#quickActionsModal"
        title="Quick Actions">
    <i class="fas fa-plus fa-lg"></i>
</button>

<?php
$page_scripts = "
<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#deploymentsTable').DataTable({
        responsive: true,
        pageLength: 25,
        order: [[ 6, 'desc' ]], // Sort by start date
        columnDefs: [
            { orderable: false, targets: -1 } // Disable sorting on Actions column
        ]
    });
    
    // Auto-submit form on filter change
    $('#status, #location, #assigned').change(function() {
        $(this).closest('form').submit();
    });
});
</script>
";

include 'includes/footer.php';
?>