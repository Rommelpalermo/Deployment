<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

// Check if user is logged in
requireLogin();

$page_title = 'Manage Categories';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'add') {
        $name = sanitize($_POST['name']);
        $description = sanitize($_POST['description']);
        $type = $_POST['type'];
        
        if (!empty($name) && !empty($type)) {
            $data = array(
                'name' => $name,
                'description' => $description,
                'type' => $type
            );
            
            try {
                $db->insert('categories', $data);
                logActivity(getCurrentUserId(), 'Category Added', "Added category: {$name}");
                setFlashMessage('success', 'Category added successfully!');
            } catch (Exception $e) {
                setFlashMessage('danger', 'Failed to add category.');
            }
        } else {
            setFlashMessage('danger', 'Please fill in all required fields.');
        }
    }
    
    header('Location: categories.php');
    exit;
}

// Handle delete
if (isset($_GET['delete']) && isAdmin()) {
    $id = (int)$_GET['delete'];
    
    // Check if category is in use
    $inUse = $db->exists('inventory', 'category_id = ?', array($id));
    
    if (!$inUse) {
        try {
            $category = $db->fetch("SELECT name FROM categories WHERE id = ?", array($id));
            $db->delete('categories', 'id = ?', array($id));
            logActivity(getCurrentUserId(), 'Category Deleted', "Deleted category: {$category['name']}");
            setFlashMessage('success', 'Category deleted successfully!');
        } catch (Exception $e) {
            setFlashMessage('danger', 'Failed to delete category.');
        }
    } else {
        setFlashMessage('warning', 'Cannot delete category - it is currently in use.');
    }
    
    header('Location: categories.php');
    exit;
}

// Get categories
$categories = $db->fetchAll("
    SELECT c.*, 
           COUNT(i.id) as item_count 
    FROM categories c 
    LEFT JOIN inventory i ON c.id = i.category_id 
    GROUP BY c.id 
    ORDER BY c.type, c.name
");

include 'includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-tags"></i> Manage Categories</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
            <i class="fas fa-plus"></i> Add Category
        </button>
    </div>
</div>

<!-- Categories Table -->
<div class="card">
    <div class="card-header">
        <h5>Categories (<?php echo count($categories); ?> total)</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th>Items Count</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $category): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($category['name']); ?></strong></td>
                        <td>
                            <span class="badge bg-<?php echo $category['type'] == 'equipment' ? 'primary' : 'success'; ?>">
                                <?php echo ucfirst($category['type']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($category['description'] ? $category['description'] : 'No description'); ?></td>
                        <td>
                            <?php if ($category['item_count'] > 0): ?>
                                <span class="badge bg-info"><?php echo $category['item_count']; ?> items</span>
                            <?php else: ?>
                                <span class="text-muted">No items</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo getStatusBadge($category['status']); ?></td>
                        <td>
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="editCategory(<?php echo $category['id']; ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if (isAdmin() && $category['item_count'] == 0): ?>
                                <a href="?delete=<?php echo $category['id']; ?>" 
                                   class="btn btn-sm btn-outline-danger delete-btn" 
                                   data-name="<?php echo htmlspecialchars($category['name']); ?>">
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

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">Category Name *</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="type" class="form-label">Type *</label>
                        <select class="form-select" id="type" name="type" required>
                            <option value="">Select Type</option>
                            <option value="equipment">Equipment</option>
                            <option value="deployment">Deployment</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>