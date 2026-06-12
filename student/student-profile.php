<?php
  require_once '../data/database.php';
  require_once '../helpers/functions.php';

  requireLogin();

  if ($_SESSION['user_type'] !== 'student') {
      redirect('../unauth.php');
  }

  //fetch student

  $student_sql = "
      SELECT
          students.id,
          students.username,
          students.email,
          valid_ids.valid_id
      FROM students
      INNER JOIN valid_ids
          ON students.valid_id = valid_ids.id
      WHERE students.id = ?
      LIMIT 1
  ";

  $student_stmt = $pdo->prepare($student_sql);
  $student_stmt->execute([$_SESSION['user_id']]);

  $student = $student_stmt->fetch();

  if (!$student) {
      session_destroy();
      redirect('../login.php');
  }

  //variables

  $username = $student['username'];
  $email = $student['email'];

  $profileErrors = [];
  $passwordErrors = [];

  //update profile

  if (
      $_SERVER['REQUEST_METHOD'] === 'POST' &&
      isset($_POST['update_profile'])
  ) {

      $username = trim($_POST['user_name'] ?? '');
      $email = trim($_POST['email'] ?? '');

      // Username validation
      if (empty($username)) {

          $profileErrors[] = "Username is required.";

      } elseif (strlen($username) < 3 || strlen($username) > 50) {

          $profileErrors[] = "Username must be between 3 and 50 characters.";

      } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {

          $profileErrors[] = "Username can only contain letters, numbers, and underscores.";
      }

      // Email validation
      if (empty($email)) {

          $profileErrors[] = "Email is required.";

      } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

          $profileErrors[] = "Please enter a valid email address.";
      }

      // Existing user check
      if (empty($profileErrors)) {

          $check_sql = "
              SELECT id
              FROM students
              WHERE (username = :username OR email = :email)
              AND id != :id
              LIMIT 1
          ";

          $check_stmt = $pdo->prepare($check_sql);

          $check_stmt->execute([
              ':username' => $username,
              ':email' => $email,
              ':id' => $_SESSION['user_id']
          ]);

          if ($check_stmt->fetch()) {
              $profileErrors[] = "Username or email already exists.";
          }
      }

      // Update profile
      if (empty($profileErrors)) {

          $update_sql = "
              UPDATE students
              SET username = :username,
                  email = :email
              WHERE id = :id
          ";

          $update_stmt = $pdo->prepare($update_sql);

          $update_stmt->execute([
              ':username' => $username,
              ':email' => $email,
              ':id' => $_SESSION['user_id']
          ]);

          $_SESSION['username'] = $username;

          setFlashMessage("Profile updated successfully.", "success");

          redirect('student-profile.php');
      }
  }

  // update password

  if (
      $_SERVER['REQUEST_METHOD'] === 'POST' &&
      isset($_POST['update_password'])
  ) {

      $currentPassword = $_POST['current_password'] ?? '';
      $newPassword = $_POST['new_password'] ?? '';
      $confirmPassword = $_POST['confirm_password'] ?? '';

      // Validate
      if (empty($currentPassword)) {
          $passwordErrors[] = "Current password is required.";
      }

      if (empty($newPassword)) {

          $passwordErrors[] = "New password is required.";

      } elseif (strlen($newPassword) < 8) {

          $passwordErrors[] = "New password must be at least 8 characters long.";
      }

      if ($newPassword !== $confirmPassword) {
          $passwordErrors[] = "Passwords do not match.";
      }

      // Verify current password
      if (empty($passwordErrors)) {

          $password_sql = "
              SELECT password_hash
              FROM students
              WHERE id = ?
              LIMIT 1
          ";

          $password_stmt = $pdo->prepare($password_sql);
          $password_stmt->execute([$_SESSION['user_id']]);

          $currentUser = $password_stmt->fetch();

          if (!$currentUser || !password_verify($currentPassword, $currentUser['password_hash'])) {

              $passwordErrors[] = "Current password is incorrect.";
          }
      }

      // Update password
      if (empty($passwordErrors)) {

          $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

          $update_sql = "
              UPDATE students
              SET password_hash = :password_hash
              WHERE id = :id
          ";

          $update_stmt = $pdo->prepare($update_sql);

          $update_stmt->execute([
              ':password_hash' => $newPasswordHash,
              ':id' => $_SESSION['user_id']
          ]);

          setFlashMessage("Password updated successfully.", "success");

          redirect('student-profile.php');
      }
  }
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Profile - Hawassa University</title>

  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/responsive.css">
  <link rel="stylesheet" href="../css/dashboard.css">
  <link rel="stylesheet" href="../css/forms.css">

  <style>
    .error {
      color: #d32f2f;
      background: #ffebee;
      padding: 10px;
      border-radius: 4px;
      margin-bottom: 15px;
    }

    .error ul {
      margin: 0;
      padding-left: 20px;
    }
  </style>
</head>

