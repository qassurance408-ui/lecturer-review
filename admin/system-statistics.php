<?php
require_once '../data/database.php';
require_once '../helpers/functions.php';

requireLogin();

if ($_SESSION['user_type'] !== 'admin') {
    redirect('../unauth.php');
}

$adminUsername = $_SESSION['username'];

// platform wide summary
$summary = $pdo->query("
    SELECT
        (SELECT COUNT(*) FROM lecturers)                    AS total_lecturers,
        (SELECT COUNT(*) FROM students)                     AS total_students,
        (SELECT COUNT(*) FROM reviews)                      AS total_reviews,
        (SELECT ROUND(AVG(rating), 1) FROM reviews)         AS avg_rating
")->fetch();

// reviews per department
$dept_stats = $pdo->query("
    SELECT
        departments.department,
        COUNT(reviews.id)             AS review_count,
        ROUND(AVG(reviews.rating), 1) AS avg_rating
    FROM departments
    LEFT JOIN lecturers ON lecturers.department_id = departments.id
    LEFT JOIN reviews   ON reviews.lecturer_id     = lecturers.id
    GROUP BY departments.id, departments.department
    ORDER BY review_count DESC
")->fetchAll();

$totalReviews = (int)$summary['total_reviews'];

// highest rated lecturers
$top_lecturers = $pdo->query("
    SELECT
        lecturers.name,
        departments.department,
        ROUND(AVG(reviews.rating), 1) AS avg_rating,
        COUNT(reviews.id)             AS review_count
    FROM lecturers
    INNER JOIN departments ON lecturers.department_id = departments.id
    INNER JOIN reviews     ON reviews.lecturer_id     = lecturers.id
    GROUP BY lecturers.id, lecturers.name, departments.department
    HAVING review_count >= 1
    ORDER BY avg_rating DESC, review_count DESC
    LIMIT 5
")->fetchAll();

// most reviews lecturers
$most_reviewed = $pdo->query("
    SELECT
        lecturers.name,
        departments.department,
        COUNT(reviews.id)             AS review_count,
        ROUND(AVG(reviews.rating), 1) AS avg_rating
    FROM lecturers
    INNER JOIN departments ON lecturers.department_id = departments.id
    INNER JOIN reviews     ON reviews.lecturer_id     = lecturers.id
    GROUP BY lecturers.id, lecturers.name, departments.department
    ORDER BY review_count DESC
    LIMIT 5
")->fetchAll();

// rating distribution
$dist_rows = $pdo->query("
    SELECT rating, COUNT(*) AS total
    FROM reviews
    GROUP BY rating
    ORDER BY rating DESC
")->fetchAll();

$distribution = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
foreach ($dist_rows as $row) {
    $distribution[(int)$row['rating']] = (int)$row['total'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>System Statistics - Hawassa University</title>
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
    <h2 class="section-title" style="text-align: left;">System Statistics & Reports</h2>

    <div class="dashboard-container">
      <aside class="sidebar">
        <h3>Admin Menu</h3>
        <nav class="sidebar-nav">
          <a href="admin-dashboard.php">Overview</a>
          <a href="manage-students.php">Manage Students</a>
          <a href="manage-lecturers.php">Manage Lecturers</a>
          <a href="manage-reviews.php">All Reviews</a>
          <a href="system-statistics.php" class="active">Reports</a>
          <a href="../logout.php">Logout</a>
        </nav>
      </aside>

      <main class="main-content">

        <?php if ($totalReviews === 0): ?>
          <div class="card">
            <p style="color: var(--text-light);">No review data yet — statistics will appear once students submit feedback.</p>
          </div>
        <?php else: ?>

          <!-- Summary stat cards -->
          <div class="stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); margin-bottom: 24px;">
            <div class="stat-card">
              <div class="stat-value"><?php echo number_format($summary['total_lecturers']); ?></div>
              <div class="stat-label">Lecturers</div>
            </div>
            <div class="stat-card">
              <div class="stat-value"><?php echo number_format($summary['total_students']); ?></div>
              <div class="stat-label">Students</div>
            </div>
            <div class="stat-card">
              <div class="stat-value"><?php echo number_format($summary['total_reviews']); ?></div>
              <div class="stat-label">Reviews</div>
            </div>
            <div class="stat-card" style="border-top-color: var(--light-blue);">
              <div class="stat-value" style="color: var(--light-blue);">
                <?php echo $summary['avg_rating'] ?? 'N/A'; ?>
              </div>
              <div class="stat-label">Platform Avg Rating</div>
            </div>
          </div>

          <!-- Rating distribution + Department activity -->
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">

            <!-- Platform-wide rating distribution -->
            <div class="card">
              <h3 style="color: var(--primary-blue); margin-bottom: 20px;">Platform Rating Distribution</h3>
              <?php for ($star = 5; $star >= 1; $star--): ?>
                <?php
                  $count   = $distribution[$star];
                  $percent = $totalReviews > 0 ? round(($count / $totalReviews) * 100) : 0;
                ?>
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
                  <span style="width: 24px; text-align: right;"><?php echo $star; ?>★</span>
                  <div style="flex: 1; background: var(--border-color); border-radius: 4px; height: 10px; overflow: hidden;">
                    <div style="height: 100%; width: <?php echo $percent; ?>%; background: var(--accent-gold); border-radius: 4px;"></div>
                  </div>
                  <span style="width: 38px; font-size: 0.85rem; color: var(--text-light);"><?php echo $percent; ?>%</span>
                  <span style="width: 28px; font-size: 0.85rem; color: var(--text-light);">(<?php echo $count; ?>)</span>
                </div>
              <?php endfor; ?>
            </div>

            <!-- Activity by department -->
            <div class="card">
              <h3 style="color: var(--primary-blue); margin-bottom: 20px;">Activity by Department</h3>
              <?php foreach ($dept_stats as $dept): ?>
                <?php
                  $pct = $totalReviews > 0 ? round(($dept['review_count'] / $totalReviews) * 100) : 0;
                ?>
                <div style="margin-bottom: 14px;">
                  <div style="display: flex; justify-content: space-between; font-size: 0.9rem; margin-bottom: 4px;">
                    <span><?php echo htmlspecialchars($dept['department']); ?></span>
                    <span style="color: var(--text-light);">
                      <?php echo $dept['review_count']; ?> reviews
                      &mdash;
                      <?php echo $dept['avg_rating'] !== null ? $dept['avg_rating'] . ' ★' : 'No reviews'; ?>
                    </span>
                  </div>
                  <div style="background: var(--border-color); border-radius: 4px; height: 8px; overflow: hidden;">
                    <div style="height: 100%; width: <?php echo $pct; ?>%; background: var(--primary-blue); border-radius: 4px;"></div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>

          </div>

          <!-- Top rated + Most reviewed -->
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">

            <!-- Top 5 highest rated -->
            <div class="card">
              <h3 style="color: var(--primary-blue); margin-bottom: 20px;">Top Rated Lecturers</h3>
              <?php if (empty($top_lecturers)): ?>
                <p style="color: var(--text-light);">No data yet.</p>
              <?php else: ?>
                <?php foreach ($top_lecturers as $i => $lec): ?>
                  <div style="display: flex; justify-content: space-between; align-items: center;
                              padding: 10px 0;
                              <?php echo $i < count($top_lecturers) - 1 ? 'border-bottom: 1px solid var(--border-color);' : ''; ?>">
                    <div>
                      <span style="font-weight: bold; font-size: 0.95rem;">
                        <?php echo htmlspecialchars($lec['name']); ?>
                      </span><br>
                      <span style="font-size: 0.8rem; color: var(--text-light);">
                        <?php echo htmlspecialchars($lec['department']); ?>
                        &mdash; <?php echo $lec['review_count']; ?> review<?php echo $lec['review_count'] !== 1 ? 's' : ''; ?>
                      </span>
                    </div>
                    <span style="color: var(--accent-gold); font-weight: bold; white-space: nowrap; margin-left: 12px;">
                      <?php echo $lec['avg_rating']; ?> ★
                    </span>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>

            <!-- Top 5 most reviewed -->
            <div class="card">
              <h3 style="color: var(--primary-blue); margin-bottom: 20px;">Most Reviewed Lecturers</h3>
              <?php if (empty($most_reviewed)): ?>
                <p style="color: var(--text-light);">No data yet.</p>
              <?php else: ?>
                <?php foreach ($most_reviewed as $i => $lec): ?>
                  <div style="display: flex; justify-content: space-between; align-items: center;
                              padding: 10px 0;
                              <?php echo $i < count($most_reviewed) - 1 ? 'border-bottom: 1px solid var(--border-color);' : ''; ?>">
                    <div>
                      <span style="font-weight: bold; font-size: 0.95rem;">
                        <?php echo htmlspecialchars($lec['name']); ?>
                      </span><br>
                      <span style="font-size: 0.8rem; color: var(--text-light);">
                        <?php echo htmlspecialchars($lec['department']); ?>
                        &mdash; avg <?php echo $lec['avg_rating']; ?> ★
                      </span>
                    </div>
                    <span style="font-weight: bold; color: var(--primary-blue); white-space: nowrap; margin-left: 12px;">
                      <?php echo $lec['review_count']; ?> review<?php echo $lec['review_count'] !== 1 ? 's' : ''; ?>
                    </span>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>

          </div>

        <?php endif; ?>

      </main>
    </div>
  </div>

  <script src="../js/main.js"></script>
</body>
</html>