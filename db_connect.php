<?php
$host = '127.0.0.1';
$port = 3306;
$db   = 'internship result management system';
$user = 'root';
$pass = 'root';

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
