const NavbarTemplate = ({ site, menu }) => {
  return (
    <header className="site-header">
      <div className="container">
        <div className="header-content flex items-center justify-between">
          <a href={site.url} className="site-logo">
            {site.title}
          </a>

          {menu && menu.primary && (
            <>
              <nav className="site-nav">
                <ul className="nav-menu">
                  {menu.primary.items.map((item: any, index: number) => (
                    <li key={index} className="nav-item">
                      <a href={item.url} className="nav-link">
                        {item.title}
                      </a>
                    </li>
                  ))}
                </ul>
              </nav>
            </>
          )}
        </div>
      </div>
    </header>
  );
};

export const Navbar = NavbarTemplate;