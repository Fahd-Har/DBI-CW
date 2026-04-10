<?php
require_once 'db_connect.php';
require_once 'auth_check.php';
requireRole('lecturer');

$lecturerId = $_SESSION['assessor_id'];

// List all internships assigned to this lecturer, with the lecturer's own mark total and the supervisor's mark total
$stmt = $conn->prepare("
  SELECT
    i.InternshipID, s.StudentID, s.Name AS StudentName, s.Programme, c.CompanyName,
    lec_a.AssessmentID AS LecAssessmentID,
    lec_a.UndertakingTaskOrProject, lec_a.HealthSafetyWorkplace, lec_a.ConnectivityTheoreticalKnowledge,
    lec_a.PresentationWrittenDocument, lec_a.ClarityLanguageIllustration, lec_a.LifelongLearningActivities,
    lec_a.ProjectManagement, lec_a.TimeManagement, lec_a.Comments AS LecComments,
    (
      SELECT (IFNULL(UndertakingTaskOrProject,0)+IFNULL(HealthSafetyWorkplace,0)+IFNULL(ConnectivityTheoreticalKnowledge,0)
              +IFNULL(PresentationWrittenDocument,0)+IFNULL(ClarityLanguageIllustration,0)+IFNULL(LifelongLearningActivities,0)
              +IFNULL(ProjectManagement,0)+IFNULL(TimeManagement,0))
      FROM assessment a WHERE a.InternshipID = i.InternshipID AND a.SupervisorID IS NOT NULL LIMIT 1
    ) AS SupervisorTotal
  FROM internship i
  JOIN student s ON s.StudentID = i.StudentID
  LEFT JOIN company c ON c.CompanyID = i.CompanyID
  LEFT JOIN assessment lec_a ON lec_a.InternshipID = i.InternshipID AND lec_a.LecturerID = ?
  WHERE i.LecturerID = ?
  ORDER BY s.Name
");
$stmt->bind_param("ii", $lecturerId, $lecturerId);
$stmt->execute();
$result = $stmt->get_result();

$rows = [];
while ($r = $result->fetch_assoc()) {
    $lecTotal = null;
    if ($r['LecAssessmentID']) {
        $lecTotal = (int)$r['UndertakingTaskOrProject'] + (int)$r['HealthSafetyWorkplace'] + (int)$r['ConnectivityTheoreticalKnowledge']
                  + (int)$r['PresentationWrittenDocument'] + (int)$r['ClarityLanguageIllustration'] + (int)$r['LifelongLearningActivities']
                  + (int)$r['ProjectManagement'] + (int)$r['TimeManagement'];
    }
    $r['LecturerTotal'] = $lecTotal;
    $r['FinalMark'] = ($lecTotal !== null && $r['SupervisorTotal'] !== null)
        ? round(($lecTotal + $r['SupervisorTotal']) / 2, 1)
        : null;
    $rows[] = $r;
}

$success = $_GET['success'] ?? '';
$error   = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Lecturer – Student List</title>
  <link rel="stylesheet" href="style.css"/>
</head>
<body>

<aside class="sidebar">
  <div class="sidebar-logo">
    <img src="nottingham-university-logo.png" alt="UNM Logo" class="logo-img"/>
    UNM
  </div>
  <div class="sidebar-sub">Internship Result Entry and Mark Calculation</div>
  <ul class="sidebar-nav">
    <li><a href="LecturerStudentList.php" class="active"><img src="users.png" alt="" class="icon"/> Student List</a></li>
  </ul>
  <div class="sidebar-footer">Logged in as<br><span><?= htmlspecialchars($_SESSION['assessor_name'] ?? $_SESSION['username']) ?></span>&nbsp;·&nbsp;<a href="logout.php" style="color:#e74c3c;text-decoration:none;">Logout</a></div>
</aside>

<main class="main">

  <div class="topbar">
    <div class="topbar-title">
      <h1>Student List</h1>
      <p>View your assigned students and enter internship marks.</p>
    </div>
  </div>

  <?php if ($success): ?>
    <div style="background:#d4f7f4;color:#0f9b8e;padding:12px 16px;border-radius:10px;margin-bottom:16px;font-size:.84rem;"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div style="background:#fde8e8;color:#e74c3c;padding:12px 16px;border-radius:10px;margin-bottom:16px;font-size:.84rem;"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="filter-bar">
    <input type="text" id="searchInput" placeholder="🔍  Search by student ID or name…" oninput="filterTable()"/>
    <button class="btn-search" onclick="filterTable()">Search</button>
  </div>

  <div class="table-card">
    <div class="table-header">
      <span>List of Students</span>
      <span class="table-count" id="recordCount"><?= count($rows) ?> Records</span>
    </div>
    <table>
      <thead>
        <tr>
          <th>Student ID</th>
          <th>Student Name</th>
          <th>Programme</th>
          <th>Company</th>
          <th>Marks from Lecturer</th>
          <th>Marks from Industry Supervisor</th>
          <th>Final Mark</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody id="tableBody">
        <?php foreach ($rows as $r): ?>
        <tr data-id="<?= $r['StudentID'] ?>"
            data-name="<?= htmlspecialchars($r['StudentName']) ?>"
            data-internship="<?= $r['InternshipID'] ?>"
            data-task="<?= $r['UndertakingTaskOrProject'] ?? '' ?>"
            data-safety="<?= $r['HealthSafetyWorkplace'] ?? '' ?>"
            data-knowledge="<?= $r['ConnectivityTheoreticalKnowledge'] ?? '' ?>"
            data-report="<?= $r['PresentationWrittenDocument'] ?? '' ?>"
            data-language="<?= $r['ClarityLanguageIllustration'] ?? '' ?>"
            data-learning="<?= $r['LifelongLearningActivities'] ?? '' ?>"
            data-project="<?= $r['ProjectManagement'] ?? '' ?>"
            data-time="<?= $r['TimeManagement'] ?? '' ?>"
            data-comments="<?= htmlspecialchars($r['LecComments'] ?? '') ?>">
          <td><?= $r['StudentID'] ?></td>
          <td><?= htmlspecialchars($r['StudentName']) ?></td>
          <td><?= htmlspecialchars($r['Programme']) ?></td>
          <td><?= htmlspecialchars($r['CompanyName'] ?? '-') ?></td>
          <td><?= $r['LecturerTotal']   !== null ? $r['LecturerTotal']   : 'Pending' ?></td>
          <td><?= $r['SupervisorTotal'] !== null ? $r['SupervisorTotal'] : 'Pending' ?></td>
          <td><?= $r['FinalMark']       !== null ? $r['FinalMark']       : 'Pending' ?></td>
          <td><button class="btn-edit" onclick="openMarksModal(this)"><?= $r['LecturerTotal'] !== null ? 'Edit Marks' : 'Enter Marks' ?></button></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

</main>

<div class="modal-overlay" id="modalOverlay" onclick="closeModalOutside(event)">
  <div class="modal">
    <div class="modal-title"><span id="modalHeading">Enter Marks</span></div>
    <form method="POST" action="lecturer_assessment_actions.php">
      <input type="hidden" name="internship_id" id="fInternshipId"/>

      <div class="form-group">
        <label>Student</label>
        <input type="text" id="fStudentDisplay" readonly style="background:#f0f4f8;"/>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Undertaking Tasks/Projects (0–10)</label>
          <input type="number" name="task" id="fTask" min="0" max="10" required/>
        </div>
        <div class="form-group">
          <label>Connectivity &amp; Theoretical Knowledge (0–10)</label>
          <input type="number" name="knowledge" id="fKnowledge" min="0" max="10" required/>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Health &amp; Safety at Workplace (0–10)</label>
          <input type="number" name="safety" id="fSafety" min="0" max="10" required/>
        </div>
        <div class="form-group">
          <label>Presentation of Report (0–15)</label>
          <input type="number" name="report" id="fReport" min="0" max="15" required/>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Clarity of Language &amp; Illustration (0–10)</label>
          <input type="number" name="language" id="fLanguage" min="0" max="10" required/>
        </div>
        <div class="form-group">
          <label>Lifelong Learning Activities (0–15)</label>
          <input type="number" name="learning" id="fLearning" min="0" max="15" required/>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Project Management (0–15)</label>
          <input type="number" name="project" id="fProject" min="0" max="15" required/>
        </div>
        <div class="form-group">
          <label>Time Management (0–15)</label>
          <input type="number" name="time" id="fTime" min="0" max="15" required/>
        </div>
      </div>

      <div class="form-group">
        <label>Remarks</label>
        <textarea name="comments" id="fComments" placeholder="Optional remarks…"></textarea>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
        <button type="submit" class="btn-save">Save Marks</button>
      </div>
    </form>
  </div>
</div>

<script>
  function openMarksModal(btn) {
    const tr = btn.closest('tr');
    document.getElementById('fInternshipId').value = tr.dataset.internship;
    document.getElementById('fStudentDisplay').value = tr.dataset.id + ' – ' + tr.dataset.name;
    document.getElementById('fTask').value      = tr.dataset.task;
    document.getElementById('fSafety').value    = tr.dataset.safety;
    document.getElementById('fKnowledge').value = tr.dataset.knowledge;
    document.getElementById('fReport').value    = tr.dataset.report;
    document.getElementById('fLanguage').value  = tr.dataset.language;
    document.getElementById('fLearning').value  = tr.dataset.learning;
    document.getElementById('fProject').value   = tr.dataset.project;
    document.getElementById('fTime').value      = tr.dataset.time;
    document.getElementById('fComments').value  = tr.dataset.comments;
    document.getElementById('modalOverlay').classList.add('open');
  }

  function closeModal() { document.getElementById('modalOverlay').classList.remove('open'); }
  function closeModalOutside(e) { if (e.target === document.getElementById('modalOverlay')) closeModal(); }

  function filterTable() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    let vis = 0;
    document.querySelectorAll('#tableBody tr').forEach(row => {
      const match = !q || row.dataset.name.toLowerCase().includes(q) || row.dataset.id.includes(q);
      row.style.display = match ? '' : 'none';
      if (match) vis++;
    });
    document.getElementById('recordCount').textContent = vis + ' Record' + (vis !== 1 ? 's' : '');
  }
</script>
</body>
</html>
