// cd .workspaces/frontend && bun src/html.tsx
import { renderToStaticMarkup, renderToString } from 'react-dom/server';
import App from "@/App"

/*
* with data-reactid attributes for React hydration
* Used when planning further interactivity on the client
*/
// const html = renderToString(<App />);

/*
* Creates "clean" static HTML without React attributes
* Saves bytes (smaller size)
*/
const html = renderToStaticMarkup(<App />);

console.log(html);