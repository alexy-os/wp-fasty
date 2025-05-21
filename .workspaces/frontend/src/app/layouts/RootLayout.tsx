import React from 'react';
import { site, menu } from '@/context/data';
import { getComponents, getTheme } from '@/utils/theme';
import { Button, SiteLogo } from '@app/components';

export type RootLayoutProps = {
  title: string
  description?: string
  children: React.ReactNode
}

export function RootLayout({ title, description, children }: RootLayoutProps) {
  const theme = getTheme();

  // Configure UI for the theme toggle button 
  const colorBtn = theme === 'semantic' ? 'bg-sky-500' : 'bg-teal-500 hover:bg-teal-400';
  const buttonText = `Switch to ${theme === 'semantic' ? 'Semantic' : 'UI8Kit'}`;

  // Get all necessary components directly
  const { Main, Container, SectionHeader, SectionFooter, Navbar, Nav, NavList, NavItem, NavLink, H2, P } = getComponents();

  return (
    <html lang="en">
      <head>
        <meta charSet="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>{title}</title>
        {description && <meta name="description" content={description} />}
        <link rel="stylesheet" href="/src/assets/css/styles.css" />
      </head>
      <body className="bg-background text-foreground">
        <SectionHeader>
          <Container>
            <Navbar>
              <H2><SiteLogo /></H2>
              <Nav>
                <NavList>
                  {menu.primary.items.map((item) => (
                    <NavItem key={item.id}>
                      <NavLink href={item.url}>{item.title}</NavLink>
                    </NavItem>
                  ))}
                </NavList>
              </Nav>

              <Button
                id="theme-toggle"
                size="sm"
                className={`${colorBtn} !rounded-full`}
                data-current-theme={theme}
                title={`Current Theme: ${theme}`}
              >
                {buttonText}
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
            <P className="text-center py-4">&copy; {new Date().getFullYear()} {site.title}</P>
          </Container>
        </SectionFooter>

        <script src="/src/assets/js/themes.js"></script>
      </body>
    </html>
  );
}