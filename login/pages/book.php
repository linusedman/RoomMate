<?php
session_start();
include 'db.php';

$user_id = 1; // For testing, assume logged in as Alice
$message = "";

// Handle booking or release
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $lab_id = $_POST['lab_id'];
    $action = $_POST['action']; // "book" or "release"

    if ($action === "book") {
        // Check if already booked
        $stmt = $conn->prepare("SELECT is_booked FROM labs WHERE id=?");
        $stmt->bind_param("i", $lab_id);
        $stmt->execute();
        $stmt->bind_result($is_booked);
        $stmt->fetch();
        $stmt->close();

        if ($is_booked) {
            $message = "⚠️ This lab is already booked.";
        } else {
            $stmt = $conn->prepare("UPDATE labs SET is_booked=1 WHERE id=?");
            $stmt->bind_param("i", $lab_id);
            $stmt->execute();
            $stmt->close();
            $message = "✅ Lab booked successfully!";
        }
    }

    if ($action === "release") {
        $stmt = $conn->prepare("UPDATE labs SET is_booked=0 WHERE id=?");
        $stmt->bind_param("i", $lab_id);
        $stmt->execute();
        $stmt->close();
        $message = "✅ Lab released successfully!";
    }
}

// Fetch all labs
$labs = $conn->query("SELECT * FROM labs");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Lab Booking</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css">
</head>
<body class="p-4">

<h2>Lab Booking System</h2>

<?php if($message != ""): ?>
    <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<h3>Book a Lab</h3>
<form method="POST" action="book.php">
    <label>Select Lab to Book:</label>
    <select name="lab_id" class="form-control mb-2" required>
        <?php while($lab = $labs->fetch_assoc()): ?>
            <?php if(!$lab['is_booked']): ?>
                <option value="<?= $lab['id'] ?>"><?= htmlspecialchars($lab['name']) ?> (Available)</option>
            <?php endif; ?>
        <?php endwhile; ?>
    </select>
    <input type="hidden" name="action" value="book">
    <button type="submit" class="btn btn-primary">Book Lab</button>
</form>

<h3 class="mt-4">Booked Labs</h3>
<table class="table table-bordered">
    <tr><th>Lab</th><th>Status</th><th>Action</th></tr>
    <?php
    $labs = $conn->query("SELECT * FROM labs");
    while($lab = $labs->fetch_assoc()):
    ?>
        <tr>
            <td><?= htmlspecialchars($lab['name']) ?></td>
            <td><?= $lab['is_booked'] ? 'Booked' : 'Available' ?></td>
            <td>
                <?php if($lab['is_booked']): ?>
                    <form method="POST" action="book.php" style="display:inline">
                        <input type="hidden" name="lab_id" value="<?= $lab['id'] ?>">
                        <input type="hidden" name="action" value="release">
                        <button type="submit" class="btn btn-warning btn-sm">Release</button>
                    </form>
                <?php else: ?>
                    <button class="btn btn-secondary btn-sm" disabled>Release</button>
                <?php endif; ?>
            </td>
        </tr>
    <?php endwhile; ?>
</table>

</body>
</html>
