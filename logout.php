<?php
session_start();
session_destroy();
$err = $_GET['error'] ?? '';
header("Location: login_page.php" . ($err ? "?error=" . urlencode($err) : ""));
exit;