import {
  Article,
  ArticleHeader,
  ArticleTitle,
  ArticleMeta,
  ArticleTime,
  ArticleContent
} from "@uikits/ui8px/core/semantic/components/article";

import { posts } from "@/context/data";

<>
  {posts.map((post: any) =>
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
</>