/* validation.js - Logic for form validation */

document.addEventListener('DOMContentLoaded', () => {
  const reviewForm = document.getElementById('review-form');
  if (reviewForm) {
    reviewForm.addEventListener('submit', handleFormSubmit);
  }
});

function handleFormSubmit(e) {
  e.preventDefault();

  let isValid = true;
  
  const studentId = document.getElementById('student-id');
  if (studentId && studentId.value.trim() === '') {
    showError(studentId, 'Student ID is required');
    isValid = false;
  } else if (studentId) {
    clearError(studentId);
  }

  const comments = document.getElementById('comments');
  if (comments && comments.value.trim().length < 20) {
    showError(comments, 'Please write at least 20 characters of feedback.');
    isValid = false;
  } else if (comments) {
    clearError(comments);
  }

  const teachingScale = document.querySelector('input[name="teaching"]:checked');
  if (!teachingScale) {
    alert("Please answer the 'Teaching effectiveness' evaluation question.");
    isValid = false;
  }

  if (isValid) {
    window.location.href = 'thankyou.php';
  }
}

function showError(input, message) {
  const formGroup = input.closest('.form-group');
  let errorElement = formGroup.querySelector('.error-msg');
  
  if (!errorElement) {
    errorElement = document.createElement('div');
    errorElement.className = 'error-msg';
    formGroup.appendChild(errorElement);
  }
  
  errorElement.textContent = message;
  errorElement.style.display = 'block';
  input.style.borderColor = '#d32f2f';
}

function clearError(input) {
  const formGroup = input.closest('.form-group');
  const errorElement = formGroup.querySelector('.error-msg');
  if (errorElement) {
    errorElement.style.display = 'none';
  }
  input.style.borderColor = 'var(--border-color)';
}
