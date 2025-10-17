<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require '../database/db_connect.php'; 

$key = $_GET['key'] ?? '';
if (!$key) {
    echo json_encode(["status"=>"error","message"=>"Missing key"]);
    exit;
}

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

// valid (we don't return the email to avoid leaking user data, just show expiry)
echo json_encode([
    "status" => "success",
    "message" => "Key valid",
    "expires_at" => $row['expDate']
]);
