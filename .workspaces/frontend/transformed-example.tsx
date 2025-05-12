const posts = [
  {
    id: 1,
    title: 'Post 1',
    date: { formatted: '2021-01-01', display: 'January 1, 2021' },
    excerpt: 'This is the excerpt for Post 1'
  }
];

{
  posts.map((post: any) => <article key={post.id} data-slot="article" class="article">
    <header data-slot="article-header" class="article-header">
      <h2 data-slot="article-title" class="article-title">{post.title}</h2>
      <div data-slot="article-meta" class="article-meta">
        {post.date && <time dateTime={post.date.formatted} data-slot="article-time" class="article-time" datetime="">{post.date.display}</time>}
      </div>
    </header>
    <div data-slot="article-content" class="article-content">
      {post.excerpt && <p>{post.excerpt}</p>}
    </div>
  </article>);
}