import {
  Article,
  ArticleHeader,
  ArticleTitle,
  ArticleMeta,
  ArticleTime,
  ArticleContent
} from "@uikits/ui8px/core/semantic/components/article";

const posts = [
  {
    id: 1,
    title: 'Post 1',
    date: { formatted: '2021-01-01', display: 'January 1, 2021' },
    excerpt: 'This is the excerpt for Post 1'
  }
];

{
  posts.map((post: any) =>
    <article data-slot="article" className="article" key={post.id}>
      <header data-slot="article-header" className="article-header">
        <h2 data-slot="article-title" className="article-title">{post.title}</h2>
        <div data-slot="article-meta" className="article-meta">
          {post.date &&
            <time data-slot="article-time" className="article-time" dateTime={post.date.formatted}>{post.date.display}</time>
          }
        </div>
      </header>
      <div data-slot="article-content" className="article-content">
        {post.excerpt &&
          <p>{post.excerpt}</p>
        }
      </div>
    </article>
  )
}