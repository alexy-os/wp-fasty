/**
 * Theme initialization script 
 * Runs on the client side to handle theme switching
 */
(function () {
  const STORAGE_KEY = 'app-theme';
  const DEFAULT_THEME = 'ui8kit';
  const THEME_TYPES = {
    SEMANTIC: 'semantic',
    UI8KIT: 'ui8kit'
  };

  /**
   * Gets theme from URL parameter
   */
  function getThemeFromUrl() {
    try {
      const url = new URL(window.location.href);
      const themeParam = url.searchParams.get('theme');

      if (themeParam && Object.values(THEME_TYPES).includes(themeParam)) {
        return themeParam;
      }
      return null;
    } catch (error) {
      console.error('Failed to get theme from URL:', error);
      return null;
    }
  }

  /**
   * Gets theme from localStorage
   */
  function getStoredTheme() {
    try {
      const theme = localStorage.getItem(STORAGE_KEY);
      if (theme && Object.values(THEME_TYPES).includes(theme)) {
        return theme;
      }
      return null;
    } catch (error) {
      console.error('Failed to get theme from localStorage:', error);
      return null;
    }
  }

  /**
   * Stores theme in localStorage
   */
  function storeTheme(theme) {
    try {
      localStorage.setItem(STORAGE_KEY, theme);
    } catch (error) {
      console.error('Failed to store theme in localStorage:', error);
    }
  }

  /**
   * Removes theme parameter from URL
   */
  function removeThemeParamFromUrl() {
    try {
      const url = new URL(window.location.href);
      if (url.searchParams.has('theme')) {
        url.searchParams.delete('theme');
        window.history.replaceState({}, '', url.toString());
      }
    } catch (error) {
      console.error('Failed to remove theme param from URL:', error);
    }
  }

  /**
   * Sets the active theme on theme toggle button
   */
  function updateThemeToggleButton(theme) {
    const toggleButton = document.getElementById('theme-toggle');
    if (!toggleButton) return;

    toggleButton.setAttribute('data-theme', theme);

    const isSemanticActive = theme === THEME_TYPES.SEMANTIC;
    toggleButton.textContent = isSemanticActive
      ? 'Switch to UI8KIT'
      : 'Switch to Semantic';

    toggleButton.setAttribute('aria-label', `Switch to ${isSemanticActive ? 'UI8KIT' : 'Semantic'} theme`);
  }

  /**
   * Attaches event listeners to theme toggle buttons
   */
  function attachThemeToggleListeners() {
    const toggleButtons = document.querySelectorAll('[id="theme-toggle"]');

    toggleButtons.forEach(button => {
      if (!button) return;

      button.addEventListener('click', function (e) {
        e.preventDefault();

        const currentTheme = button.getAttribute('data-theme') || DEFAULT_THEME;
        const newTheme = currentTheme === THEME_TYPES.SEMANTIC
          ? THEME_TYPES.UI8KIT
          : THEME_TYPES.SEMANTIC;

        // Store the new theme
        storeTheme(newTheme);

        // Redirect with theme parameter
        const url = new URL(window.location.href);
        url.searchParams.set('theme', newTheme);
        window.location.href = url.toString();
      });
    });
  }

  /**
   * Initializes theme on page load
   */
  function initTheme() {
    console.log('Theme initializer loaded');

    // Get theme from URL or localStorage
    const urlTheme = getThemeFromUrl();
    if (urlTheme) {
      console.log(`URL theme parameter: ${urlTheme}`);
      storeTheme(urlTheme);
      removeThemeParamFromUrl();
    }

    const storedTheme = getStoredTheme();
    console.log(`Saved theme from localStorage: ${storedTheme || 'none'}`);

    // Update UI based on current theme
    const currentTheme = urlTheme || storedTheme || DEFAULT_THEME;
    updateThemeToggleButton(currentTheme);

    // Set up event handlers
    attachThemeToggleListeners();
  }

  // Initialize on load
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTheme);
  } else {
    initTheme();
  }
})(); 