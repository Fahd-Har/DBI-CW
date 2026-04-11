<?php
require_once 'db_connect.php';
require_once 'auth_check.php';
requireRole('admin');

$action = $_POST['action'] ?? '';

if ($action === 'add') {
    $id   = intval($_POST['student_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $prog = trim($_POST['programme'] ?? '');

    if ($id <= 0 || $name === '' || $prog === '') {
        header("Location: User_Management_Student.php?error=" . urlencode("All fields are required"));
        exit;
    }

    try {
        $stmt = $conn->prepare("INSERT INTO student (StudentID, Name, Programme) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $id, $name, $prog);
        $stmt->execute();
        header("Location: User_Management_Student.php?success=Student added successfully");
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() == 1062) {
            $error_msg = "A student with ID $id already exists!";
        } else {
            $error_msg = "Database error: " . $e->getMessage();
        }
        header("Location: User_Management_Student.php?error=" . urlencode($error_msg));
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

    try {
        $stmt = $conn->prepare("UPDATE student SET Name = ?, Programme = ? WHERE StudentID = ?");
        $stmt->bind_param("ssi", $name, $prog, $id);
        $stmt->execute();
        header("Location: User_Management_Student.php?success=Student updated successfully");
    } catch (mysqli_sql_exception $e) {
        header("Location: User_Management_Student.php?error=" . urlencode("Database error: " . $e->getMessage()));
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
        // 1. delete grade_classification rows for this student's assessments
        $g = $conn->prepare("
            DELETE gc FROM grade_classification gc
            JOIN assessment a ON a.AssessmentID = gc.AssessmentID
            JOIN internship i ON i.InternshipID = a.InternshipID
            WHERE i.StudentID = ?
        ");
        $g->bind_param("i", $id);
        $g->execute();

        // 2. delete assessment rows for this student's internships
        $a = $conn->prepare("
            DELETE a FROM assessment a
            JOIN internship i ON i.InternshipID = a.InternshipID
            WHERE i.StudentID = ?
        ");
        $a->bind_param("i", $id);
        $a->execute();

        // 3. delete internships
        $d = $conn->prepare("DELETE FROM internship WHERE StudentID = ?");
        $d->bind_param("i", $id);
        $d->execute();

        // 4. finally delete the student
        $stmt = $conn->prepare("DELETE FROM student WHERE StudentID = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        $conn->commit();
        header("Location: User_Management_Student.php?success=Student deleted");
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        header("Location: User_Management_Student.php?error=" . urlencode("Cannot delete student: " . $e->getMessage()));
    }
    exit;
}
?>
