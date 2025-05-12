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
    <Article key={post.id}>
      <ArticleHeader>
        <ArticleTitle>{post.title}</ArticleTitle>
        <ArticleMeta>
          {post.date &&
            <ArticleTime dateTime={post.date.formatted}>{post.date.display}</ArticleTime>
          }
        </ArticleMeta>
      </ArticleHeader>
      <ArticleContent>
        {post.excerpt &&
          <p>{post.excerpt}</p>
        }
      </ArticleContent>
    </Article>
  )
}