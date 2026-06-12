<?php
require_once '../data/database.php';
require_once '../helpers/functions.php';

requireLogin();

if ($_SESSION['user_type'] !== 'admin') {
    redirect('../unauth.php');
}

$adminUsername = $_SESSION['username'];

//flash messages
$flashSuccess = '';
$flashError   = '';

if (isset($_SESSION['flash_success'])) {
    $flashSuccess = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}
if (isset($_SESSION['flash_error'])) {
    $flashError = $_SESSION['flash_error'];
    unset($_SESSION['flash_error']);
}

//search filter
$search = trim($_GET['search'] ?? '');

if ($search !== '') {
    $reviews_sql = "
        SELECT
            reviews.id,
            reviews.review,
            reviews.rating,
            reviews.created_at,
            lecturers.name          AS lecturer_name,
            departments.department,
            students.username       AS student_username,
            valid_ids.valid_id      AS student_id_number,
            courses.course
        FROM reviews
        INNER JOIN lecturers    ON reviews.lecturer_id     = lecturers.id
        INNER JOIN departments  ON lecturers.department_id = departments.id
        INNER JOIN students     ON reviews.student_id      = students.id
        INNER JOIN valid_ids    ON students.valid_id       = valid_ids.id
        INNER JOIN courses      ON reviews.course_id       = courses.id
        WHERE lecturers.name    LIKE :search_lecturer
           OR students.username LIKE :search_student
           OR courses.course    LIKE :search_course
        ORDER BY reviews.created_at DESC
    ";
    $reviews_stmt = $pdo->prepare($reviews_sql);
    $reviews_stmt->execute([
        ':search_lecturer' => '%' . $search . '%',
        ':search_student'  => '%' . $search . '%',
        ':search_course'   => '%' . $search . '%',
    ]);
} else {
    $reviews_sql = "
        SELECT
            reviews.id,
            reviews.review,
            reviews.rating,
            reviews.created_at,
            lecturers.name          AS lecturer_name,
            departments.department,
            students.username       AS student_username,
            valid_ids.valid_id      AS student_id_number,
            courses.course
        FROM reviews
        INNER JOIN lecturers    ON reviews.lecturer_id     = lecturers.id
        INNER JOIN departments  ON lecturers.department_id = departments.id
        INNER JOIN students     ON reviews.student_id      = students.id
        INNER JOIN valid_ids    ON students.valid_id       = valid_ids.id
        INNER JOIN courses      ON reviews.course_id       = courses.id
        ORDER BY reviews.created_at DESC
    ";
    $reviews_stmt = $pdo->prepare($reviews_sql);
    $reviews_stmt->execute();
}

$reviews = $reviews_stmt->fetchAll();


//handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_review'])) {
    $reviewId = (int)($_POST['review_id'] ?? 0);

    if ($reviewId > 0) {
        $del_stmt = $pdo->prepare("DELETE FROM reviews WHERE id = ?");
        $del_stmt->execute([$reviewId]);
        $_SESSION['flash_success'] = 'Review deleted successfully.';
    } else {
        $_SESSION['flash_error'] = 'Invalid review ID.';
    }

    $qs = $search !== '' ? '?search=' . urlencode($_GET['search'] ?? '') : '';
    header('Location: manage-reviews.php' . $qs);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Reviews - Hawassa University</title>
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
    <h2 class="section-title" style="text-align: left;">Manage Reviews</h2>

    <div class="dashboard-container">
      <aside class="sidebar">
        <h3>Admin Menu</h3>
        <nav class="sidebar-nav">
          <a href="admin-dashboard.php">Overview</a>
          <a href="manage-students.php">Manage Students</a>
          <a href="manage-lecturers.php">Manage Lecturers</a>
          <a href="manage-reviews.php" class="active">All Reviews</a>
          <a href="system-statistics.php">Reports</a>
          <a href="../logout.php">Logout</a>
        </nav>
      </aside>

      <main class="main-content">

        <!-- Search -->
        <form method="GET" action="manage-reviews.php"
              style="margin-bottom: 20px; display: flex; gap: 10px;">
          <input type="text" name="search" class="form-control"
                 placeholder="Search by lecturer, student or course..."
                 value="<?php echo htmlspecialchars($search); ?>"
                 style="max-width: 350px;">
          <button type="submit" class="btn btn-blue" style="padding: 8px 16px;">Search</button>
          <?php if ($search !== ''): ?>
            <a href="manage-reviews.php" class="btn btn-secondary" style="padding: 8px 16px;">Clear</a>
          <?php endif; ?>
        </form>

        <?php if ($flashSuccess): ?>
          <p style="color: green; margin-bottom: 15px;"><?php echo htmlspecialchars($flashSuccess); ?></p>
        <?php elseif ($flashError): ?>
          <p style="color: red; margin-bottom: 15px;"><?php echo htmlspecialchars($flashError); ?></p>
        <?php endif; ?>

        <p style="color: var(--text-light); margin-bottom: 15px; font-size: 0.9rem;">
          <?php echo count($reviews); ?> review<?php echo count($reviews) !== 1 ? 's' : ''; ?>
          <?php echo $search !== '' ? 'found' : 'total'; ?>
        </p>

        <?php if (empty($reviews)): ?>
          <div class="card">
            <p style="color: var(--text-light);">No reviews found.</p>
          </div>
        <?php else: ?>

          <?php foreach ($reviews as $review): ?>
            <div class="card" style="margin-bottom: 16px;">

              <!-- Top row: lecturer + rating + date -->
              <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                <div>
                  <span style="font-weight: bold; font-size: 1rem;">
                    <?php echo htmlspecialchars($review['lecturer_name']); ?>
                  </span>
                  <span style="color: var(--text-light); font-size: 0.85rem; margin-left: 8px;">
                    <?php echo htmlspecialchars($review['department']); ?>
                  </span>
                </div>
                <span style="color: var(--text-light); font-size: 0.8rem; white-space: nowrap; margin-left: 16px;">
                  <?php echo date('M j, Y', strtotime($review['created_at'])); ?>
                </span>
              </div>

              <!-- Star rating + course -->
              <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 10px;">
                <span style="color: var(--accent-gold); font-size: 1.1rem;">
                  <?php echo str_repeat('★', (int)$review['rating']); ?>
                  <?php echo str_repeat('☆', 5 - (int)$review['rating']); ?>
                </span>
                <span style="color: var(--text-light); font-size: 0.85rem;">
                  <?php echo htmlspecialchars($review['course']); ?>
                </span>
              </div>

              <!-- Review text -->
              <p style="font-size: 0.95rem; line-height: 1.6; margin-bottom: 12px; white-space: pre-line;">
                <?php echo nl2br(htmlspecialchars($review['review'])); ?>
              </p>

              <!-- Bottom row: student info + delete -->
              <div style="display: flex; justify-content: space-between; align-items: center;
                          padding-top: 10px; border-top: 1px solid var(--border-color);">
                <span style="font-size: 0.85rem; color: var(--text-light);">
                  Submitted by:
                  <strong><?php echo htmlspecialchars($review['student_id_number']); ?></strong>
                  &mdash; <?php echo htmlspecialchars($review['student_username']); ?>
                </span>
                <form method="POST" action="manage-reviews.php"
                      onsubmit="return confirm('Delete this review? This cannot be undone.');">
                  <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                  <button type="submit" name="delete_review"
                          class="btn btn-secondary"
                          style="padding: 5px 12px; color: #d32f2f; border-color: #d32f2f;">
                    Delete
                  </button>
                </form>
              </div>

            </div>
          <?php endforeach; ?>

        <?php endif; ?>

      </main>
    </div>
  </div>

  <script src="../js/main.js"></script>
</body>
</html>