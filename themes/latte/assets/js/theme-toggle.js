/**
 * Dark mode toggle: flips `dark` on <html>, mirrors it to aria-pressed and keeps the choice in localStorage.
 * The initial class is set before CSS by the inline head script (AssetsHooks::printColorSchemeScript).
 */
(function () {
  "use strict";

  var KEY = "wpfasty-color-scheme";
  var root = document.documentElement;
  var media = window.matchMedia ? window.matchMedia("(prefers-color-scheme: dark)") : null;

  function sync() {
    var dark = root.classList.contains("dark");
    document.querySelectorAll("[data-wpfasty-theme-toggle]").forEach(function (btn) {
      btn.setAttribute("aria-pressed", dark ? "true" : "false");
    });
  }

  function stored() {
    try { return localStorage.getItem(KEY); } catch (e) { return null; }
  }

  document.addEventListener("click", function (event) {
    var btn = event.target.closest ? event.target.closest("[data-wpfasty-theme-toggle]") : null;
    if (!btn) return;
    var dark = !root.classList.contains("dark");
    root.classList.toggle("dark", dark);
    try { localStorage.setItem(KEY, dark ? "dark" : "light"); } catch (e) { /* private mode */ }
    sync();
  });

  if (media && media.addEventListener) {
    media.addEventListener("change", function (event) {
      if (stored()) return;
      root.classList.toggle("dark", event.matches);
      sync();
    });
  }

  sync();
})();
