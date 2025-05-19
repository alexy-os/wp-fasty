import { Elysia } from 'elysia'
import { html } from '@elysiajs/html'
import { renderToStaticMarkup } from 'react-dom/server'
import { setTheme, getTheme } from './theme-store'
import { RootLayout } from './app/layouts/RootLayout'
import { HomePage } from './app/pages/HomePage'
import { ArchivePage } from './app/pages/ArchivePage'
import { PostPage } from './app/pages/PostPage'
import { AboutPage } from './app/pages/AboutPage'
import { ThemeProvider } from './store/theme/context'
import { THEME_TYPES, type ThemeType } from './store/theme'

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

  // Wrapper function for ThemeProvider
  .decorate(() => {
    return {
      wrapWithTheme: (component: React.ReactNode) => {
        const currentTheme = getTheme() as ThemeType;
        return (
          <ThemeProvider initialTheme={currentTheme}>
            {component}
          </ThemeProvider>
        );
      }
    };
  })

  // Home Page
  .get('/', ({ html, wrapWithTheme }) => {
    console.log(`Rendering homepage with theme: ${getTheme()}`);
    return html(`<!DOCTYPE html>${renderToStaticMarkup(
      wrapWithTheme(<HomePage />)
    )}
    <script src="/src/assets/js/theme-init.js"></script>
    `)
  })

  // Archive
  .get('/archive', ({ html, wrapWithTheme }) => {
    return html(`<!DOCTYPE html>${renderToStaticMarkup(
      wrapWithTheme(<ArchivePage />)
    )}
    <script src="/src/assets/js/theme-init.js"></script>
    `)
  })

  // Blog (alias for archive)
  .get('/blog', ({ html, wrapWithTheme }) => {
    return html(`<!DOCTYPE html>${renderToStaticMarkup(
      wrapWithTheme(<ArchivePage />)
    )}
    <script src="/src/assets/js/theme-init.js"></script>
    `)
  })

  // About
  .get('/about', ({ html, wrapWithTheme }) => {
    return html(`<!DOCTYPE html>${renderToStaticMarkup(
      wrapWithTheme(<AboutPage />)
    )}
    <script src="/src/assets/js/theme-init.js"></script>
    `)
  })

  // Single post
  .get('/post/:slug', ({ params, html, wrapWithTheme }) => {
    return html(`<!DOCTYPE html>${renderToStaticMarkup(
      wrapWithTheme(<PostPage slug={params.slug} />)
    )}
    <script src="/src/assets/js/theme-init.js"></script>
    `)
  })

  // Static files
  .get('/src/assets/*', ({ request }) => {
    try {
      const path = request.url.split('/assets/')[1];
      const file = Bun.file(`./src/assets/${path}`);
      return new Response(file);
    } catch (error) {
      console.error('Error serving static file:', error);
      return new Response('File not found', { status: 404 });
    }
  })

  // 404 for all other routes
  .get('*', ({ html, request, wrapWithTheme }) => {
    return html(`<!DOCTYPE html>${renderToStaticMarkup(
      wrapWithTheme(
        <RootLayout title="404 - Page Not Found">
          <div className="max-w-4xl mx-auto text-center py-12">
            <h1 className="text-4xl font-bold mb-4">404 - Page Not Found</h1>
            <p className="text-lg mb-8">Page {request.url} not found.</p>
            <a href="/" className="text-primary hover:underline">Back to Home</a>
          </div>
        </RootLayout>
      )
    )}
    <script src="/src/assets/js/theme-init.js"></script>
    `)
  })

app.listen(3000)

console.log('🦊 Server running at http://localhost:3000') 