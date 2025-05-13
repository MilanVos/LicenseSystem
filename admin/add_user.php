<?php
require_once '../includes/auth_functions.php';

if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

if (!isAdmin()) {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;
    
    if (empty($username) || empty($email) || empty($password)) {
        $error_message = 'Vul alstublieft alle verplichte velden in.';
    } elseif ($password !== $confirm_password) {
        $error_message = 'Wachtwoorden komen niet overeen.';
    } else {
        $result = registerUser($username, $email, $password);
        
        if ($result['success']) {
            if ($is_admin) {
                $conn = getDbConnection();
                $update_sql = "UPDATE users SET is_admin = 1 WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("i", $result['user_id']);
                $update_stmt->execute();
                $update_stmt->close();
                $conn->close();
            }
            
            header('Location: users.php');
            exit;
        } else {
            $error_message = $result['message'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nieuwe Gebruiker Toevoegen - Admin Dashboard</title>
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
        input[type="text"], input[type="email"], input[type="password"] {
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
    </style>
</head>
<body>
    <h1>Nieuwe Gebruiker Toevoegen</h1>
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
            <label for="username">Gebruikersnaam:</label>
            <input type="text" id="username" name="username" required>
        </div>
        
        <div class="form-group">
            <label for="email">E-mail:</label>
            <input type="email" id="email" name="email" required>
        </div>
        
        <div class="form-group">
            <label for="password">Wachtwoord:</label>
            <input type="password" id="password" name="password" required>
        </div>
        
        <div class="form-group">
            <label for="confirm_password">Bevestig Wachtwoord:</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
        </div>
        
        <div class="form-group checkbox-group">
            <input type="checkbox" id="is_admin" name="is_admin">
            <label for="is_admin">Admin rechten toekennen</label>
        </div>
        
        <button type="submit">Gebruiker Toevoegen</button>
    </form>
    
    <a href="users.php" class="back-link">Terug naar Gebruikers</a>
</body>
</html>