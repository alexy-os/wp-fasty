// Update button icon based on dark mode state
function updateButtonIcon(isDark) {
  const button = document.getElementById('darkmode');
  if (!button) return;

  const sunIcon = button.querySelector('.sun-icon');
  const moonIcon = button.querySelector('.moon-icon');

  if (sunIcon && moonIcon) {
    sunIcon.style.display = isDark ? 'block' : 'none';
    moonIcon.style.display = isDark ? 'none' : 'block';
  }
}

// Toggle dark mode
function toggleDarkMode() {
  const isDark = document.documentElement.classList.contains('dark');
  document.documentElement.classList.toggle('dark');
  localStorage.setItem('darkmode', isDark ? 'light' : 'dark');
  updateButtonIcon(!isDark);
}

// Initialize
(function () {
  // Set initial theme
  const isDark = localStorage.getItem('darkmode') === 'dark' ||
    (!('darkmode' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches);

  document.documentElement.classList.toggle('dark', isDark);

  // Add event listener once DOM is ready
  document.addEventListener('DOMContentLoaded', function () {
    updateButtonIcon(isDark);

    const button = document.getElementById('darkmode');
    if (button) button.addEventListener('click', toggleDarkMode);
  });
})();
