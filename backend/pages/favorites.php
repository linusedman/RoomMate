<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
session_start();
include '../database/db_connect.php';

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(["status" => "error", "message" => "Not logged in"]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // List favorites
        $stmt = $conn->prepare("
            SELECT f.room_id, r.roomname, r.floor_id
            FROM favorites f
            JOIN rooms r ON f.room_id = r.id
            WHERE f.user_id = ?
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        echo json_encode($res->fetch_all(MYSQLI_ASSOC));
        break;

    case 'POST':
        // Add favorite
        $data = json_decode(file_get_contents("php://input"), true);
        $room_id = $data['room_id'] ?? null;

        if (!$room_id) {
            echo json_encode(["status" => "error", "message" => "Missing room ID"]);
            exit;
        }

        // Validate that room exists
        $stmt = $conn->prepare("SELECT id FROM rooms WHERE id = ?");
        $stmt->bind_param("i", $room_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            echo json_encode(["status" => "error", "message" => "Room not found"]);
            exit;
        }
        
        // Insert room into favorites, ignore if user has already favorited that room
        $stmt = $conn->prepare("INSERT IGNORE INTO favorites (user_id, room_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $user_id, $room_id);
        $stmt->execute();

        echo json_encode(["status" => "success", "message" => "Room added to favorites"]);
        break;

    case 'DELETE':
        // Remove favorite
        $data = json_decode(file_get_contents("php://input"), true);
        $room_id = $data['room_id'] ?? null;

        if (!$room_id) {
            echo json_encode(["status" => "error", "message" => "Missing room ID"]);
            exit;
        }

        $stmt = $conn->prepare("DELETE FROM favorites WHERE user_id = ? AND room_id = ?");
        $stmt->bind_param("ii", $user_id, $room_id);
        $stmt->execute();

        echo json_encode(["status" => "success", "message" => "Room removed from favorites"]);
        break;
}
?>
