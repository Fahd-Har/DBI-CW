<?php
require_once 'db_connect.php';
require_once 'auth_check.php';
requireRole('lecturer');

$lecturerId   = intval($_SESSION['assessor_id']);
$internshipId = intval($_POST['internship_id']);
$task         = intval($_POST['task']);
$safety       = intval($_POST['safety']);
$knowledge    = intval($_POST['knowledge']);
$report       = intval($_POST['report']);
$language     = intval($_POST['language']);
$learning     = intval($_POST['learning']);
$project      = intval($_POST['project']);
$time         = intval($_POST['time']);
$comments     = trim($_POST['comments'] ?? '');

// Safety: ensure this internship actually belongs to this lecturer
$chk = $conn->prepare("SELECT InternshipID FROM internship WHERE InternshipID = ? AND LecturerID = ?");
$chk->bind_param("ii", $internshipId, $lecturerId);
$chk->execute();
if (!$chk->get_result()->fetch_assoc()) {
    header("Location: LecturerStudentList.php?error=Not authorised for this internship");
    exit;
}

$total = $task + $safety + $knowledge + $report + $language + $learning + $project + $time;

try {
    // Check if a lecturer assessment row already exists
    $q = $conn->prepare("SELECT AssessmentID FROM assessment WHERE InternshipID = ? AND LecturerID = ? LIMIT 1");
    $q->bind_param("ii", $internshipId, $lecturerId);
    $q->execute();
    $existing = $q->get_result()->fetch_assoc();

    if ($existing) {
        $stmt = $conn->prepare("
          UPDATE assessment SET
            UndertakingTaskOrProject = ?, HealthSafetyWorkplace = ?, ConnectivityTheoreticalKnowledge = ?,
            PresentationWrittenDocument = ?, ClarityLanguageIllustration = ?, LifelongLearningActivities = ?,
            ProjectManagement = ?, TimeManagement = ?, TotalMarks = ?, Comments = ?
          WHERE AssessmentID = ?
        ");
        $stmt->bind_param("iiiiiiiiisi",
            $task, $safety, $knowledge, $report, $language, $learning, $project, $time, $total, $comments, $existing['AssessmentID']);
    } else {
        $stmt = $conn->prepare("
          INSERT INTO assessment
            (InternshipID, LecturerID,
             UndertakingTaskOrProject, HealthSafetyWorkplace, ConnectivityTheoreticalKnowledge,
             PresentationWrittenDocument, ClarityLanguageIllustration, LifelongLearningActivities,
             ProjectManagement, TimeManagement, TotalMarks, Comments)
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("iiiiiiiiiiis",
            $internshipId, $lecturerId,
            $task, $safety, $knowledge, $report, $language, $learning, $project, $time, $total, $comments);
    }
    $stmt->execute();

    header("Location: LecturerStudentList.php?success=Marks saved successfully");
} catch (mysqli_sql_exception $e) {
    header("Location: LecturerStudentList.php?error=" . urlencode("Database error: " . $e->getMessage()));
}
exit;
?>
