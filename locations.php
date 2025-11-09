<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

// Check if user is logged in
requireLogin();

$page_title = 'Manage Locations';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'add') {
        $name = sanitize($_POST['name']);
        $type = $_POST['type'];
        $address = sanitize($_POST['address']);
        $contact_person = sanitize($_POST['contact_person']);
        $contact_phone = sanitize($_POST['contact_phone']);
        $contact_email = sanitize($_POST['contact_email']);
        $description = sanitize($_POST['description']);
        
        if (!empty($name) && !empty($type)) {
            $data = array(
                'name' => $name,
                'type' => $type,
                'address' => $address,
                'contact_person' => $contact_person,
                'contact_phone' => $contact_phone,
                'contact_email' => $contact_email,
                'description' => $description,
                'created_at' => date('Y-m-d H:i:s')
            );
            
            try {
                $db->insert('locations', $data);
                logActivity(getCurrentUserId(), 'Location Added', "Added location: {$name}");
                setFlashMessage('success', 'Location added successfully!');
            } catch (Exception $e) {
                setFlashMessage('danger', 'Failed to add location.');
            }
        } else {
            setFlashMessage('danger', 'Please fill in all required fields.');
        }
    }
    
    if ($_POST['action'] == 'edit') {
        $id = (int)$_POST['location_id'];
        $name = sanitize($_POST['name']);
        $type = $_POST['type'];
        $address = sanitize($_POST['address']);
        $contact_person = sanitize($_POST['contact_person']);
        $contact_phone = sanitize($_POST['contact_phone']);
        $contact_email = sanitize($_POST['contact_email']);
        $description = sanitize($_POST['description']);
        
        if (!empty($name) && !empty($type)) {
            $data = array(
                'name' => $name,
                'type' => $type,
                'address' => $address,
                'contact_person' => $contact_person,
                'contact_phone' => $contact_phone,
                'contact_email' => $contact_email,
                'description' => $description
            );
            
            try {
                $db->update('locations', $data, 'id = ?', array($id));
                logActivity(getCurrentUserId(), 'Location Updated', "Updated location: {$name}");
                setFlashMessage('success', 'Location updated successfully!');
            } catch (Exception $e) {
                setFlashMessage('danger', 'Failed to update location.');
            }
        } else {
            setFlashMessage('danger', 'Please fill in all required fields.');
        }
    }
    
    header('Location: locations.php');
    exit;
}

// Handle delete
if (isset($_GET['delete']) && isAdmin()) {
    $id = (int)$_GET['delete'];
    
    // Check if location is in use
    $inUse = $db->exists('inventory', 'location_id = ?', array($id)) || 
             $db->exists('deployments', 'location_id = ?', array($id));
    
    if (!$inUse) {
        try {
            $location = $db->fetch("SELECT name FROM locations WHERE id = ?", array($id));
            $db->delete('locations', 'id = ?', array($id));
            logActivity(getCurrentUserId(), 'Location Deleted', "Deleted location: {$location['name']}");
            setFlashMessage('success', 'Location deleted successfully!');
        } catch (Exception $e) {
            setFlashMessage('danger', 'Failed to delete location.');
        }
    } else {
        setFlashMessage('warning', 'Cannot delete location - it is currently in use.');
    }
    
    header('Location: locations.php');
    exit;
}

