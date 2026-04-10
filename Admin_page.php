<?php
require_once 'db_connect.php';
require_once 'auth_check.php';
requireRole('admin');

function getCount($conn, $sql) {
    $result = $conn->query($sql);
    if ($result) return $result->fetch_assoc()['c'];
    return 0;
}

$totalStudents    = getCount($conn, "SELECT COUNT(*) as c FROM student");
$totalAssessors   = getCount($conn, "SELECT (SELECT COUNT(*) FROM lecturer) + (SELECT COUNT(*) FROM supervisor) as c");
$totalInternships = getCount($conn, "SELECT COUNT(*) as c FROM internship");

// Pending = internships missing EITHER a lecturer or supervisor assessment
$totalPending = getCount($conn, "
  SELECT COUNT(*) as c FROM internship i
  WHERE NOT EXISTS (
    SELECT 1 FROM assessment a WHERE a.InternshipID = i.InternshipID AND a.LecturerID IS NOT NULL
  )
  OR NOT EXISTS (
    SELECT 1 FROM assessment a WHERE a.InternshipID = i.InternshipID AND a.SupervisorID IS NOT NULL
  )
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Dashboard – Internship Result Management</title>
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
    <li><a href="Admin_page.php" class="active"><img src="home.png" alt="" class="icon"/> Dashboard</a></li>
    <li><a href="User_Management_Student.php"><img src="users.png" alt="" class="icon"/> Student Management</a></li>
    <li><a href="User_Management_Lecturer.php"><img src="users.png" alt="" class="icon"/> Lecturer Management</a></li>
    <li><a href="User_Management_IndustrySupervisor.php"><img src="users.png" alt="" class="icon"/> Industry Supervisor Management</a></li>
    <li><a href="Internship_management.php"><img src="internship.png" alt="" class="icon"/> Internship Management</a></li>
    <li><a href="Result_viewing.php"><img src="results.png" alt="" class="icon"/> Result Viewing</a></li>
  </ul>
  <div class="sidebar-footer">Logged in as<br><span><?= htmlspecialchars($_SESSION['username']) ?></span>&nbsp;·&nbsp;<a href="logout.php" style="color:#e74c3c;text-decoration:none;">Logout</a></div>
</aside>

<main class="main">
  <div class="topbar">
    <div class="topbar-title">
      <h1>Admin Dashboard</h1>
      <p>Welcome back!</p>
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
      <div class="stat-label">Total Students</div>
      <div class="stat-value"><?= htmlspecialchars($totalStudents) ?></div>
      <div class="stat-note">Registered in system</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Assessors</div>
      <div class="stat-value"><?= htmlspecialchars($totalAssessors) ?></div>
      <div class="stat-note">Active accounts</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Internships</div>
      <div class="stat-value"><?= htmlspecialchars($totalInternships) ?></div>
      <div class="stat-note">Currently assigned</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Pending Results</div>
      <div class="stat-value"><?= htmlspecialchars($totalPending) ?></div>
      <div class="stat-note">Awaiting assessment</div>
    </div>
  </div>

  <div class="section-label">Manage Modules</div>
  <div class="modules-grid">

    <div class="module-card user">
      <div class="mod-icon"><img src="users.png" alt="" class="icon"/></div>
      <div>
        <div class="mod-title">User Management</div>
        <div class="mod-desc">Add, update, and remove student profiles and assessor accounts.</div>
      </div>
      <div class="mod-actions">
        <span class="tag">Add Student</span>
        <span class="tag">Add Assessor</span>
        <span class="tag">Edit / Delete</span>
      </div>
      <button class="mod-btn" onclick="window.location.href='User_Management_Student.php'">Manage Student Profiles →</button>
      <button class="mod-btn" onclick="window.location.href='User_Management_Lecturer.php'">Manage Lecturer Profiles →</button>
      <button class="mod-btn" onclick="window.location.href='User_Management_IndustrySupervisor.php'">Manage Industry Supervisor Profiles →</button>
    </div>

    <div class="module-card intern" onclick="window.location.href='Internship_management.php'">
      <div class="mod-icon"><img src="internship.png" alt="" class="icon"/></div>
      <div>
        <div class="mod-title">Internship Management</div>
        <div class="mod-desc">Assign students to assessors, record company details, and track internship placements across all programmes.</div>
      </div>
      <div class="mod-actions">
        <span class="tag">Assign Assessor</span>
        <span class="tag">Company Details</span>
        <span class="tag">Track Status</span>
      </div>
      <button class="mod-btn">Open →</button>
    </div>

    <div class="module-card result" onclick="window.location.href='Result_viewing.php'">
      <div class="mod-icon"><img src="results.png" alt="" class="icon"/></div>
      <div>
        <div class="mod-title">Result Viewing</div>
        <div class="mod-desc">View complete internship results for all students. Search and filter by student ID, name, or programme.</div>
      </div>
      <div class="mod-actions">
        <span class="tag">View All Results</span>
        <span class="tag">Search & Filter</span>
        <span class="tag">Breakdown</span>
      </div>
      <button class="mod-btn">Open →</button>
    </div>

  </div>
</main>

</body>
</html>
