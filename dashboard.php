<?php
require_once 'includes/auth_functions.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - License Management System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>Dashboard</h1>
        <p>Welkom, <?php echo getCurrentUsername(); ?>!</p>
        
        <h2>Jouw licenties</h2>
        <?php
        require_once 'includes/license_functions.php';
        $licenses = getUserLicenses(getCurrentUserId());
        
        if (empty($licenses)) {
            echo '<p>Je hebt nog geen licenties.</p>';
        } else {
            echo '<table>';
            echo '<tr><th>Product</th><th>Licentiesleutel</th><th>Uitgiftedatum</th><th>Vervaldatum</th><th>Status</th></tr>';
            
            foreach ($licenses as $license) {
                $status = $license['is_active'] ? 'Actief' : 'Inactief';
                $expiry = $license['expiry_date'] ? $license['expiry_date'] : 'Geen vervaldatum';
                
                echo '<tr>';
                echo '<td>' . $license['product_name'] . '</td>';
                echo '<td>' . $license['license_key'] . '</td>';
                echo '<td>' . $license['issued_date'] . '</td>';
                echo '<td>' . $expiry . '</td>';
                echo '<td>' . $status . '</td>';
                echo '</tr>';
            }
            
            echo '</table>';
        }
        ?>
        
        <p><a href="logout.php">Uitloggen</a></p>
    </div>
</body>
</html>