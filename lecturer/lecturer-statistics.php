<?php
require_once '../data/database.php';
require_once '../helpers/functions.php';

requireLogin();

if ($_SESSION['user_type'] !== 'lecturer') {
    redirect('../unauth.php');
}

//get lecturer
$lecturer_sql = "
    SELECT
        lecturers.id AS lecturer_id,
        lecturers.name
    FROM lecturer_accounts
    INNER JOIN lecturers
        ON lecturer_accounts.lecturer_id = lecturers.id
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

$lecturerId   = $lecturer['lecturer_id'];
$lecturerName = $lecturer['name'];

//rating distribution
$dist_sql = "
    SELECT
        rating,
        COUNT(*) AS total
    FROM reviews
    WHERE lecturer_id = ?
    GROUP BY rating
    ORDER BY rating DESC
";

$dist_stmt = $pdo->prepare($dist_sql);
$dist_stmt->execute([$lecturerId]);
$distRows = $dist_stmt->fetchAll();

// Build a keyed array [5 => count, 4 => count, ...]  defaulting to 0
$distribution = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
foreach ($distRows as $row) {
    $distribution[(int)$row['rating']] = (int)$row['total'];
}

$totalReviews = array_sum($distribution);

//overall average rating
$avg_sql = "
    SELECT
        ROUND(AVG(rating), 1) AS avg_rating,
        COUNT(*)              AS review_count
    FROM reviews
    WHERE lecturer_id = ?
";

$avg_stmt = $pdo->prepare($avg_sql);
$avg_stmt->execute([$lecturerId]);
$stats = $avg_stmt->fetch();

$avgRating   = $stats['avg_rating']   ?? 0;
$reviewCount = $stats['review_count'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Statistics - Hawassa University</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/responsive.css">
  <link rel="stylesheet" href="../css/dashboard.css">
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
        <img src="https://placehold.co/40x40" alt="Lecturer Avatar"
             style="border-radius:50%; width:35px; height:35px; border:2px solid rgba(255,255,255,0.85);">
        <span class="profile-name" style="color:white; font-weight:bold;">
          <?php echo htmlspecialchars($lecturerName); ?>
        </span>
      </div>
    </div>
    <button class="mobile-menu-btn">☰</button>
  </nav>

  <!-- Page Content -->
  <div class="container">
    <h2 class="section-title" style="text-align: left;">Performance Statistics</h2>

    <div class="dashboard-container">
      <!-- Sidebar Navigation -->
      <aside class="sidebar">
        <h3>Menu</h3>
        <nav class="sidebar-nav">
          <a href="lecturer-dashboard.php">Overview</a>
          <a href="lecturer-feedback.php">Recent Reviews</a>
          <a href="lecturer-statistics.php" class="active">Performance Stats</a>
          <a href="lecturer-profile.php">Update Profile</a>
          <a href="../logout.php">Logout</a>

        </nav>
      </aside>

      <!-- Main Content -->
      <main class="main-content">

        <?php if ($totalReviews === 0): ?>
          <div class="card">
            <p>No reviews yet — statistics will appear once students submit feedback.</p>
          </div>

        <?php else: ?>

          <!-- Summary Cards -->
          <div class="grid-3" style="gap: 20px; margin-bottom: 20px;">

            <div class="card" style="text-align: center;">
              <p style="color: var(--text-light); margin-bottom: 6px;">Average Rating</p>
              <h2 style="color: var(--accent-gold); font-size: 2.5rem; margin: 0;">
                <?php echo $avgRating; ?>
              </h2>
              <p style="color: var(--accent-gold); font-size: 1.2rem; margin-top: 4px;">
                <?php echo str_repeat('★', (int)round($avgRating)); ?>
                <?php echo str_repeat('☆', 5 - (int)round($avgRating)); ?>
              </p>
            </div>

            <div class="card" style="text-align: center;">
              <p style="color: var(--text-light); margin-bottom: 6px;">Total Reviews</p>
              <h2 style="color: var(--primary-blue); font-size: 2.5rem; margin: 0;">
                <?php echo $reviewCount; ?>
              </h2>
            </div>

          </div>

          <!-- Rating Distribution -->
          <div class="card">
            <h3 style="color: var(--primary-blue); margin-bottom: 20px;">Rating Distribution</h3>

            <?php for ($star = 5; $star >= 1; $star--): ?>
              <?php
                $count   = $distribution[$star];
                $percent = $totalReviews > 0 ? round(($count / $totalReviews) * 100) : 0;
              ?>
              <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                <span style="width: 24px; text-align: right;"><?php echo $star; ?>★</span>
                <div style="flex: 1; background: var(--border-color); border-radius: 4px; height: 10px; overflow: hidden;">
                  <div style="height: 100%; width: <?php echo $percent; ?>%; background: var(--accent-gold); border-radius: 4px;"></div>
                </div>
                <span style="width: 40px; color: var(--text-light); font-size: 0.9rem;">
                  <?php echo $percent; ?>%
                </span>
                <span style="width: 30px; color: var(--text-light); font-size: 0.85rem;">
                  (<?php echo $count; ?>)
                </span>
              </div>
            <?php endfor; ?>
          </div>

        <?php endif; ?>

      </main>
    </div>
  </div>

  <script src="../js/main.js"></script>
</body>
</html>