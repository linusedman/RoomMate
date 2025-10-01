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

// List instruments
if ($action === 'list') {
    $res = $conn->query("SELECT i.id, t.typename, i.room_id
                        FROM instruments i
                        JOIN instrument_types t ON i.type_id = t.id
                        ORDER BY i.id ASC");
    $instruments = $res->fetch_all(MYSQLI_ASSOC);
    echo json_encode($instruments);

// Delete instrument
} elseif ($action === 'delete') {
    $iidToDel = $_POST['id'] ?? null;
    if (!$iidToDel) {
        echo json_encode(["status" => "error", "message" => "Missing instrument ID"]);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM instruments WHERE id = ?");
    $stmt->bind_param("i", $iidToDel);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Instrument deleted"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to delete instrument"]);
    }

// Add instrument
} elseif ($action === 'add') {
    $instrument_type = $_POST['instrument_type'] ?? '';
    $room_id = $_POST['room_id'] ?? null;
    if (!$instrument_type) {
        echo json_encode(["status" => "error", "message" => "Missing instrument type"]);
        exit;
    }
    if (!$room_id) {
        echo json_encode(["status" => "error", "message" => "Missing room ID"]);
        exit;
    }

    // Check if room_id exists
    $stmt = $conn->prepare("SELECT id FROM rooms WHERE id = ?");
    $stmt->bind_param("i", $room_id);
    $stmt->execute();
    $roomRes = $stmt->get_result();
    if ($roomRes->num_rows === 0) {
        echo json_encode(["status" => "error", "message" => "Room does not exist"]);
        exit;
    }

    // Check if instrument type exists
    $stmt = $conn->prepare("SELECT id FROM instrument_types WHERE typename = ?");
    $stmt->bind_param("s", $instrument_type);
    $stmt->execute();
    $checkRes = $stmt->get_result();

    // If not, create it
    if ($checkRes->num_rows === 0) {
        $stmt = $conn->prepare("INSERT INTO instrument_types (typename) VALUES (?)");
        $stmt->bind_param("s", $instrument_type);
        if (!$stmt->execute()) {
            echo json_encode(["status" => "error", "message" => "Failed to create instrument type"]);
            exit;
        }
        $type_id = $stmt->insert_id;
    } else {
        $row = $checkRes->fetch_assoc();
        $type_id = $row['id'];
    }

    // Insert instrument
    $stmt = $conn->prepare("INSERT INTO instruments (type_id, room_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $type_id, $room_id);
    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Instrument added"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to add instrument"]);
        exit;
    }
}

?>