import {defineConfig} from "vite";
import {svelte} from "@sveltejs/vite-plugin-svelte";
export default defineConfig({plugins: [svelte()], resolve: {dedupe: ["svelte"]}, build: {outDir: "../../../modules/builder-agent/assets", emptyOutDir: false, target: "es2020", cssCodeSplit: false, lib: {entry: "../../../modules/builder-agent/editor/bridge.svelte.ts", name: "WPFastyBuilderAgent", formats: ["iife"], fileName: () => "agent.js", cssFileName: "agent"}}});
