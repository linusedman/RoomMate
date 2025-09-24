<?php
$allowedOrigins = ['http://localhost:5173'];
if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowedOrigins)) {
    header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Allow-Methods: POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");
}
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}
header("Content-Type: application/json");
session_start();
include '../database/db_connect.php';
if ($conn->connect_errno) {
    echo json_encode([
        "status"  => "error",
        "message" => "DB connection failed"
    ]);
    exit;
}
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email    = $_POST['email']    ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT id, password, admin FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($user_id, $db_password, $admin);
        $stmt->fetch();

        if (password_verify($password, $db_password)) {
            $_SESSION['user_id'] = $user_id;
            $_SESSION['email']   = $email;
            $_SESSION['admin']    = $admin;

            echo json_encode([
                "status"  => "success",
                "message" => "Login successful",
                "role"    => $admin
            ]);
        } else {
            echo json_encode([
                "status"  => "error",
                "message" => "Incorrect user or password"
            ]);
        }
    } else {
        echo json_encode([
            "status"  => "error",
            "message" => "Incorrect user or password"
        ]);
    }

    $stmt->close();
    $conn->close();
    exit;
}

echo json_encode([
    "status"  => "error",
    "message" => "Invalid request method"
]);
exit;
