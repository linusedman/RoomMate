<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header('Content-Type: application/json');
include '../database/db_connect.php';

$result = $conn->query("SELECT id, room_id, type_id FROM instruments");

$instruments = [];
while ($row = $result->fetch_assoc()) {
    $instruments[] = $row;
}

echo json_encode($instruments);
$conn->close();
