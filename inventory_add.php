<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

// Check if user is logged in
requireLogin();

$page_title = 'Add Inventory Item';

// Get form data
$categories = $db->fetchAll("SELECT * FROM categories WHERE type = 'equipment' AND status = 'active' ORDER BY name");
$suppliers = $db->fetchAll("SELECT * FROM suppliers WHERE status = 'active' ORDER BY name");
$locations = $db->fetchAll("SELECT * FROM locations WHERE status = 'active' ORDER BY name");

$errors = [];
$success = false;

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
        
        // Check if item code exists
        if ($db->exists('inventory', 'item_code = ?', [$item_code])) {
            $errors[] = 'Item code already exists.';
        }
        
        // Handle file upload
        $image_filename = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $upload_result = uploadFile($_FILES['image'], 'assets/images/uploads/', ['jpg', 'jpeg', 'png', 'gif']);
            if ($upload_result['success']) {
                $image_filename = $upload_result['filename'];
            } else {
                $errors[] = $upload_result['message'];
            }
        }
        
        if (empty($errors)) {
            // Prepare data for insertion
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
                'notes' => $notes,
                'created_by' => getCurrentUserId()
            ];
            
            try {
                $itemId = $db->insert('inventory', $data);
                
                // Log activity
                logActivity(getCurrentUserId(), 'Inventory Item Added', "Added item: {$name} ({$item_code})");
                
                setFlashMessage('success', 'Inventory item added successfully!');
                header('Location: inventory_view.php?id=' . $itemId);
                exit;
                
            } catch (Exception $e) {
                $errors[] = 'Failed to add inventory item. Please try again.';
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-plus-circle"></i> Add Inventory Item</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="inventory.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to Inventory
        </a>
    </div>
</div>

<!-- Add Item Form -->
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
                
                <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <!-- Basic Information -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="item_code" class="form-label">Item Code *</label>
                            <input type="text" class="form-control" id="item_code" name="item_code" 
                                   value="<?php echo isset($_POST['item_code']) ? htmlspecialchars($_POST['item_code']) : ''; ?>" 
                                   required>
                            <div class="form-text">Unique identifier for this item</div>
                            <div class="invalid-feedback">Please provide an item code.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="name" class="form-label">Item Name *</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" 
                                   required>
                            <div class="invalid-feedback">Please provide an item name.</div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                    </div>
                    
                    <!-- Category and Supplier -->
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
                            <label for="supplier_id" class="form-label">Supplier</label>
                            <select class="form-select" id="supplier_id" name="supplier_id">
                                <option value="">Select Supplier</option>
                                <?php foreach ($suppliers as $supplier): ?>
                                    <option value="<?php echo $supplier['id']; ?>" 
                                            <?php echo (isset($_POST['supplier_id']) && $_POST['supplier_id'] == $supplier['id']) ? 'selected' : ''; ?>>
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
                                   value="<?php echo isset($_POST['model']) ? htmlspecialchars($_POST['model']) : ''; ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="serial_number" class="form-label">Serial Number</label>
                            <input type="text" class="form-control" id="serial_number" name="serial_number" 
                                   value="<?php echo isset($_POST['serial_number']) ? htmlspecialchars($_POST['serial_number']) : ''; ?>">
                        </div>
                    </div>
                    
                    <!-- Purchase Information -->
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="purchase_date" class="form-label">Purchase Date</label>
                            <input type="date" class="form-control" id="purchase_date" name="purchase_date" 
                                   value="<?php echo isset($_POST['purchase_date']) ? $_POST['purchase_date'] : ''; ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="purchase_cost" class="form-label">Purchase Cost</label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" class="form-control" id="purchase_cost" name="purchase_cost" 
                                       step="0.01" min="0"
                                       value="<?php echo isset($_POST['purchase_cost']) ? $_POST['purchase_cost'] : ''; ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="warranty_expiry" class="form-label">Warranty Expiry</label>
                            <input type="date" class="form-control" id="warranty_expiry" name="warranty_expiry" 
                                   value="<?php echo isset($_POST['warranty_expiry']) ? $_POST['warranty_expiry'] : ''; ?>">
                        </div>
                    </div>
                    
                    <!-- Status Information -->
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="condition_status" class="form-label">Condition Status *</label>
                            <select class="form-select" id="condition_status" name="condition_status" required>
                                <option value="">Select Condition</option>
                                <option value="excellent" <?php echo (isset($_POST['condition_status']) && $_POST['condition_status'] == 'excellent') ? 'selected' : ''; ?>>Excellent</option>
                                <option value="good" <?php echo (isset($_POST['condition_status']) && $_POST['condition_status'] == 'good') ? 'selected' : ''; ?>>Good</option>
                                <option value="fair" <?php echo (isset($_POST['condition_status']) && $_POST['condition_status'] == 'fair') ? 'selected' : ''; ?>>Fair</option>
                                <option value="poor" <?php echo (isset($_POST['condition_status']) && $_POST['condition_status'] == 'poor') ? 'selected' : ''; ?>>Poor</option>
                            </select>
                            <div class="invalid-feedback">Please select a condition status.</div>
                        </div>
                        <div class="col-md-4">
                            <label for="status" class="form-label">Availability Status *</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="">Select Status</option>
                                <option value="available" <?php echo (isset($_POST['status']) && $_POST['status'] == 'available') ? 'selected' : ''; ?>>Available</option>
                                <option value="deployed" <?php echo (isset($_POST['status']) && $_POST['status'] == 'deployed') ? 'selected' : ''; ?>>Deployed</option>
                                <option value="maintenance" <?php echo (isset($_POST['status']) && $_POST['status'] == 'maintenance') ? 'selected' : ''; ?>>Maintenance</option>
                                <option value="retired" <?php echo (isset($_POST['status']) && $_POST['status'] == 'retired') ? 'selected' : ''; ?>>Retired</option>
                            </select>
                            <div class="invalid-feedback">Please select a status.</div>
                        </div>
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
                    </div>
                    
                    <!-- Image Upload -->
                    <div class="mb-3">
                        <label for="image" class="form-label">Item Image</label>
                        <input type="file" class="form-control file-input" id="image" name="image" accept="image/*">
                        <div class="form-text">Upload an image of the item (JPG, PNG, GIF - Max 5MB)</div>
                        <div class="file-preview mt-2"></div>
                    </div>
                    
                    <!-- Notes -->
                    <div class="mb-4">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" 
                                  placeholder="Additional notes about this item..."><?php echo isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : ''; ?></textarea>
                    </div>
                    
                    <!-- Submit Buttons -->
                    <div class="d-flex justify-content-between">
                        <a href="inventory.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Item
                            </button>
                            <button type="button" class="btn btn-success" onclick="saveAndAddAnother()">
                                <i class="fas fa-plus"></i> Save & Add Another
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
                <h6>Item Code Guidelines:</h6>
                <ul class="small">
                    <li>Use a consistent format (e.g., LAB-001, EQ-2024-001)</li>
                    <li>Include category prefix for easy identification</li>
                    <li>Ensure codes are unique across all items</li>
                </ul>
                
                <h6>Status Definitions:</h6>
                <ul class="small">
                    <li><strong>Available:</strong> Ready for use/deployment</li>
                    <li><strong>Deployed:</strong> Currently in use at a location</li>
                    <li><strong>Maintenance:</strong> Undergoing repairs/servicing</li>
                    <li><strong>Retired:</strong> No longer in service</li>
                </ul>
                
                <h6>Condition Guidelines:</h6>
                <ul class="small">
                    <li><strong>Excellent:</strong> Like new, no visible wear</li>
                    <li><strong>Good:</strong> Minor wear, fully functional</li>
                    <li><strong>Fair:</strong> Noticeable wear, may need attention</li>
                    <li><strong>Poor:</strong> Significant wear, limited functionality</li>
                </ul>
            </div>
        </div>
        
        <div class="card mt-3">
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
$page_scripts = "
<script>
function saveAndAddAnother() {
    // Add hidden field to indicate save and add another
    const form = document.querySelector('form');
    const hiddenInput = document.createElement('input');
    hiddenInput.type = 'hidden';
    hiddenInput.name = 'save_and_add_another';
    hiddenInput.value = '1';
    form.appendChild(hiddenInput);
    form.submit();
}

// Auto-generate item code based on category
$('#category_id').change(function() {
    const categoryName = $('#category_id option:selected').text();
    if (categoryName && categoryName !== 'Select Category') {
        const prefix = categoryName.substring(0, 3).toUpperCase();
        const timestamp = Date.now().toString().slice(-6);
        const suggestedCode = prefix + '-' + timestamp;
        
        if (!$('#item_code').val()) {
            $('#item_code').val(suggestedCode);
        }
    }
});

// Calculate warranty expiry based on purchase date
$('#purchase_date').change(function() {
    const purchaseDate = new Date(this.value);
    if (purchaseDate && !$('#warranty_expiry').val()) {
        // Default to 1 year warranty
        const warrantyDate = new Date(purchaseDate);
        warrantyDate.setFullYear(warrantyDate.getFullYear() + 1);
        $('#warranty_expiry').val(warrantyDate.toISOString().split('T')[0]);
    }
});
</script>
";

include 'includes/footer.php';
?>