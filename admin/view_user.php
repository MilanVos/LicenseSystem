<?php
require_once '../includes/auth_functions.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: users.php');
    exit;
}

$user_id = $_GET['id'];
$conn = getDbConnection();

$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: users.php');
    exit;
}

$user = $result->fetch_assoc();

$license_sql = "SELECT l.*, p.name as product_name 
                FROM licenses l
                JOIN products p ON l.product_id = p.id
                WHERE l.user_id = ?
                ORDER BY l.issued_date DESC";
$license_stmt = $conn->prepare($license_sql);
$license_stmt->bind_param("i", $user_id);
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
    <title>View User - License Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <?php include '../includes/admin_navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h2>User Details</h2>
                        <div>
                            <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-warning me-2">
                                <i class="bi bi-pencil"></i> Edit User
                            </a>
                            <a href="users.php" class="btn btn-light">Back to Users</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h4>User Information</h4>
                                <table class="table table-bordered">
                                    <tr>
                                        <th>ID</th>
                                        <td><?php echo $user['id']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Username</th>
                                        <td><?php echo $user['username']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Email</th>
                                        <td><?php echo $user['email']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Role</th>
                                        <td>
                                            <?php if ($user['is_admin']): ?>
                                                <span class="badge bg-danger">Admin</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">User</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Created</th>
                                        <td><?php echo date('Y-m-d H:i:s', strtotime($user['created_at'])); ?></td>
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
                                            <a href="add_license.php?user_id=<?php echo $user['id']; ?>" class="btn btn-info text-white">
                                                <i class="bi bi-plus-circle"></i> Add License for this User
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <h4>User's Licenses</h4>
                        <?php if (empty($licenses)): ?>
                            <div class="alert alert-info">This user has no licenses.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>License Key</th>
                                            <th>Product</th>
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
                                                <td><?php echo $license['product_name']; ?></td>
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
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>