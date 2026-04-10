<?php
require_once 'db_connect.php';
require_once 'auth_check.php';
requireRole('admin');

$lecturers = $conn->query("
  SELECT l.LecturerID, l.Name, l.Department, u.Username
  FROM lecturer l
  LEFT JOIN users u ON u.UserID = l.UserID
  ORDER BY l.LecturerID
");
$success = $_GET['success'] ?? '';
$error   = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Lecturer Profiles – UniTrack</title>
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
    <li><a href="User_Management_Lecturer.php" class="active"><img src="users.png" alt="" class="icon"/> Lecturer Management</a></li>
    <li><a href="User_Management_IndustrySupervisor.php"><img src="users.png" alt="" class="icon"/> Industry Supervisor Management</a></li>
    <li><a href="Internship_management.php"><img src="internship.png" alt="" class="icon"/> Internship Management</a></li>
    <li><a href="Result_viewing.php"><img src="results.png" alt="" class="icon"/> Result Viewing</a></li>
  </ul>
  <div class="sidebar-footer">Logged in as<br><span><?= htmlspecialchars($_SESSION['username']) ?></span>&nbsp;·&nbsp;<a href="logout.php" style="color:#e74c3c;text-decoration:none;">Logout</a></div>
</aside>

<main class="main">

  <div class="topbar">
    <div class="topbar-title">
      <div class="breadcrumb"><a href="Admin_page.php">Dashboard</a> / Lecturer Management</div>
      <h1>Lecturer Profiles</h1>
      <p>Add new or edit existing lecturer profiles.</p>
    </div>
    <button class="btn-primary" onclick="openModal()">＋ Add New Lecturer</button>
  </div>

  <?php if ($success): ?>
    <div style="background:#d4f7f4;color:#0f9b8e;padding:12px 16px;border-radius:10px;margin-bottom:16px;font-size:.84rem;"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div style="background:#fde8e8;color:#e74c3c;padding:12px 16px;border-radius:10px;margin-bottom:16px;font-size:.84rem;"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="filter-bar">
    <input type="text" id="searchInput" placeholder="🔍  Search by lecturer ID or name…" oninput="filterTable()"/>
    <select id="departmentFilter" onchange="filterTable()">
      <option value="">All Departments/Schools</option>
      <option value="School of Computer and Mathematical Sciences">School of Computer and Mathematical Sciences</option>
      <option value="School of Biological and Environmental Sciences">School of Biological and Environmental Sciences</option>
      <option value="School of Pharmacy">School of Pharmacy</option>
      <option value="School of Psychology">School of Psychology</option>
      <option value="Department of Chemical & Environmental Engineering">Department of Chemical & Environmental Engineering</option>
      <option value="Department of Civil Engineering">Department of Civil Engineering</option>
      <option value="Department of Electrical and Electronic Engineering">Department of Electrical and Electronic Engineering</option>
      <option value="Department of Mechanical, Materials and Manufacturing Engineering">Department of Mechanical, Materials and Manufacturing Engineering</option>
    </select>
    <button class="btn-search" onclick="filterTable()">Search</button>
  </div>

  <div class="table-card">
    <div class="table-header">
      <span>Lecturer Profiles</span>
      <span class="table-count" id="recordCount"><?= $lecturers->num_rows ?> Records</span>
    </div>
    <table>
      <thead>
        <tr>
          <th>Lecturer ID</th>
          <th>Full Name</th>
          <th>Department/School</th>
          <th>Username</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="tableBody">
        <?php while ($row = $lecturers->fetch_assoc()): ?>
        <tr data-id="<?= $row['LecturerID'] ?>"
            data-name="<?= htmlspecialchars($row['Name']) ?>"
            data-department="<?= htmlspecialchars($row['Department']) ?>">
          <td><?= $row['LecturerID'] ?></td>
          <td><?= htmlspecialchars($row['Name']) ?></td>
          <td><?= htmlspecialchars($row['Department']) ?></td>
          <td><?= htmlspecialchars($row['Username'] ?? '-') ?></td>
          <td>
            <button class="btn-edit" onclick="editRow(this)">Edit</button>
            <form method="POST" action="lecturer_actions.php" style="display:inline" onsubmit="return confirm('Delete this lecturer?')">
              <input type="hidden" name="action" value="delete"/>
              <input type="hidden" name="lecturer_id" value="<?= $row['LecturerID'] ?>"/>
              <button type="submit" class="btn-del">Delete</button>
            </form>
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
      <span id="modalHeading">Add Lecturer</span>
      <span class="badge-teal" id="modalBadge">New</span>
    </div>
    <form method="POST" action="lecturer_actions.php">
      <input type="hidden" name="action" id="formAction" value="add"/>
      <input type="hidden" name="lecturer_id" id="fLecturerIdHidden" value=""/>

      <div class="form-group">
        <label>Lecturer Name *</label>
        <input type="text" name="name" id="fLecturerName" placeholder="Full name" required/>
      </div>

      <div class="form-group">
        <label>Department *</label>
        <select name="department" id="fDepartment" required>
          <option value="">Select department</option>
          <option value="School of Computer and Mathematical Sciences">School of Computer and Mathematical Sciences</option>
          <option value="School of Biological and Environmental Sciences">School of Biological and Environmental Sciences</option>
          <option value="School of Pharmacy">School of Pharmacy</option>
          <option value="School of Psychology">School of Psychology</option>
          <option value="Department of Chemical & Environmental Engineering">Department of Chemical & Environmental Engineering</option>
          <option value="Department of Civil Engineering">Department of Civil Engineering</option>
          <option value="Department of Electrical and Electronic Engineering">Department of Electrical and Electronic Engineering</option>
          <option value="Department of Mechanical, Materials and Manufacturing Engineering">Department of Mechanical, Materials and Manufacturing Engineering</option>
        </select>
      </div>

      <div class="form-group">
        <label>Username * <span style="font-weight:normal;color:var(--muted);font-size:.75rem;">(used to log in as Assessor)</span></label>
        <input type="text" name="username" id="fUsername" placeholder="e.g. drtan" required/>
      </div>

      <div class="form-group" id="passwordGroup">
        <label>Password *</label>
        <input type="text" name="password" id="fPassword" placeholder="Enter password"/>
        <small style="color:var(--muted);font-size:.72rem;">Leave blank when editing to keep current password.</small>
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
    document.getElementById('fLecturerIdHidden').value = '';
    document.getElementById('modalHeading').textContent = 'Add Lecturer';
    document.getElementById('modalBadge').textContent = 'New';
    document.getElementById('fLecturerName').value = '';
    document.getElementById('fDepartment').value = '';
    document.getElementById('fUsername').value = '';
    document.getElementById('fPassword').value = '';
    document.getElementById('fPassword').required = true;
    document.getElementById('modalOverlay').classList.add('open');
  }

  function editRow(btn) {
    const tr = btn.closest('tr');
    document.getElementById('formAction').value = 'edit';
    document.getElementById('fLecturerIdHidden').value = tr.dataset.id;
    document.getElementById('modalHeading').textContent = 'Edit Lecturer';
    document.getElementById('modalBadge').textContent = 'Editing';
    document.getElementById('fLecturerName').value = tr.dataset.name;
    document.getElementById('fDepartment').value = tr.dataset.department;
    document.getElementById('fUsername').value = tr.cells[3].textContent.trim() === '-' ? '' : tr.cells[3].textContent.trim();
    document.getElementById('fPassword').value = '';
    document.getElementById('fPassword').required = false;
    document.getElementById('modalOverlay').classList.add('open');
  }

  function closeModal() { document.getElementById('modalOverlay').classList.remove('open'); }
  function closeModalOutside(e) { if (e.target === document.getElementById('modalOverlay')) closeModal(); }

  function filterTable() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    const dep = document.getElementById('departmentFilter').value;
    let vis = 0;
    document.querySelectorAll('#tableBody tr').forEach(row => {
      const match = !q || row.dataset.name.toLowerCase().includes(q) || row.dataset.id.includes(q);
      const depMatch = !dep || row.dataset.department === dep;
      const show = match && depMatch;
      row.style.display = show ? '' : 'none';
      if (show) vis++;
    });
    document.getElementById('recordCount').textContent = vis + ' Record' + (vis !== 1 ? 's' : '');
  }
</script>
</body>
</html>
