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
    http_response_code(401); // Unauthorized
    echo json_encode(["status" => "error", "message" => "Not logged in"]);
    exit;
}
if (!$room_id || !$start_time || !$end_time) {
    http_response_code(400); // Bad request
    echo json_encode(["status" => "error", "message" => "Missing parameters"]);
    exit;
}

if ($start_time > $end_time) {
    http_response_code(400); // Bad request
    echo json_encode(["status" => "error", "message" => "Not possible to book backwards in time"]);
    exit;
}


// Time validation
$start_datetime = new DateTime($start_time);
$end_datetime = new DateTime($end_time);
$start_str = $start_datetime->format('Y-m-d H:i:s');
$end_str= $end_datetime->format('Y-m-d H:i:s');

if ($start_datetime >= $end_datetime) {
    http_response_code(400); // Bad request
    echo json_encode([
        "status" => "error", 
        "message" => "Start time cannot be after or equal to end time"
    ]);
    exit;
}


// Min/Max booking duration (30 mins to 8 hours)
$duration = ($end_datetime->getTimestamp() - $start_datetime->getTimestamp()) / 60; // duration in minutes

if ($duration < 30) {
    http_response_code(400); // Bad request
    echo json_encode([
        "status" => "error", 
        "message" => "Booking duration must be at least 30 minutes"
    ]);
    exit;
}

if ($duration > 8 * 60) {
    http_response_code(400); // Bad request
    echo json_encode([
        "status" => "error", 
        "message" => "Booking duration cannot exceed 8 hours"
    ]);
    exit;
}

// Avoid Booking in the past
$now = new DateTime();
if ($start_datetime < $now) {
    echo json_encode([
        "status"  => "error",
        "message" => "Cannot book a room in the past."
    ]);
    exit;
}

// Transaction to avoid race conditions  -> atomicity 
// PREVENTS TWO EQUAL BOOKINGS AT THE SAME TIME 
$conn->begin_transaction(); 

try {

    $stmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM bookings 
        WHERE room_id = ? 
        AND (start_time < ? AND end_time > ?)
        FOR UPDATE 
    ");
    $stmt->bind_param("iss", $room_id,$start_str, $end_str);
    $stmt->execute();
    $stmt->bind_result($conflicts);
    $stmt->fetch();
    $stmt->close();

    if ($conflicts > 0) {
        $conn->rollback();
        http_response_code(409); // Conflict
        echo json_encode([
            "status"  => "error",
            "message" => "This room is already booked during the selected time.",
            "conflict" => true

        ]);
        exit;
    }
    // Only ongoing bookings > Now() 
    $maxBookings = 5;
    $stmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM bookings 
        WHERE user_id = ?
        AND end_time > NOW()  
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($userBookingCount);
    $stmt->fetch();
    $stmt->close();

    if ($userBookingCount >= $maxBookings) {
        $conn->rollback();
        http_response_code(403); // Forbidden
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
    $stmt->bind_param("iiss", $user_id, $room_id, $start_str, $end_str);

    if ($stmt->execute()) {
        $conn->commit();
        http_response_code(200); // Created 
        echo json_encode(["status" => "success", "message" => "Booking confirmed!"]);
    } else {
        $conn->rollback();
        http_response_code(500); // Internal Server Error
        echo json_encode(["status" => "error", "message" => "Error saving booking"]);
    }

    $stmt->close();

    } catch(Exception $e) {
        $conn->rollback();
        error_log("Transaction failed: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "An error occurred. Please try again."
    ]);

} 

$conn->close();
