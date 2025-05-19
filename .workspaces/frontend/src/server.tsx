import { Elysia } from 'elysia'
import { html } from '@elysiajs/html'
import { renderToStaticMarkup } from 'react-dom/server'
import { setTheme, getTheme } from './theme-store'
import { RootLayout } from './app/layouts/RootLayout'
import { HomePage } from './app/pages/HomePage'
import { ArchivePage } from './app/pages/ArchivePage'
import { PostPage } from './app/pages/PostPage'
import { AboutPage } from './app/pages/AboutPage'

const app = new Elysia()
  .use(html())

  // Process the theme parameter
  .derive(({ request }) => {
    const url = new URL(request.url);
    const theme = url.searchParams.get('theme');
    console.log(`URL theme parameter: ${theme || 'not set'}`);

    if (theme) {
      setTheme(theme);
      console.log(`Current global theme: ${getTheme()}`);
    }

    return {};
  })

  // Home Page
  .get('/', ({ html }) => {
    console.log(`Rendering homepage with theme: ${getTheme()}`);
    return html(`<!DOCTYPE html>${renderToStaticMarkup(
      <HomePage />
    )}`)
  })

  // Archive
  .get('/archive', ({ html }) => {
    return html(`<!DOCTYPE html>${renderToStaticMarkup(
      <ArchivePage />
    )}`)
  })

  // Blog (alias for archive)
  .get('/blog', ({ html }) => {
    return html(`<!DOCTYPE html>${renderToStaticMarkup(
      <ArchivePage />
    )}`)
  })

  // About
  .get('/about', ({ html }) => {
    return html(`<!DOCTYPE html>${renderToStaticMarkup(
      <AboutPage />
    )}`)
  })

  // Single post
  .get('/post/:slug', ({ params, html }) => {
    return html(`<!DOCTYPE html>${renderToStaticMarkup(
      <PostPage slug={params.slug} />
    )}`)
  })

  // Static files
  .get('/src/assets/*', ({ request }) => {
    return new Response(Bun.file(`./src/assets/${request.url.split('/assets/')[1]}`))
  })

  // 404 for all other routes
  .get('*', ({ html, request }) => {
    return html(`<!DOCTYPE html>${renderToStaticMarkup(
      <RootLayout title="404 - Page Not Found">
        <div className="max-w-4xl mx-auto text-center py-12">
          <h1 className="text-4xl font-bold mb-4">404 - Page Not Found</h1>
          <p className="text-lg mb-8">Page {request.url} not found.</p>
          <a href="/" className="text-primary hover:underline">Back to Home</a>
        </div>
      </RootLayout>
    )}`)
  })

app.listen(3000)

console.log('🦊 Server running at http://localhost:3000') 