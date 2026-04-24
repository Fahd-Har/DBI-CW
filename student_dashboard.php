<?php
require_once 'db_connect.php';
require_once 'auth_check.php';
requireRole('student');

// ── Fetch this student's record linked to the logged-in UserID ──
$userID = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT StudentID, Name, Programme FROM student WHERE UserID = ?");
$stmt->bind_param("i", $userID);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if (!$student) {
    header("Location: logout.php?error=" . urlencode("Your account is not linked to a student record. Please contact Admin."));
    exit;
}

$studentID = $student['StudentID'];

// ── Fetch internship + assessment data for this student ──
$sql = "
  SELECT
    i.InternshipID,
    i.Start_Date, i.End_Date, i.Duration,
    c.CompanyName, c.Location AS CompanyLocation, c.Sector,
    l.Name  AS LecturerName,
    sv.Name AS SupervisorName,
    (
      SELECT (IFNULL(UndertakingTaskOrProject,0)+IFNULL(HealthSafetyWorkplace,0)
              +IFNULL(ConnectivityTheoreticalKnowledge,0)+IFNULL(PresentationWrittenDocument,0)
              +IFNULL(ClarityLanguageIllustration,0)+IFNULL(LifelongLearningActivities,0)
              +IFNULL(ProjectManagement,0)+IFNULL(TimeManagement,0))
      FROM assessment a WHERE a.InternshipID = i.InternshipID AND a.LecturerID IS NOT NULL LIMIT 1
    ) AS LecturerTotal,
    (
      SELECT (IFNULL(UndertakingTaskOrProject,0)+IFNULL(HealthSafetyWorkplace,0)
              +IFNULL(ConnectivityTheoreticalKnowledge,0)+IFNULL(PresentationWrittenDocument,0)
              +IFNULL(ClarityLanguageIllustration,0)+IFNULL(LifelongLearningActivities,0)
              +IFNULL(ProjectManagement,0)+IFNULL(TimeManagement,0))
      FROM assessment a WHERE a.InternshipID = i.InternshipID AND a.SupervisorID IS NOT NULL LIMIT 1
    ) AS SupervisorTotal,
    (SELECT COUNT(*) FROM assessment a WHERE a.InternshipID = i.InternshipID AND a.LecturerID   IS NOT NULL) AS HasLecturer,
    (SELECT COUNT(*) FROM assessment a WHERE a.InternshipID = i.InternshipID AND a.SupervisorID IS NOT NULL) AS HasSupervisor
  FROM internship i
  LEFT JOIN company    c  ON c.CompanyID    = i.CompanyID
  LEFT JOIN lecturer   l  ON l.LecturerID   = i.LecturerID
  LEFT JOIN supervisor sv ON sv.SupervisorID = i.SupervisorID
  WHERE i.StudentID = ?
  ORDER BY i.Start_Date DESC
";
$stmt2 = $conn->prepare($sql);
$stmt2->bind_param("i", $studentID);
$stmt2->execute();
$internships = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

// ── Fetch per-criteria breakdown for each internship ──
$breakdown = [];
if ($internships) {
    $ids = implode(',', array_map('intval', array_column($internships, 'InternshipID')));
    $bSql = "
        SELECT InternshipID, LecturerID, SupervisorID,
               UndertakingTaskOrProject, HealthSafetyWorkplace, ConnectivityTheoreticalKnowledge,
               PresentationWrittenDocument, ClarityLanguageIllustration, LifelongLearningActivities,
               ProjectManagement, TimeManagement, Comments
        FROM assessment
        WHERE InternshipID IN ($ids)
    ";
    $bRes = $conn->query($bSql);
    while ($b = $bRes->fetch_assoc()) {
        $iid = $b['InternshipID'];
        if (!isset($breakdown[$iid])) $breakdown[$iid] = ['lec' => null, 'sup' => null];
        if ($b['LecturerID']   !== null) $breakdown[$iid]['lec'] = $b;
        if ($b['SupervisorID'] !== null) $breakdown[$iid]['sup'] = $b;
    }
}

