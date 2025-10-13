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

// PREFLIGHT:  The Browser comunicates with API to check if it can send the actual POST request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204); // Success without body
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


// Time validation
$start_datetime = new DateTime($start_time);
$end_datetime = new DateTime($end_time);

if ($start_datetime >= $end_datetime) {
    echo json_encode([
        "status" => "error", 
        "message" => "Start time cannot be after or equal to end time"
    ]);
    exit;
}


// Min/Max booking duration (30 mins to 8 hours)
$duration = ($end_datetime->getTimestamp() - $start_datetime->getTimestamp()) / 60; // duration in minutes

if ($duration < 30) {
    echo json_encode([
        "status" => "error", 
        "message" => "Booking duration must be at least 30 minutes"
    ]);
    exit;
}

if ($duration > 8 * 60) {
    echo json_encode([
        "status" => "error", 
        "message" => "Booking duration cannot exceed 8 hours"
    ]);
    exit;
}

$stmt = $conn->prepare("
    SELECT COUNT(*) 
      FROM bookings 
     WHERE room_id = ? 
       AND (start_time < ? AND end_time > ?)
");
$stmt->bind_param("iss", $room_id, $end_datetime->format('Y-m-d H:i:s'), $start_datetime->format('Y-m-d H:i:s'));
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

$maxBookings = 5;
$stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM bookings 
    WHERE user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($userBookingCount);
$stmt->fetch();
$stmt->close();

if ($userBookingCount >= $maxBookings) {
    echo json_encode([
        "status" => "error",
        "message" => "You already have the maximum number of allowed bookings ($maxBookings)."
    ]);
    exit;
}
$stmt = $conn->prepare("
    INSERT INTO bookings (user_id, room_id, start_time, end_time)
    VALUES (?, ?, ?, ?)
");
$stmt->bind_param("iiss", $user_id, $room_id, $start_datetime->format('Y-m-d H:i:s'), $end_datetime->format('Y-m-d H:i:s'));

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Booking confirmed!"]);
} else {
    echo json_encode(["status" => "error", "message" => "Error saving booking"]);
}

$stmt->close();
$conn->close();
