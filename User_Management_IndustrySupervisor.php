<?php
require_once 'db_connect.php';
require_once 'auth_check.php';
requireRole('admin');

$companies = $conn->query("
  SELECT CompanyID, CompanyName, Location, Sector 
  FROM company 
  ORDER BY CompanyID
");

$success = $_GET['success'] ?? '';
$error   = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Industry Supervisor Profiles – UniTrack</title>
  <link rel="stylesheet" href="style.css"/>
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
    <li><a href="User_Management_IndustrySupervisor.php" class="active"><img src="users.png" alt="" class="icon"/> Industry Supervisor Management</a></li>
    <li><a href="Internship_management.php"><img src="internship.png" alt="" class="icon"/> Internship Management</a></li>
    <li><a href="Result_viewing.php"><img src="results.png" alt="" class="icon"/> Result Viewing</a></li>
  </ul>
  <div class="sidebar-footer">Logged in as<br><span><?= htmlspecialchars($_SESSION['username']) ?></span>&nbsp;·&nbsp;<a href="logout.php" style="color:#e74c3c;text-decoration:none;">Logout</a></div>
</aside>

<main class="main">

  <div class="topbar">
    <div class="topbar-title">
      <div class="breadcrumb"><a href="Admin_page.php">Dashboard</a> / Supervisor Management</div>
      <h1>Industry Supervisor Profiles</h1>
      <p>Add new or edit existing supervisor profiles.</p>
    </div>
    <button class="btn-primary" onclick="openModal()">＋ Add New Supervisor</button>
  </div>

  <?php if ($success): ?>
    <div style="background:#d4f7f4;color:#0f9b8e;padding:12px 16px;border-radius:10px;margin-bottom:16px;font-size:.84rem;"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div style="background:#fde8e8;color:#e74c3c;padding:12px 16px;border-radius:10px;margin-bottom:16px;font-size:.84rem;"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="filter-bar">
    <input type="text" id="searchInput" placeholder="🔍  Search by supervisor ID or name…" oninput="filterTable()"/>
    <button class="btn-search" onclick="filterTable()">Search</button>
  </div>

  <div class="table-card">
  <div class="table-header">
    <span>Company Profiles</span>
    <span class="table-count" id="recordCount"><?= $companies->num_rows ?> Records</span>
  </div>
  <table>
    <thead>
      <tr>
        <th>Company ID</th>
        <th>Company Name</th>
        <th>Location</th>
        <th>Sector</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody id="tableBody">
      <?php while ($row = $companies->fetch_assoc()): ?>
      <tr data-id="<?= $row['CompanyID'] ?>"
          data-name="<?= htmlspecialchars($row['CompanyName'] ?? '') ?>"
          data-location="<?= htmlspecialchars($row['Location'] ?? '') ?>"
          data-sector="<?= htmlspecialchars($row['Sector'] ?? '') ?>">
        <td><?= $row['CompanyID'] ?></td>
        <td><?= htmlspecialchars($row['CompanyName'] ?? '') ?></td>
        <td><?= htmlspecialchars($row['Location'] ?? '-') ?></td>
        <td><?= htmlspecialchars($row['Sector'] ?? '-') ?></td>
        <td>
          <button class="btn-edit" onclick="editRow(this)">Edit</button>
          <form method="POST" action="company_actions.php" style="display:inline" onsubmit="return confirm('Delete this company?')">
            <input type="hidden" name="action" value="delete"/>
            <input type="hidden" name="company_id" value="<?= $row['CompanyID'] ?>"/>
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
      <span id="modalHeading">Add Company</span>
      <span class="badge-teal" id="modalBadge">New</span>
    </div>
    <form method="POST" action="company_actions.php">
      <input type="hidden" name="action" id="formAction" value="add"/>
      <input type="hidden" name="company_id" id="fCompanyIdHidden" value=""/>

      <div class="form-group">
        <label>Company Name *</label>
        <input type="text" name="company_name" id="fCompanyName" placeholder="e.g. Maybank" required/>
      </div>

      <div class="form-group">
        <label>Location</label>
        <input type="text" name="location" id="fLocation" placeholder="e.g. Kuala Lumpur"/>
      </div>

      <div class="form-group">
        <label>Sector</label>
        <input type="text" name="sector" id="fSector" placeholder="e.g. Banking"/>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
        <button type="submit" class="btn-save">Save Company</button>
      </div>
    </form>
  </div>
</div>

<script>
  function openModal() {
    document.getElementById('formAction').value = 'add';
    document.getElementById('fCompanyIdHidden').value = '';
    document.getElementById('modalHeading').textContent = 'Add Company';
    document.getElementById('fCompanyName').value = '';
    document.getElementById('fLocation').value = '';
    document.getElementById('fSector').value = '';
    document.getElementById('modalOverlay').classList.add('open');
  }

  function editRow(btn) {
    const tr = btn.closest('tr');
    document.getElementById('formAction').value = 'edit';
    document.getElementById('fCompanyIdHidden').value = tr.dataset.id;
    document.getElementById('modalHeading').textContent = 'Edit Company';
  
    document.getElementById('fCompanyName').value = tr.dataset.name;
    document.getElementById('fLocation').value = tr.dataset.location;
    document.getElementById('fSector').value = tr.dataset.sector;
  
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