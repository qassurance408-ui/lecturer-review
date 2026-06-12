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

$lecturerId = $lecturer['lecturer_id'];
$lecturerName = $lecturer['name'];

//selected course filter
$selectedCourse = $_GET['course_id'] ?? 'all';

//courses thought by lecturer
$courses_sql = "
    SELECT
        courses.id,
        courses.course
    FROM lecturer_courses
    INNER JOIN courses
        ON lecturer_courses.course_id = courses.id
    WHERE lecturer_courses.lecturer_id = ?
    ORDER BY courses.course
";

$courses_stmt = $pdo->prepare($courses_sql);
$courses_stmt->execute([$lecturerId]);
$courses = $courses_stmt->fetchAll();

//reviews filtered by course
if ($selectedCourse !== 'all' && ctype_digit($selectedCourse)) {

    $reviews_sql = "
        SELECT
            reviews.id,
            reviews.review,
            reviews.rating,
            reviews.created_at,
            courses.course,
            courses.id AS course_id
        FROM reviews
        INNER JOIN courses
            ON reviews.course_id = courses.id
        WHERE reviews.lecturer_id = ?
          AND reviews.course_id = ?
        ORDER BY reviews.created_at DESC
    ";

    $reviews_stmt = $pdo->prepare($reviews_sql);
    $reviews_stmt->execute([$lecturerId, $selectedCourse]);

} else {

    $reviews_sql = "
        SELECT
            reviews.id,
            reviews.review,
            reviews.rating,
            reviews.created_at,
            courses.course,
            courses.id AS course_id
        FROM reviews
        INNER JOIN courses
            ON reviews.course_id = courses.id
        WHERE reviews.lecturer_id = ?
        ORDER BY reviews.created_at DESC
    ";

    $reviews_stmt = $pdo->prepare($reviews_sql);
    $reviews_stmt->execute([$lecturerId]);
}

$reviews = $reviews_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Feedback - Hawassa University</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/responsive.css">
  <link rel="stylesheet" href="../css/dashboard.css">
</head>

<body>

<nav class="navbar">
  <div class="nav-brand">
    <a href="lecturer-dashboard.php" class="logo-btn-link">
      <img src="../images/download.jpg" class="logo-btn-img">
    </a>
    Lecturer Portal
  </div>

  <div class="nav-actions" style="display: flex; align-items: center; margin-left: auto; margin-right: 20px;">
      <div class="profile-avatar" style="display:flex; align-items:center; gap:10px; cursor:pointer;">
        <img src="https://placehold.co/40x40" alt="Lecturer Avatar" style="border-radius:50%; width:35px; height:35px; border:2px solid rgba(255, 255, 255, 0.85);">
        <span class="profile-name" style="color:white; font-weight:bold;">
          <?php echo htmlspecialchars($lecturerName); ?>
        </span>
      </div>
    </div>
</nav>

<div class="container">
  <h2 class="section-title">
    Student Feedback - <?php echo htmlspecialchars($lecturerName); ?>
  </h2>

  <div class="dashboard-container">

    <aside class="sidebar">
      <h3>Menu</h3>
      <nav class="sidebar-nav">
        <a href="lecturer-dashboard.php">Overview</a>
        <a href="lecturer-feedback.php" class="active">Recent Reviews</a>
        <a href="lecturer-statistics.php">Performance Stats</a>
        <a href="lecturer-profile.php">Update Profile</a>
        <a href="../logout.php">Logout</a>

      </nav>
    </aside>

    <main class="main-content">

      <div class="filter-bar" style="margin-bottom: 20px;">

        <select onchange="location = this.value;" id="course-filter">

          <option value="lecturer-feedback.php?course_id=all"
            <?php if ($selectedCourse === 'all') echo 'selected'; ?>>
            All Courses
          </option>

          <?php foreach ($courses as $course): ?>
            <option
              value="lecturer-feedback.php?course_id=<?php echo $course['id']; ?>"
              <?php if ($selectedCourse == $course['id']) echo 'selected'; ?>
            >
              <?php echo htmlspecialchars($course['course']); ?>
            </option>
          <?php endforeach; ?>

        </select>

      </div>

      <?php if (empty($reviews)): ?>
        <div class="card">
          <p>No reviews found for this selection.</p>
        </div>
      <?php else: ?>

        <?php foreach ($reviews as $review): ?>
          <div class="card" style="margin-bottom:20px;">

            <div style="color: var(--accent-gold); margin-bottom:10px;">
              <?php echo str_repeat('★', (int)$review['rating']); ?>
              <?php echo str_repeat('☆', 5 - (int)$review['rating']); ?>
            </div>

            <p><?php echo nl2br(htmlspecialchars($review['review'])); ?></p>

            <p style="color: var(--text-light); font-size:0.85rem; margin-top:10px;">
              Submitted: <?php echo date('M j, Y', strtotime($review['created_at'])); ?>
              | Course: <?php echo htmlspecialchars($review['course']); ?>
            </p>

          </div>
        <?php endforeach; ?>

      <?php endif; ?>

    </main>

  </div>
</div>

<script src="../js/main.js"></script>
</body>
</html>