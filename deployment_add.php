<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

// Check if user is logged in
requireLogin();

$page_title = 'Create New Deployment';

// Get form data
$categories = $db->fetchAll("SELECT * FROM categories WHERE type = 'deployment' AND status = 'active' ORDER BY name");
$locations = $db->fetchAll("SELECT * FROM locations WHERE status = 'active' ORDER BY name");
$users = $db->fetchAll("SELECT id, CONCAT(first_name, ' ', last_name) as name FROM users WHERE status = 'active' ORDER BY first_name, last_name");
$availableItems = $db->fetchAll("SELECT i.*, c.name as category_name FROM inventory i LEFT JOIN categories c ON i.category_id = c.id WHERE i.status = 'available' ORDER BY i.name");

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = 'Invalid security token.';
    } else {
        // Get form data
        $deployment_code = sanitize($_POST['deployment_code']);
        $title = sanitize($_POST['title']);
        $description = sanitize($_POST['description']);
        $category_id = $_POST['category_id'] ?: null;
        $location_id = $_POST['location_id'] ?: null;
        $assigned_to = $_POST['assigned_to'] ?: null;
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'] ?: null;
        $estimated_duration = $_POST['estimated_duration'] ?: null;
        $priority = $_POST['priority'];
        $budget = $_POST['budget'] ?: null;
        $notes = sanitize($_POST['notes']);
        $selected_items = isset($_POST['selected_items']) ? $_POST['selected_items'] : [];
        
        // Validation
        if (empty($deployment_code)) $errors[] = 'Deployment code is required.';
        if (empty($title)) $errors[] = 'Title is required.';
        if (empty($start_date)) $errors[] = 'Start date is required.';
        if (empty($priority)) $errors[] = 'Priority is required.';
        
        // Check if deployment code exists
        if ($db->exists('deployments', 'deployment_code = ?', [$deployment_code])) {
            $errors[] = 'Deployment code already exists.';
        }
        
        // Validate dates
        if ($start_date && $end_date && strtotime($end_date) < strtotime($start_date)) {
            $errors[] = 'End date cannot be before start date.';
        }
        
        if (empty($errors)) {
            try {
                $db->beginTransaction();
                
                // Prepare deployment data
                $deploymentData = [
                    'deployment_code' => $deployment_code,
                    'title' => $title,
                    'description' => $description,
                    'category_id' => $category_id,
                    'location_id' => $location_id,
                    'assigned_to' => $assigned_to,
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                    'estimated_duration' => $estimated_duration,
                    'priority' => $priority,
                    'budget' => $budget,
                    'notes' => $notes,
                    'created_by' => getCurrentUserId()
                ];
                
                // Insert deployment
                $deploymentId = $db->insert('deployments', $deploymentData);
                
                // Add selected items to deployment
                if (!empty($selected_items)) {
                    foreach ($selected_items as $itemId => $quantity) {
                        if ($quantity > 0) {
                            $itemData = [
                                'deployment_id' => $deploymentId,
                                'inventory_id' => $itemId,
                                'quantity' => $quantity,
                                'checkout_date' => date('Y-m-d H:i:s'),
                                'expected_return_date' => $end_date ? $end_date . ' 23:59:59' : null,
                                'condition_checkout' => 'good' // Default condition
                            ];
                            
                            $db->insert('deployment_items', $itemData);
                            
                            // Update inventory status to deployed
                            $db->update('inventory', ['status' => 'deployed'], 'id = ?', [$itemId]);
                        }
                    }
                }
                
                $db->commit();
                
                // Log activity
                logActivity(getCurrentUserId(), 'Deployment Created', "Created deployment: {$title} ({$deployment_code})");
                
                setFlashMessage('success', 'Deployment created successfully!');
                header('Location: deployment_view.php?id=' . $deploymentId);
                exit;
                
            } catch (Exception $e) {
                $db->rollback();
                $errors[] = 'Failed to create deployment. Please try again.';
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-plus-square"></i> Create New Deployment</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="deployments.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to Deployments
        </a>
    </div>
</div>

<!-- Create Deployment Form -->
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-info-circle"></i> Deployment Information</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="needs-validation" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <!-- Basic Information -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="deployment_code" class="form-label">Deployment Code *</label>
                            <input type="text" class="form-control" id="deployment_code" name="deployment_code" 
                                   value="<?php echo isset($_POST['deployment_code']) ? htmlspecialchars($_POST['deployment_code']) : ''; ?>" 
                                   required>
                            <div class="form-text">Unique identifier for this deployment</div>
                            <div class="invalid-feedback">Please provide a deployment code.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="priority" class="form-label">Priority *</label>
                            <select class="form-select" id="priority" name="priority" required>
                                <option value="">Select Priority</option>
                                <option value="low" <?php echo (isset($_POST['priority']) && $_POST['priority'] == 'low') ? 'selected' : ''; ?>>Low</option>
                                <option value="medium" <?php echo (isset($_POST['priority']) && $_POST['priority'] == 'medium') ? 'selected' : ''; ?>>Medium</option>
                                <option value="high" <?php echo (isset($_POST['priority']) && $_POST['priority'] == 'high') ? 'selected' : ''; ?>>High</option>
                                <option value="critical" <?php echo (isset($_POST['priority']) && $_POST['priority'] == 'critical') ? 'selected' : ''; ?>>Critical</option>
                            </select>
                            <div class="invalid-feedback">Please select a priority level.</div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="title" class="form-label">Title *</label>
                        <input type="text" class="form-control" id="title" name="title" 
                               value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" 
                               required>
                        <div class="invalid-feedback">Please provide a title.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                    </div>
                    
                    <!-- Category and Assignment -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="category_id" class="form-label">Category</label>
                            <select class="form-select" id="category_id" name="category_id">
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" 
                                            <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="assigned_to" class="form-label">Assigned To</label>
                            <select class="form-select" id="assigned_to" name="assigned_to">
                                <option value="">Select User</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>" 
                                            <?php echo (isset($_POST['assigned_to']) && $_POST['assigned_to'] == $user['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($user['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Location and Dates -->
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="location_id" class="form-label">Location</label>
                            <select class="form-select" id="location_id" name="location_id">
                                <option value="">Select Location</option>
                                <?php foreach ($locations as $location): ?>
                                    <option value="<?php echo $location['id']; ?>" 
                                            <?php echo (isset($_POST['location_id']) && $_POST['location_id'] == $location['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($location['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="start_date" class="form-label">Start Date *</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" 
                                   value="<?php echo isset($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-d'); ?>" 
                                   required>
                            <div class="invalid-feedback">Please provide a start date.</div>
                        </div>
                        <div class="col-md-4">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" 
                                   value="<?php echo isset($_POST['end_date']) ? $_POST['end_date'] : ''; ?>">
                        </div>
                    </div>
                    
                    <!-- Duration and Budget -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="estimated_duration" class="form-label">Estimated Duration (Days)</label>
                            <input type="number" class="form-control" id="estimated_duration" name="estimated_duration" 
                                   min="1" value="<?php echo isset($_POST['estimated_duration']) ? $_POST['estimated_duration'] : ''; ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="budget" class="form-label">Budget</label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" class="form-control" id="budget" name="budget" 
                                       step="0.01" min="0"
                                       value="<?php echo isset($_POST['budget']) ? $_POST['budget'] : ''; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Notes -->
                    <div class="mb-4">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" 
                                  placeholder="Additional notes about this deployment..."><?php echo isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : ''; ?></textarea>
                    </div>
                    
                    <!-- Equipment Selection -->
                    <div class="mb-4">
                        <h5><i class="fas fa-boxes"></i> Equipment & Items</h5>
                        <div class="card">
                            <div class="card-header">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <h6 class="mb-0">Select Items for Deployment</h6>
                                    </div>
                                    <div class="col-auto">
                                        <button type="button" class="btn btn-sm btn-outline-primary" id="selectAll">
                                            Select All
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" id="clearAll">
                                            Clear All
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                                <div class="row">
                                    <?php foreach ($availableItems as $item): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card">
                                            <div class="card-body p-3">
                                                <div class="form-check">
                                                    <input class="form-check-input item-checkbox" type="checkbox" 
                                                           id="item_<?php echo $item['id']; ?>" 
                                                           data-item-id="<?php echo $item['id']; ?>">
                                                    <label class="form-check-label" for="item_<?php echo $item['id']; ?>">
                                                        <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                                        <br><small class="text-muted">
                                                            <?php echo htmlspecialchars($item['item_code']); ?>
                                                            | <?php echo htmlspecialchars($item['category_name'] ?: 'No Category'); ?>
                                                        </small>
                                                    </label>
                                                </div>
                                                <div class="mt-2" style="display: none;" id="quantity_<?php echo $item['id']; ?>">
                                                    <label class="form-label small">Quantity:</label>
                                                    <input type="number" class="form-control form-control-sm" 
                                                           name="selected_items[<?php echo $item['id']; ?>]" 
                                                           min="1" max="1" value="1">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                    
                                    <?php if (empty($availableItems)): ?>
                                    <div class="col-12">
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle"></i> No available items found. 
                                            <a href="inventory_add.php">Add some inventory items</a> first.
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Submit Buttons -->
                    <div class="d-flex justify-content-between">
                        <a href="deployments.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Create Deployment
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Help Panel -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-question-circle"></i> Help & Tips</h5>
            </div>
            <div class="card-body">
                <h6>Deployment Code Guidelines:</h6>
                <ul class="small">
                    <li>Use format: DEPT-YYYY-### (e.g., FIELD-2024-001)</li>
                    <li>Include year for easy tracking</li>
                    <li>Sequential numbering recommended</li>
                </ul>
                
                <h6>Priority Levels:</h6>
                <ul class="small">
                    <li><strong>Critical:</strong> Urgent, immediate attention required</li>
                    <li><strong>High:</strong> Important, needs prompt handling</li>
                    <li><strong>Medium:</strong> Standard priority</li>
                    <li><strong>Low:</strong> Can be handled when resources available</li>
                </ul>
                
                <h6>Planning Tips:</h6>
                <ul class="small">
                    <li>Set realistic start and end dates</li>
                    <li>Consider equipment availability</li>
                    <li>Include buffer time for setup</li>
                    <li>Assign responsible personnel early</li>
                </ul>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h5><i class="fas fa-chart-line"></i> Quick Stats</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6">
                        <h4 class="text-primary"><?php echo count($availableItems); ?></h4>
                        <small>Available Items</small>
                    </div>
                    <div class="col-6">
                        <h4 class="text-success"><?php echo count($locations); ?></h4>
                        <small>Active Locations</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$page_scripts = "
<script>
// Auto-generate deployment code
function generateDeploymentCode() {
    const now = new Date();
    const year = now.getFullYear();
    const random = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
    return 'DEPT-' + year + '-' + random;
}

// Generate code if field is empty
if (!$('#deployment_code').val()) {
    $('#deployment_code').val(generateDeploymentCode());
}

// Calculate duration when dates change
$('#start_date, #end_date').change(function() {
    const startDate = $('#start_date').val();
    const endDate = $('#end_date').val();
    
    if (startDate && endDate) {
        const start = new Date(startDate);
        const end = new Date(endDate);
        const diffTime = Math.abs(end - start);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        
        $('#estimated_duration').val(diffDays);
    }
});

// Calculate end date when duration changes
$('#estimated_duration').change(function() {
    const startDate = $('#start_date').val();
    const duration = parseInt($(this).val());
    
    if (startDate && duration > 0) {
        const start = new Date(startDate);
        start.setDate(start.getDate() + duration);
        $('#end_date').val(start.toISOString().split('T')[0]);
    }
});

// Item selection functionality
$('.item-checkbox').change(function() {
    const itemId = $(this).data('item-id');
    const quantityDiv = $('#quantity_' + itemId);
    
    if ($(this).is(':checked')) {
        quantityDiv.show();
        quantityDiv.find('input').prop('required', true);
    } else {
        quantityDiv.hide();
        quantityDiv.find('input').prop('required', false);
    }
    
    updateSelectedCount();
});

// Select/Clear all functionality
$('#selectAll').click(function() {
    $('.item-checkbox').prop('checked', true).trigger('change');
});

$('#clearAll').click(function() {
    $('.item-checkbox').prop('checked', false).trigger('change');
});

// Update selected items count
function updateSelectedCount() {
    const count = $('.item-checkbox:checked').length;
    const text = count + ' item' + (count !== 1 ? 's' : '') + ' selected';
    // You could display this somewhere if needed
}
</script>
";

include 'includes/footer.php';
?>