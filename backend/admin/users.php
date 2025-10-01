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

// List users
if ($action === 'list') {
    $res = $conn->query("SELECT id, username, email, admin FROM users ORDER BY id ASC");
    $users = $res->fetch_all(MYSQLI_ASSOC);
    echo json_encode($users);

// Create user
} elseif ($action === 'create') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? ''; 
    $password = $_POST['password'] ?? '';
    $admin = $_POST['admin'] ?? 0;

    if (!$username || !$email || !$password) {
        echo json_encode(["status" => "error", "message" => "Missing fields"]);
        exit;
    }

    // Check if username or email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username= ? OR email= ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $checkRes = $stmt->get_result();
    if ($checkRes->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "Username or email already exists"]);
        exit;
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, admin) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $username, $email, $hashedPassword, $admin);
    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "User created"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to insert user"]);
        exit;
    }

// Delete user
} elseif ($action === 'delete') {
    $idToDel = $_POST['id'] ?? null;
    if (!$idToDel) {
        echo json_encode(["status" => "error", "message" => "Missing user ID"]);
        exit;
    }

    // Prevent deleting self
    if ($idToDel == $user_id) {
        echo json_encode(["status" => "error", "message" => "Cannot delete yourself"]);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $idToDel);
    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "User deleted"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to delete user"]);
        exit;
    }

// Update user
} elseif ($action === 'update') {
    $idToUpd = $_POST['id'] ?? null;
    $username = $_POST['username'] ?? null;
    $email = $_POST['email'] ?? null;
    $password = $_POST['password'] ?? null;
    $adminFlag = $_POST['admin'] ?? null;

    if (!$idToUpd) {
        echo json_encode(["status" => "error", "message" => "Missing user ID"]);
        exit;
    }

    // Prevent removing own admin rights
    if ($idToUpd == $user_id && $adminFlag == 0) {
        echo json_encode(["status" => "error", "message" => "Cannot remove your own admin rights"]);
        exit;
    }

    // Check uniqueness for email
    if ($email) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $email, $idToUpd);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) {
            echo json_encode(["status" => "error", "message" => "Email already in use"]);
            exit;
        }
    }

    // Check uniqueness for username
    if ($username) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->bind_param("si", $username, $idToUpd);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) {
            echo json_encode(["status" => "error", "message" => "Username already in use"]);
            exit;
        }
    }

    $updates = [];
    $params = [];
    $types = "";

    if ($username) {
        $updates[] = "username = ?";
        $params[] = $username;
        $types .= "s";
    }

    if ($email) {
        $updates[] = "email = ?";
        $params[] = $email;
        $types .= "s";
    }

    if ($password) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $updates[] = "password = ?";
        $params[] = $hashedPassword;
        $types .= "s";
    }

    if ($adminFlag !== null) {
        $updates[] = "admin = ?";
        $params[] = (bool)$adminFlag;
        $types .= "i";
    }

    if (empty($updates)) {
        echo json_encode(["status" => "error", "message" => "No fields to update"]);
        exit;
    }

    $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
    $params[] = $idToUpd;
    $types .= "i";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "User updated"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to update user"]);
    }
}
?>