// Get locations with usage counts
$locations = $db->fetchAll("
    SELECT l.*, 
           COUNT(DISTINCT i.id) as inventory_count,
           COUNT(DISTINCT d.id) as deployment_count,
           (COUNT(DISTINCT i.id) + COUNT(DISTINCT d.id)) as total_usage
    FROM locations l 
    LEFT JOIN inventory i ON l.id = i.location_id 
    LEFT JOIN deployments d ON l.id = d.location_id
    GROUP BY l.id 
    ORDER BY l.type, l.name
");

include 'includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-map-marker-alt"></i> Manage Locations</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addLocationModal">
            <i class="fas fa-plus"></i> Add Location
        </button>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <?php 
    $stats = array(
        'laboratory' => array('count' => 0, 'icon' => 'flask', 'color' => 'primary'),
        'warehouse' => array('count' => 0, 'icon' => 'warehouse', 'color' => 'success'),
        'field' => array('count' => 0, 'icon' => 'map', 'color' => 'info'),
        'other' => array('count' => 0, 'icon' => 'building', 'color' => 'warning')
    );
    
    foreach ($locations as $location) {
        if (isset($stats[$location['type']])) {
            $stats[$location['type']]['count']++;
        } else {
            $stats['other']['count']++;
        }
    }
    ?>
    
    <?php foreach ($stats as $type => $stat): ?>
    <div class="col-md-3 mb-3">
        <div class="card border-<?php echo $stat['color']; ?>">
            <div class="card-body text-center">
                <div class="text-<?php echo $stat['color']; ?>">
                    <i class="fas fa-<?php echo $stat['icon']; ?> fa-2x mb-2"></i>
                </div>
                <h5 class="card-title"><?php echo ucfirst($type); ?></h5>
                <h2 class="text-<?php echo $stat['color']; ?>"><?php echo $stat['count']; ?></h2>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Locations Table -->
<div class="card">
    <div class="card-header">
        <h5>Locations (<?php echo count($locations); ?> total)</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Contact</th>
                        <th>Usage</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($locations as $location): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($location['name']); ?></strong>
                            <?php if (!empty($location['address'])): ?>
                                <br><small class="text-muted">
                                    <i class="fas fa-map-marker-alt"></i> 
                                    <?php echo htmlspecialchars($location['address']); ?>
                                </small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                            $type_colors = array(
                                'laboratory' => 'primary',
                                'warehouse' => 'success', 
                                'field' => 'info',
                                'other' => 'warning'
                            );
                            $color = isset($type_colors[$location['type']]) ? $type_colors[$location['type']] : 'secondary';
                            ?>
                            <span class="badge bg-<?php echo $color; ?>">
                                <?php echo ucfirst($location['type']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if (!empty($location['contact_person'])): ?>
                                <strong><?php echo htmlspecialchars($location['contact_person']); ?></strong><br>
                                <?php if (!empty($location['contact_phone'])): ?>
                                    <small><i class="fas fa-phone"></i> <?php echo htmlspecialchars($location['contact_phone']); ?></small><br>
                                <?php endif; ?>
                                <?php if (!empty($location['contact_email'])): ?>
                                    <small><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($location['contact_email']); ?></small>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">No contact info</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($location['total_usage'] > 0): ?>
                                <span class="badge bg-info"><?php echo $location['inventory_count']; ?> inventory</span>
                                <span class="badge bg-warning"><?php echo $location['deployment_count']; ?> deployments</span>
                            <?php else: ?>
                                <span class="text-muted">Not in use</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo getStatusBadge($location['status']); ?></td>
                        <td>
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm btn-outline-info" 
                                        data-bs-toggle="modal" data-bs-target="#viewLocationModal"
                                        onclick="viewLocation(<?php echo htmlspecialchars(json_encode($location)); ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                        data-bs-toggle="modal" data-bs-target="#editLocationModal"
                                        onclick="editLocation(<?php echo htmlspecialchars(json_encode($location)); ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if (isAdmin() && $location['total_usage'] == 0): ?>
                                <a href="?delete=<?php echo $location['id']; ?>" 
                                   class="btn btn-sm btn-outline-danger delete-btn" 
                                   data-name="<?php echo htmlspecialchars($location['name']); ?>">
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

<!-- Add Location Modal -->
<div class="modal fade" id="addLocationModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Location</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">Location Name *</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="type" class="form-label">Type *</label>
                                <select class="form-select" id="type" name="type" required>
                                    <option value="">Select Type</option>
                                    <option value="laboratory">Laboratory</option>
                                    <option value="warehouse">Warehouse</option>
                                    <option value="field">Field Location</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="2"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="contact_person" class="form-label">Contact Person</label>
                                <input type="text" class="form-control" id="contact_person" name="contact_person">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="contact_phone" class="form-label">Phone</label>
                                <input type="text" class="form-control" id="contact_phone" name="contact_phone">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="contact_email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="contact_email" name="contact_email">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Location</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Location Modal -->
<div class="modal fade" id="editLocationModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Location</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="location_id" id="edit_location_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_name" class="form-label">Location Name *</label>
                                <input type="text" class="form-control" id="edit_name" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_type" class="form-label">Type *</label>
                                <select class="form-select" id="edit_type" name="type" required>
                                    <option value="laboratory">Laboratory</option>
                                    <option value="warehouse">Warehouse</option>
                                    <option value="field">Field Location</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_address" class="form-label">Address</label>
                        <textarea class="form-control" id="edit_address" name="address" rows="2"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="edit_contact_person" class="form-label">Contact Person</label>
                                <input type="text" class="form-control" id="edit_contact_person" name="contact_person">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="edit_contact_phone" class="form-label">Phone</label>
                                <input type="text" class="form-control" id="edit_contact_phone" name="contact_phone">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="edit_contact_email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="edit_contact_email" name="contact_email">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Location</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Location Modal -->
<div class="modal fade" id="viewLocationModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Location Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="locationDetails">
                <!-- Content will be populated by JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function editLocation(location) {
    document.getElementById('edit_location_id').value = location.id;
    document.getElementById('edit_name').value = location.name;
    document.getElementById('edit_type').value = location.type;
    document.getElementById('edit_address').value = location.address || '';
    document.getElementById('edit_contact_person').value = location.contact_person || '';
    document.getElementById('edit_contact_phone').value = location.contact_phone || '';
    document.getElementById('edit_contact_email').value = location.contact_email || '';
    document.getElementById('edit_description').value = location.description || '';
}

function viewLocation(location) {
    const details = document.getElementById('locationDetails');
    const type_colors = {
        'laboratory': 'primary',
        'warehouse': 'success', 
        'field': 'info',
        'other': 'warning'
    };
    const color = type_colors[location.type] || 'secondary';
    
    details.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <h6>Basic Information</h6>
                <p><strong>Name:</strong> ${location.name}</p>
                <p><strong>Type:</strong> <span class="badge bg-${color}">${location.type}</span></p>
                <p><strong>Status:</strong> ${getStatusBadgeHTML(location.status)}</p>
                ${location.description ? `<p><strong>Description:</strong> ${location.description}</p>` : ''}
            </div>
            <div class="col-md-6">
                <h6>Contact Information</h6>
                ${location.contact_person ? `<p><strong>Contact Person:</strong> ${location.contact_person}</p>` : ''}
                ${location.contact_phone ? `<p><strong>Phone:</strong> ${location.contact_phone}</p>` : ''}
                ${location.contact_email ? `<p><strong>Email:</strong> ${location.contact_email}</p>` : ''}
                ${location.address ? `<p><strong>Address:</strong><br>${location.address.replace(/\n/g, '<br>')}</p>` : ''}
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <h6>Usage Statistics</h6>
                <div class="row">
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h5 class="text-info">${location.inventory_count || 0}</h5>
                                <small>Inventory Items</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h5 class="text-warning">${location.deployment_count || 0}</h5>
                                <small>Deployments</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h5 class="text-success">${location.total_usage || 0}</h5>
                                <small>Total Usage</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-12">
                <small class="text-muted">Created: ${new Date(location.created_at).toLocaleDateString()}</small>
            </div>
        </div>
    `;
}

function getStatusBadgeHTML(status) {
    const statusColors = {
        'active': 'success',
        'inactive': 'secondary',
        'maintenance': 'warning'
    };
    return `<span class="badge bg-${statusColors[status] || 'secondary'}">${status.toUpperCase()}</span>`;
}
</script>

<?php include 'includes/footer.php'; ?>