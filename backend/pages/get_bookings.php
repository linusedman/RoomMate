<?php
// CORS
$allowedOrigins = ['http://localhost:5173'];
if (
    isset($_SERVER['HTTP_ORIGIN']) &&
    in_array($_SERVER['HTTP_ORIGIN'], $allowedOrigins)
) {
    header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Allow-Methods: GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");
}

// PREFLIGHT:  The Browser comunicates with API to check if it can send the actual POST request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204); // Success without body
    exit;
}

header("Content-Type: application/json");
session_start();
include '../database/db_connect.php';

$res = $conn->query("SELECT id, user_id, room_id, start_time, end_time FROM bookings ORDER BY start_time ASC");
if (!$res) {
    echo json_encode([]);
    exit;
}
$rows = $res->fetch_all(MYSQLI_ASSOC);

// Convert datetimes to same format frontend will parse
foreach ($rows as &$r) {
    $r['room_id'] = (int)$r['room_id'];
    // keep start_time and end_time as strings (MySQL DATETIME)
}
echo json_encode($rows);

$res->free();
$conn->close();
