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

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $license_id = $_GET['delete'];
    $delete_sql = "DELETE FROM licenses WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $license_id);
    $delete_stmt->execute();
    $delete_stmt->close();
    
    header('Location: licenses.php');
    exit;
}

$sql = "SELECT l.*, p.name as product_name, u.username 
        FROM licenses l
        JOIN products p ON l.product_id = p.id
        JOIN users u ON l.user_id = u.id
        ORDER BY l.issued_date DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beheer Licenties - Admin Dashboard</title>
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
    </style>
</head>
<body>
    <h1>Beheer Licenties</h1>
    <nav>
        <ul>
            <li><a href="index.php">Dashboard</a></li>
            <li><a href="users.php">Gebruikers</a></li>
            <li><a href="products.php">Producten</a></li>
            <li><a href="licenses.php">Licenties</a></li>
            <li><a href="../logout.php">Uitloggen</a></li>
        </ul>
    </nav>
    
    <a href="add_license.php" class="add-new">Nieuwe Licentie Toevoegen</a>
    
    <table>
        <tr>
            <th>ID</th>
            <th>Licentiesleutel</th>
            <th>Product</th>
            <th>Gebruiker</th>
            <th>Uitgiftedatum</th>
            <th>Vervaldatum</th>
            <th>Status</th>
            <th>Acties</th>
        </tr>
        <?php
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $status = $row['is_active'] ? 'Actief' : 'Inactief';
                $expiry = $row['expiry_date'] ? $row['expiry_date'] : 'Geen vervaldatum';
                
                echo '<tr>';
                echo '<td>' . $row['id'] . '</td>';
                echo '<td>' . $row['license_key'] . '</td>';
                echo '<td>' . $row['product_name'] . '</td>';
                echo '<td>' . $row['username'] . '</td>';
                echo '<td>' . $row['issued_date'] . '</td>';
                echo '<td>' . $expiry . '</td>';
                echo '<td>' . $status . '</td>';
                echo '<td class="actions">';
                echo '<a href="edit_license.php?id=' . $row['id'] . '" class="edit">Bewerken</a>';
                echo '<a href="licenses.php?delete=' . $row['id'] . '" class="delete" onclick="return confirm(\'Weet je zeker dat je deze licentie wilt verwijderen?\')">Verwijderen</a>';
                echo '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="8">Geen licenties gevonden</td></tr>';
        }
        ?>
    </table>
</body>
</html>