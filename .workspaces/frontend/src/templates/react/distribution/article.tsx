
import { posts } from "@/context/data";
<>
  {posts.map((post: any) =>
    <article className="article" key={post.id}>
      <header className="article-header">
        <h2 className="article-title">{post.title}</h2>
        <div className="article-meta">
          {post.date &&
            <time className="article-time" dateTime={post.date.formatted}>{post.date.display}</time>
          }
        </div>
      </header>
      <div className="article-content">
        {post.excerpt &&
          <p>{post.excerpt}</p>
        }
      </div>
    </article>
  )
  }
</>