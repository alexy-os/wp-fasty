import React from 'react';
import { site, menu } from '@/context/data';
import { getComponents, getTheme } from '@/utils/theme';

export type RootLayoutProps = {
  title: string
  description?: string
  children: React.ReactNode
}

export function RootLayout({ title, description, children }: RootLayoutProps) {
  const theme = getTheme();

  // Configure UI for the theme toggle button
  const colorBtn = theme === 'semantic' ? 'bg-sky-500 text-white' : 'bg-teal-500 text-white';
  const targetTheme = theme === 'semantic' ? 'ui8kit' : 'semantic';
  const buttonText = `Switch to ${targetTheme === 'semantic' ? 'Semantic' : 'UI8Kit'}`;

  // Get all necessary components directly
  const { Main, Container, SectionHeader, SectionFooter, Navbar, Nav, NavList, NavItem, NavLink, H2, P } = getComponents();

  let source;

  try {
    if (theme === 'semantic') {
      source = theme;
    } else {
      source = 'n4shadcn';
    }

  } catch (error) {
    console.error('Error importing Button component:', error);
  }

  const { Button } = require(`@${source}/ui/button`);

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
              <H2>{site.title}</H2>
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
                className={`${colorBtn} text-sm px-4 py-2 !rounded-full`}
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
            <p className="text-center py-4">&copy; {new Date().getFullYear()} {site.title}</p>
          </Container>
        </SectionFooter>

        <script src="/src/assets/js/themes.js"></script>
      </body>
    </html>
  );
}