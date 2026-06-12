<?php
  require_once 'data/database.php';
  require_once 'helpers/functions.php';

  $dept_sql = "
    SELECT id, department
    FROM departments
    ORDER BY department ASC
  ";

  $dept_stmt = $pdo->prepare($dept_sql);
  $dept_stmt->execute();

  $departments = $dept_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Browse Lecturers - Hawassa University</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/responsive.css">
</head>
<body>

  <!-- Navigation Bar -->
  <nav class="navbar">
    <div class="nav-brand">
      <a href="index.php" class="logo-btn-link" title="Go to Home">
        <img src="images/download.jpg" alt="Hawassa University Logo" class="logo-btn-img">
      </a>
      Lecturer Review
    </div>

    <button class="mobile-menu-btn">☰</button>

    <ul class="nav-links">
      <li><a href="index.php">Home</a></li>
      <li><a href="about.php">About</a></li>
      <li><a href="lecturers.php">Lecturers</a></li>
      <li><a href="login.php">Login</a></li>
    </ul>
  </nav>

  <!-- Page Content -->
  <div class="container">
    <div class="back-btn-container">
      <button class="back-btn" onclick="window.history.back()">
        <span class="arrow">&larr;</span> Back
      </button>
    </div>

    <h2 class="section-title">Browse Lecturers</h2>
    <p style="text-align: center; margin-bottom: 30px; color: var(--text-light);">
      Find your lecturer to submit an evaluation.
    </p>

    <!-- Filtering & Search -->
    <div class="filter-bar">
      <input type="text" id="search-bar" placeholder="Search by name or course...">

      <select id="dept-filter">
        <option value="">Select your department...</option>
        <?php foreach ($departments as $department): ?>
          <option value="<?php echo htmlspecialchars($department['id']); ?>">
            <?php echo htmlspecialchars($department['department']); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- Lecturers Grid -->
    <div id="lecturer-grid" class="grid-3">
      <div style="grid-column: 1/-1; text-align: center; padding: 40px 0;">
        <div class="spinner"></div>
        <p style="color: var(--text-light); margin-top: 10px;">Loading lecturers...</p>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <footer>
    <p>&copy; 2026 Hawassa University - Student Project Team.</p>
    <p>Contact: info@hu.edu.et | +251 46 220 5311</p>
  </footer>

  <script src="js/main.js"></script>
</body>
</html>