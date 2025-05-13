<?php
require_once '../includes/auth_functions.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: licenses.php');
    exit;
}

$license_id = $_GET['id'];
$conn = getDbConnection();
$success = '';
$error = '';

if (isset($_GET['delete_activation']) && !empty($_GET['delete_activation'])) {
    $activation_id = $_GET['delete_activation'];
    
    $delete_sql = "DELETE FROM license_activations WHERE id = ? AND license_id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("ii", $activation_id, $license_id);
    
    if ($delete_stmt->execute()) {
        $success = "Activation record deleted successfully.";
    } else {
        $error = "Failed to delete activation record.";
    }
    
    $delete_stmt->close();
}

$sql = "SELECT l.*, u.username, u.email, p.name as product_name, p.version 
        FROM licenses l
        JOIN users u ON l.user_id = u.id
        JOIN products p ON l.product_id = p.id
        WHERE l.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $license_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: licenses.php');
    exit;
}

$license = $result->fetch_assoc();

$activation_sql = "SELECT * FROM license_activations WHERE license_id = ? ORDER BY activation_date DESC";
$activation_stmt = $conn->prepare($activation_sql);
$activation_stmt->bind_param("i", $license_id);
$activation_stmt->execute();
$activation_result = $activation_stmt->get_result();

$activations = [];
while ($row = $activation_result->fetch_assoc()) {
    $activations[] = $row;
}

$stmt->close();
$activation_stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View License - License Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <?php include '../includes/admin_navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                        <h2>License Details</h2>
                        <div>
                            <a href="edit_license.php?id=<?php echo $license['id']; ?>" class="btn btn-warning me-2">
                                <i class="bi bi-pencil"></i> Edit License
                            </a>
                            <a href="licenses.php" class="btn btn-light">Back to Licenses</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if (!empty($success)): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h4>License Information</h4>
                                <table class="table table-bordered">
                                    <tr>
                                        <th>License Key</th>
                                        <td>
                                            <div class="input-group">
                                                <input type="text" class="form-control" value="<?php echo $license['license_key']; ?>" id="licenseKey" readonly>
                                                <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('licenseKey')">
                                                    <i class="bi bi-clipboard"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Status</th>
                                        <td>
                                            <?php if ($license['is_active']): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Product</th>
                                        <td>
                                            <a href="view_product.php?id=<?php echo $license['product_id']; ?>">
                                                <?php echo $license['product_name']; ?> (<?php echo $license['version']; ?>)
                                            </a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>User</th>
                                        <td>
                                            <a href="view_user.php?id=<?php echo $license['user_id']; ?>">
                                                <?php echo $license['username']; ?> (<?php echo $license['email']; ?>)
                                            </a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Issued Date</th>
                                        <td><?php echo date('Y-m-d H:i:s', strtotime($license['issued_date'])); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Expiry Date</th>
                                        <td>
                                            <?php 
                                            if ($license['expiry_date']) {
                                                echo date('Y-m-d H:i:s', strtotime($license['expiry_date']));
                                                
                                                if (strtotime($license['expiry_date']) < time()) {
                                                    echo ' <span class="badge bg-danger">Expired</span>';
                                                } else {
                                                    $days_left = ceil((strtotime($license['expiry_date']) - time()) / (60 * 60 * 24));
                                                    echo ' <span class="badge bg-info">' . $days_left . ' days left</span>';
                                                }
                                            } else {
                                                echo 'Never';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Max Activations</th>
                                        <td>
                                            <?php 
                                            if ($license['max_activations']) {
                                                echo $license['max_activations'];
                                                echo ' <span class="badge bg-info">' . count($activations) . ' used</span>';
                                            } else {
                                                echo 'Unlimited';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Notes</th>
                                        <td>
                                            <?php 
                                            if (!empty($license['notes'])) {
                                                echo nl2br(htmlspecialchars($license['notes']));
                                            } else {
                                                echo '<em>No notes</em>';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h4>Activation Summary</h4>
                                <div class="card mb-4">
                                    <div class="card-body">
                                        <div class="row text-center">
                                            <div class="col-md-6 mb-3">
                                                <h5>Total Activations</h5>
                                                <h2><?php echo count($activations); ?></h2>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <h5>Remaining</h5>
                                                <h2>
                                                    <?php 
                                                    if ($license['max_activations']) {
                                                        echo max(0, $license['max_activations'] - count($activations));
                                                    } else {
                                                        echo '∞';
                                                    }
                                                    ?>
                                                </h2>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <h4>API Information</h4>
                                <div class="card">
                                    <div class="card-body">
                                        <p>Use the following endpoint to validate this license:</p>
                                        <div class="input-group mb-3">
                                            <input type="text" class="form-control" value="<?php echo getBaseUrl(); ?>/api/validate_license.php" id="apiEndpoint" readonly>
                                            <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('apiEndpoint')">
                                                <i class="bi bi-clipboard"></i>
                                            </button>
                                        </div>
                                        <p>Example request:</p>
                                        <pre class="bg-light p-2 rounded"><code>POST /api/validate_license.php
Content-Type: application/json

{
  "license_key": "<?php echo $license['license_key']; ?>",
  "product_id": <?php echo $license['product_id']; ?>,
  "device_name": "User's Device"
}</code></pre>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <h4>Activation History</h4>
                        <?php if (empty($activations)): ?>
                            <div class="alert alert-info">This license has not been activated yet.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Device Name</th>
                                            <th>IP Address</th>
                                            <th>Activation Date</th>
                                            <th>Last Check-in</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($activations as $activation): ?>
                                            <tr>
                                                <td><?php echo $activation['id']; ?></td>
                                                <td><?php echo $activation['device_name']; ?></td>
                                                <td><?php echo $activation['ip_address']; ?></td>
                                                <td><?php echo date('Y-m-d H:i:s', strtotime($activation['activation_date'])); ?></td>
                                                <td>
                                                    <?php 
                                                    if ($activation['last_check_date']) {
                                                        echo date('Y-m-d H:i:s', strtotime($activation['last_check_date']));
                                                    } else {
                                                        echo 'Never';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <a href="view_license.php?id=<?php echo $license_id; ?>&delete_activation=<?php echo $activation['id']; ?>" 
                                                       class="btn btn-sm btn-danger" 
                                                       onclick="return confirm('Are you sure you want to delete this activation record?')">
                                                        <i class="bi bi-trash"></i> Delete
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
    
    <script>
    function copyToClipboard(elementId) {
        var copyText = document.getElementById(elementId);
        copyText.select();
        copyText.setSelectionRange(0, 99999);
        document.execCommand("copy");
        
        alert("Copied: " + copyText.value);
    }
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
