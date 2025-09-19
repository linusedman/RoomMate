<?php
// CORS
$allowedOrigins = ['http://localhost:5173'];
if (
    isset($_SERVER['HTTP_ORIGIN']) &&
    in_array($_SERVER['HTTP_ORIGIN'], $allowedOrigins)
) {
    header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Allow-Methods: POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");
}
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

// JSON response
header("Content-Type: application/json");
include '../database/db_connect.php';

$email       = $_POST['email']       ?? '';
$newPassword = $_POST['new_password'] ?? '';

if (!$email || !$newPassword) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Email and new password are required.'
    ]);
    exit;
}

$hashed = password_hash($newPassword, PASSWORD_DEFAULT);

$stmt = $conn->prepare("
    UPDATE users 
       SET password = ? 
     WHERE email = ?
");
$stmt->bind_param("ss", $hashed, $email);

if ($stmt->execute()) {
    echo json_encode([
        'status'  => 'success',
        'message' => 'Password has been reset. You can now log in.'
    ]);
} else {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Could not reset password. Please try again later.'
    ]);
}

$stmt->close();
$conn->close();
