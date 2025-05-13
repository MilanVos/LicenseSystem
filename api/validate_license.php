<?php
require_once '../includes/license_functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['valid' => false, 'message' => 'Invalid request method']);
    exit;
}

$license_key = $_POST['license_key'] ?? '';
$machine_id = $_POST['machine_id'] ?? '';

if (empty($license_key)) {
    echo json_encode(['valid' => false, 'message' => 'License key is required']);
    exit;
}

if (empty($machine_id)) {
    echo json_encode(['valid' => false, 'message' => 'Machine ID is required']);
    exit;
}

$result = validateLicense($license_key, $machine_id);

echo json_encode($result);
?>