<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

// Check if user is logged in
requireLogin();

$page_title = 'Maintenance Schedules';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] == 'add') {
        $item_id = (int)$_POST['item_id'];
        $maintenance_type = $_POST['maintenance_type'];
        $scheduled_date = $_POST['scheduled_date'];
        $description = sanitize($_POST['description']);
        $priority = $_POST['priority'];
        
        if (!empty($item_id) && !empty($maintenance_type) && !empty($scheduled_date)) {
            $data = array(
                'item_id' => $item_id,
                'maintenance_type' => $maintenance_type,
                'scheduled_date' => $scheduled_date,
                'description' => $description,
                'priority' => $priority,
                'status' => 'scheduled',
                'created_by' => getCurrentUserId(),
                'created_at' => date('Y-m-d H:i:s')
            );
            
            try {
                $db->insert('maintenance_log', $data);
                logActivity(getCurrentUserId(), 'Maintenance Scheduled', "Scheduled maintenance for item ID: {$item_id}");
                setFlashMessage('success', 'Maintenance scheduled successfully!');
            } catch (Exception $e) {
                setFlashMessage('danger', 'Failed to schedule maintenance.');
            }
        } else {
            setFlashMessage('danger', 'Please fill in all required fields.');
        }
        
        header('Location: maintenance.php');
        exit;
    }
    
    if (isset($_POST['action']) && $_POST['action'] == 'complete') {
        $id = (int)$_POST['maintenance_id'];
        $completed_date = $_POST['completed_date'];
        $notes = sanitize($_POST['notes']);
        $cost = !empty($_POST['cost']) ? (float)$_POST['cost'] : null;
        
        try {
            $update_data = array(
                'status' => 'completed',
                'completed_date' => $completed_date,
                'notes' => $notes,
                'cost' => $cost,
                'completed_by' => getCurrentUserId()
            );
            $db->update('maintenance_log', $update_data, 'id = ?', array($id));
            logActivity(getCurrentUserId(), 'Maintenance Completed', "Completed maintenance ID: {$id}");
            setFlashMessage('success', 'Maintenance marked as completed!');
        } catch (Exception $e) {
            setFlashMessage('danger', 'Failed to update maintenance record.');
        }
        
        header('Location: maintenance.php');
        exit;
    }
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$priority_filter = isset($_GET['priority']) ? $_GET['priority'] : 'all';

// Build WHERE clause for filters
$where_conditions = array();
$params = array();

if ($status_filter != 'all') {
    $where_conditions[] = "ml.status = ?";
    $params[] = $status_filter;
}

