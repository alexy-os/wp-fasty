import React from 'react';
import { site, menu } from '@/context/data';
import { useTheme } from '@/store/theme/context';

interface MainLayoutProps {
  children: React.ReactNode;
}

export function MainLayout({ children }: MainLayoutProps) {
  const { current: currentTheme } = useTheme();

  // Configure UI for the theme toggle button
  const colorBtn = currentTheme === 'semantic' ? 'bg-sky-500 text-white' : 'bg-teal-500 text-white';
  const targetTheme = currentTheme === 'semantic' ? 'ui8kit' : 'semantic';
  const buttonText = `Switch to ${targetTheme === 'semantic' ? 'Semantic' : 'UI8Kit'}`;

  // Import components safely
  let components;
  try {
    // Define components based on the theme
    if (currentTheme === 'semantic') {
      components = require('@/uikits/@semantic/src/components');
    } else {
      components = require('@/uikits/@ui8kit/src/components');
    }
  } catch (error) {
    console.error('Failed to load theme components:', error);
    return <div>Error loading theme components. Please try another theme.</div>;
  }

  const Button = components.Button || ((props: React.HTMLAttributes<HTMLButtonElement>) => <button {...props} />);

  // Structure components
  const Section = {
    Header: components.SectionHeader || ((props: React.HTMLAttributes<HTMLElement>) => <header {...props} />),
    Footer: components.SectionFooter || ((props: React.HTMLAttributes<HTMLElement>) => <footer {...props} />),
    Container: components.Container || ((props: React.HTMLAttributes<HTMLDivElement>) => <div className="container mx-auto px-4" {...props} />)
  };

  const Nav = {
    Root: components.Nav || ((props: React.HTMLAttributes<HTMLElement>) => <nav {...props} />),
    List: components.NavList || ((props: React.HTMLAttributes<HTMLUListElement>) => <ul className="flex space-x-4" {...props} />),
    Item: components.NavItem || ((props: React.HTMLAttributes<HTMLElement>) => <li {...props} />),
    Link: components.NavLink || ((props: React.HTMLAttributes<HTMLAnchorElement>) => <a className="hover:underline" {...props} />)
  };

  const Main = components.Main || ((props: React.HTMLAttributes<HTMLElement>) => <main className="py-8" {...props} />);

  return (
    <>
      <Section.Header>
        <Section.Container>
          <div className="flex justify-between items-center py-4">
            <h1 className="text-xl font-bold">{site.title}</h1>
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
              className={`${colorBtn} text-sm px-4 py-2 !rounded-full`}
              data-current-theme={currentTheme}
              title={`Current Theme: ${currentTheme}`}
            >
              {buttonText}
            </Button>
          </div>
        </Section.Container>
      </Section.Header>

      <Main>
        <Section.Container>
          {children}
        </Section.Container>
      </Main>

      <Section.Footer>
        <Section.Container>
          <p className="text-center py-4">&copy; {new Date().getFullYear()} {site.title}</p>
        </Section.Container>
      </Section.Footer>
    </>
  );
}