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

$users_count = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$products_count = $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'];
$licenses_count = $conn->query("SELECT COUNT(*) as count FROM licenses")->fetch_assoc()['count'];
$activations_count = $conn->query("SELECT COUNT(*) as count FROM license_activations")->fetch_assoc()['count'];

$recent_licenses_sql = "SELECT l.*, p.name as product_name, u.username 
                        FROM licenses l
                        JOIN products p ON l.product_id = p.id
                        JOIN users u ON l.user_id = u.id
                        ORDER BY l.issued_date DESC
                        LIMIT 5";
$recent_licenses = $conn->query($recent_licenses_sql);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - License Management System</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <h1>Admin Dashboard</h1>
        <nav>
            <ul>
                <li><a href="index.php">Dashboard</a></li>
                <li><a href="users.php">Gebruikers</a></li>
                <li><a href="products.php">Producten</a></li>
                <li><a href="licenses.php">Licenties</a></li>
                <li><a href="../logout.php">Uitloggen</a></li>
            </ul>
        </nav>
        
        <h2>Welkom, <?php echo getCurrentUsername(); ?>!</h2>
        
        <div style="display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 30px;">
            <div style="flex: 1; min-width: 200px; background-color: #e3f2fd; padding: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                <h3>Gebruikers</h3>
                <p style="font-size: 24px; font-weight: bold;"><?php echo $users_count; ?></p>
                <a href="users.php" class="button" style="margin-top: 10px; background-color: #2196F3;">Beheren</a>
            </div>
            
            <div style="flex: 1; min-width: 200px; background-color: #e8f5e9; padding: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                <h3>Producten</h3>
                <p style="font-size: 24px; font-weight: bold;"><?php echo $products_count; ?></p>
                <a href="products.php" class="button" style="margin-top: 10px; background-color: #4CAF50;">Beheren</a>
            </div>
            
            <div style="flex: 1; min-width: 200px; background-color: #fff3e0; padding: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                <h3>Licenties</h3>
                <p style="font-size: 24px; font-weight: bold;"><?php echo $licenses_count; ?></p>
                <a href="licenses.php" class="button" style="margin-top: 10px; background-color: #FF9800;">Beheren</a>
            </div>
            
            <div style="flex: 1; min-width: 200px; background-color: #f3e5f5; padding: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                <h3>Activaties</h3>
                <p style="font-size: 24px; font-weight: bold;"><?php echo $activations_count; ?></p>
                <a href="#" class="button" style="margin-top: 10px; background-color: #9C27B0;">Bekijken</a>
            </div>
        </div>
        
        <h2>Recente Licenties</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>Licentiesleutel</th>
                <th>Product</th>
                <th>Gebruiker</th>
                <th>Uitgiftedatum</th>
                <th>Status</th>
                <th>Acties</th>
            </tr>
            <?php
            if ($recent_licenses->num_rows > 0) {
                while ($license = $recent_licenses->fetch_assoc()) {
                    $status = $license['is_active'] ? 'Actief' : 'Inactief';
                    
                    echo '<tr>';
                    echo '<td>' . $license['id'] . '</td>';
                    echo '<td>' . $license['license_key'] . '</td>';
                    echo '<td>' . $license['product_name'] . '</td>';
                    echo '<td>' . $license['username'] . '</td>';
                    echo '<td>' . $license['issued_date'] . '</td>';
                    echo '<td>' . $status . '</td>';
                    echo '<td class="actions">';
                    echo '<a href="edit_license.php?id=' . $license['id'] . '" class="edit">Bewerken</a>';
                    echo '</td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="7">Geen licenties gevonden</td></tr>';
            }
            ?>
        </table>
        
        <div style="margin-top: 20px;">
            <a href="licenses.php" class="button" style="background-color: #FF9800;">Alle Licenties Bekijken</a>
            <a href="add_license.php" class="button" style="background-color: #4CAF50;">Nieuwe Licentie Toevoegen</a>
        </div>
    </div>
</body>
</html>