import React from 'react';

interface Category {
  url: string;
  name: string;
}

interface Thumbnail {
  url: string;
  alt: string;
}

interface PostDate {
  formatted: string;
  display: string;
}

interface Post {
  url: string;
  title: string;
  excerpt?: string;
  thumbnail?: Thumbnail;
  categories?: Category[];
  date?: PostDate;
}

interface Page {
  content: string;
}

interface Site {
  title: string;
  description: string;
  url: string;
}

interface FrontPageProps {
  page?: Page;
  posts?: Post[];
  site: Site;
}

// Пример данных для контекста
export const Content = {
  site: {
    title: "WP Fasty",
    description: "Современная WordPress тема с React и Latte",
    url: "https://example.com"
  },
  page: {
    content: "<h2>О нас</h2><p>Мы создаем современные веб-решения, объединяя мощь WordPress с гибкостью React и Latte.</p>"
  },
  posts: [
    {
      url: "/post-1",
      title: "Начало работы с WP Fasty",
      excerpt: "<p>Узнайте, как быстро создать современный сайт с помощью нашей темы...</p>",
      thumbnail: {
        url: "/images/post-1.jpg",
        alt: "Thumbnail for post 1"
      },
      categories: [
        { url: "/category/tutorials", name: "Туториалы" },
        { url: "/category/wordpress", name: "WordPress" }
      ],
      date: {
        formatted: "2023-04-10",
        display: "10 Апреля, 2023"
      }
    },
    {
      url: "/post-2",
      title: "Продвинутые техники в React",
      excerpt: "<p>Рассмотрим ключевые техники оптимизации React-приложений...</p>",
      thumbnail: {
        url: "/images/post-2.jpg",
        alt: "Thumbnail for post 2"
      },
      categories: [
        { url: "/category/development", name: "Разработка" },
        { url: "/category/react", name: "React" }
      ],
      date: {
        formatted: "2023-04-15",
        display: "15 Апреля, 2023"
      }
    }
  ]
};

// Компонент без пропсов, который использует Content напрямую
const FrontPage: React.FC = () => {
  const { site, page, posts } = Content;

  return (
    <>
      <section className="hero">
        <div className="container">
          <div className="hero-content">
            <h1 className="hero-title">{site.title}</h1>
            <p className="hero-description">{site.description}</p>

            <div className="hero-actions">
              <a href="#featured" className="button button-primary button-rounded">Explore</a>
              <a href="#about" className="button button-secondary button-rounded">Learn More</a>
            </div>
          </div>
        </div>
      </section>

      {page && page.content && (
        <section id="about" className="about-section">
          <div className="container">
            <div className="about-content" dangerouslySetInnerHTML={{ __html: page.content }} />
          </div>
        </section>
      )}

      {posts && posts.length > 0 && (
        <section id="featured" className="featured-posts">
          <div className="container">
            <header className="section-header">
              <h2 className="section-title">Featured Posts</h2>
              <p className="section-description">Discover our latest articles and updates</p>
            </header>

            <div className="featured-grid">
              {posts.map((post, index) => (
                <article key={index} className="card card-featured">
                  <a href={post.url} className="card-link">
                    {post.thumbnail && (
                      <figure className="card-thumbnail">
                        <img src={post.thumbnail.url} alt={post.thumbnail.alt} className="card-image" />
                      </figure>
                    )}
                  </a>

                  <header className="card-header">
                    {post.categories && post.categories.length > 0 && (
                      <div className="card-categories">
                        {post.categories.map((category, categoryIndex) => (
                          <a
                            key={categoryIndex}
                            href={category.url}
                            className="card-category"
                          >
                            {category.name}
                          </a>
                        ))}
                      </div>
                    )}

                    <h3 className="card-title">
                      <a href={post.url}>{post.title}</a>
                    </h3>
                  </header>

                  <div className="card-content">
                    {post.excerpt && (
                      <div
                        className="card-excerpt"
                        dangerouslySetInnerHTML={{ __html: post.excerpt }}
                      />
                    )}
                  </div>

                  <footer className="card-footer">
                    <div className="card-meta">
                      {post.date && (
                        <time dateTime={post.date.formatted} className="card-date">
                          {post.date.display}
                        </time>
                      )}
                    </div>

                    <a href={post.url} className="card-read-more">
                      Read More <span className="read-more-icon">→</span>
                    </a>
                  </footer>
                </article>
              ))}
            </div>

            <div className="featured-action">
              <a href={`${site.url}/blog`} className="button button-primary button-rounded">View All Posts</a>
            </div>
          </div>
        </section>
      )}
    </>
  );
};

export default FrontPage; 