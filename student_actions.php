<?php
require_once 'db_connect.php';
require_once 'auth_check.php';
requireRole('admin');

$action = $_POST['action'] ?? '';

if ($action === 'add') {
    $name     = trim($_POST['name'] ?? '');
    $prog     = trim($_POST['programme'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($name === '' || $prog === '' || $username === '' || $password === '') {
        header("Location: User_Management_Student.php?error=" . urlencode("All fields are required"));
        exit;
    }

    $check = $conn->prepare("
        SELECT StudentID FROM student
        WHERE LOWER(REPLACE(REPLACE(Name, ' ', ''), '.', '')) = LOWER(REPLACE(REPLACE(?, ' ', ''), '.', ''))
    ");
    $check->bind_param("s", $name);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        header("Location: User_Management_Student.php?error=" . urlencode("A student with a similar name already exists"));
        exit;
    }

    $userChk = $conn->prepare("SELECT UserID FROM users WHERE Username = ?");
    $userChk->bind_param("s", $username);
    $userChk->execute();
    if ($userChk->get_result()->num_rows > 0) {
        header("Location: User_Management_Student.php?error=" . urlencode("Username '$username' is already taken"));
        exit;
    }

    $conn->begin_transaction();
    try {
        $u = $conn->prepare("INSERT INTO users (Username, Password, Role) VALUES (?, ?, 'student')");
        $u->bind_param("ss", $username, $password);
        $u->execute();
        $userId = $conn->insert_id;

        $s = $conn->prepare("INSERT INTO student (UserID, Name, Programme) VALUES (?, ?, ?)");
        $s->bind_param("iss", $userId, $name, $prog);
        $s->execute();

        $conn->commit();
        header("Location: User_Management_Student.php?success=Student added successfully");
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        error_log("Student add error: " . $e->getMessage());
        $msg = ($e->getCode() == 1062) ? "Username '$username' is already taken." : "An unexpected error occurred.";
        header("Location: User_Management_Student.php?error=" . urlencode($msg));
    }
    exit;
}

if ($action === 'edit') {
    $id       = intval($_POST['student_id'] ?? 0);
    $name     = trim($_POST['name'] ?? '');
    $prog     = trim($_POST['programme'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($id <= 0 || $name === '' || $prog === '' || $username === '') {
        header("Location: User_Management_Student.php?error=" . urlencode("All fields except password are required"));
        exit;
    }

    $dup = $conn->prepare("
        SELECT StudentID FROM student
        WHERE LOWER(REPLACE(REPLACE(Name, ' ', ''), '.', '')) = LOWER(REPLACE(REPLACE(?, ' ', ''), '.', ''))
          AND StudentID <> ?
    ");
    $dup->bind_param("si", $name, $id);
    $dup->execute();
    if ($dup->get_result()->num_rows > 0) {
        header("Location: User_Management_Student.php?error=" . urlencode("Another student with a similar name already exists"));
        exit;
    }

    $curQ = $conn->prepare("SELECT UserID FROM student WHERE StudentID = ?");
    $curQ->bind_param("i", $id);
    $curQ->execute();
    $currentUserId = (int)($curQ->get_result()->fetch_assoc()['UserID'] ?? 0);

    $userChk = $conn->prepare("SELECT UserID FROM users WHERE Username = ? AND UserID <> ?");
    $userChk->bind_param("si", $username, $currentUserId);
    $userChk->execute();
    if ($userChk->get_result()->num_rows > 0) {
        header("Location: User_Management_Student.php?error=" . urlencode("Username '$username' is already taken"));
        exit;
    }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("UPDATE student SET Name = ?, Programme = ? WHERE StudentID = ?");
        $stmt->bind_param("ssi", $name, $prog, $id);
        $stmt->execute();

        if ($currentUserId > 0) {
            if ($password !== '') {
                $u = $conn->prepare("UPDATE users SET Username = ?, Password = ? WHERE UserID = ?");
                $u->bind_param("ssi", $username, $password, $currentUserId);
            } else {
                $u = $conn->prepare("UPDATE users SET Username = ? WHERE UserID = ?");
                $u->bind_param("si", $username, $currentUserId);
            }
            $u->execute();
        } else {
            // Student had no user account yet — create one
            if ($password === '') {
                throw new Exception("Password required for first-time account creation");
            }
            $u = $conn->prepare("INSERT INTO users (Username, Password, Role) VALUES (?, ?, 'student')");
            $u->bind_param("ss", $username, $password);
            $u->execute();
            $newUserId = $conn->insert_id;
            $link = $conn->prepare("UPDATE student SET UserID = ? WHERE StudentID = ?");
            $link->bind_param("ii", $newUserId, $id);
            $link->execute();
        }

        $conn->commit();
        header("Location: User_Management_Student.php?success=Student updated successfully");
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Student edit error: " . $e->getMessage());
        header("Location: User_Management_Student.php?error=" . urlencode("An unexpected error occurred."));
    }
    exit;
}

if ($action === 'delete') {
    $id = intval($_POST['student_id'] ?? 0);
    if ($id <= 0) {
        header("Location: User_Management_Student.php?error=Invalid student ID");
        exit;
    }

    $conn->begin_transaction();
    try {
        $q = $conn->prepare("SELECT UserID FROM student WHERE StudentID = ?");
        $q->bind_param("i", $id);
        $q->execute();
        $userId = $q->get_result()->fetch_assoc()['UserID'] ?? null;

        $g = $conn->prepare("
            DELETE gc FROM grade_classification gc
            JOIN assessment a ON a.AssessmentID = gc.AssessmentID
            JOIN internship i ON i.InternshipID = a.InternshipID
            WHERE i.StudentID = ?
        ");
        $g->bind_param("i", $id);
        $g->execute();

        $a = $conn->prepare("
            DELETE a FROM assessment a
            JOIN internship i ON i.InternshipID = a.InternshipID
            WHERE i.StudentID = ?
        ");
        $a->bind_param("i", $id);
        $a->execute();

        $d = $conn->prepare("DELETE FROM internship WHERE StudentID = ?");
        $d->bind_param("i", $id);
        $d->execute();

        $stmt = $conn->prepare("DELETE FROM student WHERE StudentID = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        if ($userId) {
            $u = $conn->prepare("DELETE FROM users WHERE UserID = ?");
            $u->bind_param("i", $userId);
            $u->execute();
        }

        $conn->commit();
        header("Location: User_Management_Student.php?success=Student deleted");
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        error_log("Student delete error: " . $e->getMessage());
        header("Location: User_Management_Student.php?error=" . urlencode("Cannot delete student."));
    }
    exit;
}
?>