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

$conn = getDbConnection();

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $product_id = $_GET['delete'];
    
    $check_sql = "SELECT COUNT(*) as license_count FROM licenses WHERE product_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $product_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $row = $check_result->fetch_assoc();
    $check_stmt->close();
    
    if ($row['license_count'] > 0) {
        $error_message = 'Dit product kan niet worden verwijderd omdat er licenties aan gekoppeld zijn.';
    } else {
        $delete_sql = "DELETE FROM products WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $product_id);
        $delete_stmt->execute();
        $delete_stmt->close();
        
        header('Location: products.php');
        exit;
    }
}

$sql = "SELECT p.*, COUNT(l.id) as license_count 
        FROM products p
        LEFT JOIN licenses l ON p.id = l.product_id
        GROUP BY p.id
        ORDER BY p.name";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beheer Producten - Admin Dashboard</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .actions {
            display: flex;
            gap: 10px;
        }
        .actions a {
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 3px;
            color: white;
        }
        .edit {
            background-color: #4CAF50;
        }
        .delete {
            background-color: #f44336;
        }
        .add-new {
            display: inline-block;
            margin: 20px 0;
            padding: 10px 15px;
            background-color: #2196F3;
            color: white;
            text-decoration: none;
            border-radius: 3px;
        }
        .error-message {
            color: red;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <h1>Beheer Producten</h1>
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
    
    <a href="add_product.php" class="add-new">Nieuw Product Toevoegen</a>
    
    <table>
        <tr>
            <th>ID</th>
            <th>Naam</th>
            <th>Beschrijving</th>
            <th>Aanmaakdatum</th>
            <th>Aantal Licenties</th>
            <th>Acties</th>
        </tr>
        <?php
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo '<tr>';
                echo '<td>' . $row['id'] . '</td>';
                echo '<td>' . $row['name'] . '</td>';
                echo '<td>' . $row['description'] . '</td>';
                echo '<td>' . $row['created_at'] . '</td>';
                echo '<td>' . $row['license_count'] . '</td>';
                echo '<td class="actions">';
                echo '<a href="edit_product.php?id=' . $row['id'] . '" class="edit">Bewerken</a>';
                echo '<a href="products.php?delete=' . $row['id'] . '" class="delete" onclick="return confirm(\'Weet je zeker dat je dit product wilt verwijderen?\')">Verwijderen</a>';
                echo '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="6">Geen producten gevonden</td></tr>';
        }
        ?>
    </table>
</body>
</html>