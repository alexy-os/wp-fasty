import React from 'react';
import { site, menu } from '@/context/data';
import { useTheme } from '@/store/theme/context';
import { getComponents } from '@/utils/theme/components';

interface MainLayoutProps {
  children: React.ReactNode;
}

export function MainLayout({ children }: MainLayoutProps) {
  const { current: theme } = useTheme();

  // Configure UI for the theme toggle button
  const colorBtn = theme === 'semantic' ? 'bg-sky-500 text-white' : 'bg-teal-500 text-white';
  const targetTheme = theme === 'semantic' ? 'ui8kit' : 'semantic';
  const buttonText = `Switch to ${targetTheme === 'semantic' ? 'Semantic' : 'UI8Kit'}`;

  // Get all necessary components directly
  const { Main, Container, SectionHeader, SectionFooter, Nav, NavList, NavItem, NavLink } = getComponents();

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
    <>
      <SectionHeader>
        <Container>
          <div className="flex justify-between items-center py-4">
            <h1 className="text-xl font-bold">{site.title}</h1>
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
          <p className="text-center py-4">&copy; {new Date().getFullYear()} {site.title}</p>
        </Container>
      </SectionFooter>
    </>
  );
}