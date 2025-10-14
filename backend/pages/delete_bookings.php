<?php
$allowedOrigins = ['http://localhost:5173'];
if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowedOrigins)) {
    header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Allow-Methods: POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");
}
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

header("Content-Type: application/json");
session_start();
include '../database/db_connect.php'

$user_id  = $_SESSION['user_id'] ?? null;

// Check that the user is logged in
if (!$user_id) {
    echo json_encode(["status" => "error", "message" => "Not logged in"]);
    exit;
}

$booking_id = $_POST['booking_id'] ?? null;
    if (!$booking_id ) {
        echo json_encode(["status" => "error", "message" => "Missing booking ID"]);
        exit;
    }

$stmt = $conn->prepare("DELETE FROM bookings WHERE id = ? and user_id = ?");
$stmt->bind_param("ii", $booking_id, $user_id);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo json_encode(["status" => "success", "message" => "Booking deleted"]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to delete booking"]);
    exit;
}

?>