if ($priority_filter != 'all') {
    $where_conditions[] = "ml.priority = ?";
    $params[] = $priority_filter;
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Get maintenance records
$maintenance_records = $db->fetchAll("
    SELECT ml.*, 
           i.name as item_name, i.serial_number,
           u1.username as created_by_user,
           u2.username as completed_by_user,
           c.name as category_name
    FROM maintenance_log ml
    LEFT JOIN inventory i ON ml.item_id = i.id
    LEFT JOIN users u1 ON ml.created_by = u1.id
    LEFT JOIN users u2 ON ml.completed_by = u2.id
    LEFT JOIN categories c ON i.category_id = c.id
    {$where_clause}
    ORDER BY 
        CASE ml.priority 
            WHEN 'high' THEN 1 
            WHEN 'medium' THEN 2 
            WHEN 'low' THEN 3 
        END,
        ml.scheduled_date ASC
", $params);

// Get upcoming maintenance (next 30 days)
$upcoming_maintenance = $db->fetchAll("
    SELECT ml.*, i.name as item_name, c.name as category_name
    FROM maintenance_log ml
    LEFT JOIN inventory i ON ml.item_id = i.id
    LEFT JOIN categories c ON i.category_id = c.id
    WHERE ml.status = 'scheduled' 
    AND ml.scheduled_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    ORDER BY ml.scheduled_date ASC
");

// Get overdue maintenance
$overdue_maintenance = $db->fetchAll("
    SELECT ml.*, i.name as item_name, c.name as category_name
    FROM maintenance_log ml
    LEFT JOIN inventory i ON ml.item_id = i.id
    LEFT JOIN categories c ON i.category_id = c.id
    WHERE ml.status = 'scheduled' 
    AND ml.scheduled_date < CURDATE()
    ORDER BY ml.scheduled_date ASC
");

// Get inventory items for dropdown
$inventory_items = $db->fetchAll("
    SELECT i.id, i.name, i.serial_number, c.name as category_name
    FROM inventory i
    LEFT JOIN categories c ON i.category_id = c.id
    WHERE i.status = 'available'
    ORDER BY i.name
");

include 'includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-tools"></i> Maintenance Schedules</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMaintenanceModal">
            <i class="fas fa-plus"></i> Schedule Maintenance
        </button>
    </div>
</div>

<!-- Alert Cards -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card border-warning">
            <div class="card-body text-center">
                <div class="text-warning">
                    <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                </div>
                <h5 class="card-title">Overdue</h5>
                <h2 class="text-warning"><?php echo count($overdue_maintenance); ?></h2>
                <small class="text-muted">Items requiring immediate attention</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-info">
            <div class="card-body text-center">
                <div class="text-info">
                    <i class="fas fa-calendar-check fa-2x mb-2"></i>
                </div>
                <h5 class="card-title">Upcoming</h5>
                <h2 class="text-info"><?php echo count($upcoming_maintenance); ?></h2>
                <small class="text-muted">Next 30 days</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-success">
            <div class="card-body text-center">
                <div class="text-success">
                    <i class="fas fa-check-circle fa-2x mb-2"></i>
                </div>
                <h5 class="card-title">Total Records</h5>
                <h2 class="text-success"><?php echo count($maintenance_records); ?></h2>
                <small class="text-muted">All maintenance records</small>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" name="status" id="status" onchange="this.form.submit()">
                    <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Status</option>
                    <option value="scheduled" <?php echo $status_filter == 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                    <option value="in_progress" <?php echo $status_filter == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                    <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            <div class="col-md-4">
                <label for="priority" class="form-label">Priority</label>
                <select class="form-select" name="priority" id="priority" onchange="this.form.submit()">
                    <option value="all" <?php echo $priority_filter == 'all' ? 'selected' : ''; ?>>All Priorities</option>
                    <option value="high" <?php echo $priority_filter == 'high' ? 'selected' : ''; ?>>High</option>
                    <option value="medium" <?php echo $priority_filter == 'medium' ? 'selected' : ''; ?>>Medium</option>
                    <option value="low" <?php echo $priority_filter == 'low' ? 'selected' : ''; ?>>Low</option>
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <a href="maintenance.php" class="btn btn-outline-secondary">Clear Filters</a>
            </div>
        </form>
    </div>
</div>

<!-- Maintenance Records -->
<div class="card">
    <div class="card-header">
        <h5>Maintenance Records</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover data-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Type</th>
                        <th>Priority</th>
                        <th>Scheduled Date</th>
                        <th>Status</th>
                        <th>Created By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($maintenance_records as $record): ?>
                    <tr class="<?php echo $record['scheduled_date'] < date('Y-m-d') && $record['status'] == 'scheduled' ? 'table-warning' : ''; ?>">
                        <td>
                            <strong><?php echo htmlspecialchars($record['item_name']); ?></strong><br>
                            <small class="text-muted"><?php echo htmlspecialchars($record['serial_number']); ?></small>
                        </td>
                        <td>
                            <span class="badge bg-info">
                                <?php echo ucfirst(str_replace('_', ' ', $record['maintenance_type'])); ?>
                            </span>
                        </td>
                        <td>
                            <?php 
                            $priority_colors = array(
                                'high' => 'danger',
                                'medium' => 'warning', 
                                'low' => 'success'
                            );
                            ?>
                            <span class="badge bg-<?php echo $priority_colors[$record['priority']]; ?>">
                                <?php echo ucfirst($record['priority']); ?>
                            </span>
                        </td>
                        <td>
                            <?php echo date('M d, Y', strtotime($record['scheduled_date'])); ?>
                            <?php if ($record['scheduled_date'] < date('Y-m-d') && $record['status'] == 'scheduled'): ?>
                                <br><small class="text-danger"><i class="fas fa-exclamation-triangle"></i> Overdue</small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo getStatusBadge($record['status']); ?></td>
                        <td><?php echo htmlspecialchars($record['created_by_user']); ?></td>
                        <td>
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm btn-outline-info" 
                                        data-bs-toggle="modal" data-bs-target="#viewMaintenanceModal"
                                        onclick="viewMaintenance(<?php echo htmlspecialchars(json_encode($record)); ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <?php if ($record['status'] == 'scheduled' || $record['status'] == 'in_progress'): ?>
                                <button type="button" class="btn btn-sm btn-outline-success"
                                        data-bs-toggle="modal" data-bs-target="#completeMaintenanceModal"
                                        onclick="completeMaintenance(<?php echo $record['id']; ?>)">
                                    <i class="fas fa-check"></i>
                                </button>
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

<!-- Add Maintenance Modal -->
<div class="modal fade" id="addMaintenanceModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Schedule Maintenance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="item_id" class="form-label">Select Item *</label>
                                <select class="form-select" id="item_id" name="item_id" required>
                                    <option value="">Choose an item...</option>
                                    <?php foreach ($inventory_items as $item): ?>
                                    <option value="<?php echo $item['id']; ?>">
                                        <?php echo htmlspecialchars($item['name']); ?> 
                                        (<?php echo htmlspecialchars($item['serial_number']); ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="maintenance_type" class="form-label">Maintenance Type *</label>
                                <select class="form-select" id="maintenance_type" name="maintenance_type" required>
                                    <option value="">Select type...</option>
                                    <option value="preventive">Preventive</option>
                                    <option value="corrective">Corrective</option>
                                    <option value="emergency">Emergency</option>
                                    <option value="calibration">Calibration</option>
                                    <option value="inspection">Inspection</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="scheduled_date" class="form-label">Scheduled Date *</label>
                                <input type="date" class="form-control" id="scheduled_date" name="scheduled_date" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="priority" class="form-label">Priority *</label>
                                <select class="form-select" id="priority" name="priority" required>
                                    <option value="medium">Medium</option>
                                    <option value="low">Low</option>
                                    <option value="high">High</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3" 
                                  placeholder="Describe the maintenance work to be performed..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Schedule Maintenance</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Complete Maintenance Modal -->
<div class="modal fade" id="completeMaintenanceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Complete Maintenance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="complete">
                <input type="hidden" name="maintenance_id" id="complete_maintenance_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="completed_date" class="form-label">Completion Date *</label>
                        <input type="date" class="form-control" id="completed_date" name="completed_date" 
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="cost" class="form-label">Cost</label>
                        <input type="number" class="form-control" id="cost" name="cost" step="0.01" min="0">
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Completion Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="4" 
                                  placeholder="Describe the work performed, any issues found, parts replaced, etc."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Mark as Completed</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Maintenance Modal -->
<div class="modal fade" id="viewMaintenanceModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Maintenance Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="maintenanceDetails">
                <!-- Content will be populated by JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function completeMaintenance(id) {
    document.getElementById('complete_maintenance_id').value = id;
}

function viewMaintenance(record) {
    const details = document.getElementById('maintenanceDetails');
    const priority_colors = {
        'high': 'danger',
        'medium': 'warning', 
        'low': 'success'
    };
    
    details.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <h6>Item Information</h6>
                <p><strong>Name:</strong> ${record.item_name || 'N/A'}</p>
                <p><strong>Serial Number:</strong> ${record.serial_number || 'N/A'}</p>
                <p><strong>Category:</strong> ${record.category_name || 'N/A'}</p>
            </div>
            <div class="col-md-6">
                <h6>Maintenance Information</h6>
                <p><strong>Type:</strong> <span class="badge bg-info">${record.maintenance_type.replace('_', ' ')}</span></p>
                <p><strong>Priority:</strong> <span class="badge bg-${priority_colors[record.priority]}">${record.priority}</span></p>
                <p><strong>Status:</strong> ${getStatusBadgeHTML(record.status)}</p>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <h6>Schedule</h6>
                <p><strong>Scheduled Date:</strong> ${new Date(record.scheduled_date).toLocaleDateString()}</p>
                ${record.completed_date ? `<p><strong>Completed Date:</strong> ${new Date(record.completed_date).toLocaleDateString()}</p>` : ''}
            </div>
        </div>
        ${record.description ? `
        <div class="row">
            <div class="col-12">
                <h6>Description</h6>
                <p>${record.description}</p>
            </div>
        </div>` : ''}
        ${record.notes ? `
        <div class="row">
            <div class="col-12">
                <h6>Completion Notes</h6>
                <p>${record.notes}</p>
            </div>
        </div>` : ''}
        ${record.cost ? `
        <div class="row">
            <div class="col-12">
                <h6>Cost</h6>
                <p>$${parseFloat(record.cost).toFixed(2)}</p>
            </div>
        </div>` : ''}
        <div class="row">
            <div class="col-md-6">
                <p><strong>Created By:</strong> ${record.created_by_user || 'N/A'}</p>
                <p><strong>Created Date:</strong> ${new Date(record.created_at).toLocaleDateString()}</p>
            </div>
            ${record.completed_by_user ? `
            <div class="col-md-6">
                <p><strong>Completed By:</strong> ${record.completed_by_user}</p>
            </div>` : ''}
        </div>
    `;
}

function getStatusBadgeHTML(status) {
    const statusColors = {
        'scheduled': 'warning',
        'in_progress': 'info',
        'completed': 'success',
        'cancelled': 'secondary'
    };
    return `<span class="badge bg-${statusColors[status] || 'secondary'}">${status.replace('_', ' ').toUpperCase()}</span>`;
}
</script>

<?php include 'includes/footer.php'; ?>