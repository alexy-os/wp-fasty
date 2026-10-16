import { defineConfig } from "vite";
import { svelte } from "@sveltejs/vite-plugin-svelte";

// Classic IIFE for wp-admin: one script (assets/editor.js, global WPFastyBuilder) and one stylesheet (assets/editor.css).
export default defineConfig({
  plugins: [svelte()],
  build: {
    outDir: "../assets",
    emptyOutDir: false,
    target: "es2020",
    cssCodeSplit: false,
    lib: { entry: "src/main.ts", name: "WPFastyBuilder", formats: ["iife"], fileName: () => "editor.js", cssFileName: "editor" },
  },
});
