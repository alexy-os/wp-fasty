import { ReactNode } from 'react';
import { site, menu } from '@/context/data';
import { getTheme } from '../../theme-store';

interface MainLayoutProps {
  children: ReactNode;
}

// The function for creating components is moved outside the component
function createComponents(theme: string) {
  console.log('Creating components for theme:', theme);

  // Import components based on the theme
  const { Container, SectionHeader, SectionFooter } = require(`@${theme}/components/section`);
  const { Nav, Navbar, NavList, NavItem, NavLink } = require(`@${theme}/components/nav`);
  const { Main } = require(`@${theme}/components/main`);
  const { Button } = require('@n4shadcn/ui/button');

  return {
    components: {
      Section: {
        Header: SectionHeader,
        Footer: SectionFooter,
        Container: Container
      },
      Nav: {
        Root: Nav,
        Navbar: Navbar,
        List: NavList,
        Item: NavItem,
        Link: NavLink
      },
      Main: Main,
      Button: Button
    }
  };
}

export function MainLayout({ children }: MainLayoutProps) {
  // Get the current theme from the global store
  const currentTheme = getTheme();
  console.log('Current theme in MainLayout:', currentTheme);

  // Get the components for the current theme
  const { components: ui } = createComponents(currentTheme);

  // Destructure the components
  const { Section, Nav, Main, Button } = ui;

  // Configure UI for the theme toggle button
  const colorBtn = currentTheme === 'semantic' ? 'bg-sky-500 text-white' : 'bg-teal-500 text-white';
  const targetTheme = currentTheme === 'semantic' ? 'ui8kit' : 'semantic';
  const buttonText = `Switch to ${targetTheme === 'semantic' ? 'Semantic' : 'UI8Kit'}`;

  return (
    <>
      <Section.Header>
        <Section.Container>
          <Nav.Navbar>
            <Nav.Root>
              <Nav.List>
                {menu.primary.items.map((item) => (
                  <Nav.Item key={item.id}>
                    <Nav.Link href={item.url}>{item.title}</Nav.Link>
                  </Nav.Item>
                ))}
              </Nav.List>
            </Nav.Root>

            {/* Improved theme toggle button */}
            <Button
              id="theme-toggle"
              size="sm"
              className={`${colorBtn} !rounded-full`}
              data-current-theme={currentTheme}
              title={`Current Theme: ${currentTheme}`}
            >
              {buttonText}
            </Button>
          </Nav.Navbar>
        </Section.Container>
      </Section.Header>
      <Main>
        <Section.Container>
          {children}
        </Section.Container>
      </Main>
      <Section.Footer>
        <Section.Container>
          <p className="px-4 py-6 text-center">© {new Date().getFullYear()} {site.title}</p>
        </Section.Container>
      </Section.Footer>
    </>
  );
}