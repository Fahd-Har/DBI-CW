<?php
require_once 'db_connect.php';
require_once 'auth_check.php';
requireRole('admin');

/**
 * Look up a company by name, inserting it if it doesn't exist.
 * Returns the CompanyID.
 */
function getOrCreateCompany($conn, $name) {
    $stmt = $conn->prepare("SELECT CompanyID FROM company WHERE CompanyName = ? LIMIT 1");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    if ($res) return $res['CompanyID'];

    $ins = $conn->prepare("INSERT INTO company (CompanyName) VALUES (?)");
    $ins->bind_param("s", $name);
    $ins->execute();
    return $conn->insert_id;
}

$action = $_POST['action'] ?? '';

if ($action === 'add') {
    $studentId    = intval($_POST['student_id']);
    $lecturerId   = intval($_POST['lecturer_id']);
    $supervisorId = intval($_POST['supervisor_id']);
    $company      = trim($_POST['company']);
    $start        = $_POST['start_date'];
    $end          = $_POST['end_date'];
    $duration     = (strtotime($end) - strtotime($start)) / (60*60*24*30); // months, rough
    $duration     = max(1, (int)round($duration));

    try {
        $companyId = getOrCreateCompany($conn, $company);

        $stmt = $conn->prepare("
          INSERT INTO internship (StudentID, LecturerID, SupervisorID, CompanyID, Duration, Start_Date, End_Date)
          VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("iiiiiss", $studentId, $lecturerId, $supervisorId, $companyId, $duration, $start, $end);
        $stmt->execute();
        header("Location: Internship_management.php?success=Internship added successfully");
    } catch (mysqli_sql_exception $e) {
        $msg = "Database error: " . $e->getMessage();
        header("Location: Internship_management.php?error=" . urlencode($msg));
    }
    exit;
}

if ($action === 'edit') {
    $id           = intval($_POST['internship_id']);
    $lecturerId   = intval($_POST['lecturer_id']);
    $supervisorId = intval($_POST['supervisor_id']);
    $company      = trim($_POST['company']);
    $start        = $_POST['start_date'];
    $end          = $_POST['end_date'];
    $duration     = (strtotime($end) - strtotime($start)) / (60*60*24*30);
    $duration     = max(1, (int)round($duration));

    try {
        $companyId = getOrCreateCompany($conn, $company);

        $stmt = $conn->prepare("
          UPDATE internship
          SET LecturerID = ?, SupervisorID = ?, CompanyID = ?, Duration = ?, Start_Date = ?, End_Date = ?
          WHERE InternshipID = ?
        ");
        $stmt->bind_param("iiiissi", $lecturerId, $supervisorId, $companyId, $duration, $start, $end, $id);
        $stmt->execute();
        header("Location: Internship_management.php?success=Internship updated successfully");
    } catch (mysqli_sql_exception $e) {
        header("Location: Internship_management.php?error=" . urlencode("Database error: " . $e->getMessage()));
    }
    exit;
}

if ($action === 'delete') {
    $id = intval($_POST['internship_id']);
    try {
        // Clean up assessment rows first (they FK to internship)
        $d1 = $conn->prepare("DELETE FROM assessment WHERE InternshipID = ?");
        $d1->bind_param("i", $id);
        $d1->execute();

        $stmt = $conn->prepare("DELETE FROM internship WHERE InternshipID = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        header("Location: Internship_management.php?success=Internship deleted");
    } catch (mysqli_sql_exception $e) {
        header("Location: Internship_management.php?error=Cannot delete internship");
    }
    exit;
}
?>
