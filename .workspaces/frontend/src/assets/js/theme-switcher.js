// Fixed theme switcher that removes URL parameters after theme application
document.addEventListener('DOMContentLoaded', () => {
  // Get URL params to check if theme is already specified
  const urlParams = new URLSearchParams(window.location.search);
  const urlTheme = urlParams.get('theme');

  // Check localStorage for saved theme - use URL param if available
  const savedTheme = urlTheme || localStorage.getItem('ui-theme') || 'ui8kit';

  // Save current theme to localStorage to maintain consistency
  localStorage.setItem('ui-theme', savedTheme);

  // Find the theme toggle button
  const themeToggleBtn = document.getElementById('theme-toggle');
  if (!themeToggleBtn) return;

  // Get current theme from the button's data attribute
  const currentTheme = themeToggleBtn.getAttribute('data-current-theme');

  // Update button text based on the current active theme
  themeToggleBtn.textContent = currentTheme === 'semantic' ? 'Switch to UI8Kit' : 'Switch to Semantic';

  // If URL has theme parameter, remove it after applying the theme
  if (urlTheme) {
    // Clear the URL parameter using History API without reloading the page
    const cleanUrl = new URL(window.location.href);
    cleanUrl.searchParams.delete('theme');
    window.history.replaceState({}, document.title, cleanUrl.toString());
  }

  // Only redirect if needed and there's a mismatch between actual theme and requested theme
  if (savedTheme !== currentTheme && !urlTheme) {
    // Create URL with the theme parameter for switching
    const url = new URL(window.location.href);
    url.searchParams.set('theme', savedTheme);
    window.location.href = url.toString();
    return;
  }

  // Set up the theme toggle button click handler
  themeToggleBtn.addEventListener('click', () => {
    // Toggle theme
    const newTheme = currentTheme === 'semantic' ? 'ui8kit' : 'semantic';

    // Save to localStorage
    localStorage.setItem('ui-theme', newTheme);

    // Create URL with the theme parameter for switching
    const url = new URL(window.location.href);
    url.searchParams.set('theme', newTheme);
    window.location.href = url.toString();
  });
});