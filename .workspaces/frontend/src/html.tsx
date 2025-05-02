// cd .workspaces/frontend && bun src/html.tsx
import { renderToStaticMarkup } from 'react-dom/server';
import FrontPage from "@/uikits/latte/components/FrontPage"

/*
* with data-reactid attributes for React hydration
* Used when planning further interactivity on the client
*/
// const html = renderToString(<App />);

/*
* Creates "clean" static HTML without React attributes
* Saves bytes (smaller size)
*/
const html = renderToStaticMarkup(<FrontPage />);

console.log(html);