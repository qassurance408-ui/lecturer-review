<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>About - Lecturer Review System</title>
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
      <!-- <button class="theme-toggle-btn" title="Toggle Dark Mode" aria-label="Toggle Dark Mode"></button> -->
    </div>
    <button class="mobile-menu-btn">☰</button>
    <ul class="nav-links">
      <li><a href="index.php">Home</a></li>
      <li><a href="about.php" class="active">About</a></li>
      <li><a href="lecturers.php">Lecturers</a></li>
      <li><a href="login.php">Login</a></li>
    </ul>
  </nav>

  <!-- Page Content -->
  <div class="container" style="padding-top: 60px;">
    <div class="back-btn-container">
      <button class="back-btn" onclick="window.history.back()"><span class="arrow">&larr;</span> Back</button>
    </div>
    <h2 class="section-title">About the System</h2>

    <div class="grid-3" style="margin-bottom: 40px; align-items: center;">
      <div style="grid-column: span 2;">
        <h3 style="color: var(--primary-blue); margin-bottom: 15px; font-size: 1.5rem;">Ethiopian University Context
        </h3>
        <p style="margin-bottom: 15px; font-size: 1.1rem; line-height: 1.8;">
          Higher education in Ethiopia is rapidly expanding, and Hawassa University stands at the forefront of this
          growth. However, direct feedback mechanisms between students and faculty can sometimes be formal and difficult
          to navigate. The <strong>Lecturer Review System</strong> was designed specifically to address this by
          providing a localized, culturally aware platform for constructive academic feedback.
        </p>
      </div>
      <div style="text-align: center;">
        <img src="images/images.jpg" alt="Hawassa University Campus"
          style="width: 100%; border-radius: 8px; box-shadow: var(--shadow-sm);">
      </div>
    </div>

    <div class="card" style="max-width: 900px; margin: 0 auto; line-height: 1.8;">
      <h3 style="color: var(--primary-blue); margin-bottom: 15px;">Our Mission & Vision</h3>
      <p style="margin-bottom: 20px;">
        Built by a dedicated group of Hawassa University students, our vision is to foster an environment of continuous
        academic improvement. We aim to bridge the communication gap between students and faculty by offering a
        structured, anonymous, and constructive feedback mechanism that elevates the quality of education across all
        departments.
      </p>

      <h3 style="color: var(--primary-blue); margin-bottom: 15px;">How It Works</h3>
      <ul style="list-style-type: none; margin-left: 0; margin-bottom: 20px;">
        <li style="margin-bottom: 10px; padding-left: 25px; position: relative;">
          <span style="position: absolute; left: 0; color: var(--accent-gold);">✓</span>
          <strong>Secure Access:</strong> Students log in using their verified UGR IDs.
        </li>
        <li style="margin-bottom: 10px; padding-left: 25px; position: relative;">
          <span style="position: absolute; left: 0; color: var(--accent-gold);">✓</span>
          <strong>Browse & Discover:</strong> Anyone can browse and search the lecturer directory without an account.
        </li>
        <li style="margin-bottom: 10px; padding-left: 25px; position: relative;">
          <span style="position: absolute; left: 0; color: var(--accent-gold);">✓</span>
          <strong>Constructive Evaluation:</strong> Reviews are submitted using a standardized form covering vital
          teaching metrics.
        </li>
        <li style="margin-bottom: 10px; padding-left: 25px; position: relative;">
          <span style="position: absolute; left: 0; color: var(--accent-gold);">✓</span>
          <strong>Anonymous Reporting:</strong> Reviews are aggregated and provided to the faculty anonymously to ensure
          privacy.
        </li>
      </ul>

      <h3 style="color: var(--primary-blue); margin-bottom: 15px;">Privacy Policy & Student Safety</h3>
      <p>
        We take student privacy extremely seriously. All qualitative and quantitative reviews are anonymized before
        being shared with department heads or the respective lecturers. Your Student ID is exclusively used to verify
        your enrollment status and prevent duplicate submissions, ensuring the integrity of the data.
      </p>
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
