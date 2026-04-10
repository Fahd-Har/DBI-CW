<?php
require_once 'db_connect.php';
require_once 'auth_check.php';
requireRole('admin');

$internships = $conn->query("
  SELECT i.InternshipID, i.StudentID, s.Name AS StudentName, s.Programme,
         c.CompanyName, c.CompanyID,
         l.Name AS LecturerName, i.LecturerID,
         sv.Name AS SupervisorName, i.SupervisorID,
         i.Start_Date, i.End_Date
  FROM internship i
  JOIN student s   ON s.StudentID = i.StudentID
  LEFT JOIN company c    ON c.CompanyID = i.CompanyID
  LEFT JOIN lecturer l   ON l.LecturerID = i.LecturerID
  LEFT JOIN supervisor sv ON sv.SupervisorID = i.SupervisorID
  ORDER BY i.InternshipID DESC
");

$students   = $conn->query("SELECT StudentID, Name FROM student ORDER BY Name");
$lecturers  = $conn->query("SELECT LecturerID, Name FROM lecturer ORDER BY Name");
$supervisors= $conn->query("SELECT SupervisorID, Name FROM supervisor ORDER BY Name");

$success = $_GET['success'] ?? '';
$error   = $_GET['error'] ?? '';

function statusFromDates($start, $end) {
    $today = date('Y-m-d');
    if ($today < $start) return 'Pending';
    if ($today > $end)   return 'Completed';
    return 'Active';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Internship Management – UniTrack</title>
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
    <li><a href="Internship_management.php" class="active"><img src="internship.png" alt="" class="icon"/> Internship Management</a></li>
    <li><a href="Result_viewing.php"><img src="results.png" alt="" class="icon"/> Result Viewing</a></li>
  </ul>
  <div class="sidebar-footer">Logged in as<br><span><?= htmlspecialchars($_SESSION['username']) ?></span>&nbsp;·&nbsp;<a href="logout.php" style="color:#e74c3c;text-decoration:none;">Logout</a></div>
</aside>

<main class="main">

  <div class="topbar">
    <div class="topbar-title">
      <div class="breadcrumb"><a href="Admin_page.php">Dashboard</a> / Internship Management</div>
      <h1>Internship Management</h1>
      <p>Assign students to assessors and manage company placements.</p>
    </div>
    <button class="btn-primary" onclick="openModal()">＋ Add Internship</button>
  </div>

  <?php if ($success): ?>
    <div style="background:#d4f7f4;color:#0f9b8e;padding:12px 16px;border-radius:10px;margin-bottom:16px;font-size:.84rem;"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div style="background:#fde8e8;color:#e74c3c;padding:12px 16px;border-radius:10px;margin-bottom:16px;font-size:.84rem;"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="filter-bar">
    <input type="text" id="searchInput" placeholder="🔍  Search by student ID or name…" oninput="filterTable()"/>
    <select id="statusFilter" onchange="filterTable()">
      <option value="">All Statuses</option>
      <option value="Active">Active</option>
      <option value="Pending">Pending</option>
      <option value="Completed">Completed</option>
    </select>
    <button class="btn-search" onclick="filterTable()">Search</button>
  </div>

  <div class="table-card">
    <div class="table-header">
      <span>Internship Records</span>
      <span class="table-count" id="recordCount"><?= $internships->num_rows ?> Records</span>
    </div>
    <table>
      <thead>
        <tr>
          <th>Student</th>
          <th>Programme</th>
          <th>Company</th>
          <th>Lecturer</th>
          <th>Supervisor</th>
          <th>Start Date</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="tableBody">
        <?php while ($row = $internships->fetch_assoc()):
          $status = statusFromDates($row['Start_Date'], $row['End_Date']);
          $badgeClass = $status === 'Active' ? 'badge-active' : ($status === 'Completed' ? 'badge-done' : 'badge-pending');
        ?>
        <tr data-id="<?= $row['InternshipID'] ?>"
            data-student-id="<?= $row['StudentID'] ?>"
            data-name="<?= htmlspecialchars($row['StudentName']) ?>"
            data-status="<?= $status ?>"
            data-company="<?= htmlspecialchars($row['CompanyName']) ?>"
            data-lecturer="<?= $row['LecturerID'] ?>"
            data-supervisor="<?= $row['SupervisorID'] ?>"
            data-start="<?= $row['Start_Date'] ?>"
            data-end="<?= $row['End_Date'] ?>">
          <td>
            <div class="student-cell">
              <div class="stu-avatar"><?= strtoupper(substr($row['StudentName'],0,2)) ?></div>
              <div><div class="stu-name"><?= htmlspecialchars($row['StudentName']) ?></div><div class="stu-id"><?= $row['StudentID'] ?></div></div>
            </div>
          </td>
          <td><?= htmlspecialchars($row['Programme']) ?></td>
          <td><?= htmlspecialchars($row['CompanyName']) ?></td>
          <td><?= htmlspecialchars($row['LecturerName'] ?? '-') ?></td>
          <td><?= htmlspecialchars($row['SupervisorName'] ?? '-') ?></td>
          <td><?= date('d M Y', strtotime($row['Start_Date'])) ?></td>
          <td><span class="badge <?= $badgeClass ?>"><?= $status ?></span></td>
          <td>
            <div class="action-btns">
              <button class="btn-edit" onclick="editRow(this)">Edit</button>
              <form method="POST" action="internship_actions.php" style="display:inline" onsubmit="return confirm('Delete this internship?')">
                <input type="hidden" name="action" value="delete"/>
                <input type="hidden" name="internship_id" value="<?= $row['InternshipID'] ?>"/>
                <button type="submit" class="btn-del">Delete</button>
              </form>
            </div>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

</main>

<div class="modal-overlay" id="modalOverlay" onclick="closeModalOutside(event)">
  <div class="modal">
    <div class="modal-title">
      <span id="modalHeading">Add Internship</span>
      <span class="badge-teal" id="modalBadge">New</span>
    </div>
    <form method="POST" action="internship_actions.php">
      <input type="hidden" name="action" id="formAction" value="add"/>
      <input type="hidden" name="internship_id" id="fInternshipId" value=""/>

      <div class="form-row">
        <div class="form-group">
          <label>Student *</label>
          <select name="student_id" id="fStudentId" required>
            <option value="">Select student</option>
            <?php while ($s = $students->fetch_assoc()): ?>
              <option value="<?= $s['StudentID'] ?>"><?= $s['StudentID'] ?> – <?= htmlspecialchars($s['Name']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Lecturer *</label>
          <select name="lecturer_id" id="fLecturer" required>
            <option value="">Select lecturer</option>
            <?php while ($l = $lecturers->fetch_assoc()): ?>
              <option value="<?= $l['LecturerID'] ?>"><?= htmlspecialchars($l['Name']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Industry Supervisor *</label>
          <select name="supervisor_id" id="fSupervisor" required>
            <option value="">Select supervisor</option>
            <?php while ($sv = $supervisors->fetch_assoc()): ?>
              <option value="<?= $sv['SupervisorID'] ?>"><?= htmlspecialchars($sv['Name']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Company Name *</label>
          <input type="text" name="company" id="fCompany" placeholder="e.g. Maybank" required/>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Start Date *</label>
          <input type="date" name="start_date" id="fStartDate" required/>
        </div>
        <div class="form-group">
          <label>End Date *</label>
          <input type="date" name="end_date" id="fEndDate" required/>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
        <button type="submit" class="btn-save">Save Record</button>
      </div>
    </form>
  </div>
</div>

<script>
  function openModal() {
    document.getElementById('formAction').value = 'add';
    document.getElementById('fInternshipId').value = '';
    document.getElementById('modalHeading').textContent = 'Add Internship';
    document.getElementById('modalBadge').textContent = 'New';
    document.getElementById('fStudentId').value = '';
    document.getElementById('fStudentId').disabled = false;
    document.getElementById('fLecturer').value = '';
    document.getElementById('fSupervisor').value = '';
    document.getElementById('fCompany').value = '';
    document.getElementById('fStartDate').value = '';
    document.getElementById('fEndDate').value = '';
    document.getElementById('modalOverlay').classList.add('open');
  }

  function editRow(btn) {
    const tr = btn.closest('tr');
    document.getElementById('formAction').value = 'edit';
    document.getElementById('fInternshipId').value = tr.dataset.id;
    document.getElementById('modalHeading').textContent = 'Edit Internship';
    document.getElementById('modalBadge').textContent = 'Editing';
    document.getElementById('fStudentId').value = tr.dataset.studentId;
    document.getElementById('fStudentId').disabled = true;
    document.getElementById('fLecturer').value = tr.dataset.lecturer;
    document.getElementById('fSupervisor').value = tr.dataset.supervisor;
    document.getElementById('fCompany').value = tr.dataset.company;
    document.getElementById('fStartDate').value = tr.dataset.start;
    document.getElementById('fEndDate').value = tr.dataset.end;
    document.getElementById('modalOverlay').classList.add('open');
  }

  function closeModal() { document.getElementById('modalOverlay').classList.remove('open'); }
  function closeModalOutside(e) { if (e.target === document.getElementById('modalOverlay')) closeModal(); }

  function filterTable() {
    const q  = document.getElementById('searchInput').value.toLowerCase();
    const st = document.getElementById('statusFilter').value;
    let vis = 0;
    document.querySelectorAll('#tableBody tr').forEach(row => {
      const matchQ  = !q  || row.dataset.name.toLowerCase().includes(q) || row.dataset.studentId.includes(q);
      const matchSt = !st || row.dataset.status === st;
      const show = matchQ && matchSt;
      row.style.display = show ? '' : 'none';
      if (show) vis++;
    });
    document.getElementById('recordCount').textContent = vis + ' Record' + (vis !== 1 ? 's' : '');
  }
</script>
</body>
</html>
