<?php
require_once '../data/database.php';
require_once '../helpers/functions.php';

requireLogin();

if ($_SESSION['user_type'] !== 'admin') {
    redirect('../unauth.php');
}

$adminUsername = $_SESSION['username'];

//summary counts
$counts_sql = "
    SELECT
        (SELECT COUNT(*) FROM lecturers)                        AS total_lecturers,
        (SELECT COUNT(*) FROM students)                         AS total_students,
        (SELECT COUNT(*) FROM reviews)                          AS total_reviews,
        (SELECT ROUND(AVG(rating), 1) FROM reviews)             AS avg_rating
";

$counts = $pdo->query($counts_sql)->fetch();

//recent reviews
$recent_sql = "
    SELECT
        reviews.id,
        reviews.review,
        reviews.rating,
        reviews.created_at,
        lecturers.name      AS lecturer_name,
        departments.department,
        students.username   AS student_username
    FROM reviews
    INNER JOIN lecturers    ON reviews.lecturer_id  = lecturers.id
    INNER JOIN departments  ON lecturers.department_id = departments.id
    INNER JOIN students     ON reviews.student_id   = students.id
    ORDER BY reviews.created_at DESC
    LIMIT 5
";

$recent_reviews = $pdo->query($recent_sql)->fetchAll();

// department overview
$dept_sql = "
    SELECT
        departments.department,
        ROUND(AVG(reviews.rating), 1) AS avg_rating,
        COUNT(reviews.id)             AS review_count
    FROM departments
    LEFT JOIN lecturers  ON lecturers.department_id  = departments.id
    LEFT JOIN reviews    ON reviews.lecturer_id       = lecturers.id
    GROUP BY departments.id, departments.department
    ORDER BY avg_rating DESC
";

$departments = $pdo->query($dept_sql)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard - Hawassa University</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/responsive.css">
  <link rel="stylesheet" href="../css/dashboard.css">
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
    <h2 class="section-title" style="text-align: left;">System Administration Overview</h2>

    <div class="dashboard-container">
      <aside class="sidebar">
        <h3>Admin Menu</h3>
        <nav class="sidebar-nav">
          <a href="admin-dashboard.php" class="active">Overview</a>
          <a href="manage-students.php">Manage Students</a>
          <a href="manage-lecturers.php">Manage Lecturers</a>
          <a href="manage-reviews.php">All Reviews</a>
          <a href="system-statistics.php">Reports</a>
          <a href="../logout.php">Logout</a>
        </nav>
      </aside>

      <main class="main-content">

        <!-- Stats Grid -->
        <div class="stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
          <div class="stat-card">
            <div class="stat-value"><?php echo number_format($counts['total_lecturers']); ?></div>
            <div class="stat-label">Total Lecturers</div>
          </div>
          <div class="stat-card">
            <div class="stat-value"><?php echo number_format($counts['total_students']); ?></div>
            <div class="stat-label">Total Students</div>
          </div>
          <div class="stat-card">
            <div class="stat-value"><?php echo number_format($counts['total_reviews']); ?></div>
            <div class="stat-label">Reviews Submitted</div>
          </div>
          <div class="stat-card" style="border-top-color: var(--light-blue);">
            <div class="stat-value" style="color: var(--light-blue);">
              <?php echo $counts['avg_rating'] ?? 'N/A'; ?>
            </div>
            <div class="stat-label">Avg University Rating</div>
          </div>
        </div>

        <!-- Recent Reviews & Department Overview -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-top: 30px;">

          <!-- Recent Reviews -->
          <div class="recent-activity">
            <h3>Recent Global Reviews</h3>

            <?php if (empty($recent_reviews)): ?>
              <p style="color: var(--text-light);">No reviews yet.</p>
            <?php else: ?>
              <?php foreach ($recent_reviews as $r): ?>
                <div class="activity-item">
                  <strong>
                    <?php echo htmlspecialchars($r['lecturer_name']); ?>
                    (<?php echo htmlspecialchars($r['department']); ?>)
                  </strong>
                  &nbsp;-&nbsp;
                  <span style="color: var(--accent-gold);">
                    <?php echo str_repeat('★', (int)$r['rating']); ?>
                    <?php echo str_repeat('☆', 5 - (int)$r['rating']); ?>
                  </span>
                  <p style="font-size: 0.85rem; color: var(--text-light); margin-top: 5px;">
                    Reviewed by: <?php echo htmlspecialchars($r['student_username']); ?>
                    &mdash;
                    <?php echo date('M j, Y', strtotime($r['created_at'])); ?>
                  </p>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>

            <a href="manage-reviews.php"
               style="display: inline-block; margin-top: 15px; color: var(--primary-blue); font-weight: bold;">
              View All Reviews &rarr;
            </a>
          </div>

          <!-- Department Overview -->
          <div class="recent-activity" style="background: transparent; border: none; box-shadow: none; padding: 0;">
            <h3 style="background: var(--card-bg); padding: 15px; border-radius: 8px 8px 0 0;
                       margin-bottom: 0; border: 1px solid var(--border-color); border-bottom: none;">
              Department Overview
            </h3>
            <div style="background: var(--card-bg); border: 1px solid var(--border-color);
                        border-radius: 0 0 8px 8px; padding: 15px;
                        display: flex; flex-direction: column; gap: 10px;">

              <?php if (empty($departments)): ?>
                <p style="color: var(--text-light);">No department data available.</p>
              <?php else: ?>
                <?php foreach ($departments as $i => $dept): ?>
                  <?php $isLast = $i === array_key_last($departments); ?>
                  <div style="display: flex; justify-content: space-between;
                              <?php echo !$isLast ? 'padding-bottom: 10px; border-bottom: 1px solid var(--border-color);' : ''; ?>">
                    <span><?php echo htmlspecialchars($dept['department']); ?></span>
                    <span style="font-weight: bold;">
                      <?php echo $dept['avg_rating'] !== null ? $dept['avg_rating'] . ' ★' : 'No reviews'; ?>
                    </span>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>

            </div>
          </div>

        </div>

      </main>
    </div>
  </div>

  <script src="../js/main.js"></script>
</body>
</html>