<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Lecturer Review System - Hawassa University</title>
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
      <li><a href="about.php">About</a></li>
      <li><a href="lecturers.php">Lecturers</a></li>
      <li><a href="login.php">Login</a></li>
    </ul>
  </nav>

  <!-- Hero Section -->
  <header class="hero" style="background-image: linear-gradient(135deg, rgba(0,91,161,0.82) 0%, rgba(0,50,110,0.70) 100%), url('images/lecturers.jpg'); background-size: cover; background-position: center; background-repeat: no-repeat;">
    <h1>Lecturer Review System</h1>
    <p>Empowering Hawassa University students to improve education quality through honest, constructive feedback. Your voice matters.</p>
    <a href="lecturers.php" class="btn btn-primary">Start Review</a>
  </header>

  <!-- Why Feedback Matters Section -->
  <section class="container">
    <h2 class="section-title">Why Feedback Matters</h2>
    <div class="grid-3">
      <div class="card">
        <h3 style="color: var(--primary-blue); margin-bottom: 10px;">Improve Teaching</h3>
        <p>Constructive reviews help lecturers understand their strengths and areas where they can adapt their teaching styles for better comprehension.</p>
      </div>
      <div class="card">
        <h3 style="color: var(--primary-blue); margin-bottom: 10px;">Empower Students</h3>
        <p>Gives students a formal platform to express their academic needs and participate actively in the university's quality assurance process.</p>
      </div>
      <div class="card">
        <h3 style="color: var(--primary-blue); margin-bottom: 10px;">Enhance Curriculum</h3>
        <p>Aggregate feedback allows departments to identify recurring issues with specific courses and make necessary curriculum adjustments.</p>
      </div>
    </div>
  </section>

  <!-- Featured Departments -->
  <section class="container" style="background-color: var(--card-bg); padding: 40px; border-radius: 8px; margin-top: 20px; box-shadow: var(--shadow-sm);">
    <h2 class="section-title">Featured Departments</h2>
    <div class="grid-3" style="text-align: center;">
      <div style="padding: 20px; border: 1px solid var(--border-color); border-radius: 8px;">
        <h3 style="margin-bottom: 10px;">Computer Science</h3>
        <p style="font-size: 0.9rem; color: var(--text-light);">Focus on algorithms, AI, and systems.</p>
      </div>
      <div style="padding: 20px; border: 1px solid var(--border-color); border-radius: 8px;">
        <h3 style="margin-bottom: 10px;">Information Technology</h3>
        <p style="font-size: 0.9rem; color: var(--text-light);">Building digital skills, tech support, and infrastructure.</p>
      </div>
      <div style="padding: 20px; border: 1px solid var(--border-color); border-radius: 8px;">
        <h3 style="margin-bottom: 10px;">Information Systems</h3>
        <p style="font-size: 0.9rem; color: var(--text-light);">Aligning business strategy with digital systems and analytics.</p>
      </div>
    </div>
  </section>

  <!-- Student Testimonials -->
  <section class="container">
    <h2 class="section-title">Student Voices</h2>
    <div class="grid-3">
      <div class="card" style="font-style: italic;">
        <p>"The review system allowed me to suggest changes to the lab hours. The department actually listened and extended the open lab time!"</p>
        <p style="margin-top: 15px; font-weight: bold; color: var(--primary-blue); font-style: normal;">- 3rd Year CS Student</p>
      </div>
      <div class="card" style="font-style: italic;">
        <p>"It is great to have a safe, structured way to evaluate our instructors at the end of every semester. It makes the university feel more democratic."</p>
        <p style="margin-top: 15px; font-weight: bold; color: var(--primary-blue); font-style: normal;">- 4th Year IT Student</p>
      </div>
    </div>
  </section>


  <!-- Footer -->
  <footer>
    <p>&copy; 2026 Hawassa University - Student Project Team.</p>
    <p>Contact: info@hu.edu.et | +251 46 220 5311</p>
  </footer>

  <script src="js/main.js"></script>
</body>
</html>




