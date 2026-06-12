/* auth.js - Logic for login and registration */

document.addEventListener('DOMContentLoaded', () => {
  
  // --- Password Show/Hide Toggle ---
  setupPasswordToggle('toggle-password', 'password');
  setupPasswordToggle('toggle-password-1', 'password');
  setupPasswordToggle('toggle-password-2', 'confirm-password');

  // --- Login Form Logic ---
  const loginForm = document.getElementById('login-form');
  // if (loginForm) {
  //   loginForm.addEventListener('submit', (e) => {
  //     e.preventDefault();
      
  //     const idNumber = document.getElementById('id-number').value.trim();
  //     const password = document.getElementById('password').value;
  //     const errorDiv = document.getElementById('login-error');

  //     // Clear previous error
  //     errorDiv.style.display = 'none';

  //     // Basic required check
  //     if (!idNumber || !password) {
  //       showAuthError(errorDiv, 'Please enter both ID Number and password.');
  //       return;
  //     }

  //     // Set logged in state
  //     localStorage.setItem('isLoggedIn', 'true');

  //     // Check for redirect parameter
  //     const urlParams = new URLSearchParams(window.location.search);
  //     const redirectUrl = urlParams.get('redirect');

  //     if (redirectUrl) {
  //       window.location.href = redirectUrl;
  //     } else if (idNumber === 'admin' && password === 'admin123') {
  //       window.location.href = 'admin/admin-dashboard.php';
  //     } else if (idNumber === 'instructor' && password === 'instructor123') {
  //       window.location.href = 'lecturer/lecturer-dashboard.php';
  //     } else {
  //       // Default to student dashboard for simulation
  //       window.location.href = 'student/student-dashboard.php';
  //     }
  //   });
  // }

  // --- Signup Form Logic ---
  const signupForm = document.getElementById('signup-form');
  // if (signupForm) {
  //   signupForm.addEventListener('submit', (e) => {
  //     // e.preventDefault();

  //     // const idNumber = document.getElementById('id-number').value.trim();
  //     // const username = document.getElementById('username').value.trim();
  //     // const email = document.getElementById('email').value.trim();
  //     // const password = document.getElementById('password').value;
  //     // const confirmPassword = document.getElementById('confirm-password').value;
      
  //     // const errorDiv = document.getElementById('signup-error');
  //     // errorDiv.style.display = 'none'; // reset

  //     // // 1. Required Fields Validation
  //     // if (!idNumber || !username || !email || !password || !confirmPassword) {
  //     //   showAuthError(errorDiv, 'Please fill out all required fields.');
  //     //   return;
  //     // }

  //     // // 2. Email Regex Validation
  //     // const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  //     // if (!emailRegex.test(email)) {
  //     //   showAuthError(errorDiv, 'Please enter a valid email address.');
  //     //   return;
  //     // }

  //     // // 3. Password Match Validation
  //     // if (password !== confirmPassword) {
  //     //   showAuthError(errorDiv, 'Passwords do not match.');
  //     //   return;
  //     // }

  //     // // Validation passed - Simulate successful registration
  //     // alert('Registration successful! Please login.');
  //     // window.location.href = 'login.php';
  //   });
  // }
});

// Helper function to toggle password visibility
function setupPasswordToggle(toggleBtnId, inputId) {
  const toggleBtn = document.getElementById(toggleBtnId);
  const passInput = document.getElementById(inputId);
  
  if (toggleBtn && passInput) {
    toggleBtn.textContent = 'Show';
    toggleBtn.addEventListener('click', () => {
      if (passInput.type === 'password') {
        passInput.type = 'text';
        toggleBtn.textContent = 'Hide';
      } else {
        passInput.type = 'password';
        toggleBtn.textContent = 'Show';
      }
    });
  }
}

function showAuthError(errorDiv, message) {
  errorDiv.textContent = message;
  errorDiv.style.display = 'block';
}
