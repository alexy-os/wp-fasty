import React from 'react'
import { Container, SectionHeader, SectionFooter } from '@semantic/components/section'
import { Nav, Navbar, NavList, NavItem, NavLink } from '@semantic/components/nav'
import { Main } from '@semantic/components/main'
import { Button } from '@n4shadcn/ui/button'
import { site, menu } from '@/context/data'

const currentTheme = process.env.DEFAULT_LAYOUT || 'semantic';

export type RootLayoutSemanticProps = {
  title: string
  description?: string
  children: React.ReactNode
}

export function RootLayoutSemantic({ children }: RootLayoutSemanticProps) {

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
          {/* <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script> */}

          {/* Theme switcher script */}
          <script src="/src/assets/js/theme-switcher.js"></script>
        </head>
        <body className="bg-background text-foreground">
          <SectionHeader>
            <Container>
              <Navbar>
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
                <Button
                  id="theme-toggle"
                  size="sm"
                  className="bg-teal-500 text-white !rounded-full"
                  data-current-theme={currentTheme}
                  title={`Current Theme: ${currentTheme === 'ui8kit' ? 'UI8Kit' : 'Semantic UI'}`}
                >
                  Switch to {currentTheme === 'semantic' ? 'UI8Kit' : 'Semantic'}
                </Button>
              </Navbar>
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