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
    $user_id = $_GET['delete'];
    
    $check_sql = "SELECT COUNT(*) as license_count FROM licenses WHERE user_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $row = $check_result->fetch_assoc();
    $check_stmt->close();
    
    if ($row['license_count'] > 0) {
        $error_message = 'Deze gebruiker kan niet worden verwijderd omdat er licenties aan gekoppeld zijn.';
    } else {
        $delete_sql = "DELETE FROM users WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $user_id);
        $delete_stmt->execute();
        $delete_stmt->close();
        
        header('Location: users.php');
        exit;
    }
}

if (isset($_GET['toggle_admin']) && is_numeric($_GET['toggle_admin'])) {
    $user_id = $_GET['toggle_admin'];
    
    $status_sql = "SELECT is_admin FROM users WHERE id = ?";
    $status_stmt = $conn->prepare($status_sql);
    $status_stmt->bind_param("i", $user_id);
    $status_stmt->execute();
    $status_result = $status_stmt->get_result();
    $user = $status_result->fetch_assoc();
    $status_stmt->close();
    
    $new_status = $user['is_admin'] ? 0 : 1;
    $update_sql = "UPDATE users SET is_admin = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ii", $new_status, $user_id);
    $update_stmt->execute();
    $update_stmt->close();
    
    header('Location: users.php');
    exit;
}

$sql = "SELECT u.*, COUNT(l.id) as license_count 
        FROM users u
        LEFT JOIN licenses l ON u.id = l.user_id
        GROUP BY u.id
        ORDER BY u.username";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beheer Gebruikers - Admin Dashboard</title>
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
        .toggle-admin {
            background-color: #FF9800;
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
    <h1>Beheer Gebruikers</h1>
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
    
    <a href="add_user.php" class="add-new">Nieuwe Gebruiker Toevoegen</a>
    
    <table>
        <tr>
            <th>ID</th>
            <th>Gebruikersnaam</th>
            <th>E-mail</th>
            <th>Aanmaakdatum</th>
            <th>Admin</th>
            <th>Aantal Licenties</th>
            <th>Acties</th>
        </tr>
        <?php
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $admin_status = $row['is_admin'] ? 'Ja' : 'Nee';
                $toggle_text = $row['is_admin'] ? 'Verwijder Admin' : 'Maak Admin';
                
                echo '<tr>';
                echo '<td>' . $row['id'] . '</td>';
                echo '<td>' . $row['username'] . '</td>';
                echo '<td>' . $row['email'] . '</td>';
                echo '<td>' . $row['created_at'] . '</td>';
                echo '<td>' . $admin_status . '</td>';
                echo '<td>' . $row['license_count'] . '</td>';
                echo '<td class="actions">';
                echo '<a href="edit_user.php?id=' . $row['id'] . '" class="edit">Bewerken</a>';
                echo '<a href="users.php?toggle_admin=' . $row['id'] . '" class="toggle-admin">' . $toggle_text . '</a>';
                echo '<a href="users.php?delete=' . $row['id'] . '" class="delete" onclick="return confirm(\'Weet je zeker dat je deze gebruiker wilt verwijderen?\')">Verwijderen</a>';
                echo '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="7">Geen gebruikers gevonden</td></tr>';
        }
        ?>
    </table>
</body>
</html>