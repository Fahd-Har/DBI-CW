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

/**
 * Calculate duration in months between two Y-m-d dates.
 */
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
    $studentId    = intval($_POST['student_id'] ?? 0);
    $lecturerId   = intval($_POST['lecturer_id'] ?? 0);
    $supervisorId = intval($_POST['supervisor_id'] ?? 0);
    $company      = trim($_POST['company'] ?? '');
    $start        = $_POST['start_date'] ?? '';
    $end          = $_POST['end_date'] ?? '';

    if ($studentId <= 0 || $lecturerId <= 0 || $supervisorId <= 0 || $company === '' || $start === '' || $end === '') {
        header("Location: Internship_management.php?error=" . urlencode("All fields are required"));
        exit;
    }
    if (strtotime($end) <= strtotime($start)) {
        header("Location: Internship_management.php?error=" . urlencode("End date must be after start date"));
        exit;
    }

    $duration = monthsBetween($start, $end);

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
        header("Location: Internship_management.php?error=" . urlencode("Database error: " . $e->getMessage()));
    }
    exit;
}

if ($action === 'edit') {
    $id           = intval($_POST['internship_id'] ?? 0);
    $lecturerId   = intval($_POST['lecturer_id'] ?? 0);
    $supervisorId = intval($_POST['supervisor_id'] ?? 0);
    $company      = trim($_POST['company'] ?? '');
    $start        = $_POST['start_date'] ?? '';
    $end          = $_POST['end_date'] ?? '';

    if ($id <= 0 || $lecturerId <= 0 || $supervisorId <= 0 || $company === '' || $start === '' || $end === '') {
        header("Location: Internship_management.php?error=" . urlencode("All fields are required"));
        exit;
    }
    if (strtotime($end) <= strtotime($start)) {
        header("Location: Internship_management.php?error=" . urlencode("End date must be after start date"));
        exit;
    }

    $duration = monthsBetween($start, $end);

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
    $id = intval($_POST['internship_id'] ?? 0);
    if ($id <= 0) {
        header("Location: Internship_management.php?error=Invalid internship ID");
        exit;
    }

    $conn->begin_transaction();
    try {
        // 1. delete grade_classification rows for assessments of this internship
        $g = $conn->prepare("
            DELETE gc FROM grade_classification gc
            JOIN assessment a ON a.AssessmentID = gc.AssessmentID
            WHERE a.InternshipID = ?
        ");
        $g->bind_param("i", $id);
        $g->execute();

        // 2. delete the assessments themselves
        $d1 = $conn->prepare("DELETE FROM assessment WHERE InternshipID = ?");
        $d1->bind_param("i", $id);
        $d1->execute();

        // 3. delete the internship
        $stmt = $conn->prepare("DELETE FROM internship WHERE InternshipID = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        $conn->commit();
        header("Location: Internship_management.php?success=Internship deleted");
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        header("Location: Internship_management.php?error=" . urlencode("Cannot delete internship: " . $e->getMessage()));
    }
    exit;
}
?>
