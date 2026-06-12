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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_lecturer'])) {
    $lecturerId = (int)($_POST['lecturer_id'] ?? 0);

    if ($lecturerId > 0) {
        $del_stmt = $pdo->prepare("DELETE FROM lecturers WHERE id = ?");
        $del_stmt->execute([$lecturerId]);
        $deleteSuccess = 'Lecturer deleted successfully.';
    } else {
        $deleteError = 'Invalid lecturer ID.';
    }
}

//departments
$departments = $pdo->query("SELECT id, department FROM departments ORDER BY department")->fetchAll();

// handle add new lecturers
$addError   = '';
$addSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_lecturer'])) {
    $newName    = trim($_POST['name']          ?? '');
    $newDeptId  = (int)($_POST['department_id'] ?? 0);
    $newValidId = (int)($_POST['valid_id']       ?? 0);

    if ($newName === '' || $newDeptId === 0 || $newValidId === 0) {
        $addError = 'All fields are required.';
    } else {
        // Check if valid_id is already taken
        $check_stmt = $pdo->prepare("SELECT id FROM valid_ids WHERE valid_id = ? LIMIT 1");
        $check_stmt->execute([$newValidId]);

        if ($check_stmt->fetch()) {
            $addError = 'That ID number is already assigned to someone else.';
        } else {
            // Insert into valid_ids
            $vid_stmt = $pdo->prepare("INSERT INTO valid_ids (usertype, valid_id) VALUES ('lecturer', ?)");
            $vid_stmt->execute([$newValidId]);
            $validIdRow = $pdo->lastInsertId();

            // Insert into lecturers
            $lec_stmt = $pdo->prepare("
                INSERT INTO lecturers (name, valid_id, department_id)
                VALUES (?, ?, ?)
            ");
            $lec_stmt->execute([$newName, $validIdRow, $newDeptId]);

            $addSuccess = "Lecturer '{$newName}' added successfully with ID {$newValidId}.";
        }
    }
}

//search filter
$search = trim($_GET['search'] ?? '');

if ($search !== '') {
    $lecturers_sql = "
        SELECT
            lecturers.id,
            lecturers.name,
            departments.department,
            valid_ids.valid_id          AS lecturer_id_number,
            lecturer_accounts.username,
            lecturer_accounts.email,
            ROUND(AVG(reviews.rating), 1) AS avg_rating,
            COUNT(reviews.id)             AS review_count
        FROM lecturers
        INNER JOIN departments
            ON lecturers.department_id = departments.id
        INNER JOIN valid_ids
            ON lecturers.valid_id = valid_ids.id
        LEFT JOIN lecturer_accounts
            ON lecturer_accounts.lecturer_id = lecturers.id
        LEFT JOIN reviews
            ON reviews.lecturer_id = lecturers.id
        WHERE lecturers.name         LIKE :search_name
           OR departments.department LIKE :search_dept
        GROUP BY
            lecturers.id,
            lecturers.name,
            departments.department,
            valid_ids.valid_id,
            lecturer_accounts.username,
            lecturer_accounts.email
        ORDER BY lecturers.name
    ";
    $lecturers_stmt = $pdo->prepare($lecturers_sql);
    $lecturers_stmt->execute([
        ':search_name' => '%' . $search . '%',
        ':search_dept' => '%' . $search . '%',
    ]);
} else {
    $lecturers_sql = "
        SELECT
            lecturers.id,
            lecturers.name,
            departments.department,
            valid_ids.valid_id            AS lecturer_id_number,
            lecturer_accounts.username,
            lecturer_accounts.email,
            ROUND(AVG(reviews.rating), 1) AS avg_rating,
            COUNT(reviews.id)             AS review_count
        FROM lecturers
        INNER JOIN departments
            ON lecturers.department_id = departments.id
        INNER JOIN valid_ids
            ON lecturers.valid_id = valid_ids.id
        LEFT JOIN lecturer_accounts
            ON lecturer_accounts.lecturer_id = lecturers.id
        LEFT JOIN reviews
            ON reviews.lecturer_id = lecturers.id
        GROUP BY
            lecturers.id,
            lecturers.name,
            departments.department,
            valid_ids.valid_id,
            lecturer_accounts.username,
            lecturer_accounts.email
        ORDER BY lecturers.name
    ";
    $lecturers_stmt = $pdo->prepare($lecturers_sql);
    $lecturers_stmt->execute();
}

