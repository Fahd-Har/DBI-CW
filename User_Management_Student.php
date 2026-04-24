<?php
require_once 'db_connect.php';
require_once 'auth_check.php';
requireRole('admin');

$students = $conn->query("
  SELECT s.StudentID, s.Name, s.Programme, u.Username
  FROM student s
  LEFT JOIN users u ON u.UserID = s.UserID
  ORDER BY s.StudentID
");
$success = $_GET['success'] ?? '';
$error   = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Student Profiles – UniTrack</title>
  <link rel="stylesheet" href="style.css"/>
</head>
<body>

<!-- SIDEBAR -->
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
    <li><a href="User_Management_Student.php" class="active"><img src="users.png" alt="" class="icon"/> Student Management</a></li>
    <li><a href="User_Management_Lecturer.php"><img src="users.png" alt="" class="icon"/> Lecturer Management</a></li>
    <li><a href="User_Management_IndustrySupervisor.php"><img src="users.png" alt="" class="icon"/> Industry Supervisor Management</a></li>
    <li><a href="Internship_management.php"><img src="internship.png" alt="" class="icon"/> Internship Management</a></li>
    <li><a href="Result_viewing.php"><img src="results.png" alt="" class="icon"/> Result Viewing</a></li>
  </ul>
  <div class="sidebar-footer">Logged in as<br><span><?= htmlspecialchars($_SESSION['username']) ?></span>&nbsp;·&nbsp;<a href="logout.php" style="color:#e74c3c;text-decoration:none;">Logout</a></div>
</aside>

<!-- MAIN -->
<main class="main">

  <!-- TOPBAR -->
  <div class="topbar">
    <div>
      <div class="breadcrumb"><a href="Admin_page.php">Dashboard</a> / Student Management</div>
      <h1>Student Profiles</h1>
      <p>Add new or edit existing student profiles.</p>
    </div>

    <button class="btn-primary" onclick="openModal()">＋ Add New Student</button>
  </div>

  <!-- SUCCESS / ERROR -->
  <?php if ($success): ?>
    <div style="background:#d4f7f4;color:#0f9b8e;padding:12px;border-radius:10px;margin-bottom:16px;">
      <?= htmlspecialchars($success) ?>
    </div>
  <?php endif; ?>

  <?php if ($error): ?>
    <div style="background:#fde8e8;color:#e74c3c;padding:12px;border-radius:10px;margin-bottom:16px;">
      <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <!-- FILTER -->
  <div class="filter-bar">
    <input type="text" id="searchInput" placeholder="🔍 Search by ID or name…" oninput="filterTable()"/>

    <select id="programmeFilter" onchange="filterTable()">
      <option value="">All Programmes</option>
      <?php
        $programmes = $conn->query("SELECT DISTINCT Programme FROM student ORDER BY Programme");
        while ($p = $programmes->fetch_assoc()):
      ?>
        <option value="<?= htmlspecialchars($p['Programme']) ?>">
          <?= htmlspecialchars($p['Programme']) ?>
        </option>
      <?php endwhile; ?>
    </select>

    <button class="btn-search" onclick="filterTable()">Search</button>
  </div>

  <!-- TABLE -->
  <div class="table-card">
    <div class="table-header">
      <span>Student Profiles</span>
      <span class="table-count" id="recordCount"><?= $students->num_rows ?> Records</span>
    </div>

    <table>
      <thead>
        <tr>
          <th>Student ID</th>
          <th>Full Name</th>
          <th>Programme</th>
          <th>Username</th>
          <th>Actions</th>
        </tr>
      </thead>

      <tbody id="tableBody">
        <?php while ($row = $students->fetch_assoc()): ?>
        <tr data-id="<?= $row['StudentID'] ?>"
            data-name="<?= htmlspecialchars($row['Name']) ?>"
            data-programme="<?= htmlspecialchars($row['Programme']) ?>"
            data-username="<?= htmlspecialchars($row['Username'] ?? '') ?>">

          <td><?= $row['StudentID'] ?></td>
          <td><?= htmlspecialchars($row['Name']) ?></td>
          <td><?= htmlspecialchars($row['Programme']) ?></td>
          <td><?= htmlspecialchars($row['Username'] ?? '—') ?></td>

          <td>
            <button class="btn-edit" onclick="editRow(this)">Edit</button>

            <form method="POST" action="student_actions.php"
                  style="display:inline"
                  onsubmit="return confirm('Delete this student?')">

              <input type="hidden" name="action" value="delete"/>
              <input type="hidden" name="student_id" value="<?= $row['StudentID'] ?>"/>

              <button type="submit" class="btn-del">Delete</button>
            </form>
          </td>

        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</main>

<!-- MODAL -->
<div class="modal-overlay" id="modalOverlay" onclick="closeModalOutside(event)">
  <div class="modal">

    <div class="modal-title">
      <span id="modalHeading">Add Student</span>
      <span class="badge-teal" id="modalBadge">New</span>
    </div>

    <form method="POST" action="student_actions.php">

      <input type="hidden" name="action" id="formAction" value="add"/>

      <!-- ID ONLY FOR EDIT -->
      <input type="hidden" name="student_id" id="fStudentId"/>

      <!-- NAME -->
      <div class="form-group">
        <label>Student Name *</label>
        <input type="text" name="name" id="fStudentName" required/>
      </div>

      <!-- PROGRAMME -->
      <div class="form-group">
        <label>Programme *</label>
        <select name="programme" id="fProgramme" required>
          <option value="">Select programme</option>
          <option>CS</option>
          <option>Maths</option>
          <option>Engineering</option>
          <option>Finance</option>
        </select>
      </div>

      <div class="form-group">
  <label>Username *</label>
  <input type="text" name="username" id="fUsername" required/>
</div>

<div class="form-group">
  <label>Password <span id="pwHint">*</span></label>
  <input type="text" name="password" id="fPassword"/>
  <small id="pwEditNote" style="display:none;color:#888;">Leave blank to keep the current password.</small>
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
  document.getElementById('modalHeading').textContent = 'Add Student';
  document.getElementById('modalBadge').textContent = 'New';
  document.getElementById('fStudentId').value = '';
  document.getElementById('fStudentName').value = '';
  document.getElementById('fProgramme').value = '';
  document.getElementById('fUsername').value = '';
  document.getElementById('fPassword').value = '';
  document.getElementById('fPassword').required = true;
  document.getElementById('pwHint').textContent = '*';
  document.getElementById('pwEditNote').style.display = 'none';
  document.getElementById('modalOverlay').classList.add('open');
}

