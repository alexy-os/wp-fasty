import React from 'react';
import { site, menu } from '@/context/data';
import { getComponents, getTheme } from '@/utils/theme';
import { Button, SiteLogo, DarkMode, Sidebar } from '@app/components';
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

  // Get all necessary components directly
  const { Main, Container, Section, SectionFooter, NavBar, Nav, NavList, NavItem, NavLink, H2, P, NavGroupButtons, SheetTrigger, SheetContent, SheetHeader, SheetTitle, SheetDescription, SheetBody, SheetOverlay, NavMobileList, NavMobileItem, NavMobileLink, SheetLayout } = getComponents();

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
          <NavBar>
            <SiteLogo />
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

          <Section>
            <Container className="grid grid-cols-3 gap-6">
              <Main className="col-span-3 md:col-span-2">
                {children}
              </Main>
              <Sidebar className="col-span-3 md:col-span-1" />
            </Container>
          </Section>

          <SectionFooter>
            <Container>
              <P className="text-center py-4">&copy; {new Date().getFullYear()} {site.title}</P>
            </Container>
          </SectionFooter>

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
          </SheetContent>

        </SheetLayout>

        <script src="/src/assets/js/themes.js"></script>
        <script src="/src/assets/js/darkmode.js"></script>
      </body>
    </html>
  );
}