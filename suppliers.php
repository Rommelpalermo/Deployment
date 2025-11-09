<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

// Check if user is logged in
requireLogin();

$page_title = 'Manage Suppliers';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'add') {
        $name = sanitize($_POST['name']);
        $contact_person = sanitize($_POST['contact_person']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        $address = sanitize($_POST['address']);
        $website = sanitize($_POST['website']);
        $notes = sanitize($_POST['notes']);
        
        if (!empty($name)) {
            $data = array(
                'name' => $name,
                'contact_person' => $contact_person,
                'email' => $email,
                'phone' => $phone,
                'address' => $address,
                'website' => $website,
                'notes' => $notes,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s')
            );
            
            try {
                $db->insert('suppliers', $data);
                logActivity(getCurrentUserId(), 'Supplier Added', "Added supplier: {$name}");
                setFlashMessage('success', 'Supplier added successfully!');
            } catch (Exception $e) {
                setFlashMessage('danger', 'Failed to add supplier.');
            }
        } else {
            setFlashMessage('danger', 'Please enter supplier name.');
        }
    }
    
    if ($_POST['action'] == 'edit') {
        $id = (int)$_POST['supplier_id'];
        $name = sanitize($_POST['name']);
        $contact_person = sanitize($_POST['contact_person']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        $address = sanitize($_POST['address']);
        $website = sanitize($_POST['website']);
        $notes = sanitize($_POST['notes']);
        $status = $_POST['status'];
        
        if (!empty($name)) {
            $data = array(
                'name' => $name,
                'contact_person' => $contact_person,
                'email' => $email,
                'phone' => $phone,
                'address' => $address,
                'website' => $website,
                'notes' => $notes,
                'status' => $status
            );
            
            try {
                $db->update('suppliers', $data, 'id = ?', array($id));
                logActivity(getCurrentUserId(), 'Supplier Updated', "Updated supplier: {$name}");
                setFlashMessage('success', 'Supplier updated successfully!');
            } catch (Exception $e) {
                setFlashMessage('danger', 'Failed to update supplier.');
            }
        } else {
            setFlashMessage('danger', 'Please enter supplier name.');
        }
    }
    
    header('Location: suppliers.php');
    exit;
}

// Handle delete
if (isset($_GET['delete']) && isAdmin()) {
    $id = (int)$_GET['delete'];
    
    // Check if supplier is in use
    $inUse = $db->exists('inventory', 'supplier_id = ?', array($id));
    
    if (!$inUse) {
        try {
            $supplier = $db->fetch("SELECT name FROM suppliers WHERE id = ?", array($id));
            $db->delete('suppliers', 'id = ?', array($id));
            logActivity(getCurrentUserId(), 'Supplier Deleted', "Deleted supplier: {$supplier['name']}");
            setFlashMessage('success', 'Supplier deleted successfully!');
        } catch (Exception $e) {
            setFlashMessage('danger', 'Failed to delete supplier.');
        }
    } else {
        setFlashMessage('warning', 'Cannot delete supplier - it has associated inventory items.');
    }
    
    header('Location: suppliers.php');
    exit;
}

// Get suppliers with usage counts
$suppliers = $db->fetchAll("
    SELECT s.*, 
           COUNT(i.id) as item_count,
           SUM(CASE WHEN i.purchase_price > 0 THEN i.purchase_price ELSE 0 END) as total_value
    FROM suppliers s 
    LEFT JOIN inventory i ON s.id = i.supplier_id 
    GROUP BY s.id 
    ORDER BY s.name
");

include 'includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-truck"></i> Manage Suppliers</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSupplierModal">
            <i class="fas fa-plus"></i> Add Supplier
        </button>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <?php 
    $total_suppliers = count($suppliers);
    $active_suppliers = 0;
    $total_items = 0;
    $total_value = 0;
    
    foreach ($suppliers as $supplier) {
        if ($supplier['status'] == 'active') {
            $active_suppliers++;
        }
        $total_items += $supplier['item_count'];
        $total_value += $supplier['total_value'];
    }
    ?>
    
    <div class="col-md-3 mb-3">
        <div class="card border-primary">
            <div class="card-body text-center">
                <div class="text-primary">
                    <i class="fas fa-truck fa-2x mb-2"></i>
                </div>
                <h5 class="card-title">Total Suppliers</h5>
                <h2 class="text-primary"><?php echo $total_suppliers; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card border-success">
            <div class="card-body text-center">
                <div class="text-success">
                    <i class="fas fa-check-circle fa-2x mb-2"></i>
                </div>
                <h5 class="card-title">Active Suppliers</h5>
                <h2 class="text-success"><?php echo $active_suppliers; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card border-info">
            <div class="card-body text-center">
                <div class="text-info">
                    <i class="fas fa-boxes fa-2x mb-2"></i>
                </div>
                <h5 class="card-title">Total Items</h5>
                <h2 class="text-info"><?php echo $total_items; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card border-warning">
            <div class="card-body text-center">
                <div class="text-warning">
                    <i class="fas fa-dollar-sign fa-2x mb-2"></i>
                </div>
                <h5 class="card-title">Total Value</h5>
                <h2 class="text-warning">$<?php echo number_format($total_value, 2); ?></h2>
            </div>
        </div>
    </div>
</div>

<!-- Suppliers Table -->
<div class="card">
    <div class="card-header">
        <h5>Suppliers (<?php echo count($suppliers); ?> total)</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover data-table">
                <thead>
                    <tr>
                        <th>Supplier</th>
                        <th>Contact</th>
                        <th>Items</th>
                        <th>Total Value</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($suppliers as $supplier): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($supplier['name']); ?></strong>
                            <?php if (!empty($supplier['contact_person'])): ?>
                                <br><small class="text-muted">Contact: <?php echo htmlspecialchars($supplier['contact_person']); ?></small>
                            <?php endif; ?>
                            <?php if (!empty($supplier['website'])): ?>
                                <br><small><a href="<?php echo htmlspecialchars($supplier['website']); ?>" target="_blank" class="text-decoration-none">
                                    <i class="fas fa-external-link-alt"></i> Website
                                </a></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($supplier['email'])): ?>
                                <small><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($supplier['email']); ?></small><br>
                            <?php endif; ?>
                            <?php if (!empty($supplier['phone'])): ?>
                                <small><i class="fas fa-phone"></i> <?php echo htmlspecialchars($supplier['phone']); ?></small><br>
                            <?php endif; ?>
                            <?php if (!empty($supplier['address'])): ?>
                                <small><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars(substr($supplier['address'], 0, 50)); ?><?php echo strlen($supplier['address']) > 50 ? '...' : ''; ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($supplier['item_count'] > 0): ?>
                                <span class="badge bg-info"><?php echo $supplier['item_count']; ?> items</span>
                            <?php else: ?>
                                <span class="text-muted">No items</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($supplier['total_value'] > 0): ?>
                                <strong>$<?php echo number_format($supplier['total_value'], 2); ?></strong>
                            <?php else: ?>
                                <span class="text-muted">$0.00</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo getStatusBadge($supplier['status']); ?></td>
                        <td>
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm btn-outline-info" 
                                        data-bs-toggle="modal" data-bs-target="#viewSupplierModal"
                                        onclick="viewSupplier(<?php echo htmlspecialchars(json_encode($supplier)); ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                        data-bs-toggle="modal" data-bs-target="#editSupplierModal"
                                        onclick="editSupplier(<?php echo htmlspecialchars(json_encode($supplier)); ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if (isAdmin() && $supplier['item_count'] == 0): ?>
                                <a href="?delete=<?php echo $supplier['id']; ?>" 
                                   class="btn btn-sm btn-outline-danger delete-btn" 
                                   data-name="<?php echo htmlspecialchars($supplier['name']); ?>">
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

<!-- Add Supplier Modal -->
<div class="modal fade" id="addSupplierModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Supplier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">Supplier Name *</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="contact_person" class="form-label">Contact Person</label>
                                <input type="text" class="form-control" id="contact_person" name="contact_person">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="text" class="form-control" id="phone" name="phone">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="website" class="form-label">Website</label>
                        <input type="url" class="form-control" id="website" name="website" placeholder="https://">
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" 
                                  placeholder="Additional information about the supplier..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Supplier</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Supplier Modal -->
<div class="modal fade" id="editSupplierModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Supplier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="supplier_id" id="edit_supplier_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_name" class="form-label">Supplier Name *</label>
                                <input type="text" class="form-control" id="edit_name" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_contact_person" class="form-label">Contact Person</label>
                                <input type="text" class="form-control" id="edit_contact_person" name="contact_person">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="edit_email" name="email">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_phone" class="form-label">Phone</label>
                                <input type="text" class="form-control" id="edit_phone" name="phone">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_address" class="form-label">Address</label>
                        <textarea class="form-control" id="edit_address" name="address" rows="3"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_website" class="form-label">Website</label>
                                <input type="url" class="form-control" id="edit_website" name="website">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_status" class="form-label">Status</label>
                                <select class="form-select" id="edit_status" name="status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="edit_notes" name="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Supplier</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Supplier Modal -->
<div class="modal fade" id="viewSupplierModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Supplier Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="supplierDetails">
                <!-- Content will be populated by JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function editSupplier(supplier) {
    document.getElementById('edit_supplier_id').value = supplier.id;
    document.getElementById('edit_name').value = supplier.name;
    document.getElementById('edit_contact_person').value = supplier.contact_person || '';
    document.getElementById('edit_email').value = supplier.email || '';
    document.getElementById('edit_phone').value = supplier.phone || '';
    document.getElementById('edit_address').value = supplier.address || '';
    document.getElementById('edit_website').value = supplier.website || '';
    document.getElementById('edit_status').value = supplier.status;
    document.getElementById('edit_notes').value = supplier.notes || '';
}

function viewSupplier(supplier) {
    const details = document.getElementById('supplierDetails');
    
    details.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <h6>Basic Information</h6>
                <p><strong>Name:</strong> ${supplier.name}</p>
                <p><strong>Contact Person:</strong> ${supplier.contact_person || 'Not specified'}</p>
                <p><strong>Status:</strong> ${getStatusBadgeHTML(supplier.status)}</p>
            </div>
            <div class="col-md-6">
                <h6>Contact Information</h6>
                <p><strong>Email:</strong> ${supplier.email ? `<a href="mailto:${supplier.email}">${supplier.email}</a>` : 'Not specified'}</p>
                <p><strong>Phone:</strong> ${supplier.phone ? `<a href="tel:${supplier.phone}">${supplier.phone}</a>` : 'Not specified'}</p>
                <p><strong>Website:</strong> ${supplier.website ? `<a href="${supplier.website}" target="_blank">${supplier.website} <i class="fas fa-external-link-alt"></i></a>` : 'Not specified'}</p>
            </div>
        </div>
        ${supplier.address ? `
        <div class="row">
            <div class="col-12">
                <h6>Address</h6>
                <p>${supplier.address.replace(/\n/g, '<br>')}</p>
            </div>
        </div>` : ''}
        <div class="row">
            <div class="col-12">
                <h6>Business Statistics</h6>
                <div class="row">
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h5 class="text-info">${supplier.item_count || 0}</h5>
                                <small>Items Supplied</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h5 class="text-warning">$${parseFloat(supplier.total_value || 0).toFixed(2)}</h5>
                                <small>Total Value</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h5 class="text-success">${supplier.status === 'active' ? 'Active' : 'Inactive'}</h5>
                                <small>Current Status</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        ${supplier.notes ? `
        <div class="row mt-3">
            <div class="col-12">
                <h6>Notes</h6>
                <p>${supplier.notes.replace(/\n/g, '<br>')}</p>
            </div>
        </div>` : ''}
        <div class="row mt-3">
            <div class="col-12">
                <small class="text-muted">Created: ${new Date(supplier.created_at).toLocaleDateString()}</small>
            </div>
        </div>
    `;
}

function getStatusBadgeHTML(status) {
    const statusColors = {
        'active': 'success',
        'inactive': 'secondary'
    };
    return `<span class="badge bg-${statusColors[status] || 'secondary'}">${status.toUpperCase()}</span>`;
}
</script>

<?php include 'includes/footer.php'; ?>