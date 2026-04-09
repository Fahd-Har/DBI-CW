<?php
session_start();
$_SESSION = array(); // Clears all session variables
session_destroy();   // Destroys the session completely
header("Location: login_page.php");
exit;
?>