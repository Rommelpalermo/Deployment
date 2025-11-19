<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

requireLogin();

echo "<h2>Quick Inventory Add Test</h2>";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    echo "<h3>Form Submitted!</h3>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    // Simple validation
    $item_code = sanitize($_POST['item_code']);
    $name = sanitize($_POST['name']);
    
    if (!empty($item_code) && !empty($name)) {
        try {
            $data = [
                'item_code' => $item_code,
                'name' => $name,
                'condition_status' => 'good',
                'status' => 'available',
                'created_by' => getCurrentUserId()
            ];
            
            $itemId = $db->insert('inventory', $data);
            echo "<div style='color: green; padding: 10px; border: 1px solid green; margin: 10px 0;'>";
            echo "<strong>SUCCESS!</strong><br>";
            echo "Item added with ID: {$itemId}<br>";
            echo "Item Code: {$item_code}<br>";
            echo "Name: {$name}";
            echo "</div>";
            
        } catch (Exception $e) {
            echo "<div style='color: red; padding: 10px; border: 1px solid red; margin: 10px 0;'>";
            echo "<strong>ERROR:</strong> " . $e->getMessage();
            echo "</div>";
        }
    } else {
        echo "<div style='color: orange; padding: 10px; border: 1px solid orange; margin: 10px 0;'>";
        echo "<strong>VALIDATION ERROR:</strong> Item code and name are required";
        echo "</div>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Quick Add Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Quick Add Inventory Item</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Item Code *</label>
                            <input type="text" name="item_code" class="form-control" required value="QUICK-<?php echo time(); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Item Name *</label>
                            <input type="text" name="name" class="form-control" required placeholder="Enter item name">
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Add Item</button>
                        <a href="inventory.php" class="btn btn-secondary">View Inventory</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>