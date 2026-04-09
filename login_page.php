<?php
// ── DATABASE CONNECTION ──
if (session_status() === PHP_SESSION_NONE) session_start();

// If already logged in, redirect
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') header("Location: Admin_page.php");
    elseif ($_SESSION['role'] === 'lecturer') header("Location: LecturerStudentList.php");
    else header("Location: SupervisorStudentList.php");
    exit;
}

$error = '';

// ── HANDLE LOGIN FORM SUBMISSION ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'db_connect.php';

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role     = trim($_POST['role'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        $stmt = $conn->prepare("SELECT UserID, Username, Password, Role FROM users WHERE Username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if ($user['Password'] === $password) {
                $dbRole = strtolower($user['Role']);
                $selectedRole = strtolower($role);
                $isAssessor = ($dbRole === 'lecturer' || $dbRole === 'supervisor');

                if (($selectedRole === 'admin' && $dbRole === 'admin') ||
                    ($selectedRole === 'assessor' && $isAssessor)) {

                    $_SESSION['user_id']  = $user['UserID'];
                    $_SESSION['username'] = $user['Username'];
                    $_SESSION['role']     = $user['Role'];

                    if ($dbRole === 'lecturer') {
                        $q = $conn->prepare("SELECT LecturerID, Name FROM lecturer WHERE UserID = ?");
                        $q->bind_param("i", $user['UserID']);
                        $q->execute();
                        $r = $q->get_result()->fetch_assoc();
                        $_SESSION['assessor_id']  = $r['LecturerID'];
                        $_SESSION['assessor_name'] = $r['Name'];
                        header("Location: LecturerStudentList.php");
                        exit;

                    } elseif ($dbRole === 'supervisor') {
                        $q = $conn->prepare("SELECT SupervisorID, Name FROM supervisor WHERE UserID = ?");
                        $q->bind_param("i", $user['UserID']);
                        $q->execute();
                        $r = $q->get_result()->fetch_assoc();
                        $_SESSION['assessor_id']  = $r['SupervisorID'];
                        $_SESSION['assessor_name'] = $r['Name'];
                        header("Location: SupervisorStudentList.php");
                        exit;

                    } else {
                        header("Location: Admin_page.php");
                        exit;
                    }
                } else {
                    $error = 'Invalid username, password, or role. Please try again.';
                }
            } else {
                $error = 'Invalid username, password, or role. Please try again.';
            }
        } else {
            $error = 'Invalid username, password, or role. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login – UNM Internship Result Management</title>
  <link rel="stylesheet" href="style.css"/>
  <style>
    body {
      display: flex;
      min-height: 100vh;
      background: var(--light);
      overflow: hidden;
    }
    .login-left {
      width: 420px;
      flex-shrink: 0;
      background: var(--muted);
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      padding: 48px 44px;
      position: relative;
      overflow: hidden;
    }
    .login-left::before,
    .login-left::after {
      content: '';
      position: absolute;
      border-radius: 50%;
      opacity: .08;
    }
    .login-left::before {
      width: 340px; height: 340px;
      background: var(--teal);
      top: -80px; left: -80px;
    }
    .login-left::after {
      width: 260px; height: 260px;
      background: var(--accent);
      bottom: -60px; right: -60px;
    }
    .left-logo {
      display: flex;
      align-items: center;
      gap: 14px;
      position: relative; z-index: 1;
    }
    .logo-bg {
      background: var(--white);
      border-radius: 12px;
      padding: 7px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      box-shadow: 0 2px 8px rgba(0,0,0,.18);
    }
    .logo-bg img { width: 52px; height: 52px; object-fit: contain; display: block; }
    .left-logo-text .unm {
      font-family: 'Sora', sans-serif;
      font-size: 1.4rem;
      font-weight: 700;
      color: var(--white);
      letter-spacing: .06em;
    }
    .left-logo-text .full {
      font-size: .72rem;
      color: rgba(255,255,255,.55);
      text-transform: uppercase;
      letter-spacing: .08em;
    }
    .left-body {
      position: relative; z-index: 1;
      flex: 1;
      display: flex;
      flex-direction: column;
      justify-content: center;
      gap: 20px;
    }
    .left-tagline {
      font-family: 'Sora', sans-serif;
      font-size: 1.85rem;
      font-weight: 700;
      color: var(--white);
      line-height: 1.3;
    }
    .left-tagline span { color: var(--teal); }
    .left-desc {
      font-size: .88rem;
      color: rgba(255,255,255,.55);
      line-height: 1.65;
      max-width: 300px;
    }
    .left-features { display: flex; flex-direction: column; gap: 12px; margin-top: 8px; }
    .feat-item {
      display: flex;
      align-items: center;
      gap: 12px;
      font-size: .83rem;
      color: rgba(255,255,255,.7);
    }
    .feat-dot {
      width: 28px; height: 28px;
      border-radius: 8px;
      background: rgba(255,255,255,.09);
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
    }
    .left-footer {
      position: relative; z-index: 1;
      font-size: .74rem;
      color: rgba(255,255,255,.3);
    }
    .login-right {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 40px 24px;
      background: var(--light);
    }
    .login-card {
      background: var(--white);
      border-radius: 24px;
      padding: 48px 44px;
      width: 100%;
      max-width: 420px;
      box-shadow: var(--shadow-lg);
      animation: cardIn .4s ease both;
    }
    @keyframes cardIn {
      from { opacity: 0; transform: translateY(24px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    .login-card-title {
      font-family: 'Sora', sans-serif;
      font-size: 1.5rem;
      font-weight: 700;
      color: var(--navy);
      margin-bottom: 6px;
    }
    .login-card-sub {
      font-size: .87rem;
      color: var(--muted);
      margin-bottom: 32px;
    }
    .role-toggle {
      display: flex;
      background: var(--light);
      border-radius: 12px;
      padding: 4px;
      margin-bottom: 28px;
      position: relative;
    }
    .role-toggle input[type="radio"] { display: none; }
    .role-toggle label {
      flex: 1;
      text-align: center;
      padding: 9px 0;
      font-family: 'DM Sans', sans-serif;
      font-size: .86rem;
      font-weight: 600;
      color: var(--muted);
      border-radius: 9px;
      cursor: pointer;
      transition: color .2s;
      position: relative; z-index: 1;
    }
    .role-toggle input[type="radio"]:checked + label {
      color: var(--navy);
    }
    .toggle-pill {
      position: absolute;
      top: 4px; left: 4px;
      width: calc(50% - 4px);
      height: calc(100% - 8px);
      background: var(--white);
      border-radius: 9px;
      box-shadow: var(--shadow-sm);
      transition: transform .25s cubic-bezier(.4,0,.2,1);
      pointer-events: none;
    }
    #roleAssessor:checked ~ .toggle-pill { transform: translateX(100%); }
    .lf-group { margin-bottom: 20px; }
    .lf-group label {
      display: block;
      font-size: .78rem;
      font-weight: 600;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: .05em;
      margin-bottom: 7px;
    }
    .lf-input-wrap { position: relative; }
    .lf-input-wrap .lf-icon {
      position: absolute;
      left: 14px; top: 50%;
      transform: translateY(-50%);
      opacity: .4;
      pointer-events: none;
    }
    .lf-input-wrap input {
      width: 100%;
      border: 1.5px solid var(--border);
      border-radius: 11px;
      padding: 11px 14px 11px 42px;
      font-family: 'DM Sans', sans-serif;
      font-size: .9rem;
      color: var(--text);
      background: var(--bg-input);
      outline: none;
      transition: border-color .2s, background .2s, box-shadow .2s;
    }
    .lf-input-wrap input:focus {
      border-color: var(--teal);
      background: var(--white);
      box-shadow: 0 0 0 3px rgba(23,195,178,.12);
    }
    .pw-toggle {
      position: absolute;
      right: 14px; top: 50%;
      transform: translateY(-50%);
      background: none; border: none;
      cursor: pointer; padding: 0;
      color: var(--muted);
      opacity: .5;
      transition: opacity .2s;
    }
    .pw-toggle:hover { opacity: 1; }
    .lf-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 26px;
    }
    .lf-remember {
      display: flex; align-items: center; gap: 8px;
      font-size: .83rem; color: var(--muted); cursor: pointer;
    }
    .lf-remember input[type="checkbox"] {
      width: 16px; height: 16px;
      accent-color: var(--teal);
      cursor: pointer;
    }
    .lf-forgot {
      font-size: .83rem;
      color: var(--accent);
      text-decoration: none;
      font-weight: 600;
    }
    .lf-forgot:hover { text-decoration: underline; }
    .btn-login {
      width: 100%;
      padding: 13px;
      border-radius: 12px;
      border: none;
      background: linear-gradient(135deg, var(--accent), var(--teal));
      color: #fff;
      font-family: 'Sora', sans-serif;
      font-size: 1rem;
      font-weight: 700;
      cursor: pointer;
      letter-spacing: .02em;
      transition: opacity .2s, transform .15s, box-shadow .2s;
      box-shadow: 0 4px 16px rgba(46,134,222,.3);
      display: flex; align-items: center; justify-content: center; gap: 8px;
    }
    .btn-login:hover  { opacity: .92; transform: translateY(-1px); box-shadow: 0 6px 24px rgba(46,134,222,.4); }
    .btn-login:active { transform: translateY(0); }
    .alert-box {
      border-radius: 10px;
      padding: 12px 16px;
      font-size: .84rem;
      font-weight: 500;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 10px;
      background: #fde8e8; color: var(--danger); border: 1px solid #f5c6c6;
    }
    .login-divider {
      text-align: center;
      font-size: .78rem;
      color: var(--muted);
      margin: 22px 0 0;
    }
    @media (max-width: 820px) {
      .login-left  { display: none; }
      .login-right { padding: 24px 16px; }
    }
  </style>
</head>
<body>

<!-- ── LEFT BRANDING PANEL ── -->
<div class="login-left">
  <div class="left-logo">
    <div class="logo-bg">
      <img src="nottingham-university-logo.png" alt="UNM Logo"/>
    </div>
    <div class="left-logo-text">
      <div class="unm">UNM</div>
      <div class="full">University of Nottingham Malaysia</div>
    </div>
  </div>

  <div class="left-body">
    <div class="left-tagline">
      Internship<br/><span>Result</span><br/>Management
    </div>
    <div class="left-desc">
      A centralised platform for tracking student internship placements, assessments, and results across all programmes.
    </div>
    <div class="left-features">
      <div class="feat-item">
        <div class="feat-dot"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--teal)" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg></div>
        Secure role-based access control
      </div>
      <div class="feat-item">
        <div class="feat-dot"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--teal)" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg></div>
        Automated mark calculation
      </div>
      <div class="feat-item">
        <div class="feat-dot"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--teal)" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg></div>
        Real-time internship tracking
      </div>
      <div class="feat-item">
        <div class="feat-dot"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--teal)" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg></div>
        Comprehensive result viewing
      </div>
    </div>
  </div>

  <div class="left-footer">&copy; 2025 University of Nottingham Malaysia. All rights reserved.</div>
