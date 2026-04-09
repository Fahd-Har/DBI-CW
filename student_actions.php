<?php

require_once 'db_connect.php';
require_once 'auth_check.php';
requireRole('admin');

$action = $_POST['action'] ?? '';

if ($action === 'add') {
    $id   = intval($_POST['student_id']);
    $name = trim($_POST['name']);
    $prog = trim($_POST['programme']);
    
    $stmt = $conn->prepare("INSERT INTO student (StudentID, Name, Programme) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $id, $name, $prog);
    
    try {
        // Try to insert the record
        $stmt->execute();
        header("Location: User_Management_Student.php?success=Student added successfully");
    } catch (mysqli_sql_exception $e) {
        // Catch the exception without crashing
        
        // 1062 is the MySQL error code for "Duplicate entry"
        if ($e->getCode() == 1062) {
            $error_msg = "A student with ID $id already exists!";
        } else {
            // For any other random database errors
            $error_msg = "Database error: " . $e->getMessage();
        }
        
        // Safely redirect back with the error message
        header("Location: User_Management_Student.php?error=" . urlencode($error_msg));
    }
    exit;
}

if ($action === 'edit') {
    $id   = intval($_POST['student_id']);
    $name = trim($_POST['name']);
    $prog = trim($_POST['programme']);
    $stmt = $conn->prepare("UPDATE student SET Name = ?, Programme = ? WHERE StudentID = ?");
    $stmt->bind_param("ssi", $name, $prog, $id);
    $stmt->execute();
    header("Location: User_Management_Student.php?success=Student updated successfully");
    exit;
}

if ($action === 'delete') {
    $id = intval($_POST['student_id']);
    $stmt = $conn->prepare("DELETE FROM student WHERE StudentID = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        header("Location: User_Management_Student.php?success=Student deleted");
    } else {
        header("Location: User_Management_Student.php?error=Cannot delete (student has linked records)");
    }
    exit;
}
?>
