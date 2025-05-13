<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'license_system');

function getDbConnection() {
    $host = 'db'; 
    $username = 'jouw_database_gebruiker';
    $password = 'jouw_database_wachtwoord';
    $database = 'license_system';
    
    try {
        $conn = new mysqli($host, $username, $password, $database);
        
        if ($conn->connect_error) {
            error_log("Database connection failed: " . $conn->connect_error);
            die("Database connection failed. Please check the server logs.");
        }
        
        return $conn;
    } catch (Exception $e) {
        error_log("Database connection exception: " . $e->getMessage());
        die("Database connection failed. Please check the server logs.");
    }
}
?>