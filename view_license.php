<?php
require_once 'includes/auth_functions.php';
require_once 'includes/license_functions.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: dashboard.php');
    exit;
}

$license_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

$conn = getDbConnection();
$sql = "SELECT l.*, p.name as product_name, u.username 
        FROM licenses l
        JOIN products p ON l.product_id = p.id
        JOIN users u ON l.user_id = u.id
        WHERE l.id = ? AND (l.user_id = ? OR ? = 1)";

$stmt = $conn->prepare($sql);
$is_admin = isAdmin() ? 1 : 0;
$stmt->bind_param("iii", $license_id, $user_id, $is_admin);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: dashboard.php');
    exit;
}

$license = $result->fetch_assoc();

$sql = "SELECT * FROM license_activations WHERE license_id = ? ORDER BY activation_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $license_id);
$stmt->execute();
$activations_result = $stmt->get_result();

$activations = [];
while ($row = $activations_result->fetch_assoc()) {
    $activations[] = $row;
}

$stmt->close();
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
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h2>License Details</h2>
                        <a href="dashboard.php" class="btn btn-light">Back to Dashboard</a>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h4>License Information</h4>
                                <table class="table table-bordered">
                                    <tr>
                                        <th>License Key</th>
                                        <td><?php echo $license['license_key']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Product</th>
                                        <td><?php echo $license['product_name']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Issued Date</th>
                                        <td><?php echo date('Y-m-d', strtotime($license['issued_date'])); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Expiry Date</th>
                                        <td>
                                            <?php 
                                            if ($license['expiry_date']) {
                                                echo date('Y-m-d', strtotime($license['expiry_date']));
                                            } else {
                                                echo 'Never';
                                            }
                                            ?>
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
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h4>Usage Instructions</h4>
                                <div class="alert alert-info">
                                    <p>To use this license in your application, include the following code:</p>
                                    <pre><code>// PHP Example
$license_key = "<?php echo $license['license_key']; ?>";
$machine_id = "YOUR_MACHINE_ID";
$validation_url = "<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]"; ?>/api/validate_license.php";

$ch = curl_init($validation_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'license_key' => $license_key,
    'machine_id' => $machine_id
]);
$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);
if ($result['valid']) {
} else {
    echo $result['message'];
}</code></pre>
                                </div>
                            </div>
                        </div>
                        
                        <h4>Activation History</h4>
                        <?php if (empty($activations)): ?>
                            <div class="alert alert-info">
                                This license has not been activated yet.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Machine ID</th>
                                            <th>Activation Date</th>
                                            <th>Last Check Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($activations as $activation): ?>
                                            <tr>
                                                <td><?php echo $activation['machine_id']; ?></td>
                                                <td><?php echo date('Y-m-d H:i:s', strtotime($activation['activation_date'])); ?></td>
                                                <td>
                                                    <?php 
                                                    if ($activation['last_check_date']) {
                                                        echo date('Y-m-d H:i:s', strtotime($activation['last_check_date']));
                                                    } else {
                                                        echo 'N/A';
                                                    }
                                                    ?>
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