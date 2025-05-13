<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/license_functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
function registerUser($username, $email, $password) {
    $conn = getDbConnection();
    
    if ($conn->connect_error) {
        return ['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error];
    }
    
    $check_sql = "SELECT * FROM users WHERE username = ? OR email = ?";
    $check_stmt = $conn->prepare($check_sql);
    
    if ($check_stmt === false) {
        return ['success' => false, 'message' => 'Prepare statement failed: ' . $conn->error];
    }
    
    $check_stmt->bind_param("ss", $username, $email);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        $check_stmt->close();
        $conn->close();
        return ['success' => false, 'message' => 'Username or email already exists'];
    }
    
    $check_stmt->close();
    
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $insert_sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_sql);
    
    if ($insert_stmt === false) {
        return ['success' => false, 'message' => 'Prepare statement failed: ' . $conn->error];
    }
    
    $insert_stmt->bind_param("sss", $username, $email, $hashed_password);
    
    if ($insert_stmt->execute()) {
        $user_id = $insert_stmt->insert_id;
        $insert_stmt->close();
        $conn->close();
        return ['success' => true, 'user_id' => $user_id];
    } else {
        $error = $insert_stmt->error;
        $insert_stmt->close();
        $conn->close();
        return ['success' => false, 'message' => 'Registration failed: ' . $error];
    }
}

/**
 * Login a user
 */
function loginUser($username, $password) {
    $conn = getDbConnection();
    
    if ($conn->connect_error) {
        return ['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error];
    }
    
    $sql = "SELECT id, username, password, is_admin FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        return ['success' => false, 'message' => 'Prepare statement failed: ' . $conn->error . ' SQL: ' . $sql];
    }
    
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_admin'] = $user['is_admin'];
            $_SESSION['logged_in'] = true;
            
            $stmt->close();
            $conn->close();
            
            return ['success' => true, 'is_admin' => $user['is_admin']];
        } else {
            $stmt->close();
            $conn->close();
            return ['success' => false, 'message' => 'Invalid password'];
        }
    } else {
        $stmt->close();
        $conn->close();
        return ['success' => false, 'message' => 'User not found'];
    }
}


function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Check if user is admin
 */
function isAdmin() {

    return isLoggedIn() && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}

/**
 * Logout user
 */
function logoutUser() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $_SESSION = [];
    
    session_destroy();
}

function getCurrentUserId() {
    return isLoggedIn() ? $_SESSION['user_id'] : null;
}


function getCurrentUsername() {
    return isLoggedIn() ? $_SESSION['username'] : null;
}


function generateLicenseKey() {
    $length = 20;
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $license_key = '';
    
    for ($i = 0; $i < $length; $i++) {
        $license_key .= $characters[rand(0, strlen($characters) - 1)];
    }
    
    $formatted_key = '';
    for ($i = 0; $i < $length; $i++) {
        $formatted_key .= $license_key[$i];
        if (($i + 1) % 5 === 0 && $i < $length - 1) {
            $formatted_key .= '-';
        }
    }
    
    return $formatted_key;
}

/**
 * Get base URL of the site
 */
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $script = dirname($_SERVER['SCRIPT_NAME']);
    
    $base_path = preg_replace('/\/admin$|\/api$/', '', $script);
    
    return $protocol . '://' . $host . $base_path;
}
?>
