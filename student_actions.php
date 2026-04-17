<?php
require_once 'db_connect.php';
require_once 'auth_check.php';
requireRole('admin');

$action = $_POST['action'] ?? '';

if ($action === 'add') {
    $name = trim($_POST['name'] ?? '');
    $prog = trim($_POST['programme'] ?? '');

    if ($name === '' || $prog === '') {
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
        header("Location: User_Management_Student.php?error=" . urlencode("A student with a similar name to '$name' already exists"));
        exit;
    }

    try {
        $stmt = $conn->prepare("INSERT INTO student (Name, Programme) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $prog);
        $stmt->execute();
        header("Location: User_Management_Student.php?success=Student added successfully");
    } catch (mysqli_sql_exception $e) {
        error_log("Student add error: " . $e->getMessage());
        $msg = ($e->getCode() == 1062) ? "Student already exists." : "An unexpected error occurred. Please try again.";
        header("Location: User_Management_Student.php?error=" . urlencode($msg));
    }
    exit;
}

if ($action === 'edit') {
    $id   = intval($_POST['student_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $prog = trim($_POST['programme'] ?? '');

    if ($id <= 0 || $name === '' || $prog === '') {
        header("Location: User_Management_Student.php?error=" . urlencode("All fields are required"));
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
        header("Location: User_Management_Student.php?error=" . urlencode("Another student with a similar name to '$name' already exists"));
        exit;
    }

    try {
        $stmt = $conn->prepare("UPDATE student SET Name = ?, Programme = ? WHERE StudentID = ?");
        $stmt->bind_param("ssi", $name, $prog, $id);
        $stmt->execute();
        header("Location: User_Management_Student.php?success=Student updated successfully");
    } catch (mysqli_sql_exception $e) {
        error_log("Student edit error: " . $e->getMessage());
        header("Location: User_Management_Student.php?error=" . urlencode("An unexpected error occurred. Please try again."));
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

        $conn->commit();
        header("Location: User_Management_Student.php?success=Student deleted");
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        error_log("Student delete error: " . $e->getMessage());
        header("Location: User_Management_Student.php?error=" . urlencode("Cannot delete student. Please try again."));
    }
    exit;
}
?>