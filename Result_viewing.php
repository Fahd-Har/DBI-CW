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
        if ($r['FinalMark'] >= 40) $passCount++;
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
    if ($m >= 70) return 'B';
    if ($m >= 60) return 'C';
    if ($m >= 50) return 'D';
    if ($m >= 40) return 'E';
    return 'F';
}

// ── Fetch per-criteria breakdown for all internships ──────────────────
// Build a map: InternshipID => ['lec' => [...], 'sup' => [...]]
$breakdown = [];
$bSql = "
  SELECT InternshipID, LecturerID, SupervisorID,
         UndertakingTaskOrProject, HealthSafetyWorkplace, ConnectivityTheoreticalKnowledge,
         PresentationWrittenDocument, ClarityLanguageIllustration, LifelongLearningActivities,
         ProjectManagement, TimeManagement, Comments
  FROM assessment
";
$bRes = $conn->query($bSql);
while ($b = $bRes->fetch_assoc()) {
    $iid = $b['InternshipID'];
    if (!isset($breakdown[$iid])) $breakdown[$iid] = ['lec' => null, 'sup' => null];
    if ($b['LecturerID'] !== null)  $breakdown[$iid]['lec'] = $b;
    if ($b['SupervisorID'] !== null) $breakdown[$iid]['sup'] = $b;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Result Viewing – UNM</title>
  <link rel="stylesheet" href="style.css"/>
  <style>
    /* ── 5-column stats row for this page ── */
    .stats-row { grid-template-columns: repeat(5, 1fr); }
    .stats-row .stat-card:nth-child(5) { border-color: var(--teal); }

    /* compact spacing so the table is visible without scrolling */
    .main    { padding: 24px 36px; }
    .topbar  { margin-bottom: 20px; }
    .stats-row { margin-bottom: 20px; }
    .filter-bar { margin-bottom: 16px; }
    .filter-bar select { min-width: 140px; }

    /* ── Breakdown modal ── */
    .bd-overlay {
      display: none;
      position: fixed; inset: 0;
      background: rgba(0,0,0,.45);
      z-index: 1000;
      align-items: center;
      justify-content: center;
      padding: 24px;
    }
    .bd-overlay.open { display: flex; }
    .bd-modal {
      background: var(--white);
      border-radius: 20px;
      padding: 36px 40px;
      width: 100%;
      max-width: 780px;
      max-height: 90vh;
      overflow-y: auto;
      box-shadow: 0 20px 60px rgba(0,0,0,.25);
      animation: bdIn .3s ease both;
    }
    @keyframes bdIn {
      from { opacity:0; transform:translateY(20px); }
      to   { opacity:1; transform:translateY(0); }
    }
    .bd-header {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      margin-bottom: 24px;
      gap: 16px;
    }
    .bd-title { font-family:'Sora',sans-serif; font-size:1.2rem; font-weight:700; color:var(--navy); }
    .bd-sub   { font-size:.82rem; color:var(--muted); margin-top:3px; }
    .bd-close {
      background:none; border:none; cursor:pointer;
      color:var(--muted); font-size:1.4rem; line-height:1;
      padding:2px 6px; border-radius:6px;
      transition: background .15s;
    }
    .bd-close:hover { background:var(--light); }

    /* Criteria comparison table */
    .bd-table { width:100%; border-collapse:collapse; font-size:.84rem; }
    .bd-table th {
      text-align:left; padding:8px 12px;
      font-size:.75rem; text-transform:uppercase; letter-spacing:.05em;
      color:var(--muted); border-bottom:2px solid var(--border);
    }
    .bd-table th.col-lec  { color: var(--accent); }
    .bd-table th.col-sup  { color: var(--teal);   }
    .bd-table td { padding:10px 12px; border-bottom:1px solid var(--border); vertical-align:middle; }
    .bd-table tr:last-child td { border-bottom:none; }
    .bd-table .criteria-name { color:var(--text); font-weight:500; }
    .bd-table .max-hint { font-size:.75rem; color:var(--muted); }

    /* Score cell with bar */
    .score-cell { display:flex; align-items:center; gap:10px; min-width:110px; }
    .score-num  { font-weight:700; color:var(--navy); min-width:26px; text-align:right; font-size:.9rem; }
    .score-bar-wrap { flex:1; background:var(--border); border-radius:99px; height:6px; overflow:hidden; }
    .score-bar { height:100%; border-radius:99px; }
    .bar-lec   { background: var(--accent); }
    .bar-sup   { background: var(--teal);   }
    .score-na  { color:var(--muted); font-style:italic; font-size:.82rem; }

    /* Totals row */
    .bd-totals { display:flex; gap:16px; margin-top:20px; }
    .bd-total-card {
      flex:1; border-radius:12px; padding:16px 20px;
      display:flex; flex-direction:column; gap:4px;
    }
    .bd-total-card.lec { background:#eef2ff; border:1px solid #c7d2fe; }
    .bd-total-card.sup { background:#e6faf8; border:1px solid #99e6df; }
    .bd-total-card.final { background:#f0fdf4; border:1px solid #86efac; }
    .bd-total-label { font-size:.75rem; text-transform:uppercase; letter-spacing:.05em; color:var(--muted); font-weight:600; }
    .bd-total-val   { font-family:'Sora',sans-serif; font-size:1.6rem; font-weight:700; color:var(--navy); }
    .bd-total-note  { font-size:.75rem; color:var(--muted); }
    .bd-comments { margin-top:16px; }
    .bd-comments-title { font-size:.78rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em; color:var(--muted); margin-bottom:6px; }
    .bd-comment-box { background:var(--light); border-radius:10px; padding:12px 14px; font-size:.83rem; color:var(--text); line-height:1.55; }
    .bd-comment-row { display:flex; gap:16px; }
    .bd-comment-row > div { flex:1; }
    .bd-comment-label { font-size:.75rem; font-weight:600; margin-bottom:4px; }
    .btn-breakdown {
      background: none;
      border: 1.5px solid var(--border);
      border-radius: 8px;
      padding: 5px 12px;
      font-size: .78rem;
      font-weight: 600;
      color: var(--navy);
      cursor: pointer;
      transition: border-color .2s, background .2s;
      white-space: nowrap;
    }
    .btn-breakdown:hover { border-color: var(--accent); background: #eef2ff; color: var(--accent); }
  </style>
</head>
<body>

<aside class="sidebar">
  <div class="sidebar-logo">
    <div class="logo-bg">
      <img src="nottingham-university-logo.png" alt="UNM Logo" class="logo-img"/>
    </div>
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
      <div class="stat-note">Score ≥ 40</div>
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
      <?php
        $programmes = $conn->query("SELECT DISTINCT Programme FROM student ORDER BY Programme");
        while ($p = $programmes->fetch_assoc()):
      ?>
        <option value="<?= htmlspecialchars($p['Programme']) ?>"><?= htmlspecialchars($p['Programme']) ?></option>
      <?php endwhile; ?>
    </select>
    <select id="markFilter" onchange="filterTable()">
      <option value="">All Final Marks</option>
      <option value="90">90 and above</option>
      <option value="80">80 and above</option>
      <option value="70">70 and above</option>
      <option value="60">60 and above</option>
      <option value="50">50 and above</option>
      <option value="40">40 and above</option>

    </select>
    <select id="gradeFilter" onchange="filterTable()">
      <option value="">All Grades</option>
      <option value="A">A</option>
      <option value="B">B</option>
      <option value="C">C</option>
      <option value="D">D</option>
      <option value="E">E</option>
      <option value="F">F</option>
    </select>
    <select id="statusFilter" onchange="filterTable()">
      <option value="">All Status</option>
      <option value="assessed">Assessed</option>
      <option value="pending">Pending</option>
    </select>
    <button class="btn-search" onclick="filterTable()">Search</button>
  </div>

  <div class="table-card">
    <div class="table-header">
      <span>Assessment Results</span>
      <span class="table-count" id="recordCount"><?= count($rows) ?> Records</span>
    </div>
    <div style="overflow-x: auto;">
    <table style="min-width: 900px;">
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
          <th>Breakdown</th>
        </tr>
      </thead>
      <tbody id="tableBody">
        <?php foreach ($rows as $r): ?>
        <tr data-id="<?= $r['StudentID'] ?>"
            data-name="<?= htmlspecialchars($r['StudentName']) ?>"
            data-prog="<?= htmlspecialchars($r['Programme']) ?>"
            data-company="<?= htmlspecialchars($r['CompanyName'] ?? '') ?>"
            data-finalmark="<?= $r['FinalMark'] !== null ? $r['FinalMark'] : '' ?>"
            data-grade="<?= $r['FinalMark'] !== null ? grade($r['FinalMark']) : '' ?>"
            data-status="<?= $r['FullyAssessed'] ? 'assessed' : 'pending' ?>">
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
          <td>
            <?php
              $iid = $r['InternshipID'];
              $lec = $breakdown[$iid]['lec'] ?? null;
              $sup = $breakdown[$iid]['sup'] ?? null;
              // Encode breakdown data as JSON for the modal
              $bdData = json_encode([
                'student'  => $r['StudentName'],
                'id'       => $r['StudentID'],
                'lecturer' => $r['LecturerName'] ?? '-',
                'supervisor' => $r['SupervisorName'] ?? '-',
                'lec' => $lec ? [
                  'task'     => (int)$lec['UndertakingTaskOrProject'],
                  'safety'   => (int)$lec['HealthSafetyWorkplace'],
                  'knowledge'=> (int)$lec['ConnectivityTheoreticalKnowledge'],
                  'report'   => (int)$lec['PresentationWrittenDocument'],
                  'language' => (int)$lec['ClarityLanguageIllustration'],
                  'learning' => (int)$lec['LifelongLearningActivities'],
                  'project'  => (int)$lec['ProjectManagement'],
                  'time'     => (int)$lec['TimeManagement'],
                  'comments' => $lec['Comments'] ?? '',
                ] : null,
                'sup' => $sup ? [
                  'task'     => (int)$sup['UndertakingTaskOrProject'],
                  'safety'   => (int)$sup['HealthSafetyWorkplace'],
                  'knowledge'=> (int)$sup['ConnectivityTheoreticalKnowledge'],
                  'report'   => (int)$sup['PresentationWrittenDocument'],
                  'language' => (int)$sup['ClarityLanguageIllustration'],
                  'learning' => (int)$sup['LifelongLearningActivities'],
                  'project'  => (int)$sup['ProjectManagement'],
                  'time'     => (int)$sup['TimeManagement'],
                  'comments' => $sup['Comments'] ?? '',
                ] : null,
              ]);
            ?>
            <button class="btn-breakdown" onclick='openBreakdown(<?= htmlspecialchars($bdData, ENT_QUOTES) ?>)'>
              View Breakdown
            </button>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div><!-- /scroll wrapper -->
  </div><!-- /table-card -->

</main>

<!-- ── BREAKDOWN MODAL ── -->
<div class="bd-overlay" id="bdOverlay" onclick="closeBdOutside(event)">
  <div class="bd-modal" id="bdModal">
    <div class="bd-header">
      <div>
        <div class="bd-title" id="bdTitle">Assessment Breakdown</div>
        <div class="bd-sub" id="bdSub"></div>
      </div>
      <button class="bd-close" onclick="closeBd()">✕</button>
    </div>

    <table class="bd-table">
      <thead>
        <tr>
          <th>Criteria</th>
          <th>Max</th>
          <th class="col-lec">Lecturer <span id="bdLecName" style="font-weight:400;text-transform:none;letter-spacing:0"></span></th>
          <th class="col-sup">Industry Supervisor <span id="bdSupName" style="font-weight:400;text-transform:none;letter-spacing:0"></span></th>
        </tr>
      </thead>
      <tbody id="bdTableBody"></tbody>
    </table>

    <div class="bd-totals">
      <div class="bd-total-card lec">
        <div class="bd-total-label">Lecturer Total</div>
        <div class="bd-total-val" id="bdLecTotal">–</div>
        <div class="bd-total-note">Out of 100</div>
      </div>
      <div class="bd-total-card sup">
        <div class="bd-total-label">Supervisor Total</div>
        <div class="bd-total-val" id="bdSupTotal">–</div>
        <div class="bd-total-note">Out of 100</div>
      </div>
      <div class="bd-total-card final">
        <div class="bd-total-label">Final Mark</div>
        <div class="bd-total-val" id="bdFinal">–</div>
        <div class="bd-total-note">Average of both</div>
      </div>
    </div>

    <div class="bd-comments" id="bdCommentsWrap">
      <div class="bd-comments-title">Remarks</div>
      <div class="bd-comment-row">
        <div>
          <div class="bd-comment-label" style="color:var(--accent)">Lecturer</div>
          <div class="bd-comment-box" id="bdLecComment">–</div>
        </div>
        <div>
          <div class="bd-comment-label" style="color:var(--teal)">Industry Supervisor</div>
          <div class="bd-comment-box" id="bdSupComment">–</div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  /* ── Filter table ── */
  function filterTable() {
    const q      = document.getElementById('searchInput').value.toLowerCase();
    const prog   = document.getElementById('progFilter').value;
    const mark   = document.getElementById('markFilter').value;
    const grade  = document.getElementById('gradeFilter').value;
    const status = document.getElementById('statusFilter').value;
    let vis = 0;
    document.querySelectorAll('#tableBody tr').forEach(row => {
      const matchQ = !q || row.dataset.name.toLowerCase().includes(q) || row.dataset.id.includes(q) || (row.dataset.company||'').toLowerCase().includes(q);
      const matchP = !prog || row.dataset.prog === prog;

      // Final Mark filter
      let matchM = true;
      if (mark) {
        const fm = row.dataset.finalmark;
        if (fm === '') {
          matchM = false;  // pending rows have no mark
        } else if (mark === '100') {
          matchM = parseFloat(fm) === 100;
        } else {
          matchM = parseFloat(fm) >= parseFloat(mark);
        }
      }

      // Grade filter
      const matchG = !grade || row.dataset.grade === grade;

      // Status filter
      const matchS = !status || row.dataset.status === status;

      const show = matchQ && matchP && matchM && matchG && matchS;
      row.style.display = show ? '' : 'none';
      if (show) vis++;
    });
    document.getElementById('recordCount').textContent = vis + ' Record' + (vis !== 1 ? 's' : '');
  }

  /* ── Breakdown modal ── */
  const CRITERIA = [
    { key:'task',      label:'Undertaking Tasks / Projects',          max:10 },
    { key:'safety',    label:'Health & Safety at Workplace',           max:10 },
    { key:'knowledge', label:'Connectivity & Theoretical Knowledge',   max:10 },
    { key:'report',    label:'Presentation of Written Report',         max:15 },
    { key:'language',  label:'Clarity of Language & Illustration',     max:10 },
    { key:'learning',  label:'Lifelong Learning Activities',           max:15 },
    { key:'project',   label:'Project Management',                     max:15 },
    { key:'time',      label:'Time Management',                        max:15 },
  ];

  function scoreCell(val, max, barClass) {
    if (val === null || val === undefined) return '<span class="score-na">Not entered</span>';
    const pct = Math.round((val / max) * 100);
    return `<div class="score-cell">
      <span class="score-num">${val}</span>
      <div class="score-bar-wrap"><div class="score-bar ${barClass}" style="width:${pct}%"></div></div>
    </div>`;
  }

  function openBreakdown(d) {
    document.getElementById('bdTitle').textContent = d.student + ' — Assessment Breakdown';
    document.getElementById('bdSub').textContent   = 'Student ID: ' + d.id;
    document.getElementById('bdLecName').textContent = d.lecturer !== '-' ? '(' + d.lecturer + ')' : '';
    document.getElementById('bdSupName').textContent = d.supervisor !== '-' ? '(' + d.supervisor + ')' : '';

    let rows = '';
    let lecSum = 0, supSum = 0;
    CRITERIA.forEach(c => {
      const lv = d.lec ? d.lec[c.key] : null;
      const sv = d.sup ? d.sup[c.key] : null;
      if (lv !== null) lecSum += lv;
      if (sv !== null) supSum += sv;
      rows += `<tr>
        <td><div class="criteria-name">${c.label}</div><div class="max-hint">Max ${c.max}</div></td>
        <td style="color:var(--muted);font-weight:600">${c.max}</td>
        <td>${scoreCell(lv, c.max, 'bar-lec')}</td>
        <td>${scoreCell(sv, c.max, 'bar-sup')}</td>
      </tr>`;
    });
    document.getElementById('bdTableBody').innerHTML = rows;

    document.getElementById('bdLecTotal').textContent = d.lec ? lecSum : '–';
    document.getElementById('bdSupTotal').textContent = d.sup ? supSum : '–';
    document.getElementById('bdFinal').textContent    = (d.lec && d.sup) ? Math.round((lecSum + supSum) / 2 * 10) / 10 : '–';

    document.getElementById('bdLecComment').textContent = (d.lec && d.lec.comments) ? d.lec.comments : '(No remarks)';
    document.getElementById('bdSupComment').textContent = (d.sup && d.sup.comments) ? d.sup.comments : '(No remarks)';

    document.getElementById('bdOverlay').classList.add('open');
  }

  function closeBd() { document.getElementById('bdOverlay').classList.remove('open'); }
  function closeBdOutside(e) { if (e.target === document.getElementById('bdOverlay')) closeBd(); }
</script>
</body>
</html>

