<?php
require_once '../data/database.php';
require_once '../helpers/functions.php';

requireLogin();

if ($_SESSION['user_type'] !== 'lecturer') {
    redirect('../unauth.php');
}

// get lecturer + details 
$lecturer_sql = "
    SELECT
        lecturers.id          AS lecturer_id,
        lecturers.name,
        departments.department,
        lecturer_accounts.email,
        lecturer_accounts.username
    FROM lecturer_accounts
    INNER JOIN lecturers
        ON lecturer_accounts.lecturer_id = lecturers.id
    INNER JOIN departments
        ON lecturers.department_id = departments.id
    WHERE lecturer_accounts.id = ?
    LIMIT 1
";

$lecturer_stmt = $pdo->prepare($lecturer_sql);
$lecturer_stmt->execute([$_SESSION['user_id']]);
$lecturer = $lecturer_stmt->fetch();

if (!$lecturer) {
    session_destroy();
    redirect('../login.php');
}

$lecturerName  = $lecturer['name'];
$lecturerEmail = $lecturer['email'];
$department    = $lecturer['department'];

//handled profile update
$profileSuccess = '';
$profileError   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {

    $newName  = trim($_POST['full_name']  ?? '');
    $newEmail = trim($_POST['email']      ?? '');

    if ($newName === '' || $newEmail === '') {
        $profileError = 'Name and email cannot be empty.';

    } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        $profileError = 'Please enter a valid email address.';

    } else {
        // Check email uniqueness (exclude current account)
        $check_stmt = $pdo->prepare("
            SELECT id FROM lecturer_accounts
            WHERE email = ? AND id != ?
            LIMIT 1
        ");
        $check_stmt->execute([$newEmail, $_SESSION['user_id']]);

        if ($check_stmt->fetch()) {
            $profileError = 'That email is already in use by another account.';
        } else {
            $update_stmt = $pdo->prepare("
                UPDATE lecturer_accounts
                SET email = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $update_stmt->execute([$newEmail, $_SESSION['user_id']]);

            $name_stmt = $pdo->prepare("
                UPDATE lecturers
                SET name = ?
                WHERE id = ?
            ");
            $name_stmt->execute([$newName, $lecturer['lecturer_id']]);

            $profileSuccess  = 'Profile updated successfully.';
            $lecturerName    = $newName;
            $lecturerEmail   = $newEmail;
        }
    }
}

//handle password change
$passwordSuccess = '';
$passwordError   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {

    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword     = $_POST['new_password']     ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Fetch current hash
    $hash_stmt = $pdo->prepare("
        SELECT password_hash FROM lecturer_accounts WHERE id = ? LIMIT 1
    ");
    $hash_stmt->execute([$_SESSION['user_id']]);
    $row = $hash_stmt->fetch();

    if (!$row || !password_verify($currentPassword, $row['password_hash'])) {
        $passwordError = 'Current password is incorrect.';

    } elseif (strlen($newPassword) < 8) {
        $passwordError = 'New password must be at least 8 characters.';

    } elseif ($newPassword !== $confirmPassword) {
        $passwordError = 'New passwords do not match.';

    } else {
        $newHash    = password_hash($newPassword, PASSWORD_BCRYPT);
        $pw_stmt    = $pdo->prepare("
            UPDATE lecturer_accounts
            SET password_hash = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $pw_stmt->execute([$newHash, $_SESSION['user_id']]);

        $passwordSuccess = 'Password changed successfully.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Update Profile - Hawassa University</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/responsive.css">
  <link rel="stylesheet" href="../css/dashboard.css">
  <link rel="stylesheet" href="../css/forms.css">
</head>
<body>

  <!-- Navigation Bar -->
  <nav class="navbar">
    <div class="nav-brand">
      <a href="lecturer-dashboard.php" class="logo-btn-link" title="Go to Dashboard">
        <img src="../images/download.jpg" alt="Hawassa University Logo" class="logo-btn-img">
      </a>
      Lecturer Portal
    </div>
    <div class="nav-actions" style="display: flex; align-items: center; margin-left: auto; margin-right: 20px;">
      <div class="profile-avatar" style="display:flex; align-items:center; gap:10px; cursor:pointer;">
        <img src="https://placehold.co/40x40" alt="Lecturer Avatar" style="border-radius:50%; width:35px; height:35px; border:2px solid rgba(255,255,255,0.85);">
        <span class="profile-name" style="color:white; font-weight:bold;">
          <?php echo htmlspecialchars($lecturerName); ?>
        </span>
      </div>
    </div>
    <button class="mobile-menu-btn">☰</button>
  </nav>

  <!-- Page Content -->
  <div class="container">
    <h2 class="section-title" style="text-align: left;">Profile Settings</h2>

    <div class="dashboard-container">
      <!-- Sidebar Navigation -->
      <aside class="sidebar">
        <h3>Menu</h3>
        <nav class="sidebar-nav">
          <a href="lecturer-dashboard.php">Overview</a>
          <a href="lecturer-feedback.php">Recent Reviews</a>
          <a href="lecturer-statistics.php">Performance Stats</a>
          <a href="lecturer-profile.php" class="active">Update Profile</a>
          <a href="../logout.php">Logout</a>

        </nav>
      </aside>

      <!-- Main Content -->
      <main class="main-content">
        <div class="back-btn-container">
          <button class="back-btn" onclick="window.history.back()"><span class="arrow">&larr;</span> Back</button>
        </div>

        <div class="form-container" style="max-width: 600px; margin: 0;">

          <!-- ── Profile Form ── -->
          <h3 style="color: var(--primary-blue); margin-bottom: 20px;">Edit Profile</h3>

          <?php if ($profileSuccess): ?>
            <div class="alert alert-success" style="margin-bottom:15px; color:green;">
              <?php echo htmlspecialchars($profileSuccess); ?>
            </div>
          <?php elseif ($profileError): ?>
            <div class="alert alert-error" style="margin-bottom:15px; color:red;">
              <?php echo htmlspecialchars($profileError); ?>
            </div>
          <?php endif; ?>

          <form method="POST" action="lecturer-profile.php">
            <div class="form-group">
              <label>Full Name</label>
              <input type="text" name="full_name" class="form-control"
                     value="<?php echo htmlspecialchars($lecturerName); ?>" required>
            </div>
            <div class="form-group">
              <label>Email Address</label>
              <input type="email" name="email" class="form-control"
                     value="<?php echo htmlspecialchars($lecturerEmail); ?>" required>
            </div>
            <div class="form-group">
              <label>Department</label>
              <select class="form-control" disabled style="background-color: var(--bg-color);">
                <option selected><?php echo htmlspecialchars($department); ?></option>
              </select>
              <small style="color: var(--text-light); margin-top: 5px; display: block;">
                Contact Admin to change your primary department.
              </small>
            </div>
            <button type="submit" name="update_profile" class="btn btn-blue">Save Changes</button>
          </form>

          <hr style="margin: 30px 0; border: none; border-top: 1px solid var(--border-color);">

          <!-- ── Password Form ── -->
          <h3 style="color: var(--primary-blue); margin-bottom: 20px;">Change Password</h3>

          <?php if ($passwordSuccess): ?>
            <div class="alert alert-success" style="margin-bottom:15px; color:green;">
              <?php echo htmlspecialchars($passwordSuccess); ?>
            </div>
          <?php elseif ($passwordError): ?>
            <div class="alert alert-error" style="margin-bottom:15px; color:red;">
              <?php echo htmlspecialchars($passwordError); ?>
            </div>
          <?php endif; ?>

          <form method="POST" action="lecturer-profile.php">
            <div class="form-group">
              <label>Current Password</label>
              <input type="password" name="current_password" class="form-control"
                     placeholder="Enter current password" required>
            </div>
            <div class="form-group">
              <label>New Password</label>
              <input type="password" name="new_password" class="form-control"
                     placeholder="Enter new password (min 8 characters)" required>
            </div>
            <div class="form-group">
              <label>Confirm New Password</label>
              <input type="password" name="confirm_password" class="form-control"
                     placeholder="Confirm new password" required>
            </div>
            <button type="submit" name="change_password" class="btn btn-secondary">Update Password</button>
          </form>

        </div>
      </main>
    </div>
  </div>

  <script src="../js/main.js"></script>
</body>
</html>