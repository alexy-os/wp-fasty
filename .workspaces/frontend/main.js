import { mount } from 'svelte';
import App from './App.svelte';

// Svelte 5 использует mount
mount(App, {
  target: document.getElementById('app')
});
