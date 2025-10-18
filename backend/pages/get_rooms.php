<?php
// CORS
$allowedOrigins = ['http://localhost:5173'];
if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowedOrigins)) {
    header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");
}
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204); // Success without body
    exit;
}
header("Content-Type: application/json");
include '../database/db_connect.php';

// Ensure array and parse instrument IDs as integers only
$instrumentId = $_POST['instrumentId'] ?? [];
if (!is_array($instrumentId)) {
    $instrumentId = [$instrumentId];
}
$instrumentId = array_filter(array_map('intval', $instrumentId));

$start = $_POST['start'] ?? '';
$end = $_POST['end'] ?? '';
$isFilteringByInstruments = count($instrumentId) > 0;
if ($isFilteringByInstruments) {
    $placeholders = implode(',', array_fill(0, count($instrumentId), '?'));
}

if ($isFilteringByInstruments && $start && $end) {
    // FILTER: by instruments AND by time
    $sql = "
        SELECT r.id, r.roomname, r.floor_id AS floor, r.path
        FROM rooms r
        JOIN instruments i ON r.id = i.room_id
        WHERE i.type_id IN ($placeholders)
        AND r.id NOT IN (
            SELECT room_id FROM bookings
            WHERE (start_time < ? AND end_time > ?)
        )
        GROUP BY r.id
        HAVING COUNT(DISTINCT i.type_id) = ?
    ";
    $stmt = $conn->prepare($sql);
    $types = str_repeat('i', count($instrumentId)) . 'ssi';
    $params = [...$instrumentId, $end, $start, count($instrumentId)];
    $stmt->bind_param($types, ...$params);

} elseif ($start && $end) {
    // FILTER: by time only
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

} elseif ($isFilteringByInstruments) {
    // FILTER: by instruments only
    $sql = "
        SELECT r.id, r.roomname, r.floor_id AS floor, r.path
        FROM rooms r
        JOIN instruments i ON r.id = i.room_id
        WHERE i.type_id IN ($placeholders)
        GROUP BY r.id
        HAVING COUNT(DISTINCT i.type_id) = ?
    ";
    $stmt = $conn->prepare($sql);
    $types = str_repeat('i', count($instrumentId)) . 'i';
    $params = [...$instrumentId, count($instrumentId)];
    $stmt->bind_param($types, ...$params);

} else {
    // ALL ROOMS (no filter)
    $sql = "SELECT id, roomname, floor_id AS floor, path FROM rooms";
    $stmt = $conn->prepare($sql);
}

$stmt->execute();
$result = $stmt->get_result();
if (!$result) {
    echo json_encode([
        "status" => "error", 
        "message" => "Failed to retrieve rooms"]);
    exit;
}
$rows = $result->fetch_all(MYSQLI_ASSOC);
$rooms = array_map(function($r) {
    return [
        'id' => (int)$r['id'],
        'roomname' => $r['roomname'],
        'floor' => (int)$r['floor'],
        'path' => $r['path'],
    ];
}, $rows);
echo json_encode($rooms);
$result->free();
$conn->close();
