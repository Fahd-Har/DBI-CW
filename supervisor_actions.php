<?php
require_once 'db_connect.php';
require_once 'auth_check.php';
requireRole('admin');

$action = $_POST['action'] ?? '';

if ($action === 'add') {
    $name     = trim($_POST['name']);
    $company  = trim($_POST['company']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("INSERT INTO users (Username, Password, Role) VALUES (?, ?, 'supervisor')");
        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();
        $userId = $conn->insert_id;

        $stmt2 = $conn->prepare("INSERT INTO supervisor (UserID, Name, Department) VALUES (?, ?, ?)");
        $stmt2->bind_param("iss", $userId, $name, $company);
        $stmt2->execute();

        $conn->commit();
        header("Location: User_Management_IndustrySupervisor.php?success=Supervisor added successfully");
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        $msg = ($e->getCode() == 1062) ? "Username '$username' is already taken." : "Database error: " . $e->getMessage();
        header("Location: User_Management_IndustrySupervisor.php?error=" . urlencode($msg));
    }
    exit;
}

if ($action === 'edit') {
    $id       = intval($_POST['supervisor_id']);
    $name     = trim($_POST['name']);
    $company  = trim($_POST['company']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $conn->begin_transaction();
    try {
        $q = $conn->prepare("SELECT UserID FROM supervisor WHERE SupervisorID = ?");
        $q->bind_param("i", $id);
        $q->execute();
        $userId = $q->get_result()->fetch_assoc()['UserID'] ?? null;

        $stmt = $conn->prepare("UPDATE supervisor SET Name = ?, Department = ? WHERE SupervisorID = ?");
        $stmt->bind_param("ssi", $name, $company, $id);
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
        header("Location: User_Management_IndustrySupervisor.php?success=Supervisor updated successfully");
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        $msg = ($e->getCode() == 1062) ? "Username '$username' is already taken." : "Database error: " . $e->getMessage();
        header("Location: User_Management_IndustrySupervisor.php?error=" . urlencode($msg));
    }
    exit;
}

if ($action === 'delete') {
    $id = intval($_POST['supervisor_id']);
    $conn->begin_transaction();
    try {
        $q = $conn->prepare("SELECT UserID FROM supervisor WHERE SupervisorID = ?");
        $q->bind_param("i", $id);
        $q->execute();
        $userId = $q->get_result()->fetch_assoc()['UserID'] ?? null;

        $stmt = $conn->prepare("DELETE FROM supervisor WHERE SupervisorID = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        if ($userId) {
            $u = $conn->prepare("DELETE FROM users WHERE UserID = ?");
            $u->bind_param("i", $userId);
            $u->execute();
        }

        $conn->commit();
        header("Location: User_Management_IndustrySupervisor.php?success=Supervisor deleted");
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        header("Location: User_Management_IndustrySupervisor.php?error=Cannot delete (supervisor has linked records)");
    }
    exit;
}
?>