</div>

<!-- ── RIGHT LOGIN PANEL ── -->
<div class="login-right">
  <div class="login-card">

    <div class="login-card-title">Welcome back</div>
    <div class="login-card-sub">Sign in to your account to continue.</div>

    <!-- Show error from PHP if login failed -->
    <?php if ($error): ?>
      <div class="alert-box">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <!-- Form now submits to THIS SAME FILE via POST -->
    <form method="POST" action="login_page.php">

      <!-- Role toggle -->
      <div class="role-toggle">
        <input type="radio" name="role" id="roleAdmin" value="admin" checked/>
        <label for="roleAdmin">Admin</label>
        <input type="radio" name="role" id="roleAssessor" value="assessor"/>
        <label for="roleAssessor">Assessor</label>
        <div class="toggle-pill"></div>
      </div>

      <!-- Username -->
      <div class="lf-group">
        <label>Username</label>
        <div class="lf-input-wrap">
          <svg class="lf-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--text)" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
          <input type="text" name="username" id="fUsername" placeholder="Enter your username" autocomplete="username" required/>
        </div>
      </div>

      <!-- Password -->
      <div class="lf-group">
        <label>Password</label>
        <div class="lf-input-wrap">
          <svg class="lf-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--text)" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          <input type="password" name="password" id="fPassword" placeholder="Enter your password" autocomplete="current-password" required/>
          <button class="pw-toggle" type="button" onclick="togglePw()" title="Show/hide password">
            <svg id="eyeIcon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
      </div>

      <!-- Remember + Forgot -->
      <div class="lf-row">
        <label class="lf-remember">
          <input type="checkbox" id="rememberMe"/>
          Remember me
        </label>
        <a href="#" class="lf-forgot">Forgot password?</a>
      </div>

      <!-- Submit -->
      <button type="submit" class="btn-login">
        <span class="btn-label">Sign In</span>
      </button>

    </form>

    <div class="login-divider">
      Having trouble? Contact your system administrator.
    </div>

  </div>
</div>

<script>
  /* ── PASSWORD VISIBILITY TOGGLE ── */
  let pwVisible = false;
  function togglePw() {
    pwVisible = !pwVisible;
    const input = document.getElementById('fPassword');
    input.type = pwVisible ? 'text' : 'password';
    document.getElementById('eyeIcon').innerHTML = pwVisible
      ? '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>'
      : '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
  }
</script>
</body>
</html>