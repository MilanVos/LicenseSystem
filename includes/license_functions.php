<?php

// Generate a unique license key
// function generateLicenseKey($length = 24) {
//     $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
//     $license_key = '';
    
//     for ($i = 0; $i < $length; $i++) {
//         $license_key .= $characters[rand(0, strlen($characters) - 1)];
//     }
    
//     // Format as XXXX-XXXX-XXXX-XXXX-XXXX-XXXX
//     return implode('-', str_split($license_key, 4));
// }

// Create a new license
function createLicense($product_id, $user_id, $expiry_date = null) {
    $conn = getDbConnection();
    
    $license_key = generateLicenseKey();
    
    $sql = "INSERT INTO licenses (license_key, product_id, user_id, expiry_date) 
            VALUES (?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("siis", $license_key, $product_id, $user_id, $expiry_date);
    
    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        return $license_key;
    } else {
        $stmt->close();
        $conn->close();
        return false;
    }
}

function validateLicense($license_key, $machine_id = null) {
    $conn = getDbConnection();
    
    $sql = "SELECT l.*, p.name as product_name 
            FROM licenses l
            JOIN products p ON l.product_id = p.id
            WHERE l.license_key = ? AND l.is_active = 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $license_key);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $license = $result->fetch_assoc();
        
        if ($license['expiry_date'] && strtotime($license['expiry_date']) < time()) {
            $stmt->close();
            $conn->close();
            return ['valid' => false, 'message' => 'License has expired'];
        }
        
        if ($machine_id) {
            recordActivation($license['id'], $machine_id);
        }
        
        $stmt->close();
        $conn->close();
        return ['valid' => true, 'license' => $license];
    } else {
        $stmt->close();
        $conn->close();
        return ['valid' => false, 'message' => 'Invalid license key'];
    }
}

function recordActivation($license_id, $machine_id) {
    $conn = getDbConnection();
    
    $sql = "SELECT * FROM license_activations 
            WHERE license_id = ? AND machine_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $license_id, $machine_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $sql = "UPDATE license_activations 
                SET last_check_date = CURRENT_TIMESTAMP 
                WHERE license_id = ? AND machine_id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $license_id, $machine_id);
        $stmt->execute();
    } else {
        $sql = "INSERT INTO license_activations (license_id, machine_id) 
                VALUES (?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $license_id, $machine_id);
        $stmt->execute();
    }
    
    $stmt->close();
    $conn->close();
}

function getUserLicenses($user_id) {
    $conn = getDbConnection();
    
    $sql = "SELECT l.*, p.name as product_name 
            FROM licenses l
            JOIN products p ON l.product_id = p.id
            WHERE l.user_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $licenses = [];
    while ($row = $result->fetch_assoc()) {
        $licenses[] = $row;
    }
    
    $stmt->close();
    $conn->close();
    return $licenses;
}
?>