function grade($m) {
    if ($m === null) return ['label' => '–', 'class' => ''];
    if ($m >= 80)    return ['label' => 'A', 'class' => 'grade-a'];
    if ($m >= 70)    return ['label' => 'B', 'class' => 'grade-b'];
    if ($m >= 60)    return ['label' => 'C', 'class' => 'grade-c'];
    if ($m >= 50)    return ['label' => 'D', 'class' => 'grade-d'];
    if ($m >= 40)    return ['label' => 'E', 'class' => 'grade-e'];
    return                  ['label' => 'F', 'class' => 'grade-f'];
}

// Compute finals for the first (most recent) internship for the summary cards
$latestInternship = $internships[0] ?? null;
$finalMark = null;
if ($latestInternship && $latestInternship['HasLecturer'] && $latestInternship['HasSupervisor']) {
    $finalMark = round(($latestInternship['LecturerTotal'] + $latestInternship['SupervisorTotal']) / 2, 1);
}
$gradeInfo = grade($finalMark);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>My Results – UNM Internship</title>
  <link rel="stylesheet" href="style.css"/>
  <style>
    /* ── Student-specific overrides ── */
    body { display: flex; min-height: 100vh; }

    /* Sidebar — deep navy with subtle gradient */
    .sidebar {
      background: linear-gradient(180deg, #0d2137 0%, #0a1a2e 100%);
      overflow: hidden;
      border-right: 1px solid rgba(255,255,255,.06);
    }

    /* Logo — white card background behind the crest */
    .sidebar-logo { font-size: 1.4rem; gap: 12px; margin-bottom: 4px; }
    .sidebar-logo .logo-img {
      width: 48px; height: 48px;
      background: #ffffff;
      border-radius: 10px;
      padding: 5px;
      box-shadow: 0 2px 10px rgba(0,0,0,.35);
      object-fit: contain;
      flex-shrink: 0;
    }

    /* Sidebar subtitle */
    .sidebar-sub {
      font-size: .7rem !important;
      color: rgba(255,255,255,.35) !important;
      letter-spacing: .08em;
      margin-bottom: 32px !important;
    }

    /* Nav links */
    .sidebar-nav a        { color: rgba(255,255,255,.55); border-radius: 10px; transition: all .2s; }
    .sidebar-nav a.active { background: rgba(23,195,178,.15); color: var(--teal); border-left: 3px solid var(--teal); }
    .sidebar-nav a:hover  { background: rgba(255,255,255,.07); color: rgba(255,255,255,.9); }

    /* Role badge */
    .role-badge {
      display: inline-block;
      background: rgba(23,195,178,.18);
      color: var(--teal);
      font-size:.68rem; font-weight:700;
      padding: 3px 10px; border-radius:99px;
      text-transform: uppercase; letter-spacing:.07em;
      margin-top: 5px;
      border: 1px solid rgba(23,195,178,.3);
    }

    /* Sidebar footer — brighter, clearer logout button */
    .sidebar-footer {
      font-size: .78rem !important;
      color: rgba(255,255,255,.55) !important;
      padding-top: 16px !important;
      border-top: 1px solid rgba(255,255,255,.08) !important;
    }
    .sidebar-footer span { color: #fff !important; font-weight: 600; }
    .logout-btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      margin-top: 12px;
      color: #ff6b6b !important;
      text-decoration: none !important;
      font-size: .82rem;
      font-weight: 600;
      background: rgba(231,76,60,.12);
      border: 1px solid rgba(231,76,60,.3);
      padding: 7px 14px;
      border-radius: 8px;
      transition: background .2s, border-color .2s, color .2s;
      width: 100%;
      justify-content: center;
      box-sizing: border-box;
    }
    .logout-btn:hover {
      background: rgba(231,76,60,.22) !important;
      border-color: rgba(231,76,60,.55) !important;
      color: #ff9f9f !important;
    }

    /* Main layout */
    .main { margin-left: 240px; padding: 32px 40px; flex: 1; }

    /* Welcome banner */
    .welcome-banner {
      background: linear-gradient(135deg, #0d1b2a 0%, #1a3a5c 100%);
      border-radius: 20px;
      padding: 32px 36px;
      margin-bottom: 28px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 20px;
      position: relative;
      overflow: hidden;
    }
    .welcome-banner::before {
      content: '';
      position: absolute;
      width: 300px; height: 300px;
      border-radius: 50%;
      background: rgba(23,195,178,.07);
      top: -100px; right: -80px;
    }
    .welcome-banner::after {
      content: '';
      position: absolute;
      width: 200px; height: 200px;
      border-radius: 50%;
      background: rgba(46,134,222,.08);
      bottom: -60px; left: 30%;
    }
    .wb-left { position:relative; z-index:1; }
    .wb-greeting { font-size:.85rem; color:rgba(255,255,255,.5); text-transform:uppercase; letter-spacing:.08em; margin-bottom:6px; }
    .wb-name { font-family:'Sora',sans-serif; font-size:1.8rem; font-weight:700; color:#fff; margin-bottom:4px; }
    .wb-prog { font-size:.88rem; color:rgba(255,255,255,.5); }
    .wb-right { position:relative; z-index:1; text-align:center; }
    .wb-score-ring {
      width: 110px; height: 110px;
      border-radius: 50%;
      border: 3px solid rgba(23,195,178,.35);
      display: flex; flex-direction:column;
      align-items:center; justify-content:center;
      background: rgba(255,255,255,.07);
      backdrop-filter: blur(8px);
      box-shadow: 0 0 0 6px rgba(23,195,178,.08), inset 0 1px 0 rgba(255,255,255,.1);
    }
    .wb-score-num { font-family:'Sora',sans-serif; font-size:1.75rem; font-weight:700; color:#fff; line-height:1; }
    .wb-score-label { font-size:.6rem; color:rgba(255,255,255,.5); text-transform:uppercase; letter-spacing:.08em; margin-top:4px; }

    /* Summary cards */
    .summary-cards {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 16px;
      margin-bottom: 28px;
    }
    .sum-card {
      background: var(--white);
      border-radius: 16px;
      padding: 22px 24px;
      box-shadow: 0 2px 12px rgba(0,0,0,.06);
      border-top: 3px solid var(--border);
      transition: transform .2s, box-shadow .2s;
    }
    .sum-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,.1); }
    .sum-card.lec   { border-top-color: var(--accent); }
    .sum-card.sup   { border-top-color: var(--teal); }
    .sum-card.final { border-top-color: var(--gold); }
    .sum-card.grade { border-top-color: #8b5cf6; }
    .sum-label { font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:var(--muted); margin-bottom:8px; }
    .sum-val   { font-family:'Sora',sans-serif; font-size:2rem; font-weight:700; color:var(--navy); line-height:1; }
    .sum-note  { font-size:.74rem; color:var(--muted); margin-top:6px; }

    /* Grade colours */
    .grade-a { color: var(--success); }
    .grade-b { color: var(--accent); }
    .grade-c { color: var(--gold); }
    .grade-d { color: #f39c12; }
    .grade-e { color: #e67e22; }
    .grade-f { color: var(--danger); }

    /* Section title */
    .section-title {
      font-family:'Sora',sans-serif; font-size:1.1rem; font-weight:700;
      color:var(--navy); margin-bottom:16px;
      display:flex; align-items:center; gap:10px;
    }
    .section-title::after {
      content:''; flex:1; height:1px; background:var(--border);
    }

    /* Internship card */
    .internship-card {
      background: var(--white);
      border-radius: 20px;
      box-shadow: 0 2px 16px rgba(0,0,0,.07);
      margin-bottom: 28px;
      overflow: hidden;
      border: 1px solid var(--border-light);
    }
    .ic-header {
      background: linear-gradient(90deg, #f8fafc 0%, #ffffff 100%);
      border-bottom: 1px solid var(--border);
      padding: 22px 28px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
    }
    .ic-company { font-family:'Sora',sans-serif; font-size:1.05rem; font-weight:700; color:var(--navy); }
    .ic-meta    { font-size:.79rem; color:var(--muted); margin-top:4px; }
    .ic-body    { padding: 28px; }

    /* Criteria grid */
    .criteria-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
      margin-bottom: 20px;
    }
    .crit-row {
      background: var(--light);
      border-radius: 12px;
      padding: 14px 16px;
      border: 1px solid var(--border-light);
      transition: background .15s;
    }
    .crit-row:hover { background: #e8edf3; }
    .crit-name  { font-size:.79rem; font-weight:600; color:var(--text); margin-bottom:10px; }
    .crit-bars  { display:flex; flex-direction:column; gap:6px; }
    .crit-bar-row { display:flex; align-items:center; gap:8px; font-size:.74rem; }
    .crit-bar-label { width:32px; color:var(--muted); font-weight:700; flex-shrink:0; font-size:.72rem; }
    .crit-bar-wrap  { flex:1; background:rgba(0,0,0,.08); border-radius:99px; height:8px; overflow:hidden; }
    .crit-bar       { height:100%; border-radius:99px; transition:width .6s cubic-bezier(.4,0,.2,1); }
    .bar-lec  { background: linear-gradient(90deg, #2e86de, #5aa3e8); }
    .bar-sup  { background: linear-gradient(90deg, #17c3b2, #4dd9cc); }
    .crit-val { width:28px; text-align:right; font-weight:700; color:var(--navy); font-size:.82rem; flex-shrink:0; }
    .crit-max { font-size:.7rem; color:var(--muted); margin-left:2px; }

    /* Totals row inside card */
    .ic-totals {
      display:flex; gap:12px;
      padding: 0 0 16px;
    }
    .ic-total-box {
      flex:1; border-radius:12px; padding:14px 16px;
      display:flex; flex-direction:column; gap:3px;
    }
    .ic-total-box.lec   { background:#eef2ff; border:1px solid #c7d2fe; }
    .ic-total-box.sup   { background:#e6faf8; border:1px solid #99e6df; }
    .ic-total-box.final { background:#fffbeb; border:1px solid #fde68a; }
    .ic-total-label { font-size:.72rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em; color:var(--muted); }
    .ic-total-val   { font-family:'Sora',sans-serif; font-size:1.5rem; font-weight:700; color:var(--navy); }
    .ic-total-note  { font-size:.7rem; color:var(--muted); }

    /* Assessor legend */
    .ic-legend {
      display:flex; gap:16px; margin-bottom:16px; flex-wrap:wrap;
    }
    .legend-item {
      display:flex; align-items:center; gap:6px;
      font-size:.78rem; color:var(--muted); font-weight:500;
    }
    .legend-dot {
      width:10px; height:10px; border-radius:50%;
      flex-shrink:0;
    }

    /* Comments section */
    .ic-comments { display:flex; gap:12px; margin-top:4px; }
    .ic-comment  { flex:1; }
    .ic-comment-label { font-size:.74rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; margin-bottom:5px; }
    .ic-comment-box   {
      background:var(--light); border-radius:10px;
      padding:12px 14px; font-size:.82rem; color:var(--text);
      line-height:1.55; min-height:54px;
    }

    /* Pending state */
    .pending-banner {
      background: #fffbeb;
      border: 1px solid #fde68a;
      border-radius: 12px;
      padding: 16px 20px;
      font-size:.86rem;
      color:#92400e;
      display:flex; align-items:center; gap:10px;
    }

    /* No internship state */
    .empty-state {
      text-align:center; padding: 60px 40px;
      color: var(--muted);
    }
    .empty-state svg { opacity:.3; margin-bottom:16px; }
    .empty-state h3  { font-family:'Sora',sans-serif; font-size:1.1rem; color:var(--navy); margin-bottom:8px; }

    /* Status badge */
    .status-pill {
      display:inline-flex; align-items:center; gap:6px;
      padding:4px 12px; border-radius:99px; font-size:.74rem; font-weight:700;
    }
    .status-pill.assessed { background:#e6faf8; color:#0f766e; }
    .status-pill.pending  { background:#fffbeb; color:#92400e; }
    .status-dot { width:6px; height:6px; border-radius:50%; background:currentColor; }
  </style>
</head>
<body>

<!-- ── SIDEBAR ── -->
<aside class="sidebar">
  <div class="sidebar-logo">
    <img src="nottingham-university-logo.png" alt="UNM Logo" class="logo-img"/>
    UNM
  </div>
  <div class="sidebar-sub">Internship Result Management</div>
  <ul class="sidebar-nav">
    <li><a href="student_dashboard.php" class="active">
      <img src="results.png" alt="" class="icon"/> My Results
    </a></li>
  </ul>
  <div class="sidebar-footer">
    Logged in as<br>
    <span><?= htmlspecialchars($_SESSION['username']) ?></span>
    <div class="role-badge">Student</div>
    <br>
    <a href="logout.php" class="logout-btn">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      Logout
    </a>
  </div>
</aside>

<!-- ── MAIN CONTENT ── -->
<main class="main">

  <!-- Welcome banner -->
  <div class="welcome-banner">
    <div class="wb-left">
      <div class="wb-greeting">Welcome back</div>
      <div class="wb-name"><?= htmlspecialchars($student['Name']) ?></div>
      <div class="wb-prog"><?= htmlspecialchars($student['Programme']) ?> · Student ID: <?= $student['StudentID'] ?></div>
    </div>
    <div class="wb-right">
      <div class="wb-score-ring">
        <?php if ($finalMark !== null): ?>
          <div class="wb-score-num <?= $gradeInfo['class'] ?>"><?= $finalMark ?></div>
          <div class="wb-score-label">Final Score</div>
        <?php else: ?>
          <div class="wb-score-num" style="font-size:1.1rem;color:rgba(255,255,255,.4)">–</div>
          <div class="wb-score-label">Pending</div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <?php if ($latestInternship): ?>

  <!-- Summary cards (most recent internship) -->
  <div class="summary-cards">
    <div class="sum-card lec">
      <div class="sum-label">Lecturer Score</div>
      <div class="sum-val">
        <?= $latestInternship['HasLecturer'] ? $latestInternship['LecturerTotal'] : '–' ?>
      </div>
      <div class="sum-note">
        <?= $latestInternship['LecturerName'] ? htmlspecialchars($latestInternship['LecturerName']) : 'Not assigned' ?>
      </div>
    </div>
    <div class="sum-card sup">
      <div class="sum-label">Supervisor Score</div>
      <div class="sum-val">
        <?= $latestInternship['HasSupervisor'] ? $latestInternship['SupervisorTotal'] : '–' ?>
      </div>
      <div class="sum-note">
        <?= $latestInternship['SupervisorName'] ? htmlspecialchars($latestInternship['SupervisorName']) : 'Not assigned' ?>
      </div>
    </div>
    <div class="sum-card final">
      <div class="sum-label">Final Mark</div>
      <div class="sum-val"><?= $finalMark ?? '–' ?></div>
      <div class="sum-note">Average of both assessors</div>
    </div>
    <div class="sum-card grade">
      <div class="sum-label">Grade</div>
      <div class="sum-val <?= $gradeInfo['class'] ?>"><?= $gradeInfo['label'] ?></div>
      <div class="sum-note">
        <?php
          if ($finalMark === null) echo 'Awaiting assessment';
          elseif ($finalMark >= 40) echo 'Pass';
          else echo 'Fail';
        ?>
      </div>
    </div>
  </div>

  <!-- Internship results (one card per internship) -->
  <div class="section-title">My Internship Assessment<?= count($internships) > 1 ? 's' : '' ?></div>

  <?php foreach ($internships as $intern):
    $iid   = $intern['InternshipID'];
    $lec   = $breakdown[$iid]['lec'] ?? null;
    $sup   = $breakdown[$iid]['sup'] ?? null;
    $hasL  = (bool)$intern['HasLecturer'];
    $hasS  = (bool)$intern['HasSupervisor'];
    $both  = $hasL && $hasS;
    $fMark = $both ? round(($intern['LecturerTotal'] + $intern['SupervisorTotal']) / 2, 1) : null;
    $gi    = grade($fMark);

    $criteria = [
      ['key'=>'UndertakingTaskOrProject',         'label'=>'Undertaking Tasks / Projects',        'max'=>10],
      ['key'=>'HealthSafetyWorkplace',             'label'=>'Health & Safety at Workplace',         'max'=>10],
      ['key'=>'ConnectivityTheoreticalKnowledge',  'label'=>'Connectivity & Theoretical Knowledge', 'max'=>10],
      ['key'=>'PresentationWrittenDocument',        'label'=>'Presentation of Written Report',       'max'=>15],
      ['key'=>'ClarityLanguageIllustration',        'label'=>'Clarity of Language & Illustration',   'max'=>10],
      ['key'=>'LifelongLearningActivities',         'label'=>'Lifelong Learning Activities',         'max'=>15],
      ['key'=>'ProjectManagement',                  'label'=>'Project Management',                   'max'=>15],
      ['key'=>'TimeManagement',                     'label'=>'Time Management',                      'max'=>15],
    ];
  ?>
  <div class="internship-card">
    <!-- Card header -->
    <div class="ic-header">
      <div>
        <div class="ic-company">
          <?= htmlspecialchars($intern['CompanyName'] ?? 'Company Not Set') ?>
        </div>
        <div class="ic-meta">
          <?= htmlspecialchars($intern['CompanyLocation'] ?? '') ?>
          <?= $intern['Sector'] ? ' · '.htmlspecialchars($intern['Sector']) : '' ?>
          · <?= date('d M Y', strtotime($intern['Start_Date'])) ?>
          – <?= date('d M Y', strtotime($intern['End_Date'])) ?>
        </div>
      </div>
      <div style="display:flex;align-items:center;gap:12px;">
        <span class="status-pill <?= $both ? 'assessed' : 'pending' ?>">
          <span class="status-dot"></span>
          <?= $both ? 'Fully Assessed' : 'Pending' ?>
        </span>
        <?php if ($fMark !== null): ?>
          <span style="font-family:'Sora',sans-serif;font-size:1.5rem;font-weight:700;" class="<?= $gi['class'] ?>">
            <?= $fMark ?> <span style="font-size:.85rem;color:var(--muted);font-family:'DM Sans',sans-serif;font-weight:400;">(<?= $gi['label'] ?>)</span>
          </span>
        <?php endif; ?>
      </div>
    </div>

    <div class="ic-body">

      <?php if (!$hasL && !$hasS): ?>
        <!-- No assessment at all yet -->
        <div class="pending-banner">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          Your assessment has not been entered yet. Check back after your lecturer and supervisor have submitted their scores.
        </div>

      <?php else: ?>

        <!-- Legend -->
        <div class="ic-legend">
          <?php if ($hasL): ?>
          <div class="legend-item">
            <div class="legend-dot" style="background:var(--accent)"></div>
            Lecturer: <?= htmlspecialchars($intern['LecturerName'] ?? '–') ?>
          </div>
          <?php endif; ?>
          <?php if ($hasS): ?>
          <div class="legend-item">
            <div class="legend-dot" style="background:var(--teal)"></div>
            Industry Supervisor: <?= htmlspecialchars($intern['SupervisorName'] ?? '–') ?>
          </div>
          <?php endif; ?>
        </div>

        <!-- Per-criteria breakdown grid -->
        <div class="criteria-grid">
          <?php foreach ($criteria as $c):
            $lv = $lec ? (int)$lec[$c['key']] : null;
            $sv = $sup ? (int)$sup[$c['key']] : null;
            $lpct = $lv !== null ? round(($lv / $c['max']) * 100) : 0;
            $spct = $sv !== null ? round(($sv / $c['max']) * 100) : 0;
          ?>
          <div class="crit-row">
            <div class="crit-name">
              <?= htmlspecialchars($c['label']) ?>
              <span class="crit-max">/ <?= $c['max'] ?></span>
            </div>
            <div class="crit-bars">
              <?php if ($hasL): ?>
              <div class="crit-bar-row">
                <div class="crit-bar-label" style="color:var(--accent)">Lec</div>
                <div class="crit-bar-wrap">
                  <div class="crit-bar bar-lec" style="width:<?= $lpct ?>%"></div>
                </div>
                <div class="crit-val"><?= $lv ?? '–' ?></div>
              </div>
              <?php endif; ?>
              <?php if ($hasS): ?>
              <div class="crit-bar-row">
                <div class="crit-bar-label" style="color:var(--teal)">Sup</div>
                <div class="crit-bar-wrap">
                  <div class="crit-bar bar-sup" style="width:<?= $spct ?>%"></div>
                </div>
                <div class="crit-val"><?= $sv ?? '–' ?></div>
              </div>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- Totals -->
        <div class="ic-totals">
          <?php if ($hasL): ?>
          <div class="ic-total-box lec">
            <div class="ic-total-label">Lecturer Total</div>
            <div class="ic-total-val"><?= $intern['LecturerTotal'] ?></div>
            <div class="ic-total-note">Out of 90</div>
          </div>
          <?php endif; ?>
          <?php if ($hasS): ?>
          <div class="ic-total-box sup">
            <div class="ic-total-label">Supervisor Total</div>
            <div class="ic-total-val"><?= $intern['SupervisorTotal'] ?></div>
            <div class="ic-total-note">Out of 90</div>
          </div>
          <?php endif; ?>
          <?php if ($both): ?>
          <div class="ic-total-box final">
            <div class="ic-total-label">Final Mark</div>
            <div class="ic-total-val <?= $gi['class'] ?>"><?= $fMark ?></div>
            <div class="ic-total-note">Average of both</div>
          </div>
          <?php endif; ?>
        </div>

        <!-- Comments -->
        <?php $hasComment = ($lec && !empty($lec['Comments'])) || ($sup && !empty($sup['Comments'])); ?>
        <?php if ($hasComment): ?>
        <div class="section-title" style="font-size:.85rem;margin-bottom:10px;">Assessor Remarks</div>
        <div class="ic-comments">
          <?php if ($lec): ?>
          <div class="ic-comment">
            <div class="ic-comment-label" style="color:var(--accent)">Lecturer</div>
            <div class="ic-comment-box">
              <?= htmlspecialchars($lec['Comments'] ?: '(No remarks provided)') ?>
            </div>
          </div>
          <?php endif; ?>
          <?php if ($sup): ?>
          <div class="ic-comment">
            <div class="ic-comment-label" style="color:var(--teal)">Industry Supervisor</div>
            <div class="ic-comment-box">
              <?= htmlspecialchars($sup['Comments'] ?: '(No remarks provided)') ?>
            </div>
          </div>
          <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Pending partial notice -->
        <?php if ($hasL xor $hasS): ?>
        <div class="pending-banner" style="margin-top:16px;">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          <?= $hasL ? 'Your supervisor score is still pending.' : 'Your lecturer score is still pending.' ?>
          Final mark will be shown once both assessors have submitted.
        </div>
        <?php endif; ?>

      <?php endif; // has assessments ?>
    </div>
  </div>
  <?php endforeach; ?>

  <?php else: // no internships ?>
  <div class="empty-state">
    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="var(--muted)" stroke-width="1.5">
      <rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/>
    </svg>
    <h3>No Internship Records Found</h3>
    <p>You have not been assigned to an internship yet. Please contact your administrator.</p>
  </div>
  <?php endif; ?>

</main>

</body>
</html>