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

    session_unset();
    session_destroy();

    redirect('../login.php');
}

//review stats 

$review_count_sql = "
    SELECT COUNT(*) AS total_reviews
    FROM reviews
    WHERE student_id = ?
";

$review_count_stmt = $pdo->prepare($review_count_sql);
$review_count_stmt->execute([$student['id']]);

$reviewCount = $review_count_stmt->fetch()['total_reviews'];

//pending evals

$pending_sql = "
    SELECT COUNT(*) AS pending_reviews
    FROM lecturers
    WHERE lecturers.id NOT IN (
        SELECT lecturer_id
        FROM reviews
        WHERE student_id = ?
    )
";

$pending_stmt = $pdo->prepare($pending_sql);
$pending_stmt->execute([$student['id']]);

$pendingCount = $pending_stmt->fetch()['pending_reviews'];

//recent activity 
$recent_sql = "
    SELECT
        reviews.review,
        reviews.rating,
        reviews.created_at,
        lecturers.name AS lecturer_name
    FROM reviews
    INNER JOIN lecturers
        ON reviews.lecturer_id = lecturers.id
    WHERE reviews.student_id = ?
    ORDER BY reviews.created_at DESC
    LIMIT 5
";

$recent_stmt = $pdo->prepare($recent_sql);
$recent_stmt->execute([$student['id']]);

$recentActivities = $recent_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - Hawassa University</title>

  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/responsive.css">
  <link rel="stylesheet" href="../css/dashboard.css">
</head>

<body>

  <!-- Navigation Bar -->
  <nav class="navbar">

    <div class="nav-brand">

      <a href="student-dashboard.php"
         class="logo-btn-link"
         title="Go to Dashboard">

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
             style="
                border-radius:50%;
                width:35px;
                height:35px;
                border:2px solid rgba(255, 255, 255, 0.85);
             ">

        <span class="profile-name"
              style="color:white; font-weight:bold;">

          <?php echo htmlspecialchars($student['username']); ?>

        </span>

      </div>

    </div>

    <button class="mobile-menu-btn">☰</button>

  </nav>

  <!-- Page Content -->
  <div class="container">

    <h2 class="section-title" style="text-align: left;">
      Student Dashboard
    </h2>

    <div class="dashboard-container">

      <!-- Sidebar -->
      <aside class="sidebar">

        <h3>Menu</h3>

        <nav class="sidebar-nav">

          <a href="student-dashboard.php" class="active">
            Overview
          </a>

          <a href="student-reviews.php">
            My Reviews
          </a>

          <a href="../lecturers.php">
            Evaluate Lecturer
          </a>

          <a href="student-profile.php">
            Profile Settings
          </a>

          <a href="../about.php">
            About the System
          </a>

          <a href="../logout.php">Logout</a>

        </nav>

      </aside>

      <!-- Main Content -->
      <main class="main-content">

        <!-- Stats -->
        <div class="stat-grid">

          <div class="stat-card">

            <div class="stat-value">
              <?php echo $reviewCount; ?>
            </div>

            <div class="stat-label">
              Reviews Submitted
            </div>

          </div>

          <div class="stat-card">

            <div class="stat-value">
              <?php echo $pendingCount; ?>
            </div>

            <div class="stat-label">
              Lecturers not reviewed
            </div>

          </div>

        </div>

        <!-- Recent Activity -->
        <div class="recent-activity">

          <h3>Recent Activity</h3>

          <?php if (!empty($recentActivities)): ?>

            <?php foreach ($recentActivities as $activity): ?>

              <div class="activity-item">

                <strong>
                  Evaluated
                  <?php echo htmlspecialchars($activity['lecturer_name']); ?>
                </strong>

                <em>
                  -
                  <?php echo date('M d, Y', strtotime($activity['created_at'])); ?>
                </em>

                <p style="
                    font-size: 0.9rem;
                    color: var(--text-light);
                    margin-top: 5px;
                ">

                  Rating:
                  <?php echo htmlspecialchars($activity['rating']); ?>

                </p>

              </div>

            <?php endforeach; ?>

          <?php else: ?>

            <div class="activity-item">

              <p style="color: var(--text-light);">

                No reviews submitted yet.

              </p>

            </div>

          <?php endif; ?>

        </div>

      </main>

    </div>

  </div>

  <script src="../js/main.js"></script>
  <script src="../js/dashboard.js"></script>

</body>

</html>