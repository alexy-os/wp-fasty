import { Elysia } from 'elysia'
import { html } from '@elysiajs/html'
import { renderToStaticMarkup } from 'react-dom/server'
import { HomePage } from './app/pages/HomePage'
import { ArchivePage } from './app/pages/ArchivePage'
import { PostPage } from './app/pages/PostPage'
import { RootLayout } from './app/layouts/RootLayout'
import { AboutPage } from './app/pages/AboutPage'
// import { dirname } from 'path'
// import { fileURLToPath } from 'url'

// Получаем абсолютный путь к текущей директории
// const __filename = fileURLToPath(import.meta.url)
// const __dirname = dirname(__filename)

const app = new Elysia()
  .use(html())

  // Главная страница
  .get('/', ({ html }) => {
    // Просто возвращаем HTML без дополнительной обработки
    return html(`<!DOCTYPE html>${renderToStaticMarkup(<HomePage />)}`)
  })

  // Архив
  .get('/archive', ({ html }) => {
    return html(`<!DOCTYPE html>${renderToStaticMarkup(<ArchivePage />)}`)
  })

  // Blog (алиас для archive)
  .get('/blog', ({ html }) => {
    return html(`<!DOCTYPE html>${renderToStaticMarkup(<ArchivePage />)}`)
  })

  // Архив
  .get('/about', ({ html }) => {
    return html(`<!DOCTYPE html>${renderToStaticMarkup(<AboutPage />)}`)
  })

  // Отдельный пост
  .get('/post/:slug', ({ params, html }) => {
    return html(`<!DOCTYPE html>${renderToStaticMarkup(<PostPage slug={params.slug} />)}`)
  })

  // Статика
  .get('/src/assets/*', ({ request }) => {
    return new Response(Bun.file(`./src/assets/${request.url.split('/assets/')[1]}`))
  })

  // 404 для всех остальных маршрутов
  .get('*', ({ html, request }) => {
    return html(`<!DOCTYPE html>${renderToStaticMarkup(
      <RootLayout title="404 - Page Not Found">
        <div className="max-w-4xl mx-auto text-center py-12">
          <h1 className="text-4xl font-bold mb-4">404 - Page Not Found</h1>
          <p className="text-lg mb-8">Страница {request.url} не найдена.</p>
          <a href="/" className="text-primary hover:underline">На главную</a>
        </div>
      </RootLayout>
    )}`)
  })

app.listen(3000)

console.log('🦊 Server running at http://localhost:3000') 