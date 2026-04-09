<?php
require_once 'db_connect.php';
require_once 'auth_check.php';
requireRole('admin');

$students = $conn->query("SELECT * FROM student ORDER BY StudentID");
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
    <img src="nottingham-university-logo.png" alt="UNM Logo" class="logo-img"/>
    UNM
  </div>
  <div class="sidebar-sub">Internship Result Management System</div>
  <ul class="sidebar-nav">
    <li><a href="Admin_page.php"><img src="home.png" alt="" class="icon"/> Dashboard</a></li>
    <li><a href="User_Management_Student.php" class="active"><img src="users.png" alt="" class="icon"/> Student Management</a></li>
    <li><a href="User_Management_Assessor.php"><img src="users.png" alt="" class="icon"/> Assessor Management</a></li>
    <li><a href="Internship_management.php"><img src="internship.png" alt="" class="icon"/> Internship Management</a></li>
    <li><a href="Result_viewing.php"><img src="results.png" alt="" class="icon"/> Result Viewing</a></li>
  </ul>
  <div class="sidebar-footer">Logged in as<br><span><?= htmlspecialchars($_SESSION['username']) ?></span>&nbsp;·&nbsp;<a href="logout.php" style="color:#e74c3c;text-decoration:none;">Logout</a></div>
</aside>

<!-- MAIN -->
<main class="main">

  <!-- TOPBAR -->
  <div class="topbar">
    <div class="topbar-title">
      <div class="breadcrumb"><a href="Admin_page.php">Dashboard</a> / Student Management</div>
      <h1>Student Profiles</h1>
      <p>Add new or edit existing student profiles.</p>
    </div>
    <button class="btn-primary" onclick="openModal()">＋ Add New Student</button>
  </div>

   <?php if ($success): ?>
    <div style="background:#d4f7f4;color:#0f9b8e;padding:12px 16px;border-radius:10px;margin-bottom:16px;font-size:.84rem;"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div style="background:#fde8e8;color:#e74c3c;padding:12px 16px;border-radius:10px;margin-bottom:16px;font-size:.84rem;"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>


  <!-- SEARCH BAR -->
  <div class="filter-bar">
    <input type="text" id="searchInput" placeholder="🔍  Search by student ID or name…" oninput="filterTable()"/>
    <select id="programmeFilter" onchange="filterTable()">
      <option value="">All Programmes</option>
      <option value="CS">Computer Science</option>
      <option value="Maths">Mathematics</option>
      <option value="Engineering">Engineering</option>
      <option value="Finance">Finance</option>
    </select>
    <button class="btn-search" onclick="filterTable()">Search</button>
  </div>

    <!-- INTERNSHIP TABLE -->
     <!-- TABLE -->
  <div class="table-card">
    <div class="table-header"><span>Student Profiles</span><span class="table-count" id="recordCount"><?= $students->num_rows ?> Records</span></div>
    <table>
      <thead><tr><th>Student ID</th><th>Full Name</th><th>Programme</th><th>Actions</th></tr></thead>
      <tbody id="tableBody">
        <?php while ($row = $students->fetch_assoc()): ?>
        <tr data-id="<?= $row['StudentID'] ?>" data-name="<?= htmlspecialchars($row['Name']) ?>" data-programme="<?= htmlspecialchars($row['Programme']) ?>">
          <td><?= $row['StudentID'] ?></td>
          <td><?= htmlspecialchars($row['Name']) ?></td>
          <td><?= htmlspecialchars($row['Programme']) ?></td>
          <td>
            <button class="btn-edit" onclick="editRow(this)">Edit</button>
            <form method="POST" action="student_actions.php" style="display:inline" onsubmit="return confirm('Delete this student?')">
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

<!-- MODAL: Add / Edit Internship -->
<div class="modal-overlay" id="modalOverlay" onclick="closeModalOutside(event)">
  <div class="modal">
    <div class="modal-title"><span id="modalHeading">Add Student</span><span class="badge-teal" id="modalBadge">New</span></div>
    <form method="POST" action="student_actions.php">
      <input type="hidden" name="action" id="formAction" value="add"/>
      <div class="form-group"><label>Student ID *</label><input type="number" name="student_id" id="fStudentId" placeholder="e.g. 20001" required/></div>
      <div class="form-group"><label>Student Name *</label><input type="text" name="name" id="fStudentName" placeholder="Full name" required/></div>
      <div class="form-group"><label>Programme *</label>
        <select name="programme" id="fProgramme" required>
          <option value="">Select programme</option>
          <option>CS</option><option>Maths</option><option>Engineering</option><option>Finance</option>
        </select>
      </div>
      <div class="modal-footer"><button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button><button type="submit" class="btn-save">Save Record</button></div>
    </form>
  </div>
</div>

<script>
  /* ── MODAL ── */
 
  function openModal() {
    document.getElementById('formAction').value = 'add';       
    document.getElementById('modalHeading').textContent = 'Add Student';
    document.getElementById('modalBadge').textContent = 'New';
    document.getElementById('fStudentId').value = '';
    document.getElementById('fStudentId').readOnly = false;    
    document.getElementById('fStudentName').value = '';
    document.getElementById('fProgramme').value = '';
    document.getElementById('modalOverlay').classList.add('open');
}

  function editRow(btn) {
    const tr = btn.closest('tr');
    document.getElementById('formAction').value = 'edit';      
    document.getElementById('modalHeading').textContent = 'Edit Student';
    document.getElementById('modalBadge').textContent = 'Editing';
    document.getElementById('fStudentId').value = tr.dataset.id;
    document.getElementById('fStudentId').readOnly = true;       
    document.getElementById('fStudentName').value = tr.dataset.name;
    document.getElementById('fProgramme').value = tr.dataset.programme;
    document.getElementById('modalOverlay').classList.add('open');
}

  function closeModal() { document.getElementById('modalOverlay').classList.remove('open'); }
  function closeModalOutside(e) { if (e.target === document.getElementById('modalOverlay')) closeModal(); }

  /* ── FILTER ── */
  function filterTable() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    const prog = document.getElementById('programmeFilter').value;

    let vis = 0;

    document.querySelectorAll('#tableBody tr').forEach(row => {
      const match = !q || row.dataset.name.toLowerCase().includes(q) || row.dataset.id.includes(q);
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
