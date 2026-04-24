<?php
require_once 'db_connect.php';
require_once 'auth_check.php';
requireRole('admin');


function cleanupOrphanedCompany($conn, $companyId) {
    if (!$companyId) return;
    $chk = $conn->prepare("SELECT COUNT(*) AS c FROM internship WHERE CompanyID = ?");
    $chk->bind_param("i", $companyId);
    $chk->execute();
    $count = (int)$chk->get_result()->fetch_assoc()['c'];
    if ($count === 0) {
        $del = $conn->prepare("DELETE FROM company WHERE CompanyID = ?");
        $del->bind_param("i", $companyId);
        $del->execute();
    }
}

function monthsBetween($startStr, $endStr) {
    try {
        $d1 = new DateTime($startStr);
        $d2 = new DateTime($endStr);
        if ($d2 <= $d1) return 1;
        $diff = $d1->diff($d2);
        $months = ($diff->y * 12) + $diff->m + ($diff->d >= 15 ? 1 : 0);
        return max(1, $months);
    } catch (Exception $e) {
        return 1;
    }
}

$action = $_POST['action'] ?? '';

if ($action === 'add') {
    $studentId    = intval($_POST['student_id']    ?? 0);
    $lecturerId   = intval($_POST['lecturer_id']   ?? 0);
    $supervisorId = intval($_POST['supervisor_id'] ?? 0);
    $companyId    = intval($_POST['company_id']    ?? 0);
    $start        = $_POST['start_date'] ?? '';
    $end          = $_POST['end_date']   ?? '';

    if ($studentId <= 0 || $lecturerId <= 0 || $supervisorId <= 0
        || $companyId <= 0 || $start === '' || $end === '') {
        header("Location: Internship_management.php?error=" . urlencode("All fields are required"));
        exit;
    }
    if (strtotime($end) <= strtotime($start)) {
        header("Location: Internship_management.php?error=" . urlencode("End date must be after start date"));
        exit;
    }

    $duration = monthsBetween($start, $end);

    try {
        $stmt = $conn->prepare("
          INSERT INTO internship (StudentID, LecturerID, SupervisorID, CompanyID, Duration, Start_Date, End_Date)
          VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("iiiiiss", $studentId, $lecturerId, $supervisorId, $companyId, $duration, $start, $end);
        $stmt->execute();
        header("Location: Internship_management.php?success=Internship added successfully");
    } catch (mysqli_sql_exception $e) {
        error_log("Internship add error: " . $e->getMessage());
        header("Location: Internship_management.php?error=" . urlencode("An unexpected error occurred. Please try again."));
    }
    exit;
}

if ($action === 'edit') {
    $id           = intval($_POST['internship_id'] ?? 0);
    $lecturerId   = intval($_POST['lecturer_id']   ?? 0);
    $supervisorId = intval($_POST['supervisor_id'] ?? 0);
    $companyId    = intval($_POST['company_id']    ?? 0);
    $start        = $_POST['start_date'] ?? '';
    $end          = $_POST['end_date']   ?? '';

    if ($id <= 0 || $lecturerId <= 0 || $supervisorId <= 0
        || $companyId <= 0 || $start === '' || $end === '') {
        header("Location: Internship_management.php?error=" . urlencode("All fields are required"));
        exit;
    }
    if (strtotime($end) <= strtotime($start)) {
        header("Location: Internship_management.php?error=" . urlencode("End date must be after start date"));
        exit;
    }

    $duration = monthsBetween($start, $end);

    try {
        $stmt = $conn->prepare("
          UPDATE internship
          SET LecturerID = ?, SupervisorID = ?, CompanyID = ?, Duration = ?, Start_Date = ?, End_Date = ?
          WHERE InternshipID = ?
        ");
        $stmt->bind_param("iiiissi", $lecturerId, $supervisorId, $companyId, $duration, $start, $end, $id);
        $stmt->execute();
        header("Location: Internship_management.php?success=Internship updated successfully");
    } catch (mysqli_sql_exception $e) {
        error_log("Internship edit error: " . $e->getMessage());
        header("Location: Internship_management.php?error=" . urlencode("An unexpected error occurred. Please try again."));
    }
    exit;
}

if ($action === 'delete') {
    $id = intval($_POST['internship_id'] ?? 0);
    if ($id <= 0) {
        header("Location: Internship_management.php?error=Invalid internship ID");
        exit;
    }

    $conn->begin_transaction();
    try {
    // Capture the company before we delete the internship
    $oldQ = $conn->prepare("SELECT CompanyID FROM internship WHERE InternshipID = ?");
    $oldQ->bind_param("i", $id);
    $oldQ->execute();
    $oldCompanyId = $oldQ->get_result()->fetch_assoc()['CompanyID'] ?? null;

    $g = $conn->prepare("
        DELETE gc FROM grade_classification gc
        JOIN assessment a ON a.AssessmentID = gc.AssessmentID
        WHERE a.InternshipID = ?
    ");
    $g->bind_param("i", $id);
    $g->execute();

    $d1 = $conn->prepare("DELETE FROM assessment WHERE InternshipID = ?");
    $d1->bind_param("i", $id);
    $d1->execute();

    $stmt = $conn->prepare("DELETE FROM internship WHERE InternshipID = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    // Drop the company if no other internship uses it
    cleanupOrphanedCompany($conn, $oldCompanyId);

    $conn->commit();
    header("Location: Internship_management.php?success=Internship deleted");
} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    error_log("Internship delete error: " . $e->getMessage());
    header("Location: Internship_management.php?error=" . urlencode("Cannot delete internship. Please try again."));
}
exit;
}
?>