<?php
session_start();
include '../database/db_connect.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $room_id = $_POST['room_id'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    // Check for booking conflicts
    $stmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM bookings 
        WHERE room_id = ? 
        AND (start_time < ? AND end_time > ?)
    ");
    $stmt->bind_param("iss", $room_id, $end_time, $start_time);
    $stmt->execute();
    $stmt->bind_result($conflict_count);
    $stmt->fetch();
    $stmt->close();

    // Insert the booking
    $stmt = $conn->prepare("
        INSERT INTO bookings (user_id, room_id, start_time, end_time)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("iiss", $user_id, $room_id, $start_time, $end_time);

    if ($stmt->execute()) {
        echo "Booking confirmed!";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book a Room</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>Book a Room</h2>

    <?php if ($message): ?>
        <div class="alert alert-info"><?php echo $message; ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="mb-3">
            <label for="room_id" class="form-label">Select Room</label>
            <select name="room_id" id="room_id" class="form-select" required>
                <?php
                include '../database/db_connect.php';
                $result = $conn->query("SELECT id, roomname FROM rooms");
                while ($row = $result->fetch_assoc()) {
                    echo "<option value='{$row['id']}'>{$row['roomname']}</option>";
                }
                ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="start_time" class="form-label">Start Time</label>
            <input type="datetime-local" name="start_time" id="start_time" class="form-control" required>
        </div>

        <div class="mb-3">
            <label for="end_time" class="form-label">End Time</label>
            <input type="datetime-local" name="end_time" id="end_time" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-success">Book Room</button>
    </form>
</div>
</body>
</html>
