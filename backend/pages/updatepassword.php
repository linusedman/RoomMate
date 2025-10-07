<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require '../database/db_connect.php'; // $conn

$key = $_POST['key'] ?? '';
$password = $_POST['password'] ?? '';

if (!$key || !$password || strlen($password) < 8) {
    echo json_encode(["status"=>"error","message"=>"Invalid input"]);
    exit;
}

// Lookup token
$stmt = $conn->prepare("SELECT user_email, expDate FROM password_reset_temp WHERE `key` = ? LIMIT 1");
$stmt->bind_param("s", $key);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();

if (!$row) {
    echo json_encode(["status"=>"error","message"=>"Invalid or used key"]);
    exit;
}

if (strtotime($row['expDate']) < time()) {
    echo json_encode(["status"=>"error","message"=>"Key expired"]);
    exit;
}

$email = $row['email'];
$hashed = password_hash($password, PASSWORD_DEFAULT);

$conn->begin_transaction();
try {
    // update password
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
    $stmt->bind_param("ss", $hashed, $email);
    $stmt->execute();
    $stmt->close();

    // delete token
    $stmt = $conn->prepare("DELETE FROM password_reset_temp WHERE `key` = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $stmt->close();

    $conn->commit();

    echo json_encode(["status"=>"success","message"=>"Password updated","redirect"=>"/login"]);
} catch (Exception $e) {
    $conn->rollback();
    error_log("updatepassword error: " . $e->getMessage());
    echo json_encode(["status"=>"error","message"=>"Server error"]);
}
