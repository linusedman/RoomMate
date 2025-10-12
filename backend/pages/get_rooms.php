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

$instrumentId = $_POST['instrumentId'] ?? '';
$start = $_POST['start'] ?? '';
$end = $_POST['end'] ?? '';


if ($instrumentId && $start && $end) {
    $sql = "
        SELECT DISTINCT r.id, r.roomname, r.floor_id AS floor, r.path
        FROM rooms r
        JOIN instruments i ON r.id = i.room_id
        WHERE i.type_id = ?
        AND r.id NOT IN (
            SELECT room_id FROM bookings
            WHERE (start_time < ? AND end_time > ?)
        )
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $instrumentId, $end, $start);
    $stmt->execute();
    $result = $stmt->get_result();
} elseif ($start && $end) {
    $sql = "
        SELECT r.id, r.roomname, r.floor_id AS floor, r.path
        FROM rooms r
        WHERE r.id NOT IN (
            SELECT room_id FROM bookings
            WHERE (start_time < ? AND end_time > ?)
        )
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $end, $start);
    $stmt->execute();
    $result = $stmt->get_result();
} elseif ($instrumentId) {
    $sql = "
        SELECT DISTINCT r.id, r.roomname, r.floor_id AS floor, r.path
        FROM rooms r
        JOIN instruments i ON r.id = i.room_id
        WHERE i.type_id = ?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $instrumentId);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("SELECT id, roomname, floor_id AS floor, path FROM rooms");
}
if (!$result) {
    echo json_encode([
        "status" => "error", 
        "message" => "Failed to retrieve rooms"]);
    exit;
} 

$rows = $result->fetch_all(MYSQLI_ASSOC);
$rooms = array_map(function($r) {
    return [
        'id'       => (int)$r['id'],
        'roomname' => $r['roomname'],
        'floor'    => (int)$r['floor'],
        'path'    => $r['path'],
    ];
}, $rows);

echo json_encode($rooms);

$result->free();
$conn->close();
