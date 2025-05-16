import { Container, SectionHeader, SectionFooter } from '@semantic/components/section'
import { Nav, NavList, NavItem, NavLink } from '@semantic/components/nav'
import { Main } from '@semantic/components/main'

import { site, menu } from '@/context/data'

export type SemanticLayoutProps = {
  title: string
  description?: string
  children: React.ReactNode
}

export function SemanticLayout({ title, description, children }: SemanticLayoutProps) {
  return (
    <>
      <html lang={site.lang}>
        <head>
          <meta charSet={site.charset} />
          <meta name="viewport" content="width=device-width, initial-scale=1.0" />
          <title>{site.title}</title>
          <meta name="description" content={site.description} />
          <link rel="icon" type="image/svg+xml" href="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCA0NyA0MCIgZmlsbD0iIzBlYTVlOSI+DQogICAgPHBhdGggZD0iTTIzLjUgNi41QzE3LjUgNi41IDEzLjc1IDkuNSAxMi4yNSAxNS41QzE0LjUgMTIuNSAxNy4xMjUgMTEuMzc1IDIwLjEyNSAxMi4xMjVDMjEuODM2NyAxMi41NTI5IDIzLjA2MDEgMTMuNzk0NyAyNC40MTQyIDE1LjE2OTJDMjYuNjIwMiAxNy40MDg0IDI5LjE3MzQgMjAgMzQuNzUgMjBDNDAuNzUgMjAgNDQuNSAxNyA0NiAxMUM0My43NSAxNCA0MS4xMjUgMTUuMTI1IDM4LjEyNSAxNC4zNzVDMzYuNDEzMyAxMy45NDcxIDM1LjE4OTkgMTIuNzA1MyAzMy44MzU3IDExLjMzMDhDMzEuNjI5NyA5LjA5MTU4IDI5LjA3NjYgNi41IDIzLjUgNi41Wk0xMi4yNSAyMEM2LjI1IDIwIDIuNSAyMyAxIDI5QzMuMjUgMjYgNS44NzUgMjQuODc1IDguODc1IDI1LjYyNUMxMC41ODY3IDI2LjA1MjkgMTEuODEwMSAyNy4yOTQ3IDEzLjE2NDIgMjguNjY5M0MxNS4zNzAyIDMwLjkwODQgMTcuOTIzNCAzMy41IDIzLjUgMzMuNUMyOS41IDMzLjUgMzMuMjUgMzAuNSAzNC43NSAyNC41QzMyLjUgMjcuNSAyOS44NzUgMjguNjI1IDI2Ljg3NSAyNy44NzVDMjUuMTYzMyAyNy40NDcxIDIzLjkzOTkgMjYuMjA1MyAyMi41ODU4IDI0LjgzMDdDMjAuMzc5OCAyMi41OTE2IDE3LjgyNjYgMjAgMTIuMjUgMjBaIj48L3BhdGg+DQo8L3N2Zz4=" />
          <link rel="stylesheet" href="/src/assets/css/styles.css" />

          {/* Theme switcher script */}
          <script src="/src/assets/js/theme-switcher.js"></script>
        </head>
        <body className="bg-background text-foreground">
          <SectionHeader>
            <Container>
              <div className="flex justify-between items-center">
                <Nav>
                  <NavList>
                    {menu.primary.items.map((item) => (
                      <NavItem key={item.id}>
                        <NavLink href={item.url}>{item.title}</NavLink>
                      </NavItem>
                    ))}
                  </NavList>
                </Nav>

                {/* Simple theme toggle button */}
                <button
                  id="theme-toggle"
                  className="px-4 py-2 bg-primary text-white rounded-full text-sm"
                  data-current-theme="semantic"
                >
                  Switch to UI8Kit
                </button>
              </div>
            </Container>
          </SectionHeader>
          <Main>
            <Container>
              {children}
            </Container>
          </Main>
          <SectionFooter>
            <Container>
              <p className="px-4 py-6 text-center">© {new Date().getFullYear()} {site.title}</p>
            </Container>
          </SectionFooter>
        </body>
      </html>
    </>
  )
}