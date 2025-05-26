import React from 'react';
import { site, menu } from '@/context/data';
import { getComponents, getTheme, getUI } from '@/utils/theme';
import { Button, SiteLogo, DarkMode } from '@app/components';
import { Menu } from 'lucide-react';

export type RootLayoutProps = {
  title: string
  description?: string
  children: React.ReactNode
}

export function RootLayout({ title, description, children }: RootLayoutProps) {
  const theme = getTheme();

  // Configure UI for the theme toggle button 
  const colorBtn = theme === 'semantic' ? 'bg-sky-500' : 'bg-teal-500 text-white hover:bg-teal-400';
  const buttonText = `Switch to ${theme === 'semantic' ? 'Semantic' : 'UI8Kit'}`;

  const { Card, CardContent } = getUI();

  // Get all necessary components directly
  const { Main, Container, SectionHeader, SectionFooter, NavBar, Nav, NavList, NavItem, NavLink, H2, P, NavGroupButtons, SheetTrigger, SheetContent, SheetHeader, SheetTitle, SheetDescription, SheetFooter, SheetBody, SheetOverlay, NavMobileList, NavMobileItem, NavMobileLink, SheetLayout } = getComponents();

  return (
    <html lang="en">
      <head>
        <meta charSet="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>{title}</title>
        {description && <meta name="description" content={description} />}
        <link rel="stylesheet" href="/src/assets/css/styles.css" />
        <script dangerouslySetInnerHTML={{
          __html: `!function(){try{const e="dark"===localStorage.getItem("darkmode")||!("darkmode"in localStorage)&&window.matchMedia("(prefers-color-scheme: dark)").matches;document.documentElement.classList.toggle("dark",e)}catch(e){console.error("Failed to set initial theme:",e)}}();`
        }} />
      </head>
      <body className="bg-background text-foreground">
        <SheetLayout>
          <SectionHeader>
            <NavBar>
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

              <NavGroupButtons>
                <DarkMode />
                <Button
                  id="theme-toggle"
                  size="sm"
                  className={`${colorBtn} !rounded-full !text-white`}
                  data-current-theme={theme}
                  title={`Current Theme: ${theme}`}
                >
                  {buttonText}
                </Button>
                <SheetTrigger htmlFor="sheet-toggle">
                  <Menu className="h-5 w-5" />
                </SheetTrigger>
              </NavGroupButtons>
            </NavBar>
          </SectionHeader>

          <Main>
            <Container>
              {children}
            </Container>
          </Main>

          {/* <Sidebar /> */}

          <SectionFooter>
            <Container>
              <P className="text-center py-4">&copy; {new Date().getFullYear()} {site.title}</P>
            </Container>
          </SectionFooter>

          {/* Mobile menu overlay and content */}
          <SheetOverlay />
          <SheetContent>
            <SheetHeader>
              <SheetTitle>Menu</SheetTitle>
              <SheetDescription>Navigation menu</SheetDescription>
            </SheetHeader>

            <SheetBody>
              <NavMobileList>
                {menu.primary.items.map((item) => (
                  <NavMobileItem key={item.id}>
                    <NavMobileLink href={item.url}>{item.title}</NavMobileLink>
                  </NavMobileItem>
                ))}
              </NavMobileList>
            </SheetBody>

            <SheetFooter>
              <Card className="w-full">
                <CardContent>
                  <P className="text-center py-4">&copy; {new Date().getFullYear()} {site.title}</P>
                </CardContent>
              </Card>
            </SheetFooter>
          </SheetContent>

        </SheetLayout>

        <script src="/src/assets/js/themes.js"></script>
        <script src="/src/assets/js/darkmode.js"></script>
      </body>
    </html>
  );
}