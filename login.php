<?php
require_once 'data/database.php';
require_once 'helpers/functions.php';

$login    = "";
$password = "";
$errors   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $login    = trim($_POST['login']    ?? '');
    $password =      $_POST['password'] ?? '';

    if (empty($login)) {
        $errors[] = "ID number or username is required.";
    }

    if (empty($password)) {
        $errors[] = "Password is required.";
    }

    if (empty($errors)) {

        $user     = null;
        $userType = null;

        // check students
        $student_stmt = $pdo->prepare("
            SELECT
                students.id,
                students.username,
                students.password_hash,
                students.role_id,
                valid_ids.valid_id
            FROM students
            INNER JOIN valid_ids
                ON students.valid_id = valid_ids.id
            WHERE students.username = :login
               OR valid_ids.valid_id = :id_number
            LIMIT 1
        ");
        $student_stmt->execute([':login' => $login, ':id_number' => $login]);
        $student = $student_stmt->fetch();

        //check lecturers
        $lecturer_stmt = $pdo->prepare("
            SELECT
                lecturer_accounts.id,
                lecturer_accounts.username,
                lecturer_accounts.password_hash,
                lecturer_accounts.role_id,
                valid_ids.valid_id
            FROM lecturer_accounts
            INNER JOIN lecturers
                ON lecturer_accounts.lecturer_id = lecturers.id
            INNER JOIN valid_ids
                ON lecturers.valid_id = valid_ids.id
            WHERE lecturer_accounts.username = :login
               OR valid_ids.valid_id         = :id_number
            LIMIT 1
        ");
        $lecturer_stmt->execute([':login' => $login, ':id_number' => $login]);
        $lecturer = $lecturer_stmt->fetch();

        //check admins
        $admin_stmt = $pdo->prepare("
            SELECT
                id,
                username,
                password_hash,
                role_id
            FROM admins
            WHERE username = :login
               OR email    = :email
            LIMIT 1
        ");
        $admin_stmt->execute([':login' => $login, ':email' => $login]);
        $admin = $admin_stmt->fetch();

        //identify role
        if ($student) {
            $user     = $student;
            $userType = 'student';
        } elseif ($lecturer) {
            $user     = $lecturer;
            $userType = 'lecturer';
        } elseif ($admin) {
            $user     = $admin;
            $userType = 'admin';
        }

        //verify and starting session
        if ($user) {

            if (password_verify($password, $user['password_hash'])) {

                session_regenerate_id(true);

                $_SESSION['user_id']   = $user['id'];
                $_SESSION['username']  = $user['username'];
                $_SESSION['role_id']   = $user['role_id'];
                $_SESSION['user_type'] = $userType;

                if (isset($_SESSION['redirect_after_login'])) {
                    $redirect = $_SESSION['redirect_after_login'];
                    unset($_SESSION['redirect_after_login']);
                    redirect($redirect);
                }

                if ($userType === 'student') {
                    redirect('student/student-dashboard.php');
                } elseif ($userType === 'lecturer') {
                    redirect('lecturer/lecturer-dashboard.php');
                } else {
                    redirect('admin/admin-dashboard.php');
                }

            } else {
                $errors[] = "Invalid username/ID or password.";
            }

        } else {
            $errors[] = "Invalid username/ID or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Hawassa University</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/responsive.css">
  <link rel="stylesheet" href="css/forms.css">
  <style>
    .error { color: #d32f2f; background: #ffebee; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
    .error ul { margin: 0; padding-left: 20px; }
  </style>
</head>
<body>

  <nav class="navbar">
    <div class="nav-brand">
      <a href="index.php" class="logo-btn-link" title="Go to Home">
        <img src="images/download.jpg" alt="Hawassa University Logo" class="logo-btn-img">
      </a>
      Lecturer Review
    </div>
    <button class="mobile-menu-btn">☰</button>
    <ul class="nav-links">
      <li><a href="index.php">Home</a></li>
      <li><a href="about.php">About</a></li>
      <li><a href="lecturers.php">Lecturers</a></li>
      <li><a href="login.php">Login</a></li>
    </ul>
  </nav>

  <div class="container">
    <div class="back-btn-container" style="max-width: 450px; margin: 0 auto 20px;">
      <button class="back-btn" onclick="window.history.back()"><span class="arrow">&larr;</span> Back</button>
    </div>
    <div class="form-container" style="max-width: 450px; text-align: left;">
      <div style="text-align: center; margin-bottom: 30px;">
        <h2 style="color: var(--primary-blue); margin-bottom: 10px;">Welcome Back</h2>
        <p style="color: var(--text-light); font-size: 0.95rem;">Log in to access your dashboard.</p>
      </div>

      <?php echo getFlashMessage(); ?>

      <?php if (!empty($errors)): ?>
        <div class="error">
          <strong>Please fix the following:</strong>
          <ul>
            <?php foreach ($errors as $error): ?>
              <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form id="login-form" action="" method="POST">
        <div class="form-group">
          <label for="id-number">ID Number / Username</label>
          <input type="text" id="id-number" class="form-control"
                 placeholder="Enter your ID number or Username" required name="login">
        </div>
        <div class="form-group">
          <label for="password">Password</label>
          <div style="position: relative;">
            <input type="password" id="password" class="form-control"
                   placeholder="Enter your password" required name="password">
            <button type="button" id="toggle-password"
              style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
                     background: none; border: none; cursor: pointer;
                     color: var(--text-light); font-size: 0.9rem;">Show</button>
          </div>
        </div>

        <button type="submit" class="btn btn-blue"
          style="width: 100%; font-size: 1.1rem; padding: 15px; margin-top: 10px;">Login</button>

        <p style="text-align: center; margin-top: 25px; font-size: 0.95rem;">
          Don't have an account?
          <a href="signup.php" style="color: var(--primary-blue); font-weight: bold;">Sign Up here</a>
        </p>
      </form>
    </div>
  </div>

  <script src="js/main.js"></script>
  <script src="js/auth.js"></script>
</body>
</html>