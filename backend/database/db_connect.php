<?php
$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "roomMate"; 

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

date_default_timezone_set('UTC'); // PHP -> UTC
$conn->query("SET time_zone = '+00:00'"); // MySQL session -> UTC
?>
