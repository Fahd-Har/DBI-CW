<?php
function letterGrade($mark) {
    if ($mark === null) return null;
    if ($mark >= 80) return 'A';
    if ($mark >= 70) return 'B';
    if ($mark >= 60) return 'C';
    if ($mark >= 50) return 'D';
    if ($mark >= 40) return 'E';
    return 'F';
}

function classifyIfComplete($conn, $internshipId) {
    $stmt = $conn->prepare("
      SELECT AssessmentID, LecturerID, SupervisorID,
             IFNULL(UndertakingTaskOrProject,0) + IFNULL(HealthSafetyWorkplace,0)
             + IFNULL(ConnectivityTheoreticalKnowledge,0) + IFNULL(PresentationWrittenDocument,0)
             + IFNULL(ClarityLanguageIllustration,0) + IFNULL(LifelongLearningActivities,0)
             + IFNULL(ProjectManagement,0) + IFNULL(TimeManagement,0) AS Total
      FROM assessment WHERE InternshipID = ?
    ");
    $stmt->bind_param("i", $internshipId);
    $stmt->execute();
    $res = $stmt->get_result();

    $lec = null; $sup = null;
    while ($row = $res->fetch_assoc()) {
        if (!empty($row['LecturerID']))   $lec = $row;
        if (!empty($row['SupervisorID'])) $sup = $row;
    }

    // Need BOTH assessments before we grade
    if (!$lec || !$sup) return;

    $final = round((($lec['Total'] + $sup['Total']) / 2), 1);
    $grade = letterGrade($final);

    foreach ([$lec['AssessmentID'], $sup['AssessmentID']] as $aid) {
        $check = $conn->prepare("SELECT GradeID FROM grade_classification WHERE AssessmentID = ? LIMIT 1");
        $check->bind_param("i", $aid);
        $check->execute();
        $existing = $check->get_result()->fetch_assoc();

        if ($existing) {
            $upd = $conn->prepare("UPDATE grade_classification SET GradeClassification = ? WHERE GradeID = ?");
            $upd->bind_param("si", $grade, $existing['GradeID']);
            $upd->execute();
        } else {
            $ins = $conn->prepare("INSERT INTO grade_classification (AssessmentID, GradeClassification) VALUES (?, ?)");
            $ins->bind_param("is", $aid, $grade);
            $ins->execute();
        }
    }
}
?>