<body>

  <!-- Navigation -->
  <nav class="navbar">

    <div class="nav-brand">
      <a href="student-dashboard.php" class="logo-btn-link" title="Go to Dashboard">
        <img src="../images/download.jpg"
             alt="Hawassa University Logo"
             class="logo-btn-img">
      </a>

      Lecturer Review
    </div>

    <div class="nav-actions"
         style="display: flex; align-items: center; margin-left: auto; margin-right: 20px;">

      <div class="profile-avatar"
           style="display:flex; align-items:center; gap:10px; cursor:pointer;">

        <img src="https://placehold.co/40x40"
             alt="Student Avatar"
             style="border-radius:50%; width:35px; height:35px; border:2px solid rgba(255, 255, 255, 0.85);">

        <span class="profile-name"
              style="color:white; font-weight:bold;">

          <?php echo htmlspecialchars($_SESSION['username']); ?>

        </span>
      </div>
    </div>

    <button class="mobile-menu-btn">☰</button>
  </nav>

  <!-- Page -->
  <div class="container">

    <h2 class="section-title" style="text-align: left;">
      My Profile
    </h2>

    <div class="dashboard-container">

      <!-- Sidebar -->
      <aside class="sidebar">

        <h3>Menu</h3>

        <nav class="sidebar-nav">
          <a href="student-dashboard.php">Overview</a>
          <a href="student-reviews.php">My Reviews</a>
          <a href="../lecturers.php">Evaluate Lecturer</a>
          <a href="student-profile.php" class="active">Profile Settings</a>
          <a href="../about.php">About the System</a>
          <a href="../logout.php">Logout</a>

        </nav>
      </aside>

      <!-- Main -->
      <main class="main-content">

        <div class="back-btn-container">
          <button class="back-btn"
                  onclick="window.history.back()">

            <span class="arrow">&larr;</span> Back
          </button>
        </div>

        <div class="form-container"
             style="max-width: 600px; margin: 0;">

          <?php echo getFlashMessage(); ?>

          <!-- ================================= -->
          <!-- PROFILE FORM -->
          <!-- ================================= -->

          <h3 style="color: var(--primary-blue); margin-bottom: 20px;">
            Edit Profile
          </h3>

          <?php if (!empty($profileErrors)): ?>

            <div class="error">
              <strong>Please fix the following:</strong>

              <ul>
                <?php foreach ($profileErrors as $error): ?>

                  <li>
                    <?php echo htmlspecialchars($error); ?>
                  </li>

                <?php endforeach; ?>
              </ul>
            </div>

          <?php endif; ?>

          <form action="" method="POST">

            <div class="form-group">

              <label for="username">
                Username
              </label>

              <input type="text"
                     id="username"
                     class="form-control"
                     name="user_name"
                     value="<?php echo htmlspecialchars($username); ?>">
            </div>

            <div class="form-group">

              <label for="student-id">
                Student ID
              </label>

              <input type="text"
                     id="student-id"
                     class="form-control"
                     readonly
                     value="<?php echo htmlspecialchars($student['valid_id']); ?>"
                     style="background-color: var(--bg-color);">
            </div>

            <div class="form-group">

              <label for="email">
                Email Address
              </label>

              <input type="email"
                     id="email"
                     class="form-control"
                     name="email"
                     value="<?php echo htmlspecialchars($email); ?>">
            </div>

            <button type="submit"
                    name="update_profile"
                    class="btn btn-blue">

              Save Changes
            </button>

          </form>

          <hr style="margin: 30px 0; border: none; border-top: 1px solid var(--border-color);">

          <!-- password form -->

          <h3 style="color: var(--primary-blue); margin-bottom: 20px;">
            Change Password
          </h3>

          <?php if (!empty($passwordErrors)): ?>

            <div class="error">
              <strong>Please fix the following:</strong>

              <ul>
                <?php foreach ($passwordErrors as $error): ?>

                  <li>
                    <?php echo htmlspecialchars($error); ?>
                  </li>

                <?php endforeach; ?>
              </ul>
            </div>

          <?php endif; ?>

          <form action="" method="POST">

            <div class="form-group">

              <label for="current-password">
                Current Password
              </label>

              <input type="password"
                     id="current-password"
                     class="form-control"
                     placeholder="Enter current password"
                     name="current_password">
            </div>

            <div class="form-group">

              <label for="new-password">
                New Password
              </label>

              <input type="password"
                     id="new-password"
                     class="form-control"
                     placeholder="Enter new password"
                     name="new_password">
            </div>

            <div class="form-group">

              <label for="confirm-password">
                Confirm New Password
              </label>

              <input type="password"
                     id="confirm-password"
                     class="form-control"
                     placeholder="Confirm new password"
                     name="confirm_password">
            </div>

            <button type="submit"
                    name="update_password"
                    class="btn btn-secondary">

              Update Password
            </button>

          </form>

        </div>
      </main>
    </div>
  </div>

  <script src="../js/main.js"></script>

</body>
</html>