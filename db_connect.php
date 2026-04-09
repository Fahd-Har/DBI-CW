<?php
$host = '127.0.0.1';
$port = 3307;
$db   = 'internship result management system';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
