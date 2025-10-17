<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
session_start();
include '../database/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Not logged in"]);
    exit;
}

$user_id = $_SESSION['user_id'];

$sql = "SELECT b.id AS booking_id, r.roomname, b.start_time, b.end_time

        FROM bookings b
        JOIN rooms r ON b.room_id = r.id
        WHERE b.user_id = ?
        ORDER BY b.start_time ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$bookings = [];
while ($row = $result->fetch_assoc()) {
    $bookings[] = $row;
}

echo json_encode([
    "status" => "success",
    "bookings" => $bookings
]);

$stmt->close();
$conn->close();
