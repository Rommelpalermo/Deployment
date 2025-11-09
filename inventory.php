<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

// Check if user is logged in
requireLogin();

$page_title = 'Inventory Management';

// Get filters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build query conditions
$conditions = ['1=1'];
$params = [];

if ($status_filter) {
    $conditions[] = 'i.status = ?';
    $params[] = $status_filter;
}

if ($category_filter) {
    $conditions[] = 'i.category_id = ?';
    $params[] = $category_filter;
}

if ($search) {
    $conditions[] = '(i.name LIKE ? OR i.item_code LIKE ? OR i.model LIKE ? OR i.serial_number LIKE ?)';
    $searchTerm = '%' . $search . '%';
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

$whereClause = implode(' AND ', $conditions);

// Get inventory items
$inventory = $db->fetchAll("
    SELECT i.*, c.name as category_name, s.name as supplier_name, l.name as location_name,
           CONCAT(u1.first_name, ' ', u1.last_name) as assigned_user,
           CONCAT(u2.first_name, ' ', u2.last_name) as created_by_name
    FROM inventory i 
    LEFT JOIN categories c ON i.category_id = c.id 
    LEFT JOIN suppliers s ON i.supplier_id = s.id 
    LEFT JOIN locations l ON i.location_id = l.id 
    LEFT JOIN users u1 ON i.assigned_to = u1.id 
    LEFT JOIN users u2 ON i.created_by = u2.id 
    WHERE {$whereClause}
    ORDER BY i.created_at DESC
", $params);

// Get categories for filter
$categories = $db->fetchAll("SELECT * FROM categories WHERE type = 'equipment' AND status = 'active' ORDER BY name");

include 'includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-boxes"></i> Inventory Management</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="inventory_add.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Item
            </a>
            <button type="button" class="btn btn-outline-secondary" onclick="exportTableToCSV('inventory.csv')">
                <i class="fas fa-download"></i> Export
            </button>
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
                       placeholder="Search items...">
            </div>
            
            <div class="col-md-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Status</option>
                    <option value="available" <?php echo $status_filter == 'available' ? 'selected' : ''; ?>>Available</option>
                    <option value="deployed" <?php echo $status_filter == 'deployed' ? 'selected' : ''; ?>>Deployed</option>
                    <option value="maintenance" <?php echo $status_filter == 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                    <option value="retired" <?php echo $status_filter == 'retired' ? 'selected' : ''; ?>>Retired</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label for="category" class="form-label">Category</label>
                <select class="form-select" id="category" name="category">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" 
                                <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-outline-primary me-2">
                    <i class="fas fa-search"></i> Filter
                </button>
                <a href="inventory.php" class="btn btn-outline-secondary">
                    <i class="fas fa-times"></i> Clear
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Inventory Table -->
<div class="card">
    <div class="card-header">
        <h5>Inventory Items (<?php echo count($inventory); ?> items)</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover data-table" id="inventoryTable">
                <thead>
                    <tr>
                        <th>Item Code</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Model</th>
                        <th>Serial Number</th>
                        <th>Status</th>
                        <th>Location</th>
                        <th>Assigned To</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inventory as $item): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($item['item_code']); ?></strong>
                        </td>
                        <td>
                            <div>
                                <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                <?php if ($item['image']): ?>
                                    <br><small><i class="fas fa-image text-muted"></i> Has image</small>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($item['category_name'] ?: 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($item['model'] ?: 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($item['serial_number'] ?: 'N/A'); ?></td>
                        <td><?php echo getStatusBadge($item['status']); ?></td>
                        <td><?php echo htmlspecialchars($item['location_name'] ?: 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($item['assigned_user'] ?: 'Unassigned'); ?></td>
                        <td>
                            <div class="btn-group" role="group">
                                <a href="inventory_view.php?id=<?php echo $item['id']; ?>" 
                                   class="btn btn-sm btn-outline-info" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="inventory_edit.php?id=<?php echo $item['id']; ?>" 
                                   class="btn btn-sm btn-outline-primary" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if (isAdmin()): ?>
                                <a href="inventory_delete.php?id=<?php echo $item['id']; ?>" 
                                   class="btn btn-sm btn-outline-danger delete-btn" 
                                   title="Delete" data-name="<?php echo htmlspecialchars($item['name']); ?>">
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
                    <a href="inventory_add.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Item
                    </a>
                    <a href="categories.php" class="btn btn-outline-secondary">
                        <i class="fas fa-tags"></i> Manage Categories
                    </a>
                    <a href="suppliers.php" class="btn btn-outline-secondary">
                        <i class="fas fa-building"></i> Manage Suppliers
                    </a>
                    <a href="locations.php" class="btn btn-outline-secondary">
                        <i class="fas fa-map-marker-alt"></i> Manage Locations
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
    // Initialize DataTable with custom settings
    $('#inventoryTable').DataTable({
        responsive: true,
        pageLength: 25,
        order: [[ 0, 'desc' ]],
        columnDefs: [
            { orderable: false, targets: -1 } // Disable sorting on Actions column
        ],
        dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ]
    });
    
    // Auto-submit form on filter change
    $('#status, #category').change(function() {
        $(this).closest('form').submit();
    });
    
    // Clear search on escape key
    $('#search').keyup(function(e) {
        if (e.keyCode == 27) { // ESC key
            $(this).val('');
        }
    });
});
</script>
";

include 'includes/footer.php';
?>