import {
  Article,
  ArticleHeader,
  ArticleTitle,
  ArticleMeta,
  ArticleTime,
  ArticleContent
} from "@uikits/ui8px/core/source/clsx/components/article";

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
    <article data-slot="article" className="bg-card text-card-foreground flex flex-col gap-4 rounded-xl border shadow-sm" key={post.id}>
      <header data-slot="article-header" className="flex flex-col gap-2 px-6 pt-6">
        <h2 data-slot="article-title" className="text-2xl font-semibold tracking-tight">{post.title}</h2>
        <div data-slot="article-meta" className="flex flex-wrap items-center gap-3 text-sm text-muted-foreground">
          {post.date &&
            <time data-slot="article-time" className="text-sm text-muted-foreground" dateTime={post.date.formatted}>{post.date.display}</time>
          }
        </div>
      </header>
      <div data-slot="article-content" className="prose dark:prose-invert max-w-none px-6 py-4">
        {post.excerpt &&
          <p>{post.excerpt}</p>
        }
      </div>
    </article>
  )
}