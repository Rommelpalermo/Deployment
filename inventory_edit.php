<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

// Check if user is logged in
requireLogin();

$page_title = 'Edit Inventory Item';

// Get item ID
$item_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$item_id) {
    setFlashMessage('danger', 'Invalid item ID.');
    header('Location: inventory.php');
    exit;
}

// Get item data
$item = $db->fetch("SELECT * FROM inventory WHERE id = ?", [$item_id]);

if (!$item) {
    setFlashMessage('danger', 'Item not found.');
    header('Location: inventory.php');
    exit;
}

// Get form data
$categories = $db->fetchAll("SELECT * FROM categories WHERE type = 'equipment' AND status = 'active' ORDER BY name");
$suppliers = $db->fetchAll("SELECT * FROM suppliers WHERE status = 'active' ORDER BY name");
$locations = $db->fetchAll("SELECT * FROM locations WHERE status = 'active' ORDER BY name");

$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = 'Invalid security token.';
    } else {
        // Get form data
        $item_code = sanitize($_POST['item_code']);
        $name = sanitize($_POST['name']);
        $description = sanitize($_POST['description']);
        $category_id = $_POST['category_id'] ?: null;
        $supplier_id = $_POST['supplier_id'] ?: null;
        $model = sanitize($_POST['model']);
        $serial_number = sanitize($_POST['serial_number']);
        $purchase_date = $_POST['purchase_date'] ?: null;
        $purchase_cost = $_POST['purchase_cost'] ?: null;
        $warranty_expiry = $_POST['warranty_expiry'] ?: null;
        $condition_status = $_POST['condition_status'];
        $status = $_POST['status'];
        $location_id = $_POST['location_id'] ?: null;
        $notes = sanitize($_POST['notes']);
        
        // Validation
        if (empty($item_code)) $errors[] = 'Item code is required.';
        if (empty($name)) $errors[] = 'Item name is required.';
        if (empty($condition_status)) $errors[] = 'Condition status is required.';
        if (empty($status)) $errors[] = 'Status is required.';
        
        // Check if item code exists (excluding current item)
        $existing = $db->fetch('SELECT id FROM inventory WHERE item_code = ? AND id != ?', [$item_code, $item_id]);
        if ($existing) {
            $errors[] = 'Item code already exists.';
        }
        
        // Handle file upload
        $image_filename = $item['image'];
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $upload_result = uploadFile($_FILES['image'], 'assets/images/uploads/', ['jpg', 'jpeg', 'png', 'gif']);
            if ($upload_result['success']) {
                $image_filename = $upload_result['filename'];
                // Delete old image if exists
                if ($item['image'] && file_exists('assets/images/uploads/' . $item['image'])) {
                    unlink('assets/images/uploads/' . $item['image']);
                }
            } else {
                $errors[] = $upload_result['message'];
            }
        }
        
        if (empty($errors)) {
            // Prepare data for update
            $data = [
                'item_code' => $item_code,
                'name' => $name,
                'description' => $description,
                'category_id' => $category_id,
                'supplier_id' => $supplier_id,
                'model' => $model,
                'serial_number' => $serial_number,
                'purchase_date' => $purchase_date,
                'purchase_cost' => $purchase_cost,
                'warranty_expiry' => $warranty_expiry,
                'condition_status' => $condition_status,
                'status' => $status,
                'location_id' => $location_id,
                'image' => $image_filename,
                'notes' => $notes
            ];
            
            try {
                $db->update('inventory', $data, 'id = ?', [$item_id]);
                
                // Log activity
                logActivity(getCurrentUserId(), 'Inventory Item Updated', "Updated item: {$name} ({$item_code})");
                
                setFlashMessage('success', 'Inventory item updated successfully!');
                header('Location: inventory.php');
                exit;
                
            } catch (Exception $e) {
                error_log("Database error in inventory_edit.php: " . $e->getMessage());
                $errors[] = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-edit"></i> Edit Inventory Item</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="inventory.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to Inventory
        </a>
    </div>
</div>

<!-- Edit Item Form -->
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-info-circle"></i> Item Information</h5>
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
                
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <!-- Basic Information -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="item_code" class="form-label">Item Code *</label>
                            <input type="text" class="form-control" id="item_code" name="item_code" 
                                   value="<?php echo htmlspecialchars($item['item_code']); ?>" 
                                   required>
                            <div class="form-text">Unique identifier for this item</div>
                        </div>
                        <div class="col-md-6">
                            <label for="name" class="form-label">Item Name *</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?php echo htmlspecialchars($item['name']); ?>" 
                                   required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($item['description']); ?></textarea>
                    </div>
                    
                    <!-- Category and Supplier -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="category_id" class="form-label">Category</label>
                            <select class="form-select" id="category_id" name="category_id">
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" 
                                            <?php echo ($item['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="supplier_id" class="form-label">Supplier</label>
                            <select class="form-select" id="supplier_id" name="supplier_id">
                                <option value="">Select Supplier</option>
                                <?php foreach ($suppliers as $supplier): ?>
                                    <option value="<?php echo $supplier['id']; ?>" 
                                            <?php echo ($item['supplier_id'] == $supplier['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($supplier['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Technical Details -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="model" class="form-label">Model</label>
                            <input type="text" class="form-control" id="model" name="model" 
                                   value="<?php echo htmlspecialchars($item['model']); ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="serial_number" class="form-label">Serial Number</label>
                            <input type="text" class="form-control" id="serial_number" name="serial_number" 
                                   value="<?php echo htmlspecialchars($item['serial_number']); ?>">
                        </div>
                    </div>
                    
                    <!-- Purchase Information -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="purchase_date" class="form-label">Purchase Date</label>
                            <input type="date" class="form-control" id="purchase_date" name="purchase_date" 
                                   value="<?php echo $item['purchase_date']; ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="purchase_cost" class="form-label">Purchase Cost</label>
                            <input type="number" step="0.01" class="form-control" id="purchase_cost" name="purchase_cost" 
                                   value="<?php echo $item['purchase_cost']; ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="warranty_expiry" class="form-label">Warranty Expiry</label>
                        <input type="date" class="form-control" id="warranty_expiry" name="warranty_expiry" 
                               value="<?php echo $item['warranty_expiry']; ?>">
                    </div>
                    
                    <!-- Status and Condition -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="condition_status" class="form-label">Condition *</label>
                            <select class="form-select" id="condition_status" name="condition_status" required>
                                <option value="">Select Condition</option>
                                <option value="excellent" <?php echo ($item['condition_status'] == 'excellent') ? 'selected' : ''; ?>>Excellent</option>
                                <option value="good" <?php echo ($item['condition_status'] == 'good') ? 'selected' : ''; ?>>Good</option>
                                <option value="fair" <?php echo ($item['condition_status'] == 'fair') ? 'selected' : ''; ?>>Fair</option>
                                <option value="poor" <?php echo ($item['condition_status'] == 'poor') ? 'selected' : ''; ?>>Poor</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="status" class="form-label">Status *</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="">Select Status</option>
                                <option value="available" <?php echo ($item['status'] == 'available') ? 'selected' : ''; ?>>Available</option>
                                <option value="deployed" <?php echo ($item['status'] == 'deployed') ? 'selected' : ''; ?>>Deployed</option>
                                <option value="maintenance" <?php echo ($item['status'] == 'maintenance') ? 'selected' : ''; ?>>Maintenance</option>
                                <option value="retired" <?php echo ($item['status'] == 'retired') ? 'selected' : ''; ?>>Retired</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Location -->
                    <div class="mb-3">
                        <label for="location_id" class="form-label">Location</label>
                        <select class="form-select" id="location_id" name="location_id">
                            <option value="">Select Location</option>
                            <?php foreach ($locations as $location): ?>
                                <option value="<?php echo $location['id']; ?>" 
                                        <?php echo ($item['location_id'] == $location['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($location['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Image Upload -->
                    <div class="mb-3">
                        <label for="image" class="form-label">Item Image</label>
                        <?php if ($item['image']): ?>
                            <div class="mb-2">
                                <img src="assets/images/uploads/<?php echo htmlspecialchars($item['image']); ?>" 
                                     alt="Current image" class="img-thumbnail" style="max-width: 200px;">
                                <p class="text-muted small">Current image (upload a new one to replace)</p>
                            </div>
                        <?php endif; ?>
                        <input type="file" class="form-control" id="image" name="image" accept="image/*">
                        <div class="form-text">Allowed formats: JPG, JPEG, PNG, GIF (Max 5MB)</div>
                    </div>
                    
                    <!-- Notes -->
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($item['notes']); ?></textarea>
                    </div>
                    
                    <!-- Submit Buttons -->
                    <div class="d-flex justify-content-between">
                        <a href="inventory.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Item
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Quick Links Sidebar -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-link"></i> Quick Links</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="categories.php" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-tags"></i> Manage Categories
                    </a>
                    <a href="suppliers.php" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-building"></i> Manage Suppliers
                    </a>
                    <a href="locations.php" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-map-marker-alt"></i> Manage Locations
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include 'includes/footer.php';
?>
