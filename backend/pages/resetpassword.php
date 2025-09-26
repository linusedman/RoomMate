<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require '../../vendor/autoload.php'; // PHPMailer
require '../database/db_connect.php';  

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

try {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    if (!$email) {
        echo json_encode(["status" => "error", "message" => "Invalid email"]);
        exit;
    }

    // 1. Check if email exists in users
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if (!$user) {
        // Don’t reveal whether email exists
        echo json_encode(["status" => "success", "message" => "If this email exists, a reset link has been sent."]);
        exit;
    }

    // 2. Generate reset key and expiration
    $key     = bin2hex(random_bytes(32)); // secure token
    $expDate = date("Y-m-d H:i:s", time() + 3600); // valid 1 hour

    // 3. Clean up old requests for this email
    $stmt = $pdo->prepare("DELETE FROM password_reset_temp WHERE email = ?");
    $stmt->execute([$email]);

    // 4. Insert new reset request
    $stmt = $pdo->prepare("INSERT INTO password_reset_temp (email, `key`, expDate) VALUES (?, ?, ?)");
    $stmt->execute([$email, $key, $expDate]);

    // 5. Build reset link
    $resetLink = "http://localhost/RoomMate/backend/pages/resetpassword_form.php?key=$key";
    $smtp = require '../config/smtp_config.php';
    // 6. Send email with PHPMailer
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $smtp['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtp['username'];
        $mail->Password   = $smtp['password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('mimarulandaa@unal.edu.co', 'RoomMate');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = "Password Reset Request";
        $mail->Body    = "Hello,<br><br>Click the link below to reset your password:<br>
                          <a href='$resetLink'>$resetLink</a><br><br>
                          This link will expire in 1 hour.";

        $mail->send();
        echo json_encode(["status" => "success", "message" => "Check your email for the reset link."]);
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => "Email could not be sent."]);
    }
} catch (Throwable $e) {
    echo json_encode(["status" => "error", "message" => "Server error: " . $e->getMessage()]);
}
$conn->close();