<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
header('Content-Type: application/json');
include '../database/db_connect.php';

$search = isset($_GET['search']) ? $_GET['search'] : '';


if (!empty($search)) {
    $sql = "SELECT id, typename 
            FROM instrument_types 
            WHERE typename LIKE ?
            ORDER BY LOCATE(?, typename), typename ASC";
    $stmt = $conn->prepare($sql);
    $term = '%' . $search . '%';
    $stmt->bind_param("ss", $term, $search);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("SELECT id, typename FROM instrument_types ORDER BY typename ASC");
}

$types = [];
while ($row = $result->fetch_assoc()) {
    $types[] = $row;
}
echo json_encode($types);
$conn->close();
