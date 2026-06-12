<?php
  require_once 'data/database.php';
  require_once 'helpers/functions.php';

  requireLogin();

  if ($_SESSION['user_type'] !== 'student') {
      redirect('unauth.php');
  }

  $errors = [];

  $studentName = '';
  $studentIdNumber = '';
  $studentDept = '';
  $lecturerId = '';
  $teaching = '';
  $communication = '';
  $punctuality = '';
  $overallRating = '';
  $comments = '';
  $courseId = '';

  // fetch the logged-in student
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
      redirect('login.php');
  }

  $studentName = $student['username'];
  $studentIdNumber = $student['valid_id'];

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {

      $studentName   = trim($_POST['student_name'] ?? $studentName);
      $studentDept   = trim($_POST['student_dept'] ?? '');
      $lecturerId    = trim($_POST['lecturer_id'] ?? '');
      $teaching      = trim($_POST['teaching'] ?? '');
      $communication = trim($_POST['communication'] ?? '');
      $punctuality   = trim($_POST['punctuality'] ?? '');
      $overallRating = trim($_POST['overall_rating'] ?? '');
      $comments      = trim($_POST['comments'] ?? '');
      $courseId = trim($_POST['course_id'] ?? '');

      // lecturer validation
      if (empty($lecturerId)) {
          $errors[] = "Please choose a lecturer to evaluate.";
      } elseif (!ctype_digit($lecturerId)) {
          $errors[] = "Invalid lecturer selected.";
      } else {
          $lecturer_check_sql = "SELECT id, name FROM lecturers WHERE id = ? LIMIT 1";
          $lecturer_check_stmt = $pdo->prepare($lecturer_check_sql);
          $lecturer_check_stmt->execute([$lecturerId]);
          $lecturer = $lecturer_check_stmt->fetch();

          if (!$lecturer) {
              $errors[] = "The selected lecturer does not exist.";
          }
      }

      if (empty($courseId)) {

          $errors[] = "Please select a course.";

      } else {

          $course_sql = "
              SELECT 1
              FROM lecturer_courses
              WHERE lecturer_id = ?
              AND course_id = ?
              LIMIT 1
          ";

          $course_stmt = $pdo->prepare($course_sql);

          $course_stmt->execute([
              $lecturerId,
              $courseId
          ]);

          if (!$course_stmt->fetch()) {

              $errors[] =
                  "Selected course does not belong to selected lecturer.";
          }
      }

      // required question validation
      if (empty($teaching)) {
          $errors[] = "Teaching effectiveness rating is required.";
      } elseif (!in_array($teaching, ['1', '2', '3', '4', '5'], true)) {
          $errors[] = "Invalid teaching effectiveness rating.";
      }

      if (empty($overallRating)) {
          $errors[] = "Overall rating is required.";
      } elseif (!in_array($overallRating, ['1', '2', '3', '4', '5'], true)) {
          $errors[] = "Invalid overall rating.";
      }

      if (empty($comments)) {
          $errors[] = "Comments are required.";
      } elseif (strlen($comments) < 20) {
          $errors[] = "Comments must be at least 20 characters long.";
      }

      // optional duplicate check: one review per student per lecturer
      if (empty($errors)) {
          $duplicate_sql = "
              SELECT id
              FROM reviews
              WHERE student_id = ? AND lecturer_id = ? AND course_id = ?
              LIMIT 1
          ";
          $duplicate_stmt = $pdo->prepare($duplicate_sql);
          $duplicate_stmt->execute([$student['id'], $lecturerId, $courseId]);

          if ($duplicate_stmt->fetch()) {
              $errors[] = "You have already reviewed this lecturer.";
          }
      }

      if (empty($errors)) {

          $reviewText = "";

          if (!empty($studentDept)) {
              $reviewText .= "Student Department: " . $studentDept . "\n";
          }

          $reviewText .= "Teaching Effectiveness: " . $teaching . "/5\n";

          if (!empty($communication)) {
              $reviewText .= "Communication Skills: " . $communication . "/5\n";
          }

          if (!empty($punctuality)) {
              $reviewText .= "Punctuality and Attendance: " . $punctuality . "/5\n";
          }

          $reviewText .= "\nComments:\n" . $comments;

          $insert_sql = "
              INSERT INTO reviews (
                  review,
                  rating,
                  lecturer_id,
                  student_id, 
                  course_id
              ) VALUES (
                  :review,
                  :rating,
                  :lecturer_id,
                  :student_id,
                  :course_id
              )
          ";

          $insert_stmt = $pdo->prepare($insert_sql);
          $insert_stmt->execute([
              ':review'      => $reviewText,
              ':rating'      => $overallRating,
              ':lecturer_id' => $lecturerId,
              ':student_id'  => $student['id'],
              ':course_id' => $courseId
          ]);

          setFlashMessage("Evaluation submitted successfully.", "success");
          redirect('student/student-reviews.php');
      }
  }

  $selectedLecturerId = $_GET['lecturer_id'] ?? $lecturerId ?? '';

  $courses = [];

  if (!empty($selectedLecturerId)) {

      $course_sql = "
          SELECT
              courses.id,
              courses.course
          FROM lecturer_courses
          INNER JOIN courses
              ON lecturer_courses.course_id = courses.id
          WHERE lecturer_courses.lecturer_id = ?
          ORDER BY courses.course
      ";

      $course_stmt = $pdo->prepare($course_sql);
      $course_stmt->execute([$selectedLecturerId]);

      $courses = $course_stmt->fetchAll();
  }
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Submit Evaluation - Hawassa University</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/responsive.css">
  <link rel="stylesheet" href="css/forms.css">

  <style>
    .error {
      color: #d32f2f;
      background: #ffebee;
      padding: 10px;
      border-radius: 4px;
      margin-bottom: 15px;
    }
    .error ul {
      margin: 0;
      padding-left: 20px;
    }
  </style>
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

  <div class="container">
    <div class="back-btn-container">
      <button class="back-btn" onclick="window.history.back()">
        <span class="arrow">&larr;</span> Back
      </button>
    </div>

    <h2 class="section-title">Lecturer Evaluation Form</h2>
    <p style="text-align: center; margin-bottom: 40px; color: var(--text-light);">
      Please provide honest and constructive feedback. Your responses are anonymous to the lecturer.
    </p>

    <?php echo getFlashMessage(); ?>

    <?php if (!empty($errors)): ?>
      <div class="error">
        <strong>Please fix the following:</strong>
        <ul>
          <?php foreach ($errors as $error): ?>
            <li><?php echo htmlspecialchars($error); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <div class="form-container">
      <form id="review-form" action="" method="POST">

        <!-- Section A: Student Information -->
        <fieldset style="border: none; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid var(--border-color);">
          <legend style="font-weight: bold; font-size: 1.2rem; color: var(--primary-blue); margin-bottom: 15px;">A. Student Information</legend>

          <div class="form-group">
            <label for="student-name">Full Name (Optional - for validation only)</label>
            <input
              type="text"
              id="student-name"
              name="student_name"
              class="form-control"
              placeholder="Enter your full name"
              value="<?php echo htmlspecialchars($studentName); ?>"
            >
          </div>

          <div class="form-group">
            <label for="student-id">Student ID (Required) *</label>
            <input
              type="text"
              id="student-id"
              class="form-control"
              value="<?php echo htmlspecialchars($studentIdNumber); ?>"
              readonly
              style="background-color: var(--bg-color);"
            >
          </div>

          <div class="form-group">
            <label for="student-dept">Your Department</label>
            <select id="student-dept" name="student_dept" class="form-control">
              <option value="">Select your department...</option>
              <option value="Computer Science" <?php echo ($studentDept === 'Computer Science') ? 'selected' : ''; ?>>Computer Science</option>
              <option value="Information Technology" <?php echo ($studentDept === 'Information Technology') ? 'selected' : ''; ?>>Information Technology</option>
              <option value="Information Systems" <?php echo ($studentDept === 'Information Systems') ? 'selected' : ''; ?>>Information Systems</option>
              <option value="Other" <?php echo ($studentDept === 'Other') ? 'selected' : ''; ?>>Other</option>
            </select>
          </div>
        </fieldset>

        <!-- Section B: Lecturer Information -->
        <fieldset style="border: none; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid var(--border-color);">
          <legend style="font-weight: bold; font-size: 1.2rem; color: var(--primary-blue); margin-bottom: 15px;">B. Lecturer Information</legend>

          <div class="form-group">
            <label for="lecturer-select">Select Lecturer to Evaluate *</label>
            <select
              id="lecturer-select"
              name="lecturer_id"
              class="form-control"
              required
              data-selected="<?php echo htmlspecialchars($lecturerId); ?>"
            >
              <option value="">Choose a lecturer...</option>
            </select>
          </div>
        </fieldset>

          <div class="form-group">
              <label for="course-select">
                  Select Course *
              </label>

              <select
                  id="course-select"
                  name="course_id"
                  class="form-control"
                  required
              >

                  <?php if (empty($courses)): ?>

                      <option value="">
                          Select lecturer first...
                      </option>

                  <?php else: ?>

                      <option value="">
                          Choose a course...
                      </option>

                      <?php foreach ($courses as $course): ?>

                          <option
                              value="<?php echo $course['id']; ?>"
                              <?php echo ($courseId == $course['id']) ? 'selected' : ''; ?>
                          >
                              <?php echo htmlspecialchars($course['course']); ?>
                          </option>

                      <?php endforeach; ?>

                  <?php endif; ?>

              </select>
          </div>

        <!-- Section C: Evaluation Questions -->
        <fieldset style="border: none; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid var(--border-color);">
          <legend style="font-weight: bold; font-size: 1.2rem; color: var(--primary-blue); margin-bottom: 15px;">C. Evaluation Questions</legend>
          <p style="font-size: 0.9rem; color: var(--text-light); margin-bottom: 20px;">Rate the following from 1 (Poor) to 5 (Excellent).</p>

          <div class="form-group">
            <label>1. Teaching Effectiveness *</label>
            <div class="scale-group">
              <label class="scale-option"><input type="radio" name="teaching" value="1" <?php echo ($teaching === '1') ? 'checked' : ''; ?>> 1</label>
              <label class="scale-option"><input type="radio" name="teaching" value="2" <?php echo ($teaching === '2') ? 'checked' : ''; ?>> 2</label>
              <label class="scale-option"><input type="radio" name="teaching" value="3" <?php echo ($teaching === '3') ? 'checked' : ''; ?>> 3</label>
              <label class="scale-option"><input type="radio" name="teaching" value="4" <?php echo ($teaching === '4') ? 'checked' : ''; ?>> 4</label>
              <label class="scale-option"><input type="radio" name="teaching" value="5" <?php echo ($teaching === '5') ? 'checked' : ''; ?>> 5</label>
            </div>
          </div>

          <div class="form-group">
            <label>2. Communication Skills</label>
            <div class="scale-group">
              <label class="scale-option"><input type="radio" name="communication" value="1" <?php echo ($communication === '1') ? 'checked' : ''; ?>> 1</label>
              <label class="scale-option"><input type="radio" name="communication" value="2" <?php echo ($communication === '2') ? 'checked' : ''; ?>> 2</label>
              <label class="scale-option"><input type="radio" name="communication" value="3" <?php echo ($communication === '3') ? 'checked' : ''; ?>> 3</label>
              <label class="scale-option"><input type="radio" name="communication" value="4" <?php echo ($communication === '4') ? 'checked' : ''; ?>> 4</label>
              <label class="scale-option"><input type="radio" name="communication" value="5" <?php echo ($communication === '5') ? 'checked' : ''; ?>> 5</label>
            </div>
          </div>

          <div class="form-group">
            <label>3. Punctuality and Attendance</label>
            <div class="scale-group">
              <label class="scale-option"><input type="radio" name="punctuality" value="1" <?php echo ($punctuality === '1') ? 'checked' : ''; ?>> 1</label>
              <label class="scale-option"><input type="radio" name="punctuality" value="2" <?php echo ($punctuality === '2') ? 'checked' : ''; ?>> 2</label>
              <label class="scale-option"><input type="radio" name="punctuality" value="3" <?php echo ($punctuality === '3') ? 'checked' : ''; ?>> 3</label>
              <label class="scale-option"><input type="radio" name="punctuality" value="4" <?php echo ($punctuality === '4') ? 'checked' : ''; ?>> 4</label>
              <label class="scale-option"><input type="radio" name="punctuality" value="5" <?php echo ($punctuality === '5') ? 'checked' : ''; ?>> 5</label>
            </div>
          </div>

          <div class="form-group">
            <label>4. Overall Rating *</label>
            <div class="star-rating">
              <input type="radio" id="star5" name="overall_rating" value="5" <?php echo ($overallRating === '5') ? 'checked' : ''; ?> />
              <label for="star5" title="5 stars">★</label>
              <input type="radio" id="star4" name="overall_rating" value="4" <?php echo ($overallRating === '4') ? 'checked' : ''; ?> />
              <label for="star4" title="4 stars">★</label>
              <input type="radio" id="star3" name="overall_rating" value="3" <?php echo ($overallRating === '3') ? 'checked' : ''; ?> />
              <label for="star3" title="3 stars">★</label>
              <input type="radio" id="star2" name="overall_rating" value="2" <?php echo ($overallRating === '2') ? 'checked' : ''; ?> />
              <label for="star2" title="2 stars">★</label>
              <input type="radio" id="star1" name="overall_rating" value="1" <?php echo ($overallRating === '1') ? 'checked' : ''; ?> />
              <label for="star1" title="1 star">★</label>
            </div>
          </div>
        </fieldset>

        <!-- Section D: Written Review -->
        <fieldset style="border: none; margin-bottom: 30px;">
          <legend style="font-weight: bold; font-size: 1.2rem; color: var(--primary-blue); margin-bottom: 15px;">D. Written Review</legend>

          <div class="form-group">
            <label for="comments">Constructive Feedback / Comments (Min 20 chars) *</label>
            <textarea
              id="comments"
              name="comments"
              class="form-control"
              placeholder="Please elaborate on your ratings. What did the lecturer do well? What could be improved?"
            ><?php echo htmlspecialchars($comments); ?></textarea>
          </div>
        </fieldset>

        <button type="submit" class="btn btn-blue" style="width: 100%; font-size: 1.1rem; padding: 15px;">
          Submit Evaluation
        </button>
      </form>
    </div>
  </div>

  <!-- Footer -->
  <footer>
    <p>&copy; 2026 Hawassa University - Student Project Team.</p>
    <p>Contact: info@hu.edu.et | +251 46 220 5311</p>
  </footer>

  <script>
  document.addEventListener('DOMContentLoaded', function () {

      const lecturerSelect = document.getElementById('lecturer-select');

      const selectedLecturer = new URLSearchParams(window.location.search) .get('lecturer_id');

      if (selectedLecturer) {
          lecturerSelect.value = selectedLecturer;
      }

      const courseSelect = document.getElementById('course-select');

      lecturerSelect.addEventListener('change', async function () {

          const lecturerId = this.value;

          courseSelect.innerHTML =
              '<option value="">Loading courses...</option>';

          if (!lecturerId) {

              courseSelect.innerHTML =
                  '<option value="">Select lecturer first...</option>';

              return;
          }

          try {

              const response =
                  await fetch(
                      'get-lecturer-courses.php?lecturer_id=' +
                      lecturerId
                  );

              const courses = await response.json();

              courseSelect.innerHTML =
                  '<option value="">Select course...</option>';

              courses.forEach(course => {

                  const option =
                      document.createElement('option');

                  option.value = course.id;
                  option.textContent = course.course;

                  courseSelect.appendChild(option);
              });

          } catch (error) {

              console.error(error);

              courseSelect.innerHTML =
                  '<option value="">Failed to load courses</option>';
          }

      });

  });
</script>
  <script src="js/main.js"></script>
</body>
</html>