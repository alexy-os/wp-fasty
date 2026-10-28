import { defineConfig } from "vite";
import { svelte } from "@sveltejs/vite-plugin-svelte";
import tailwindcss from "@tailwindcss/vite";

// Tailwind only compiles the @apply of src/scrollbar.css (no @import "tailwindcss": no preflight, no utilities).
// Classic IIFE for wp-admin: one script (assets/editor.js, global WPFastyBuilder) and one stylesheet (assets/editor.css).
export default defineConfig({
  plugins: [tailwindcss(), svelte()],
  build: {
    outDir: "../assets",
    emptyOutDir: false,
    target: "es2020",
    cssCodeSplit: false,
    lib: { entry: "src/main.ts", name: "WPFastyBuilder", formats: ["iife"], fileName: () => "editor.js", cssFileName: "editor" },
  },
});
