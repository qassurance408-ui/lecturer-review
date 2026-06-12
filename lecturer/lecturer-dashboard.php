<?php
require_once '../data/database.php';
require_once '../helpers/functions.php';

requireLogin();

if($_SESSION['user_type'] !== 'lecturer'){
    redirect('../unauth.php');
}

//logged in lecturer

$account_id = $_SESSION['user_id'];

$lecturer_sql = "
    SELECT
        lecturers.id,
        lecturers.name,
        departments.department
    FROM lecturer_accounts
    INNER JOIN lecturers
        ON lecturer_accounts.lecturer_id = lecturers.id
    INNER JOIN departments
        ON lecturers.department_id = departments.id
    WHERE lecturer_accounts.id = ?
    LIMIT 1
";

$lecturer_stmt = $pdo->prepare($lecturer_sql);
$lecturer_stmt->execute([$account_id]);

$lecturer = $lecturer_stmt->fetch(PDO::FETCH_ASSOC);

if(!$lecturer){
    die('Lecturer not found.');
}

$lecturer_id = $lecturer['id'];
$lecturer_name = $lecturer['name'];
$department = $lecturer['department'];

//stats

$stats_sql = "
    SELECT
        COUNT(*) AS total_reviews,
        ROUND(AVG(rating),1) AS average_rating
    FROM reviews
    WHERE lecturer_id = ?
";

$stats_stmt = $pdo->prepare($stats_sql);
$stats_stmt->execute([$lecturer_id]);

$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

$total_reviews = $stats['total_reviews'] ?? 0;
$average_rating = $stats['average_rating'] ?? 0;

//reviews this week

$week_sql = "
    SELECT COUNT(*) AS weekly_reviews
    FROM reviews
    WHERE lecturer_id = ?
    AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
";

$week_stmt = $pdo->prepare($week_sql);
$week_stmt->execute([$lecturer_id]);

$weekly_reviews = $week_stmt->fetchColumn();

//latest reviews

$reviews_sql = "
    SELECT
        review,
        rating,
        created_at
    FROM reviews
    WHERE lecturer_id = ?
    ORDER BY created_at DESC
    LIMIT 5
";

$reviews_stmt = $pdo->prepare($reviews_sql);
$reviews_stmt->execute([$lecturer_id]);

$latest_reviews = $reviews_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Lecturer Dashboard - Hawassa University</title>
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
        <img src="https://placehold.co/40x40"
             alt="Lecturer Avatar"
             style="border-radius:50%; width:35px; height:35px; border:2px solid rgba(255, 255, 255, 0.85);">

        <span class="profile-name" style="color:white; font-weight:bold;">
          <?php echo htmlspecialchars($lecturer_name); ?>
        </span>
      </div>
    </div>

    <button class="mobile-menu-btn">☰</button>
  </nav>

  <!-- Page Content -->
  <div class="container">

    <h2 class="section-title" style="text-align: left;">
      Welcome, <?php echo htmlspecialchars($lecturer_name); ?>
    </h2>

    <div class="dashboard-container">

      <!-- Sidebar -->
      <aside class="sidebar">
        <h3>Menu</h3>

        <nav class="sidebar-nav">
          <a href="lecturer-dashboard.php" class="active">Overview</a>
          <a href="lecturer-feedback.php">Recent Reviews</a>
          <a href="lecturer-statistics.php">Performance Stats</a>
          <a href="lecturer-profile.php">Update Profile</a>
          <a href="../logout.php">Logout</a>
        </nav>
      </aside>

      <!-- Main Content -->
      <main class="main-content">

        <!-- Statistics -->
        <div class="stat-grid">

          <div class="stat-card">
            <div class="stat-value" style="color: var(--accent-gold);">
              <?php echo $average_rating; ?>
            </div>
            <div class="stat-label">Average Rating</div>
          </div>

          <div class="stat-card">
            <div class="stat-value">
              <?php echo $total_reviews; ?>
            </div>
            <div class="stat-label">Total Reviews</div>
          </div>

          <div class="stat-card">
            <div class="stat-value">
              <?php echo $weekly_reviews; ?>
            </div>
            <div class="stat-label">New Reviews This Week</div>
          </div>

        </div>

        <!-- Latest Reviews -->
        <div class="recent-activity" style="margin-top: 30px;">

          <h3>Latest Student Feedback</h3>

          <?php if(empty($latest_reviews)): ?>

            <div class="activity-item">
              <p>No reviews have been submitted yet.</p>
            </div>

          <?php else: ?>

            <?php foreach($latest_reviews as $review): ?>

              <div class="activity-item">

                <span style="color: var(--accent-gold); font-size: 1.2rem;">

                  <?php
                  echo str_repeat('★', $review['rating']);
                  echo str_repeat('☆', 5 - $review['rating']);
                  ?>

                </span>

                <p style="margin-top: 5px;">
                  "<?php echo htmlspecialchars($review['review']); ?>"
                </p>

                <p style="font-size: 0.8rem; color: var(--text-light); margin-top: 5px;">
                  <?php echo date('M d, Y', strtotime($review['created_at'])); ?>
                </p>

              </div>

            <?php endforeach; ?>

          <?php endif; ?>

          <a href="lecturer-feedback.php"
             style="display: inline-block; margin-top: 15px; color: var(--primary-blue); font-weight: bold;">
            Read all feedback →
          </a>

        </div>

      </main>
    </div>
  </div>

  <script src="../js/main.js"></script>

</body>
</html>