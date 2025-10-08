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
include '../database/db_connect.php';


$result = $conn->query("SELECT id, path FROM floors");
        if (!$result) {
            echo json_encode([]);
            exit;
        }
$rows = $result->fetch_all(MYSQLI_ASSOC);
$floors = array_map(function($r) {
    return [
        'id'       => (int)$r['id'],
        'path'    => $r['path'],
    ];
}, $rows);

echo json_encode($floors);

$result->free();
$conn->close();