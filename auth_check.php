<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login_page.php");
    exit;
}

function requireRole($roles) {
    if (!is_array($roles)) $roles = [$roles];
    if (!in_array($_SESSION['role'], $roles)) {
        header("Location: login_page.php?error=Unauthorized");
        exit;
    }
}
?>
