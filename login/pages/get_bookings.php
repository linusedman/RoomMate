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
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

header("Content-Type: application/json");
session_start();
include '../database/db_connect.php';

$res = $conn->query("SELECT room_id FROM bookings");
if (!$res) {
    echo json_encode([]);
    exit;
}
$rows     = $res->fetch_all(MYSQLI_ASSOC);
$bookings = array_map('intval', array_column($rows, 'room_id'));

echo json_encode($bookings);

$res->free();
$conn->close();
