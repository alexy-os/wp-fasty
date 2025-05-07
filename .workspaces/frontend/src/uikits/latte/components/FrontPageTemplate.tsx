import { Navbar } from "./NavbarTemplate";

const FrontPageTemplate = ({ site, page, posts, menu }: any) => {

  return (
    <>
      <Navbar site={site} menu={menu} />
      <section className="hero">
        <div className="container">
          <div className="hero-content">
            {page.title && <h1 className="hero-title">{page.title}</h1>}
            {page.description && <p className="hero-description">{page.description}</p>}

            <div className="hero-actions">
              <a href="#featured" className="button button-primary button-rounded">Explore</a>
              <a href="#about" className="button button-secondary button-rounded">Learn More</a>
            </div>
          </div>
        </div>
      </section>

      {page &&
        <section id="about" className="about-section">
          <div className="container">
            <div className="about-content">{page.content}</div>
          </div>
        </section>
      }

      {posts &&
        <section id="featured" className="featured-posts">
          <div className="container">
            <header className="section-header">
              <h2 className="section-title">Featured</h2>
              <p className="section-description">Discover our latest articles and updates</p>
            </header>

            <div className="featured-grid">
              {posts.map((post: any) =>
                <article key={post.id} className="card card-featured">
                  <a href={post.url} className="card-link">
                    {post.thumbnail &&
                      <figure className="card-thumbnail">
                        <img src={post.thumbnail.url} alt={post.thumbnail.alt} className="card-image" />
                      </figure>
                    }
                  </a>

                  <header className="card-header">
                    {post.categories &&
                      <div className="card-categories">
                        {post.categories.map((category: any) =>
                          <a
                            key={category.id}
                            href={category.url}
                            className="card-category">
                            {category.name}
                          </a>
                        )}
                      </div>
                    }

                    <h3 className="card-title">
                      <a href={post.url}>{post.title}</a>
                    </h3>
                  </header>

                  <div className="card-content">
                    {post.excerpt &&
                      <div className="card-excerpt">{post.excerpt}</div>
                    }
                  </div>

                  <footer className="card-footer">
                    <div className="card-meta">
                      {post.date &&
                        <time dateTime={post.date.formatted} className="card-date">
                          {post.date.display}
                        </time>
                      }
                    </div>

                    <a href={post.url} className="card-read-more">
                      Read More <span className="read-more-icon">→</span>
                    </a>
                  </footer>
                </article>
              )}
            </div>

            <div className="featured-action">
              <a href="/blog" className="button button-primary button-rounded">View All Posts</a>
            </div>
          </div>
        </section>
      }
    </>);
};

export const FrontPage = FrontPageTemplate;