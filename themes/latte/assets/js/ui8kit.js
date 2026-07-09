/**
 * Minimal UI8Kit dialog/sheet behavior for Latte themes.
 * Matches docs/aria.md contract: data-ui8kit-dialog-* hooks.
 * With behavior="ui8kit", initial open/hidden is SSR state only.
 */
(function () {
  "use strict";

  function panel(id) {
    return document.getElementById(id);
  }

  function setOpen(el, open) {
    if (!el) return;
    el.hidden = !open;
    el.setAttribute("data-state", open ? "open" : "closed");
    document.querySelectorAll(
      '[data-ui8kit-dialog-open="true"][data-ui8kit-dialog-target="' + el.id + '"]'
    ).forEach(function (btn) {
      btn.setAttribute("aria-expanded", open ? "true" : "false");
    });
    if (open) {
      var focusable = el.querySelector(
        'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
      );
      if (focusable) focusable.focus();
    }
  }

  function onClick(event) {
    var openBtn = event.target.closest('[data-ui8kit-dialog-open="true"]');
    if (openBtn) {
      var openId = openBtn.getAttribute("data-ui8kit-dialog-target");
      setOpen(panel(openId), true);
      return;
    }
    var closeBtn = event.target.closest('[data-ui8kit-dialog-close="true"]');
    if (closeBtn) {
      var closeId = closeBtn.getAttribute("data-ui8kit-dialog-target");
      setOpen(panel(closeId), false);
    }
  }

  function onKey(event) {
    if (event.key !== "Escape") return;
    document.querySelectorAll('[data-ui8kit-dialog="true"][data-state="open"]').forEach(
      function (el) {
        setOpen(el, false);
      }
    );
  }

  document.addEventListener("click", onClick);
  document.addEventListener("keydown", onKey);
})();