$lecturers = $lecturers_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Lecturers - Hawassa University</title>
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
    <h2 class="section-title" style="text-align: left;">Manage Lecturers</h2>

    <div class="dashboard-container">
      <aside class="sidebar">
        <h3>Admin Menu</h3>
        <nav class="sidebar-nav">
          <a href="admin-dashboard.php">Overview</a>
          <a href="manage-students.php">Manage Students</a>
          <a href="manage-lecturers.php" class="active">Manage Lecturers</a>
          <a href="manage-reviews.php">All Reviews</a>
          <a href="system-statistics.php">Reports</a>
          <a href="../logout.php">Logout</a>
        </nav>
      </aside>

      <main class="main-content">

        <!-- Add New Lecturer Form -->
        <div class="card" style="margin-bottom: 24px;">
          <h3 style="color: var(--primary-blue); margin-bottom: 20px;">Add New Lecturer</h3>

          <?php if ($addSuccess): ?>
            <p style="color: green; margin-bottom: 15px;"><?php echo htmlspecialchars($addSuccess); ?></p>
          <?php elseif ($addError): ?>
            <p style="color: red; margin-bottom: 15px;"><?php echo htmlspecialchars($addError); ?></p>
          <?php endif; ?>

          <form method="POST" action="manage-lecturers.php"
                style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
            <div class="form-group" style="margin-bottom: 0;">
              <label>Full Name</label>
              <input type="text" name="name" class="form-control" placeholder="e.g. Dr. Abebe Bekele" required>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
              <label>Valid ID Number</label>
              <input type="number" name="valid_id" class="form-control" placeholder="e.g. 10049" required>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
              <label>Department</label>
              <select name="department_id" class="form-control" required>
                <option value="">Select department</option>
                <?php foreach ($departments as $dept): ?>
                  <option value="<?php echo $dept['id']; ?>">
                    <?php echo htmlspecialchars($dept['department']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div style="display: flex; align-items: flex-end;">
              <button type="submit" name="add_lecturer" class="btn btn-blue" style="width: 100%;">
                + Add Lecturer
              </button>
            </div>
          </form>
        </div>

        <!-- Lecturer Directory -->
        <div class="card">
          <h3 style="color: var(--primary-blue); margin-bottom: 20px;">
            Lecturer Directory
            <span style="font-size: 0.9rem; font-weight: normal; color: var(--text-light); margin-left: 10px;">
              <?php echo count($lecturers); ?> lecturer<?php echo count($lecturers) !== 1 ? 's' : ''; ?>
              <?php echo $search !== '' ? 'found' : 'registered'; ?>
            </span>
          </h3>

          <?php if ($deleteSuccess): ?>
            <p style="color: green; margin-bottom: 15px;"><?php echo htmlspecialchars($deleteSuccess); ?></p>
          <?php elseif ($deleteError): ?>
            <p style="color: red; margin-bottom: 15px;"><?php echo htmlspecialchars($deleteError); ?></p>
          <?php endif; ?>

          <!-- Search -->
          <form method="GET" action="manage-lecturers.php" style="margin-bottom: 20px; display: flex; gap: 10px;">
            <input type="text" name="search" class="form-control"
                   placeholder="Search by name or department..."
                   value="<?php echo htmlspecialchars($search); ?>"
                   style="max-width: 350px;">
            <button type="submit" class="btn btn-blue" style="padding: 8px 16px;">Search</button>
            <?php if ($search !== ''): ?>
              <a href="manage-lecturers.php" class="btn btn-secondary" style="padding: 8px 16px;">Clear</a>
            <?php endif; ?>
          </form>

          <?php if (empty($lecturers)): ?>
            <p style="color: var(--text-light);">No lecturers found.</p>
          <?php else: ?>
            <div style="overflow-x: auto;">
              <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                  <tr style="border-bottom: 2px solid var(--border-color);">
                    <th style="padding: 10px;">ID</th>
                    <th style="padding: 10px;">Name</th>
                    <th style="padding: 10px;">Department</th>
                    <th style="padding: 10px;">Username</th>
                    <th style="padding: 10px;">Rating</th>
                    <th style="padding: 10px;">Reviews</th>
                    <th style="padding: 10px;">Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($lecturers as $lec): ?>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                      <td style="padding: 10px; font-weight: bold;">
                        <?php echo htmlspecialchars($lec['lecturer_id_number']); ?>
                      </td>
                      <td style="padding: 10px;">
                        <?php echo htmlspecialchars($lec['name']); ?>
                      </td>
                      <td style="padding: 10px;">
                        <?php echo htmlspecialchars($lec['department']); ?>
                      </td>
                      <td style="padding: 10px; color: var(--text-light); font-size: 0.9rem;">
                        <?php echo $lec['username'] ? htmlspecialchars($lec['username']) : '<em>No account</em>'; ?>
                      </td>
                      <td style="padding: 10px; color: var(--accent-gold);">
                        <?php echo $lec['avg_rating'] !== null ? $lec['avg_rating'] . ' ★' : '—'; ?>
                      </td>
                      <td style="padding: 10px; color: var(--text-light);">
                        <?php echo $lec['review_count']; ?>
                      </td>
                      <td style="padding: 10px;">
                        <form method="POST" action="manage-lecturers.php"
                              onsubmit="return confirm('Delete <?php echo htmlspecialchars(addslashes($lec['name'])); ?>? This will also remove their account and all reviews.');">
                          <input type="hidden" name="lecturer_id" value="<?php echo $lec['id']; ?>">
                          <button type="submit" name="delete_lecturer"
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