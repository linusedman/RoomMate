<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require '../../vendor/autoload.php'; // PHPMailer
require '../database/db_connect.php';  

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

try {
    // 1. Get email from request
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    if (!$email) {
        echo json_encode(["status" => "error", "message" => "Invalid email"]);
        exit;
    }

    // 2. Check if email exists in users
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user) {
        // Don’t reveal whether email exists
        echo json_encode(["status" => "success", "message" => "Email could not be sent."]);
        exit;
    }

    // 3. Generate reset key and expiration
    $key     = bin2hex(random_bytes(32));
    $expDate = date("Y-m-d H:i:s", time() + 3600);

    // 4. Clean up old requests
    $stmt = $conn->prepare("DELETE FROM password_reset_temp WHERE user_email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->close();

    // 5. Insert new reset request
    $stmt = $conn->prepare("INSERT INTO password_reset_temp (user_email, `key`, expDate) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $email, $key, $expDate);
    $stmt->execute();
    $stmt->close();

    // 6. Build reset link
    $resetLink = "http://localhost:5173/reset-password?key=" . urlencode($key);


    // 7. Send email using MailHog (PHPMailer)
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'localhost';
        $mail->Port = 1025; // MailHog default
        $mail->SMTPAuth = false; 

        $mail->setFrom('no-reply@roommate.test', 'RoomMate App');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = "Reset your password";
        $mail->Body = "New message: Click here to reset your password: <a href='{$resetLink}' target='_blank'>{$resetLink}</a>";


        $mail->send();
        echo json_encode(["status" => "success", "message" => "Check your email for the reset link."]);
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => "Email could not be sent."]);
    }

} catch (Throwable $e) {
    echo json_encode(["status" => "error", "message" => "Server error: " . $e->getMessage()]);
}

$conn->close();