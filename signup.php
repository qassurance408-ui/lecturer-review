<?php
require_once 'data/database.php';
require_once 'helpers/functions.php';

$idNumber = "";
$username = "";
$email = "";
$password = "";
$confirmPassword = "";
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $idNumber = trim($_POST['id_number'] ?? '');
    $username = trim($_POST['user_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['password_confirm'] ?? '';

    $validIdRecord = null;
    $roleId = null;
    $lecturer = null;

    if (empty($idNumber)) {
        $errors[] = "Id number is required.";
    } elseif (strlen($idNumber) !== 5) {
        $errors[] = "Id number must be 5 digits long.";
    } elseif (!preg_match('/^[0-9]+$/', $idNumber)) {
        $errors[] = "Id number can only contain numbers.";
    }

    if (empty($username)) {
        $errors[] = "Username is required.";
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $errors[] = "Username must be between 3 and 50 characters.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = "Username can only contain letters, numbers, and underscores.";
    }

    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }

    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    }

    if ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match.";
    }

    if (empty($errors)) {

        $check_sql = "
            SELECT id, usertype, valid_id
            FROM valid_ids
            WHERE valid_id = ?
            LIMIT 1
        ";

        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute([$idNumber]);

        $validIdRecord = $check_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$validIdRecord) {

            $errors[] = "ID doesn't exist in the system.";

        } else {
            
          $role_sql = " SELECT id FROM roles WHERE name = ? LIMIT 1 ";

            $role_stmt = $pdo->prepare($role_sql);
            $role_stmt->execute([$validIdRecord['usertype']]);

            $role = $role_stmt->fetch(PDO::FETCH_ASSOC);

            if (!$role) {
                $errors[] = "No matching role found.";
            } else {
                $roleId = $role['id'];
            }

            if ($validIdRecord['usertype'] === 'student') {

                $check_sql = " SELECT id, valid_id FROM students WHERE username = :username OR email = :email OR valid_id = :valid_id LIMIT 1 ";

                $check_stmt = $pdo->prepare($check_sql);

                $check_stmt->execute([
                    ':username' => $username,
                    ':email' => $email,
                    ':valid_id' => $validIdRecord['id']
                ]);

                $existingUser = $check_stmt->fetch(PDO::FETCH_ASSOC);

                if ($existingUser) {

                    if ($existingUser['valid_id'] == $validIdRecord['id']) {
                        $errors[] = "Account with this ID already exists.";
                    } else {
                        $errors[] = "Username or email already exists.";
                    }
                }

            } elseif ($validIdRecord['usertype'] === 'lecturer') {

                $lecturer_sql = " SELECT id FROM lecturers WHERE valid_id = ? LIMIT 1 ";

                $lecturer_stmt = $pdo->prepare($lecturer_sql);
                $lecturer_stmt->execute([$validIdRecord['id']]);

                $lecturer = $lecturer_stmt->fetch(PDO::FETCH_ASSOC);

                if (!$lecturer) {

                    $errors[] = "Lecturer record not found.";

                } else {

                    $check_sql = " SELECT id, lecturer_id FROM lecturer_accounts WHERE username = :username OR email = :email OR lecturer_id = :lecturer_id LIMIT 1 ";

                    $check_stmt = $pdo->prepare($check_sql);

                    $check_stmt->execute([
                        ':username' => $username,
                        ':email' => $email,
                        ':lecturer_id' => $lecturer['id']
                    ]);

                    $existingLecturer = $check_stmt->fetch(PDO::FETCH_ASSOC);

                    if ($existingLecturer) {

                        if ($existingLecturer['lecturer_id'] == $lecturer['id']) {
                            $errors[] = "Account with this ID already exists.";
                        } else {
                            $errors[] = "Username or email already exists.";
                        }
                    }
                }
            }
        }
    }

    if (empty($errors)) {

        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        if ($validIdRecord['usertype'] === 'student') {

            $insert_sql = " INSERT INTO students ( username, email, password_hash, valid_id, role_id ) 
                            VALUES ( :username, :email, :password_hash, :valid_id, :role_id ) ";

            $insert_stmt = $pdo->prepare($insert_sql);

            $insert_stmt->execute([
                ':username'      => $username,
                ':email'         => $email,
                ':password_hash' => $password_hash,
                ':valid_id'      => $validIdRecord['id'],
                ':role_id'       => $roleId
            ]);


        } elseif ($validIdRecord['usertype'] === 'lecturer') {

            $insert_sql = " INSERT INTO lecturer_accounts ( lecturer_id, username, email, password_hash, role_id ) 
                            VALUES ( :lecturer_id, :username, :email, :password_hash, :role_id ) ";

            $insert_stmt = $pdo->prepare($insert_sql);

            $insert_stmt->execute([
                ':lecturer_id'   => $lecturer['id'],
                ':username'      => $username,
                ':email'         => $email,
                ':password_hash' => $password_hash,
                ':role_id'       => $roleId
            ]);
        }

        setFlashMessage("Account created successfully! Please log in.", "success");

        session_unset();
        session_destroy();
        redirect('login.php');
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign Up - Hawassa University</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/responsive.css">
  <link rel="stylesheet" href="css/forms.css">

  <style>
    .error { color: #d32f2f; background: #ffebee; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
    .error ul { margin: 0; padding-left: 20px; }
  </style>
</head>

<body>

  <!-- Navigation Bar -->
  <nav class="navbar">
    <div class="nav-brand">
      <a href="index.php" class="logo-btn-link" title="Go to Home">
        <img src="images/download.jpg" alt="Hawassa University Logo" class="logo-btn-img">
      </a>
      Lecturer Review
      <!-- <button class="theme-toggle-btn" title="Toggle Dark Mode" aria-label="Toggle Dark Mode"></button> -->
    </div>
    <button class="mobile-menu-btn">☰</button>
    <ul class="nav-links">
      <li><a href="index.php">Home</a></li>
      <li><a href="about.php">About</a></li>
      <li><a href="lecturers.php">Lecturers</a></li>
      <li><a href="login.php">Login</a></li>
    </ul>
  </nav>

  <!-- Page Content -->
  <div class="container">
    <div class="back-btn-container" style="max-width: 600px; margin: 0 auto 20px;">
      <button class="back-btn" onclick="window.history.back()"><span class="arrow">&larr;</span> Back</button>
    </div>
    <div class="form-container" style="max-width: 600px; text-align: left;">
      <div style="text-align: center; margin-bottom: 30px;">
        <h2 style="color: var(--primary-blue); margin-bottom: 10px;">Create an Account</h2>
        <p style="color: var(--text-light); font-size: 0.95rem;">Join the Hawassa University review platform.</p>
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

      <form id="signup-form" action="" method="POST">
        <div id="signup-error" class="error-msg" style="text-align: center; margin-bottom: 15px; display: none;"></div>

        <div class="grid-3" style="gap: 15px; margin-bottom: 20px;">
          <div class="form-group" style="margin-bottom: 0;">
            <label for="id-number">ID Number</label>
            <input type="text" id="id-number" class="form-control" required placeholder="Enter your ID number" name="id_number">
          </div>
          <div class="form-group" style="margin-bottom: 0;">
            <label for="username">Username</label>
            <input type="text" id="username" class="form-control" required placeholder="Choose a username" name="user_name">
          </div>
        </div>

        <div class="form-group">
          <label for="email">Email Address</label>
          <input type="email" id="email" class="form-control" required placeholder="e.g. student@hu.edu.et" name="email">
        </div>



        <div class="grid-3" style="gap: 15px; margin-bottom: 20px;">
          <div class="form-group" style="margin-bottom: 0;">
            <label for="password">Password</label>
            <div style="position: relative;">
              <input type="password" id="password" class="form-control" required placeholder="Create a password" name="password">
              <button type="button" id="toggle-password-1"
                style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: var(--text-light); font-size: 0.8rem;">Show</button>
            </div>
          </div>
          <div class="form-group" style="margin-bottom: 0;">
            <label for="confirm-password">Confirm Password</label>
            <div style="position: relative;">
              <input type="password" id="confirm-password" class="form-control" required placeholder="Confirm password" name="password_confirm">
              <button type="button" id="toggle-password-2"
                style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: var(--text-light); font-size: 0.8rem;">Show</button>
            </div>
          </div>
        </div>



        <button type="submit" class="btn btn-blue"
          style="width: 100%; font-size: 1.1rem; padding: 15px; margin-top: 10px;">Create Account</button>

        <p style="text-align: center; margin-top: 25px; font-size: 0.95rem;">
          Already have an account? <a href="login.php" style="color: var(--primary-blue); font-weight: bold;">Login
            here</a>
        </p>
      </form>
    </div>
  </div>

  <script src="js/main.js"></script>
  <script src="js/auth.js"></script>
</body>

</html>
