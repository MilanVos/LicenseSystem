<?php
require_once '../includes/auth_functions.php';
require_once '../includes/license_functions.php';

if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

if (!isAdmin()) {
    header('Location: ../index.php');
    exit;
}

$conn = getDbConnection();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: licenses.php');
    exit;
}

$license_id = $_GET['id'];

$sql = "SELECT * FROM licenses WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $license_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    header('Location: licenses.php');
    exit;
}

$license = $result->fetch_assoc();
$stmt->close();

$products_sql = "SELECT id, name FROM products ORDER BY name";
$products_result = $conn->query($products_sql);

$users_sql = "SELECT id, username FROM users ORDER BY username";
$users_result = $conn->query($users_sql);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['product_id'] ?? '';
    $user_id = $_POST['user_id'] ?? '';
    $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($product_id) || empty($user_id)) {
        $error_message = 'Vul alstublieft alle verplichte velden in.';
    } else {
        $update_sql = "UPDATE licenses SET product_id = ?, user_id = ?, expiry_date = ?, is_active = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("iisii", $product_id, $user_id, $expiry_date, $is_active, $license_id);
        
        if ($update_stmt->execute()) {
            header('Location: licenses.php');
            exit;
        } else {
            $error_message = 'Er is een fout opgetreden bij het bijwerken van de licentie: ' . $update_stmt->error;
        }
        
        $update_stmt->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Licentie Bewerken - Admin Dashboard</title>
    <style>
        form {
            max-width: 500px;
            margin: 20px 0;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        select, input[type="date"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 3px;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
        }
        .checkbox-group label {
            margin-left: 10px;
            font-weight: normal;
        }
        button {
            padding: 10px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
        .error-message {
            color: red;
            margin-bottom: 15px;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #2196F3;
            text-decoration: none;
        }
        .license-key {
            font-family: monospace;
            padding: 10px;
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 3px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <h1>Licentie Bewerken</h1>
    <nav>
        <ul>
            <li><a href="index.php">Dashboard</a></li>
            <li><a href="users.php">Gebruikers</a></li>
            <li><a href="products.php">Producten</a></li>
            <li><a href="licenses.php">Licenties</a></li>
            <li><a href="../logout.php">Uitloggen</a></li>
        </ul>
    </nav>
    
    <?php if (isset($error_message)): ?>
        <div class="error-message"><?php echo $error_message; ?></div>
    <?php endif; ?>
    
    <div class="license-key">
        <strong>Licentiesleutel:</strong> <?php echo htmlspecialchars($license['license_key']); ?>
    </div>
    
    <form method="POST" action="">
        <div class="form-group">
            <label for="product_id">Product:</label>
            <select id="product_id" name="product_id" required>
                <option value="">Selecteer een product</option>
                <?php
                if ($products_result->num_rows > 0) {
                    while ($product = $products_result->fetch_assoc()) {
                        $selected = ($product['id'] == $license['product_id']) ? 'selected' : '';
                        echo '<option value="' . $product['id'] . '" ' . $selected . '>' . $product['name'] . '</option>';
                    }
                }
                ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="user_id">Gebruiker:</label>
            <select id="user_id" name="user_id" required>
                <option value="">Selecteer een gebruiker</option>
                <?php
                if ($users_result->num_rows > 0) {
                    while ($user = $users_result->fetch_assoc()) {
                        $selected = ($user['id'] == $license['user_id']) ? 'selected' : '';
                        echo '<option value="' . $user['id'] . '" ' . $selected . '>' . $user['username'] . '</option>';
                    }
                }
                ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="expiry_date">Vervaldatum (optioneel):</label>
            <input type="date" id="expiry_date" name="expiry_date" value="<?php echo $license['expiry_date'] ? date('Y-m-d', strtotime($license['expiry_date'])) : ''; ?>">
        </div>
        
        <div class="form-group checkbox-group">
            <input type="checkbox" id="is_active" name="is_active" <?php echo $license['is_active'] ? 'checked' : ''; ?>>
            <label for="is_active">Actief</label>
        </div>
        
        <button type="submit">Licentie Bijwerken</button>
    </form>
    
    <a href="licenses.php" class="back-link">Terug naar Licenties</a>
</body>
</html>