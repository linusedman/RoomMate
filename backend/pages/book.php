<?php
// CORS
$allowedOrigins = ['http://localhost:5173'];
if (
    isset($_SERVER['HTTP_ORIGIN']) &&
    in_array($_SERVER['HTTP_ORIGIN'], $allowedOrigins)
) {
    header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Allow-Methods: POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");
}
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

header("Content-Type: application/json");
session_start();
include '../database/db_connect.php';

$user_id    = $_SESSION['user_id']    ?? null;
$room_id    = $_POST['room_id']       ?? null;
$start_time = $_POST['start_time']    ?? null;
$end_time   = $_POST['end_time']      ?? null;


if (!$user_id) {
    echo json_encode(["status" => "error", "message" => "Not logged in"]);
    exit;
}
if (!$room_id || !$start_time || !$end_time) {
    echo json_encode(["status" => "error", "message" => "Missing parameters"]);
    exit;
}

$stmt = $conn->prepare("
    SELECT COUNT(*) 
      FROM bookings 
     WHERE room_id = ? 
       AND (start_time < ? AND end_time > ?)
");
$stmt->bind_param("iss", $room_id, $end_time, $start_time);
$stmt->execute();
$stmt->bind_result($conflicts);
$stmt->fetch();
$stmt->close();

if ($conflicts > 0) {
    echo json_encode([
        "status"  => "error",
        "message" => "This room is already booked during the selected time."
    ]);
    exit;
}

$stmt = $conn->prepare("
    INSERT INTO bookings (user_id, room_id, start_time, end_time)
    VALUES (?, ?, ?, ?)
");
$stmt->bind_param("iiss", $user_id, $room_id, $start_time, $end_time);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Booking confirmed!"]);
} else {
    echo json_encode(["status" => "error", "message" => "Error saving booking"]);
}

$stmt->close();
$conn->close();
