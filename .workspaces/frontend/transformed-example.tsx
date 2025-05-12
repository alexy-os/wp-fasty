
{
  posts.map((post: any) =>
    <article data-slot="article" class="article" key={post.id}>
      <header data-slot="article-header" class="article-header">
        <h2 data-slot="article-title" class="article-title">{post.title}</h2>
        <div data-slot="article-meta" class="article-meta">
          {post.date &&
            <time data-slot="article-time" class="article-time" dateTime={post.date.formatted}>
              {post.date.display}
            </time>
          }
        </div>
      </header>
      <div data-slot="article-content" class="article-content">
        {post.excerpt &&
          <p>{post.excerpt}</p>
        }
      </div>
    </article>
  )
}