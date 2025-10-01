<?php

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


$user_id  = $_SESSION['user_id'] ?? null;
$is_admin = $_SESSION['admin'] ?? 0;

if (!$user_id) {
    echo json_encode(["status" => "error", "message" => "Not logged in"]);
    exit;
}

if (!$is_admin) {
    echo json_encode(["status" => "error", "message" => "Access denied"]);
    exit;
}

// Decide action
$action = $_GET['action'] ?? 'list';

// List bookings
if ($action === 'list') {
    $res = $conn->query("SELECT b.id, b.user_id, u.username, b.room_id, b.start_time, b.end_time 
                        FROM bookings b 
                        JOIN users u ON b.user_id = u.id
                        ORDER BY start_time ASC");
    $bookings = $res->fetch_all(MYSQLI_ASSOC);
    echo json_encode($bookings);

// Delete booking
} elseif ($action === 'delete') {
    $bidToDel = $_POST['id'] ?? null;
    if (!$bidToDel) {
        echo json_encode(["status" => "error", "message" => "Missing booking ID"]);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM bookings WHERE id = ?");
    $stmt->bind_param("i", $bidToDel);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Booking deleted"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to delete booking"]);
        exit;
    }

// Create booking
} elseif ($action === 'create') {
    $target_user_id = $_POST['user_id'] ?? null;
    $room_id = $_POST['room_id'] ?? null;
    $start_time = $_POST['start_time'] ?? null;
    $end_time = $_POST['end_time'] ?? null;

    if (!$target_user_id || !$room_id || !$start_time || !$end_time) {
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

    // Validate user
    $stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->bind_param("i", $target_user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0) {
        echo json_encode(["status" => "error", "message" => "User does not exist"]);
        exit;
    }

    // Validate room
    $stmt = $conn->prepare("SELECT id FROM rooms WHERE id = ?");
    $stmt->bind_param("i", $room_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0) {
        echo json_encode(["status" => "error", "message" => "Room does not exist"]);
        exit;
    }

    // Check for conflicts
    $stmt = $conn->prepare("SELECT COUNT(*) FROM bookings WHERE room_id = ? AND (start_time < ? AND end_time > ?)");
    $stmt->bind_param("iss", $room_id, $end_time, $start_time);
    $stmt->execute();
    $stmt->bind_result($conflicts);
    $stmt->fetch();
    $stmt->close();

    if ($conflicts > 0) {
        echo json_encode(["status" => "error", "message" => "Room is already booked during this time"]);
        exit;
    }

    // Insert booking
    $stmt = $conn->prepare("INSERT INTO bookings (user_id, room_id, start_time, end_time) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $target_user_id, $room_id, $start_time, $end_time);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Booking created"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to create booking"]);
        exit;
    }

}   
?>