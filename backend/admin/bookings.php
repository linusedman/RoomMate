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
    $rows = $res->fetch_all(MYSQLI_ASSOC);
    foreach ($rows as &$r) {
    $start = new DateTime($r['start_time']);
    $start->setTimezone(new DateTimeZone('UTC'));
    $r['start_time'] = $start->format('Y-m-d\TH:i:s\Z'); // UTC ISO

    $end = new DateTime($r['end_time']);
    $end->setTimezone(new DateTimeZone('UTC'));
    $r['end_time'] = $end->format('Y-m-d\TH:i:s\Z'); // UTC ISO
    }
    echo json_encode($rows);

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
    $stmt->bind_param("iss", $room_id, $end_datetime->format('Y-m-d H:i:s'), $start_datetime->format('Y-m-d H:i:s'));
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
    $stmt->bind_param("iiss", $target_user_id, $room_id, $start_datetime->format('Y-m-d H:i:s'), $end_datetime->format('Y-m-d H:i:s'));

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Booking created"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to create booking"]);
        exit;
    }

// Update booking
} elseif ($action === 'update') {
    $bidToUpd = $_POST['id'] ?? null;
    $user_id = $_POST['user_id'] ?? null;
    $room_id = $_POST['room_id'] ?? null;
    $start_time = $_POST['start_time'] ?? null;
    $end_time = $_POST['end_time'] ?? null;

    if (!$bidToUpd) {
        echo json_encode(["status" => "error", "message" => "Missing booking ID"]);
        exit;
    }

    // Fetch current booking based on booking ID
    $stmt = $conn->prepare("SELECT * FROM bookings WHERE id = ?");
    $stmt->bind_param("i", $bidToUpd);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0) {
        echo json_encode(["status" => "error", "message" => "Booking not found"]);
        exit;
    }
    $currentBooking = $res->fetch_assoc();

    $updates = [];
    $params = [];
    $types = "";
    
    if ($room_id) {
        // Validate room ID
        $stmt = $conn->prepare("SELECT id FROM rooms WHERE id = ?");
        $stmt->bind_param("i", $room_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows === 0) {
            echo json_encode(["status" => "error", "message" => "Room does not exist"]);
            exit;
        }
        $updates[] = "room_id = ?";
        $params[] = $room_id;
        $types .= "i";
    } else {
        $room_id = $currentBooking['room_id'];
    }

    if ($user_id) {
        // Validate user ID
        $stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows === 0) {
            echo json_encode(["status" => "error", "message" => "User does not exist"]);
            exit;
        }
        $updates[] = "user_id = ?";
        $params[] = $user_id;
        $types .= "i";
    }

    // Time validation
    $new_start = $start_time ?? $currentBooking['start_time'];
    $new_end = $end_time ?? $currentBooking['end_time'];

    $start_datetime = new DateTime($new_start);
    $end_datetime = new DateTime($new_end);

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

    // Check for conflicts
    $stmt = $conn->prepare("SELECT COUNT(*) FROM bookings WHERE room_id = ? AND id != ? AND (start_time < ? AND end_time > ?)");
    $stmt->bind_param("iiss", $room_id, $bidToUpd, $end_datetime->format('Y-m-d H:i:s'), $start_datetime->format('Y-m-d H:i:s'));
    $stmt->execute();
    $stmt->bind_result($conflicts);
    $stmt->fetch();
    $stmt->close();

    if ($conflicts > 0) {
        echo json_encode(["status" => "error", "message" => "Room is already booked during this time"]);
        exit;
    }

    if ($start_time) {
        $updates[] = "start_time = ?";
        $params[] = $start_datetime->format('Y-m-d H:i:s');
        $types .= "s";
    }
    if ($end_time) {
        $updates[] = "end_time = ?";
        $params[] = $end_datetime->format('Y-m-d H:i:s');
        $types .= "s";
    }

    if (empty($updates)) {
        echo json_encode(["status" => "error", "message" => "No fields to update"]);
        exit;
    }

    $sql = "UPDATE bookings SET " . implode(", ", $updates) . " WHERE id = ?";
    $params[] = $bidToUpd;
    $types .= "i";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Booking updated"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to update booking"]);
    }
}
?>