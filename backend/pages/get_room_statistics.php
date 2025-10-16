<?php
$allowedOrigins = ['http://localhost:5173'];
if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowedOrigins)) {
    header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Allow-Methods: GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");
}
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

header("Content-Type: application/json");
session_start();
include '../database/db_connect.php';

$sql = "
    SELECT 
        r.id AS room_id,
        r.roomname,
        r.floor_id,
        COUNT(b.id) AS booking_count
    FROM rooms r
    LEFT JOIN bookings b ON r.id = b.room_id
    GROUP BY r.id, r.roomname, r.floor_id
    ORDER BY booking_count ASC, r.roomname ASC
";

$res = $conn->query($sql);

if (!$res) {
    echo json_encode(["status" => "error", "message" => "Failed to fetch room statistics"]);
    exit;
}

$stats = $res->fetch_all(MYSQLI_ASSOC);
echo json_encode(["status" => "success", "data" => $stats]);

?>
