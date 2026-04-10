<?php
require_once 'db_connect.php';
require_once 'auth_check.php';
requireRole('admin');

// Each internship has at most one lecturer assessment row and one supervisor assessment row.
// LecturerTotal  = sum of the 8 criteria from the lecturer row
// SupervisorTotal = sum of the 8 criteria from the supervisor row
// FinalMark = (LecturerTotal + SupervisorTotal) / 2
$sql = "
  SELECT
    i.InternshipID, s.StudentID, s.Name AS StudentName, s.Programme,
    c.CompanyName,
    l.Name AS LecturerName,
    sv.Name AS SupervisorName,
    (
      SELECT (IFNULL(UndertakingTaskOrProject,0)+IFNULL(HealthSafetyWorkplace,0)+IFNULL(ConnectivityTheoreticalKnowledge,0)
              +IFNULL(PresentationWrittenDocument,0)+IFNULL(ClarityLanguageIllustration,0)+IFNULL(LifelongLearningActivities,0)
              +IFNULL(ProjectManagement,0)+IFNULL(TimeManagement,0))
      FROM assessment a WHERE a.InternshipID = i.InternshipID AND a.LecturerID IS NOT NULL LIMIT 1
    ) AS LecturerTotal,
    (
      SELECT (IFNULL(UndertakingTaskOrProject,0)+IFNULL(HealthSafetyWorkplace,0)+IFNULL(ConnectivityTheoreticalKnowledge,0)
              +IFNULL(PresentationWrittenDocument,0)+IFNULL(ClarityLanguageIllustration,0)+IFNULL(LifelongLearningActivities,0)
              +IFNULL(ProjectManagement,0)+IFNULL(TimeManagement,0))
      FROM assessment a WHERE a.InternshipID = i.InternshipID AND a.SupervisorID IS NOT NULL LIMIT 1
    ) AS SupervisorTotal,
    (
      SELECT COUNT(*) FROM assessment a WHERE a.InternshipID = i.InternshipID AND a.LecturerID IS NOT NULL
    ) AS HasLecturer,
    (
      SELECT COUNT(*) FROM assessment a WHERE a.InternshipID = i.InternshipID AND a.SupervisorID IS NOT NULL
    ) AS HasSupervisor
  FROM internship i
  JOIN student s ON s.StudentID = i.StudentID
  LEFT JOIN company c    ON c.CompanyID = i.CompanyID
  LEFT JOIN lecturer l   ON l.LecturerID = i.LecturerID
  LEFT JOIN supervisor sv ON sv.SupervisorID = i.SupervisorID
  ORDER BY s.Name
";
$results = $conn->query($sql);

// Summary stats
$totalAssessed = 0; $sumFinal = 0; $highest = null; $pending = 0; $passCount = 0;
$rows = [];
while ($r = $results->fetch_assoc()) {
    $hasBoth = $r['HasLecturer'] && $r['HasSupervisor'];
    $r['FullyAssessed'] = $hasBoth;
    if ($hasBoth) {
        $r['FinalMark'] = round((($r['LecturerTotal'] + $r['SupervisorTotal']) / 2), 1);
        $totalAssessed++;
        $sumFinal += $r['FinalMark'];
        if ($highest === null || $r['FinalMark'] > $highest['FinalMark']) $highest = $r;
        if ($r['FinalMark'] >= 50) $passCount++;
    } else {
        $r['FinalMark'] = null;
        $pending++;
    }
    $rows[] = $r;
}
$avgFinal = $totalAssessed > 0 ? round($sumFinal / $totalAssessed, 1) : null;
$passRate = $totalAssessed > 0 ? round(($passCount / $totalAssessed) * 100) : null;

