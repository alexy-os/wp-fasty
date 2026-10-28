import { mount } from "svelte";
import App from "./App.svelte";
import NewPageScreen from "./NewPageScreen.svelte";
import "./tokens.css";
import "./editor.css";
import type { Config } from "./types";

declare global {
  interface Window { WPFastyBuilderConfig?: Config }
}

export function start(target: HTMLElement, config: Config) {
  target.innerHTML = "";
  return mount(App, { target, props: { config } });
}

const el = document.getElementById("wpfasty-builder");
if (el && window.WPFastyBuilderConfig) start(el, window.WPFastyBuilderConfig);
// the Builder page chooser (no page open): only the "New page" panel
const chooser = document.getElementById("wpfasty-builder-new");
if (chooser && window.WPFastyBuilderConfig) mount(NewPageScreen, { target: chooser, props: { config: window.WPFastyBuilderConfig } });
