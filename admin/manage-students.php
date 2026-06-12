<?php
require_once '../data/database.php';
require_once '../helpers/functions.php';

requireLogin();

if ($_SESSION['user_type'] !== 'admin') {
    redirect('../unauth.php');
}

$adminUsername = $_SESSION['username'];

//handle delete
$deleteSuccess = '';
$deleteError   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_student'])) {
    $studentId = (int)($_POST['student_id'] ?? 0);

    if ($studentId > 0) {
        $del_stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
        $del_stmt->execute([$studentId]);
        $deleteSuccess = 'Student account deleted successfully.';
    } else {
        $deleteError = 'Invalid student ID.';
    }
}

// search filter
$search = trim($_GET['search'] ?? '');

if ($search !== '') {
    $students_sql = "
        SELECT
            students.id,
            students.username,
            students.email,
            students.created_at,
            valid_ids.valid_id AS student_id_number
        FROM students
        INNER JOIN valid_ids
            ON students.valid_id = valid_ids.id
        WHERE students.username LIKE :search_username
          OR students.email    LIKE :search_email
          OR CAST(valid_ids.valid_id AS CHAR) LIKE :search_id
        ORDER BY students.created_at DESC
    ";
    $students_stmt = $pdo->prepare($students_sql);
    $students_stmt->execute([
        ':search_username' => '%' . $search . '%',
        ':search_email'    => '%' . $search . '%',
        ':search_id'       => '%' . $search . '%'
    ]);
} else {
    $students_sql = "
        SELECT
            students.id,
            students.username,
            students.email,
            students.created_at,
            valid_ids.valid_id AS student_id_number
        FROM students
        INNER JOIN valid_ids
            ON students.valid_id = valid_ids.id
        ORDER BY students.created_at DESC
    ";
    $students_stmt = $pdo->prepare($students_sql);
    $students_stmt->execute();
}

$students = $students_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Students - Hawassa University</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/responsive.css">
  <link rel="stylesheet" href="../css/dashboard.css">
  <link rel="stylesheet" href="../css/forms.css">
</head>
<body>

  <nav class="navbar">
    <div class="nav-brand">
      <a href="admin-dashboard.php" class="logo-btn-link" title="Go to Dashboard">
        <img src="../images/download.jpg" alt="Hawassa University Logo" class="logo-btn-img">
      </a>
      Admin Portal
    </div>
    <div class="nav-actions" style="display: flex; align-items: center; margin-left: auto; margin-right: 20px;">
      <div class="profile-avatar" style="display:flex; align-items:center; gap:10px; cursor:pointer;">
        <img src="https://placehold.co/40x40" alt="Admin Avatar"
             style="border-radius:50%; width:35px; height:35px; border:2px solid rgba(255,255,255,0.85);">
        <span class="profile-name" style="color:white; font-weight:bold;">
          <?php echo htmlspecialchars($adminUsername); ?>
        </span>
      </div>
    </div>
    <button class="mobile-menu-btn">☰</button>
  </nav>

  <div class="container">
    <h2 class="section-title" style="text-align: left;">Manage Students</h2>

    <div class="dashboard-container">
      <aside class="sidebar">
        <h3>Admin Menu</h3>
        <nav class="sidebar-nav">
          <a href="admin-dashboard.php">Overview</a>
          <a href="manage-students.php" class="active">Manage Students</a>
          <a href="manage-lecturers.php">Manage Lecturers</a>
          <a href="manage-reviews.php">All Reviews</a>
          <a href="system-statistics.php">Reports</a>
          <a href="../logout.php">Logout</a>

        </nav>
      </aside>

      <main class="main-content">
        <div class="card">
          <h3 style="color: var(--primary-blue); margin-bottom: 20px;">
            Student Directory
            <span style="font-size: 0.9rem; font-weight: normal; color: var(--text-light); margin-left: 10px;">
              <?php echo count($students); ?> student<?php echo count($students) !== 1 ? 's' : ''; ?>
              <?php echo $search !== '' ? 'found' : 'registered'; ?>
            </span>
          </h3>

          <?php if ($deleteSuccess): ?>
            <p style="color: green; margin-bottom: 15px;"><?php echo htmlspecialchars($deleteSuccess); ?></p>
          <?php elseif ($deleteError): ?>
            <p style="color: red; margin-bottom: 15px;"><?php echo htmlspecialchars($deleteError); ?></p>
          <?php endif; ?>

          <form method="GET" action="manage-students.php" style="margin-bottom: 20px; display: flex; gap: 10px;">
            <input
              type="text"
              name="search"
              class="form-control"
              placeholder="Search by username, email or ID..."
              value="<?php echo htmlspecialchars($search); ?>"
              style="max-width: 350px;">
            <button type="submit" class="btn btn-blue" style="padding: 8px 16px;">Search</button>
            <?php if ($search !== ''): ?>
              <a href="manage-students.php" class="btn btn-secondary" style="padding: 8px 16px;">Clear</a>
            <?php endif; ?>
          </form>

          <?php if (empty($students)): ?>
            <p style="color: var(--text-light);">No students found.</p>
          <?php else: ?>
            <div style="overflow-x: auto;">
              <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                  <tr style="border-bottom: 2px solid var(--border-color);">
                    <th style="padding: 10px;">Student ID</th>
                    <th style="padding: 10px;">Username</th>
                    <th style="padding: 10px;">Email</th>
                    <th style="padding: 10px;">Joined</th>
                    <th style="padding: 10px;">Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($students as $student): ?>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                      <td style="padding: 10px; font-weight: bold;">
                        <?php echo htmlspecialchars($student['student_id_number']); ?>
                      </td>
                      <td style="padding: 10px;">
                        <?php echo htmlspecialchars($student['username']); ?>
                      </td>
                      <td style="padding: 10px;">
                        <?php echo htmlspecialchars($student['email']); ?>
                      </td>
                      <td style="padding: 10px; color: var(--text-light); font-size: 0.9rem;">
                        <?php echo date('M j, Y', strtotime($student['created_at'])); ?>
                      </td>
                      <td style="padding: 10px;">
                        <form method="POST" action="manage-students.php"
                              onsubmit="return confirm('Delete this student account? This cannot be undone.');">
                          <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                          <button type="submit" name="delete_student"
                                  class="btn btn-secondary"
                                  style="padding: 5px 10px; color: #d32f2f; border-color: #d32f2f;">
                            Delete
                          </button>
                        </form>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>

        </div>
      </main>
    </div>
  </div>

  <script src="../js/main.js"></script>
</body>
</html>