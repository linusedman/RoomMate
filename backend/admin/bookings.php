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
}
?>