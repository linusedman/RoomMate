<?php
// --- Security headers ---
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require '../database/db_connect.php'; // <-- your PDO connection here

// Grab token (key) from URL
$token = $_GET['key'] ?? null;
if (!$token) {
    die("Invalid request. No reset key provided.");
}

// Step 1: Validate token
$stmt = $pdo->prepare("
    SELECT email, expDate
    FROM password_reset_temp
    WHERE `key` = ?
    LIMIT 1
");
$stmt->execute([$token]);
$reset = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reset) {
    die("Invalid or expired reset link.");
}

// Check expiration
if (strtotime($reset['expDate']) < time()) {
    die("This reset link has expired. Please request a new one.");
}

// Step 2: Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        // Hash and save new password
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $pdo->beginTransaction();
        try {
            // Update user’s password
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
            $stmt->execute([$hashed, $reset['email']]);

            // Remove the token so it cannot be reused
            $stmt = $pdo->prepare("DELETE FROM password_reset_temp WHERE `key` = ?");
            $stmt->execute([$token]);

            $pdo->commit();
            $success = "Your password has been updated successfully. You can now <a href='/login'>log in</a>.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Something went wrong. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Reset Password</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container d-flex justify-content-center align-items-center vh-100">
  <div class="card shadow p-4" style="max-width: 400px; width:100%;">
    <h4 class="text-center mb-3">Set New Password</h4>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
      <div class="alert alert-success"><?= $success ?></div>
    <?php else: ?>
      <form method="POST">
        <div class="mb-3">
          <label for="password" class="form-label">New Password</label>
          <input type="password" id="password" name="password" class="form-control" required minlength="8">
        </div>
        <div class="mb-3">
          <label for="confirm" class="form-label">Confirm Password</label>
          <input type="password" id="confirm" name="confirm" class="form-control" required minlength="8">
        </div>
        <button type="submit" class="btn btn-primary w-100 fw-bold">Update Password</button>
      </form>
    <?php endif; ?>
  </div>
</div>

</body>
</html>
