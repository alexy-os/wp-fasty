// Dark mode of the editor (pure logic; the runes state is theme.svelte.ts). The switch is the last item of the WP
// toolbar (Admin::darkSwitch, button#wpfb-dark-toggle); the mode is remembered in localStorage per user, like the
// panel layout: a display preference of this browser, read before the first paint of the editor with no request.

/** id of the toolbar switch rendered by Admin::darkButton() */
export const TOGGLE_ID = "wpfb-dark-toggle";
/** the class that turns the dark tokens on: the editor (.wpfb-app.dark), the preview page (<html class="dark">) */
export const DARK_CLASS = "dark";

/** the localStorage key: per user when the bootstrap data has the user id, shared otherwise */
export function darkKey(user?: number | null): string {
  return user && user > 0 ? `wpfasty-builder:dark:${user}` : "wpfasty-builder:dark";
}

/** a stored mode: "1" is dark, anything else (nothing stored, blocked storage) light */
export function parseDark(raw: string | null): boolean {
  return raw === "1";
}

/** the card image for the mode: the dark shot in dark mode when there is one, else the light one */
export function previewSrc(preview: { src: string; dark?: string }, dark: boolean): string {
  return dark && preview.dark ? preview.dark : preview.src;
}
