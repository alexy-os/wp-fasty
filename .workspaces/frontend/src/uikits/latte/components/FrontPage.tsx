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

const placeholderImage = "data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjxyZWN0IHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIGZpbGw9IiM2NjY2NjYiLz48L3N2Zz4=";

// Example data for the context
export const Content = {
  site: {
    title: "WP Fasty",
    description: "Modern WordPress theme with React and Latte",
    url: "https://example.com"
  },
  page: {
    content: "<h2>About us</h2><p>We create modern web solutions, combining the power of WordPress with the flexibility of React and Latte.</p>"
  },
  posts: [
    {
      url: "/post-1",
      title: "Getting started with WP Fasty",
      excerpt: "<p>Learn how to quickly create a modern website using our theme...</p>",
      thumbnail: {
        url: placeholderImage,
        alt: "Thumbnail for post 1"
      },
      categories: [
        { url: "/category/tutorials", name: "Tutorials" },
        { url: "/category/wordpress", name: "WordPress" }
      ],
      date: {
        formatted: "2023-04-10",
        display: "April 10, 2023"
      }
    },
    {
      url: "/post-2",
      title: "Advanced techniques in React",
      excerpt: "<p>Let's look at the key techniques for optimizing React applications...</p>",
      thumbnail: {
        url: placeholderImage,
        alt: "Thumbnail for post 2"
      },
      categories: [
        { url: "/category/development", name: "Development" },
        { url: "/category/react", name: "React" }
      ],
      date: {
        formatted: "2023-04-15",
        display: "April 15, 2025"
      }
    },
    {
      url: "/post-3",
      title: "React and Latte: working together",
      excerpt: "<p>Learn how to use React and Latte together to create powerful web applications...</p>",
      thumbnail: {
        url: placeholderImage,
        alt: "Thumbnail for post 3"
      },
      categories: [
        { url: "/category/development", name: "Development" },
        { url: "/category/react", name: "React" }
      ],
    }
  ]
};

// Component without props that uses Content directly
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