function grade($m) {
    if ($m === null) return '-';
    if ($m >= 80) return 'A';
    if ($m >= 65) return 'B';
    if ($m >= 50) return 'C';
    if ($m >= 40) return 'D';
    return 'F';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Result Viewing – UNM</title>
  <link rel="stylesheet" href="style.css"/>
</head>
<body>

<aside class="sidebar">
  <div class="sidebar-logo">
    <img src="nottingham-university-logo.png" alt="UNM Logo" class="logo-img"/>
    UNM
  </div>
  <div class="sidebar-sub">Internship Result Management System</div>
  <ul class="sidebar-nav">
    <li><a href="Admin_page.php"><img src="home.png" alt="" class="icon"/> Dashboard</a></li>
    <li><a href="User_Management_Student.php"><img src="users.png" alt="" class="icon"/> Student Management</a></li>
    <li><a href="User_Management_Lecturer.php"><img src="users.png" alt="" class="icon"/> Lecturer Management</a></li>
    <li><a href="User_Management_IndustrySupervisor.php"><img src="users.png" alt="" class="icon"/> Industry Supervisor Management</a></li>
    <li><a href="Internship_management.php"><img src="internship.png" alt="" class="icon"/> Internship Management</a></li>
    <li><a href="Result_viewing.php" class="active"><img src="results.png" alt="" class="icon"/> Result Viewing</a></li>
  </ul>
  <div class="sidebar-footer">Logged in as<br><span><?= htmlspecialchars($_SESSION['username']) ?></span>&nbsp;·&nbsp;<a href="logout.php" style="color:#e74c3c;text-decoration:none;">Logout</a></div>
</aside>

<main class="main">

  <div class="topbar">
    <div class="topbar-title">
      <div class="breadcrumb"><a href="Admin_page.php">Dashboard</a> / Result Viewing</div>
      <h1>Result Viewing</h1>
      <p>View and analyse internship assessment results for all students.</p>
    </div>
    <div class="topbar-admin">
      <div class="avatar">AD</div>
      <div>
        <div class="name">Administrator</div>
        <div class="role">System Admin</div>
      </div>
    </div>
  </div>

  <div class="stats-row">
    <div class="stat-card">
      <div class="stat-label">Fully Assessed</div>
      <div class="stat-value"><?= $totalAssessed ?></div>
      <div class="stat-note">Both marks entered</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Average Score</div>
      <div class="stat-value"><?= $avgFinal ?? '–' ?></div>
      <div class="stat-note">Out of 100</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Highest Score</div>
      <div class="stat-value"><?= $highest ? $highest['FinalMark'] : '–' ?></div>
      <div class="stat-note"><?= $highest ? htmlspecialchars($highest['StudentName']) : '–' ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Pass Rate</div>
      <div class="stat-value"><?= $passRate !== null ? $passRate.'%' : '–' ?></div>
      <div class="stat-note">Score ≥ 50</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Pending</div>
      <div class="stat-value"><?= $pending ?></div>
      <div class="stat-note">Not fully assessed</div>
    </div>
  </div>

  <div class="filter-bar">
    <input type="text" id="searchInput" placeholder="🔍  Search by ID, name or company…" oninput="filterTable()"/>
    <select id="progFilter" onchange="filterTable()">
      <option value="">All Programmes</option>
      <option value="CS">CS</option>
      <option value="Engineering">Engineering</option>
      <option value="Finance">Finance</option>
      <option value="Maths">Maths</option>
    </select>
    <button class="btn-search" onclick="filterTable()">Search</button>
  </div>

  <div class="table-card">
    <div class="table-header">
      <span>Assessment Results</span>
      <span class="table-count" id="recordCount"><?= count($rows) ?> Records</span>
    </div>
    <table>
      <thead>
        <tr>
          <th>Student</th>
          <th>Programme</th>
          <th>Company</th>
          <th>Lecturer</th>
          <th>Supervisor</th>
          <th>Lecturer Mark</th>
          <th>Supervisor Mark</th>
          <th>Final Mark</th>
          <th>Grade</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody id="tableBody">
        <?php foreach ($rows as $r): ?>
        <tr data-id="<?= $r['StudentID'] ?>"
            data-name="<?= htmlspecialchars($r['StudentName']) ?>"
            data-prog="<?= htmlspecialchars($r['Programme']) ?>"
            data-company="<?= htmlspecialchars($r['CompanyName']) ?>">
          <td>
            <div class="student-cell">
              <div class="stu-avatar"><?= strtoupper(substr($r['StudentName'],0,2)) ?></div>
              <div><div class="stu-name"><?= htmlspecialchars($r['StudentName']) ?></div><div class="stu-id"><?= $r['StudentID'] ?></div></div>
            </div>
          </td>
          <td><?= htmlspecialchars($r['Programme']) ?></td>
          <td><?= htmlspecialchars($r['CompanyName'] ?? '-') ?></td>
          <td><?= htmlspecialchars($r['LecturerName'] ?? '-') ?></td>
          <td><?= htmlspecialchars($r['SupervisorName'] ?? '-') ?></td>
          <td><?= $r['HasLecturer']   ? $r['LecturerTotal']   : '<em style="color:var(--muted)">Pending</em>' ?></td>
          <td><?= $r['HasSupervisor'] ? $r['SupervisorTotal'] : '<em style="color:var(--muted)">Pending</em>' ?></td>
          <td><?= $r['FullyAssessed'] ? '<b>'.$r['FinalMark'].'</b>' : '<em style="color:var(--muted)">Pending</em>' ?></td>
          <td><?= grade($r['FinalMark']) ?></td>
          <td>
            <?php if ($r['FullyAssessed']): ?>
              <span class="badge badge-done">Assessed</span>
            <?php else: ?>
              <span class="badge badge-pending">Pending</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

</main>

<script>
  function filterTable() {
    const q    = document.getElementById('searchInput').value.toLowerCase();
    const prog = document.getElementById('progFilter').value;
    let vis = 0;
    document.querySelectorAll('#tableBody tr').forEach(row => {
      const matchQ = !q || row.dataset.name.toLowerCase().includes(q) || row.dataset.id.includes(q) || (row.dataset.company||'').toLowerCase().includes(q);
      const matchP = !prog || row.dataset.prog === prog;
      const show = matchQ && matchP;
      row.style.display = show ? '' : 'none';
      if (show) vis++;
    });
    document.getElementById('recordCount').textContent = vis + ' Record' + (vis !== 1 ? 's' : '');
  }
</script>
</body>
</html>
