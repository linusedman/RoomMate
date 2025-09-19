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
include '../database/db_connect.php';

$result = $conn->query("SELECT id, roomname, floor_id AS floor FROM rooms");
if (!$result) {
    echo json_encode([]);
    exit;
}

$rows = $result->fetch_all(MYSQLI_ASSOC);
$rooms = array_map(function($r) {
    return [
        'id'       => (int)$r['id'],
        'roomname' => $r['roomname'],
        'floor'    => (int)$r['floor'],
    ];
}, $rows);

echo json_encode($rooms);

$result->free();
$conn->close();
