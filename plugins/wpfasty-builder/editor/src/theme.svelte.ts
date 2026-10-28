// Runes state of the editor's dark mode (remembered in localStorage per user, see theme.ts).
import { darkKey, parseDark } from "./theme";

export class ThemeMode {
  dark = $state(false);
  #key: string;

  constructor(user?: number | null) {
    this.#key = darkKey(user);
    try {
      this.dark = parseDark(localStorage.getItem(this.#key));
    } catch {
      this.dark = false;
    }
  }

  toggle() {
    this.dark = !this.dark;
    try {
      localStorage.setItem(this.#key, this.dark ? "1" : "0");
    } catch {
      // storage full or blocked: the mode just is not remembered
    }
  }
}