function editRow(btn) {
  const tr = btn.closest('tr');
  document.getElementById('formAction').value = 'edit';
  document.getElementById('modalHeading').textContent = 'Edit Student';
  document.getElementById('modalBadge').textContent = 'Editing';
  document.getElementById('fStudentId').value = tr.dataset.id;
  document.getElementById('fStudentName').value = tr.dataset.name;
  document.getElementById('fProgramme').value = tr.dataset.programme;
  document.getElementById('fUsername').value = tr.dataset.username || '';
  document.getElementById('fPassword').value = '';
  document.getElementById('fPassword').required = false;
  document.getElementById('pwHint').textContent = '';
  document.getElementById('pwEditNote').style.display = 'block';
  document.getElementById('modalOverlay').classList.add('open');
}

function closeModal() {
  document.getElementById('modalOverlay').classList.remove('open');
}

function closeModalOutside(e) {
  if (e.target === document.getElementById('modalOverlay')) closeModal();
}

/* FILTER */
function filterTable() {
  const q = document.getElementById('searchInput').value.toLowerCase();
  const prog = document.getElementById('programmeFilter').value;

  let vis = 0;

  document.querySelectorAll('#tableBody tr').forEach(row => {
    const match =
      !q ||
      row.dataset.name.toLowerCase().includes(q) ||
      row.dataset.id.includes(q);

    const progMatch = !prog || row.dataset.programme === prog;

    const show = match && progMatch;

    row.style.display = show ? '' : 'none';

    if (show) vis++;
  });

  document.getElementById('recordCount').textContent =
    vis + ' Record' + (vis !== 1 ? 's' : '');
}
</script>

</body>
</html>