import { WPFastyContext } from '@/context/types/wpfasty'
import { site, menu } from '@/context/data'

export type RootLayoutProps = {
  title?: string
  children: React.ReactNode
}

export function RootLayout({ title = site.title, children }: RootLayoutProps) {
  return (
    <>
      <html lang={site.lang}>
        <head>
          <meta charSet={site.charset} />
          <meta name="viewport" content="width=device-width, initial-scale=1.0" />
          <title>{title}</title>
          <link rel="stylesheet" href="/src/assets/css/styles.css" />
          <script src="https://cdn.tailwindcss.com" />
        </head>
        <body className="bg-background text-foreground">
          <header className="container mx-auto px-4 py-6">
            <nav>
              <ul className="flex space-x-4">
                {menu.primary.items.map((item) => (
                  <li key={item.id}>
                    <a
                      href={item.url}
                      className={`hover:text-primary ${item.current ? 'text-primary' : ''}`}
                    >
                      {item.title}
                    </a>
                  </li>
                ))}
              </ul>
            </nav>
          </header>
          <main className="container mx-auto px-4 py-8">
            {children}
          </main>
          <footer className="container mx-auto px-4 py-6 text-center">
            <p>© {new Date().getFullYear()} {site.title}</p>
          </footer>
        </body>
      </html>
    </>
  )
} 