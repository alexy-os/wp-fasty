import React from 'react';
import { WPFastyContextSchema } from './types/WPFastY-YM-ContextTypes';

// Define specific types to match the schema exactly
type PostType = WPFastyContextSchema['archive']['posts'];
// type CategoryType = WPFastyContextSchema['archive']['posts']['categories'][number];

// Обновленная типизация
type FrontPageProps = {
  site: WPFastyContextSchema['site'];
  page?: WPFastyContextSchema['page'];
  posts?: PostType[];
};

const placeholderImage = "data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjxyZWN0IHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIGZpbGw9IiM2NjY2NjYiLz48L3N2Zz4=";

// Обновленные данные для контекста с полным соответствием схеме
export const Content: FrontPageProps = {
  site: {
    title: "WP Fasty",
    description: "Современная WordPress тема с React и Latte",
    url: "https://example.com",
    theme_url: "/wp-content/themes/wp-fasty",
    lang: "ru",
    charset: "UTF-8"
  },
  page: {
    page: {
      title: "Главная страница",
      content: "<h2>О нас</h2><p>Описание компании</p>",
      slug: "home",
      url: "/",
      id: 1,
      excerpt: "",
      featuredImage: {
        url: "",
        width: 0,
        height: 0,
        alt: ""
      },
      thumbnail: {
        url: "",
        width: 0,
        height: 0,
        alt: ""
      },
      meta: {
        _edit_last: "",
        _edit_lock: ""
      },
      categories: [],
      date: {
        formatted: "",
        display: "",
        modified: "",
        modified_display: "",
        timestamp: 0,
        year: "",
        month: "",
        day: ""
      }
    }
  },
  posts: [
    {
      title: "Начало работы с WP Fasty",
      content: "",
      slug: "post-1",
      url: "/post-1",
      id: 1,
      excerpt: "<p>Узнайте, как быстро создать современный сайт с помощью нашей темы...</p>",
      featuredImage: {
        url: placeholderImage,
        width: 800,
        height: 600,
        alt: "Featured image for post 1"
      },
      thumbnail: {
        url: placeholderImage,
        width: 300,
        height: 200,
        alt: "Thumbnail for post 1"
      },
      meta: {
        _edit_last: "1",
        _edit_lock: "1628772640:1"
      },
      categories: [
        {
          name: "Туториалы",
          url: "/category/tutorials",
          id: 1,
          slug: "tutorials",
          description: "Обучающие материалы",
          count: 5
        },
        {
          name: "WordPress",
          url: "/category/wordpress",
          id: 2,
          slug: "wordpress",
          description: "Статьи о WordPress",
          count: 10
        }
      ],
      date: {
        formatted: "2023-04-10",
        display: "10 Апреля, 2023",
        modified: "2023-04-11",
        modified_display: "11 Апреля, 2023",
        timestamp: 1681084800,
        year: "2023",
        month: "04",
        day: "10"
      }
    },
    {
      title: "Продвинутые техники в React",
      content: "",
      slug: "post-2",
      url: "/post-2",
      id: 2,
      excerpt: "<p>Рассмотрим ключевые техники оптимизации React-приложений...</p>",
      featuredImage: {
        url: placeholderImage,
        width: 800,
        height: 600,
        alt: "Featured image for post 2"
      },
      thumbnail: {
        url: placeholderImage,
        width: 300,
        height: 200,
        alt: "Thumbnail for post 2"
      },
      meta: {
        _edit_last: "1",
        _edit_lock: "1628772641:1"
      },
      categories: [
        {
          name: "Разработка",
          url: "/category/development",
          id: 3,
          slug: "development",
          description: "Материалы для разработчиков",
          count: 7
        },
        {
          name: "React",
          url: "/category/react",
          id: 4,
          slug: "react",
          description: "Статьи о React",
          count: 12
        }
      ],
      date: {
        formatted: "2023-04-15",
        display: "15 Апреля, 2023",
        modified: "2023-04-16",
        modified_display: "16 Апреля, 2023",
        timestamp: 1681516800,
        year: "2023",
        month: "04",
        day: "15"
      }
    },
    {
      title: "React и Latte: совместная работа",
      content: "",
      slug: "post-3",
      url: "/post-3",
      id: 3,
      excerpt: "<p>Узнайте, как использовать React и Latte вместе для создания мощных веб-приложений...</p>",
      featuredImage: {
        url: placeholderImage,
        width: 800,
        height: 600,
        alt: "Featured image for post 3"
      },
      thumbnail: {
        url: placeholderImage,
        width: 300,
        height: 200,
        alt: "Thumbnail for post 3"
      },
      meta: {
        _edit_last: "1",
        _edit_lock: "1628772642:1"
      },
      categories: [
        {
          name: "Разработка",
          url: "/category/development",
          id: 3,
          slug: "development",
          description: "Материалы для разработчиков",
          count: 7
        },
        {
          name: "React",
          url: "/category/react",
          id: 4,
          slug: "react",
          description: "Статьи о React",
          count: 12
        }
      ],
      date: {
        formatted: "2023-04-20",
        display: "20 Апреля, 2023",
        modified: "2023-04-21",
        modified_display: "21 Апреля, 2023",
        timestamp: 1681948800,
        year: "2023",
        month: "04",
        day: "20"
      }
    }
  ]
};

// Компонент с демо-данными
const FrontPage: React.FC<FrontPageProps> = ({ site, page, posts }) => {
  // Для демонстрации используем локальные данные
  const demoData = Content;

  // В реальном использовании берем данные из props
  const siteData = site || demoData.site;
  const pageData = page || demoData.page;
  const postsData = posts || demoData.posts;

  return (
    <>
      <section className="hero">
        <div className="container">
          <div className="hero-content">
            {siteData.title && (<h1 className="hero-title" >{siteData.title}</h1>)}
            {siteData.description && (<p className="hero-description" >{siteData.description}</p>)}

            <div className="hero-actions">
              <a href="#featured" className="button button-primary button-rounded">Explore</a>
              <a href="#about" className="button button-secondary button-rounded">Learn More</a>
            </div>
          </div>
        </div>
      </section>

      {pageData && pageData.page && pageData.page.content &&
        <section id="about" className="about-section">
          <div className="container">
            <div className="about-content" dangerouslySetInnerHTML={{ __html: pageData.page.content }} />
          </div>
        </section>
      }

      {Array.isArray(postsData) && postsData.length > 0 &&
        <section id="featured" className="featured-posts">
          <div className="container">
            <header className="section-header">
              <h2 className="section-title">Featured</h2>
              <p className="section-description">Discover our latest articles and updates</p>
            </header>

            <div className="featured-grid">
              {postsData.map((post: WPFastyContextSchema['archive']['posts'], index) =>
                <article key={index} className="card card-featured" data-loop="posts">
                  <a href={post.url} className="card-link">
                    {post.thumbnail &&
                      <figure className="card-thumbnail">
                        <img src={post.thumbnail.url} alt={post.thumbnail.alt} className="card-image" />
                      </figure>
                    }
                  </a>

                  <header className="card-header">
                    {post.categories && post.categories.length > 0 &&
                      <div className="card-categories">
                        {post.categories.map((category, categoryIndex) =>
                          <a
                            key={categoryIndex}
                            href={category.url}
                            className="card-category" data-loop="post.categories">
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
                      <div
                        className="card-excerpt"
                        dangerouslySetInnerHTML={{ __html: post.excerpt }} />
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
              <a href={`${siteData.url}/blog`} className="button button-primary button-rounded">View All Posts</a>
            </div>
          </div>
        </section>
      }
    </>);
};

export default FrontPage;