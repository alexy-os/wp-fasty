import React from 'react';
import { site, menu } from '@/context/data';
import { getTheme } from '@/theme-store';

export type RootLayoutProps = {
  title: string;
  description?: string;
  children: React.ReactNode;
}

export function RootLayout({ title, description, children }: RootLayoutProps) {
  // Получаем компоненты напрямую без хуков
  const currentTheme = getTheme();

  // Динамический импорт компонентов на стороне сервера
  const { Container, SectionHeader, SectionFooter } = require(`@${currentTheme}/components/section`);
  const { Nav, NavList, NavItem, NavLink } = require(`@${currentTheme}/components/nav`);
  const { Main } = require(`@${currentTheme}/components/main`);

  // Настройки для кнопки переключения темы
  const targetTheme = currentTheme === 'semantic' ? 'ui8kit' : 'semantic';
  const buttonText = `Switch to ${targetTheme}`;

  return (
    <html lang="en">
      <head>
        <meta charSet="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>{title}</title>
        {description && <meta name="description" content={description} />}
        <link rel="stylesheet" href="/src/assets/css/global.css" />
      </head>
      <body className="bg-background text-foreground">
        <header className="bg-slate-800 text-white p-4">
          <Container>
            <Nav>
              <div className="flex justify-between items-center">
                <h1 className="text-xl font-bold">{site.title}</h1>
                <NavList>
                  {menu.primary.items.map((item) => (
                    <NavItem key={item.id}>
                      <NavLink href={item.url}>{item.title}</NavLink>
                    </NavItem>
                  ))}
                </NavList>
                <button
                  id="theme-toggle"
                  type="button"
                  className="theme-toggle-btn"
                  data-theme={currentTheme}
                >
                  {buttonText}
                </button>
              </div>
            </Nav>
          </Container>
        </header>

        <Main>
          <Container>
            {children}
          </Container>
        </Main>

        <footer className="bg-slate-800 text-white p-4 mt-8">
          <Container>
            <p className="text-center">&copy; {new Date().getFullYear()} {site.title}</p>
          </Container>
        </footer>

        <script src="/src/assets/js/theme-init.js"></script>
      </body>
    </html>
  );
}