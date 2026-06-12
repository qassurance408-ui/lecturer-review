<?php
  require_once '../data/database.php';
  require_once '../helpers/functions.php';

  requireLogin();

  if ($_SESSION['user_type'] !== 'student') {
      redirect('../unauth.php');
  }

  //fetch student

  $reviews_sql = "
      SELECT
          reviews.id,
          reviews.review,
          reviews.rating,
          reviews.created_at,
          lecturers.name AS lecturer_name
      FROM reviews
      INNER JOIN lecturers
          ON reviews.lecturer_id = lecturers.id
      WHERE reviews.student_id = ?
      ORDER BY reviews.created_at DESC
  ";

  $reviews_stmt = $pdo->prepare($reviews_sql);
  $reviews_stmt->execute([$_SESSION['user_id']]);

  $reviews = $reviews_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Reviews - Hawassa University</title>

  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/responsive.css">
  <link rel="stylesheet" href="../css/dashboard.css">
</head>

<body>

  <!-- Navigation Bar -->
  <nav class="navbar">
    <div class="nav-brand">
      <a href="student-dashboard.php" class="logo-btn-link" title="Go to Dashboard">
        <img src="../images/download.jpg" alt="Hawassa University Logo" class="logo-btn-img">
      </a>
      Lecturer Review
    </div>

    <div class="nav-actions" style="display: flex; align-items: center; margin-left: auto; margin-right: 20px;">
      <div class="profile-avatar" style="display:flex; align-items:center; gap:10px; cursor:pointer;">
        <img src="https://placehold.co/40x40" alt="Student Avatar"
          style="border-radius:50%; width:35px; height:35px; border:2px solid rgba(255, 255, 255, 0.85);">

        <span class="profile-name" style="color:white; font-weight:bold;">
          <?php echo htmlspecialchars($_SESSION['username']); ?>
        </span>
      </div>
    </div>

    <button class="mobile-menu-btn">☰</button>
  </nav>

  <!-- Page Content -->
  <div class="container">

    <div class="back-btn-container" style="margin-bottom: 10px;">
      <button class="back-btn"
              onclick="window.history.back()"
              style="background: none; border: none; cursor: pointer; color: var(--primary-blue); font-weight: bold;">
        <span class="arrow">&larr;</span> Back
      </button>
    </div>

    <h2 class="section-title" style="text-align: left;">
      My Submitted Reviews
    </h2>

    <div class="dashboard-container">

      <!-- Sidebar Navigation -->
      <aside class="sidebar">
        <h3>Menu</h3>

        <nav class="sidebar-nav">
          <a href="student-dashboard.php">Overview</a>
          <a href="student-reviews.php" class="active">My Reviews</a>
          <a href="../lecturers.php">Evaluate Lecturer</a>
          <a href="student-profile.php">Profile Settings</a>
          <a href="../about.php">About the System</a>
          <a href="../logout.php">Logout</a>
        </nav>
      </aside>

      <!-- Main Content -->
      <main class="main-content">

        <?php if (empty($reviews)): ?>

          <div class="card">
            <h3 style="color: var(--primary-blue); margin-bottom: 10px;">
              No Reviews Yet
            </h3>

            <p style="color: var(--text-light);">
              You haven't submitted any lecturer reviews yet.
            </p>
          </div>

        <?php else: ?>

          <?php foreach ($reviews as $review): ?>

            <div class="card" style="margin-bottom: 20px;">

              <h3 style="color: var(--primary-blue); margin-bottom: 5px;">
                <?php echo htmlspecialchars($review['lecturer_name']); ?>
              </h3>

              <p style="color: var(--text-light); font-size: 0.9rem; margin-bottom: 10px;">

                Submitted:
                <?php echo date('M d, Y', strtotime($review['created_at'])); ?>

              </p>

              <div style="color: var(--accent-gold); margin-bottom: 10px;">

                <?php
                  $rating = (int)$review['rating'];

                  for ($i = 1; $i <= 5; $i++) {
                      echo $i <= $rating ? '★' : '☆';
                  }
                ?>

                (<?php echo htmlspecialchars($review['rating']); ?>/5)

              </div>

              <p>
                "<?php echo nl2br(htmlspecialchars($review['review'])); ?>"
              </p>

            </div>

          <?php endforeach; ?>

        <?php endif; ?>

      </main>
    </div>
  </div>

  <script src="../js/main.js"></script>
  <script src="../js/reviews.js"></script>

</body>
</html>