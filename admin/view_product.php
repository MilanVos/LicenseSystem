<?php
require_once '../includes/auth_functions.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: products.php');
    exit;
}

$product_id = $_GET['id'];
$conn = getDbConnection();

$sql = "SELECT * FROM products WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: products.php');
    exit;
}

$product = $result->fetch_assoc();

$license_sql = "SELECT l.*, u.username, u.email 
                FROM licenses l
                JOIN users u ON l.user_id = u.id
                WHERE l.product_id = ?
                ORDER BY l.issued_date DESC";
$license_stmt = $conn->prepare($license_sql);
$license_stmt->bind_param("i", $product_id);
$license_stmt->execute();
$license_result = $license_stmt->get_result();

$licenses = [];
while ($row = $license_result->fetch_assoc()) {
    $licenses[] = $row;
}

$stmt->close();
$license_stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Product - License Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <?php include '../includes/admin_navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                        <h2>Product Details</h2>
                        <div>
                            <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn btn-warning me-2">
                                <i class="bi bi-pencil"></i> Edit Product
                            </a>
                            <a href="products.php" class="btn btn-light">Back to Products</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h4>Product Information</h4>
                                <table class="table table-bordered">
                                    <tr>
                                        <th>ID</th>
                                        <td><?php echo $product['id']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Name</th>
                                        <td><?php echo $product['name']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Version</th>
                                        <td><?php echo $product['version']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Created</th>
                                        <td><?php echo date('Y-m-d H:i:s', strtotime($product['created_at'])); ?></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h4>License Summary</h4>
                                <div class="card">
                                    <div class="card-body">
                                        <div class="row text-center">
                                            <div class="col-md-6 mb-3">
                                                <h5>Total Licenses</h5>
                                                <h2><?php echo count($licenses); ?></h2>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <h5>Active Licenses</h5>
                                                <h2>
                                                    <?php 
                                                    $active_count = 0;
                                                    foreach ($licenses as $license) {
                                                        if ($license['is_active']) {
                                                            $active_count++;
                                                        }
                                                    }
                                                    echo $active_count;
                                                    ?>
                                                </h2>
                                            </div>
                                        </div>
                                        <div class="d-grid gap-2 mt-3">
                                            <a href="add_license.php?product_id=<?php echo $product['id']; ?>" class="btn btn-info text-white">
                                                <i class="bi bi-plus-circle"></i> Add License for this Product
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <h4>Description</h4>
                            <div class="card">
                                <div class="card-body">
                                    <?php if (empty($product['description'])): ?>
                                        <em>No description provided.</em>
                                    <?php else: ?>
                                        <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <h4>Product Licenses</h4>
                        <?php if (empty($licenses)): ?>
                            <div class="alert alert-info">
                                No licenses have been created for this product yet.
                                <a href="add_license.php?product_id=<?php echo $product['id']; ?>" class="btn btn-sm btn-primary ms-2">
                                    <i class="bi bi-plus-circle"></i> Create License
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>License Key</th>
                                            <th>User</th>
                                            <th>Issued Date</th>
                                            <th>Expiry Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($licenses as $license): ?>
                                            <tr>
                                                <td><?php echo $license['license_key']; ?></td>
                                                <td><?php echo $license['username']; ?></td>
                                                <td><?php echo date('Y-m-d', strtotime($license['issued_date'])); ?></td>
                                                <td>
                                                    <?php 
                                                    if ($license['expiry_date']) {
                                                        echo date('Y-m-d', strtotime($license['expiry_date']));
                                                    } else {
                                                        echo 'Never';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php if ($license['is_active']): ?>
                                                        <span class="badge bg-success">Active</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Inactive</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="view_license.php?id=<?php echo $license['id']; ?>" class="btn btn-sm btn-info">
                                                        <i class="bi bi-eye"></i> View
                                                    </a>
                                                    <a href="edit_license.php?id=<?php echo $license['id']; ?>" class="btn btn-sm btn-warning">
                                                        <i class="bi bi-pencil"></i> Edit
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-3">
                                <a href="add_license.php?product_id=<?php echo $product['id']; ?>" class="btn btn-primary">
                                    <i class="bi bi-plus-circle"></i> Create New License
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>