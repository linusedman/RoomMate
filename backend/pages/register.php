<?php
include '../database/db_connect.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

$response = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!$username || !$email || !$password) {
        echo json_encode(["status" => "error", "message" => "Missing fields"]);
        exit();
    }

    if (strlen($_POST['password']) < 8) {
    echo json_encode(["status" => "error", "message" => "Password must be at least 8 characters long."]);
    exit;
    }

    if (strlen($_POST['username']) > 20) {
    echo json_encode(["status" => "error", "message" => "Username must be 20 characters or shorter."]);
    exit;
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    try {$checkEmailStmt = $conn->prepare("SELECT email FROM users WHERE email = ?");

    } catch(Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ]);
    exit;
    }

    $checkEmailStmt->bind_param("s", $email);
    $checkEmailStmt->execute();

    $checkEmailStmt->store_result();

    if ($checkEmailStmt->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "Email ID already exists"]);
        exit();
    }

    try {$stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");

    } catch(Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ]);
    exit;
    }

    $stmt->bind_param("sss", $username, $email, $hashedPassword);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Account created successfully"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Database error: " . $stmt->error]);
    }

    $stmt->close();
    $checkEmailStmt->close();
    $conn->close();
}
?>
