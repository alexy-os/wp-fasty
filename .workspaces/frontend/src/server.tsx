import { Elysia } from 'elysia'
import { html } from '@elysiajs/html'
import { renderToStaticMarkup } from 'react-dom/server'
import { setTheme, getTheme } from '@/utils/theme'
import { RootLayout } from './app/layouts/RootLayout'
import { ThemeProvider } from './store/theme/context'
import { THEME_TYPES, type ThemeType } from './store/theme'
import { routes } from './utils/theme/routes'

const app = new Elysia()
  .use(html())

  // Process the theme parameter
  .derive(({ request }) => {
    const url = new URL(request.url);
    const theme = url.searchParams.get('theme');

    if (theme && Object.values(THEME_TYPES).includes(theme as ThemeType)) {
      setTheme(theme as ThemeType);
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

// Registering routes
routes.forEach(({ path, component: Component, paramMapper }) => {
  if (path.includes(':')) {
    // Dynamic route with parameters
    app.get(path, ({ params, html: htmlFn, wrapWithTheme }) => {
      const typedParams = params as Record<string, string>;

      // Use parameter mapper if it's defined, otherwise pass parameters directly
      const componentProps = paramMapper ? paramMapper(typedParams) : typedParams;

      return htmlFn(`<!DOCTYPE html>${renderToStaticMarkup(
        wrapWithTheme(<Component {...componentProps} />)
      )}`)
    });
  } else {
    // Static route
    app.get(path, ({ html: htmlFn, wrapWithTheme }) => {
      return htmlFn(`<!DOCTYPE html>${renderToStaticMarkup(
        wrapWithTheme(<Component />)
      )}`)
    });
  }
});

// Static files
app.get('/src/assets/*', ({ request }) => {
  try {
    const path = request.url.split('/assets/')[1];
    const file = Bun.file(`./src/assets/${path}`);
    return new Response(file);
  } catch (error) {
    console.error('Error serving static file:', error);
    return new Response('File not found', { status: 404 });
  }
});

// 404 for other routes
app.get('*', ({ html: htmlFn, request, wrapWithTheme }) => {
  return htmlFn(`<!DOCTYPE html>${renderToStaticMarkup(
    wrapWithTheme(
      <RootLayout title="404 - Page Not Found">
        <div className="max-w-4xl mx-auto text-center py-12">
          <h1 className="text-4xl font-bold mb-4">404 - Page Not Found</h1>
          <p className="text-lg mb-8">Page {request.url} not found.</p>
          <a href="/" className="text-primary hover:underline">Back to Home</a>
        </div>
      </RootLayout>
    )
  )}`)
});

app.listen(3000);
console.log('🦊 Server running at http://localhost:3000'); 