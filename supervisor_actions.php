<?php
require_once 'db_connect.php';
require_once 'auth_check.php';
requireRole('admin');

$action = $_POST['action'] ?? '';

if ($action === 'add') {
    $name     = trim($_POST['name'] ?? '');
    $company  = trim($_POST['company'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($name === '' || $company === '' || $username === '' || $password === '') {
        header("Location: User_Management_IndustrySupervisor.php?error=" . urlencode("All fields are required"));
        exit;
    }

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
    $id       = intval($_POST['supervisor_id'] ?? 0);
    $name     = trim($_POST['name'] ?? '');
    $company  = trim($_POST['company'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($id <= 0 || $name === '' || $company === '' || $username === '') {
        header("Location: User_Management_IndustrySupervisor.php?error=" . urlencode("All fields except password are required"));
        exit;
    }

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
    $id = intval($_POST['supervisor_id'] ?? 0);
    if ($id <= 0) {
        header("Location: User_Management_IndustrySupervisor.php?error=Invalid supervisor ID");
        exit;
    }

    $conn->begin_transaction();
    try {
        $q = $conn->prepare("SELECT UserID FROM supervisor WHERE SupervisorID = ?");
        $q->bind_param("i", $id);
        $q->execute();
        $userId = $q->get_result()->fetch_assoc()['UserID'] ?? null;

        // 1. delete grade_classification rows for assessments by this supervisor
        $g = $conn->prepare("
            DELETE gc FROM grade_classification gc
            JOIN assessment a ON a.AssessmentID = gc.AssessmentID
            WHERE a.SupervisorID = ?
        ");
        $g->bind_param("i", $id);
        $g->execute();

        // 2. delete this supervisor's assessment rows
        $d1 = $conn->prepare("DELETE FROM assessment WHERE SupervisorID = ?");
        $d1->bind_param("i", $id);
        $d1->execute();

        // 3. detach internships pointing at this supervisor (column is NULLABLE)
        $d2 = $conn->prepare("UPDATE internship SET SupervisorID = NULL WHERE SupervisorID = ?");
        $d2->bind_param("i", $id);
        $d2->execute();

        // 4. delete the supervisor row
        $stmt = $conn->prepare("DELETE FROM supervisor WHERE SupervisorID = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        // 5. delete the linked user account
        if ($userId) {
            $u = $conn->prepare("DELETE FROM users WHERE UserID = ?");
            $u->bind_param("i", $userId);
            $u->execute();
        }

        $conn->commit();
        header("Location: User_Management_IndustrySupervisor.php?success=Supervisor deleted");
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        header("Location: User_Management_IndustrySupervisor.php?error=" . urlencode("Cannot delete supervisor: " . $e->getMessage()));
    }
    exit;
}
?>
