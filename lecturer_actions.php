<?php
require_once 'db_connect.php';
require_once 'auth_check.php';
requireRole('admin');

$action = $_POST['action'] ?? '';

if ($action === 'add') {
    $name     = trim($_POST['name']);
    $dep      = trim($_POST['department']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("INSERT INTO users (Username, Password, Role) VALUES (?, ?, 'lecturer')");
        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();
        $userId = $conn->insert_id;

        $stmt2 = $conn->prepare("INSERT INTO lecturer (UserID, Name, Department) VALUES (?, ?, ?)");
        $stmt2->bind_param("iss", $userId, $name, $dep);
        $stmt2->execute();

        $conn->commit();
        header("Location: User_Management_Lecturer.php?success=Lecturer added successfully");
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        $msg = ($e->getCode() == 1062) ? "Username '$username' is already taken." : "Database error: " . $e->getMessage();
        header("Location: User_Management_Lecturer.php?error=" . urlencode($msg));
    }
    exit;
}

if ($action === 'edit') {
    $id       = intval($_POST['lecturer_id']);
    $name     = trim($_POST['name']);
    $dep      = trim($_POST['department']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $conn->begin_transaction();
    try {
        // Find linked UserID
        $q = $conn->prepare("SELECT UserID FROM lecturer WHERE LecturerID = ?");
        $q->bind_param("i", $id);
        $q->execute();
        $userId = $q->get_result()->fetch_assoc()['UserID'] ?? null;

        $stmt = $conn->prepare("UPDATE lecturer SET Name = ?, Department = ? WHERE LecturerID = ?");
        $stmt->bind_param("ssi", $name, $dep, $id);
        $stmt->execute();

        if ($userId) {
            if ($password !== '') {
                $u = $conn->prepare("UPDATE users SET Username = ?, Password = ? WHERE UserID = ?");
                $u->bind_param("ssi", $username, $password, $userId);
            } else {
                $u = $conn->prepare("UPDATE users SET Username = ? WHERE UserID = ?");
                $u->bind_param("si", $username, $userId);
            }
            $u->execute();
        }

        $conn->commit();
        header("Location: User_Management_Lecturer.php?success=Lecturer updated successfully");
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        $msg = ($e->getCode() == 1062) ? "Username '$username' is already taken." : "Database error: " . $e->getMessage();
        header("Location: User_Management_Lecturer.php?error=" . urlencode($msg));
    }
    exit;
}

if ($action === 'delete') {
    $id = intval($_POST['lecturer_id']);
    $conn->begin_transaction();
    try {
        $q = $conn->prepare("SELECT UserID FROM lecturer WHERE LecturerID = ?");
        $q->bind_param("i", $id);
        $q->execute();
        $userId = $q->get_result()->fetch_assoc()['UserID'] ?? null;

        $stmt = $conn->prepare("DELETE FROM lecturer WHERE LecturerID = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        if ($userId) {
            $u = $conn->prepare("DELETE FROM users WHERE UserID = ?");
            $u->bind_param("i", $userId);
            $u->execute();
        }

        $conn->commit();
        header("Location: User_Management_Lecturer.php?success=Lecturer deleted");
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        header("Location: User_Management_Lecturer.php?error=Cannot delete (lecturer has linked records)");
    }
    exit;
}
?>
