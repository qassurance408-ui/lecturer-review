/*
  Lecturer Review System - JavaScript
  Author: Student Project Team
*/

document.addEventListener('DOMContentLoaded', () => {
  // --- Mobile Navigation Toggle ---
  const mobileBtn = document.querySelector('.mobile-menu-btn');
  const navLinks = document.querySelector('.nav-links');

  if (mobileBtn && navLinks) {
    mobileBtn.addEventListener('click', () => {
      navLinks.classList.toggle('show');
    });
  }

  // --- Dark Mode Toggle Logic ---
  const themeToggleBtns = document.querySelectorAll('.theme-toggle-btn');
  const currentTheme = localStorage.getItem('theme') || 'light';
  
  if (currentTheme === 'dark') {
    document.documentElement.setAttribute('data-theme', 'dark');
  }

  themeToggleBtns.forEach(btn => {
    btn.innerHTML = '<span class="theme-toggle-thumb"></span>';
    
    if (currentTheme === 'dark') {
      btn.classList.add('dark-active');
    } else {
      btn.classList.remove('dark-active');
    }

    btn.addEventListener('click', () => {
      let theme = document.documentElement.getAttribute('data-theme');
      if (theme === 'dark') {
        document.documentElement.removeAttribute('data-theme');
        localStorage.setItem('theme', 'light');
        themeToggleBtns.forEach(b => b.classList.remove('dark-active'));
      } else {
        document.documentElement.setAttribute('data-theme', 'dark');
        localStorage.setItem('theme', 'dark');
        themeToggleBtns.forEach(b => b.classList.add('dark-active'));
      }
    });
  });

  // --- Lecturers Page Logic ---
  const lecturerGrid = document.getElementById('lecturer-grid');
  if (lecturerGrid) {
    loadLecturers();

    document.getElementById('dept-filter').addEventListener('change', filterLecturers);
    document.getElementById('search-bar').addEventListener('input', filterLecturers);
  }

  // --- Review Page Form Validation & Interactions ---
  const reviewForm = document.getElementById('review-form');
  if (reviewForm) {
    populateLecturerDropdown();
  }
});

// --- Functions for Lecturers Page ---
let allLecturers = [];

async function loadLecturers() {
  try {
    const dataPath =
      window.location.pathname.includes('/student/') ||
      window.location.pathname.includes('/lecturer/') ||
      window.location.pathname.includes('/admin/')
        ? '../get-lecturers.php'
        : 'get-lecturers.php';

    const response = await fetch(dataPath);

    if (!response.ok) throw new Error('Network response was not ok');
    
    allLecturers = await response.json();
    filterLecturers();
  } catch (error) {
    console.error('Error fetching lecturers:', error);
    const grid = document.getElementById('lecturer-grid');
    grid.innerHTML = '<p style="text-align: center; color: red;">Failed to load lecturers.</p>';
  }
}

function displayLecturers(lecturers) {
  const grid = document.getElementById('lecturer-grid');
  grid.innerHTML = '';

  if (lecturers.length === 0) {
    grid.innerHTML = '<p style="text-align: center; grid-column: 1/-1;">No lecturers found matching your criteria.</p>';
    return;
  }

  lecturers.forEach(lecturer => {
    const card = document.createElement('div');
    card.className = 'card lecturer-card';

    const rating = parseFloat(lecturer.average_rating || 0);
    const fullStars = Math.floor(rating);
    const hasHalfStar = rating % 1 !== 0;
    let starsHtml = '★'.repeat(fullStars) + (hasHalfStar ? '½' : '') + '☆'.repeat(5 - Math.ceil(rating));

    card.innerHTML = `
      <h3 class="lecturer-name">${lecturer.name}</h3>
      <p class="lecturer-dept">${lecturer.department}</p>
      <div class="lecturer-rating">${starsHtml} (${rating.toFixed(1)})</div>
      <p class="lecturer-bio">${lecturer.courses || 'No courses listed yet.'}</p>
      <a href="review.php?lecturer_id=${lecturer.id}" class="btn btn-secondary">Review Lecturer</a>
    `;
    grid.appendChild(card);
  });
}

function filterLecturers() {
  const deptFilter = document.getElementById('dept-filter').value;
  const searchTerm = document.getElementById('search-bar').value.toLowerCase();

  const filtered = allLecturers.filter(lecturer => {
    const matchesDept = deptFilter === "" || String(lecturer.department_id) === deptFilter;

    const matchesSearch =
      lecturer.name.toLowerCase().includes(searchTerm) ||
      lecturer.department.toLowerCase().includes(searchTerm) ||
      (lecturer.courses || '').toLowerCase().includes(searchTerm);

    return matchesDept && matchesSearch;
  });

  displayLecturers(filtered);
}


// --- Functions for Review Page ---

async function populateLecturerDropdown() {
  const select = document.getElementById('lecturer-select');
  if (!select) return;

  try {
    const dataPath =
      window.location.pathname.includes('/student/') ||
      window.location.pathname.includes('/lecturer/') ||
      window.location.pathname.includes('/admin/')
        ? '../get-lecturers.php'
        : 'get-lecturers.php';

    const response = await fetch(dataPath);
    if (!response.ok) throw new Error('Network response was not ok');

    const lecturers = await response.json();

    lecturers.forEach(l => {
      const option = document.createElement('option');
      option.value = l.id;
      option.textContent = `${l.name} - ${l.courses || 'No courses listed'}`;
      select.appendChild(option);
    });

    const selectedLecturer = select.dataset.selected || '';
    if (selectedLecturer) {
      select.value = selectedLecturer;
    }

    const urlParams = new URLSearchParams(window.location.search);
    const lecturerId = urlParams.get('lecturer_id');
    if (lecturerId) {
      select.value = lecturerId;
    }
  } catch (error) {
    console.error("Could not load lecturers for dropdown.");
  }
}