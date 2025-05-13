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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['product_id'] ?? '';
    $user_id = $_POST['user_id'] ?? '';
    $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
    
    if (empty($product_id) || empty($user_id)) {
        $error_message = 'Vul alstublieft alle verplichte velden in.';
    } else {
        $result = createLicense($product_id, $user_id, $expiry_date);
        
        if ($result) {
            header('Location: licenses.php');
            exit;
        } else {
            $error_message = 'Er is een fout opgetreden bij het aanmaken van de licentie.';
        }
    }
}

$products_sql = "SELECT id, name FROM products ORDER BY name";
$products_result = $conn->query($products_sql);

$users_sql = "SELECT id, username FROM users ORDER BY username";
$users_result = $conn->query($users_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nieuwe Licentie Toevoegen - Admin Dashboard</title>
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
    </style>
</head>
<body>
    <h1>Nieuwe Licentie Toevoegen</h1>
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
    
    <form method="POST" action="">
        <div class="form-group">
            <label for="product_id">Product:</label>
            <select id="product_id" name="product_id" required>
                <option value="">Selecteer een product</option>
                <?php
                if ($products_result->num_rows > 0) {
                    while ($product = $products_result->fetch_assoc()) {
                        echo '<option value="' . $product['id'] . '">' . $product['name'] . '</option>';
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
                        echo '<option value="' . $user['id'] . '">' . $user['username'] . '</option>';
                    }
                }
                ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="expiry_date">Vervaldatum (optioneel):</label>
            <input type="date" id="expiry_date" name="expiry_date">
        </div>
        
        <button type="submit">Licentie Aanmaken</button>
    </form>
    
    <a href="licenses.php" class="back-link">Terug naar Licenties</a>
</body>
</html>