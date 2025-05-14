import {
  Article,
  ArticleHeader,
  ArticleTitle,
  ArticleMeta,
  ArticleTime,
  ArticleContent
} from "@uikits/ui8px/core/tailwind/clsx/components/article";

import { posts } from "@/context/data";

<>
  {posts.map((post: any) =>
    <Article key={post.id}>
      <ArticleHeader>
        <ArticleTitle>{post.title}</ArticleTitle>
        {post.date &&
          <ArticleMeta>
            <ArticleTime dateTime={post.date.formatted}>{post.date.display}</ArticleTime>
          </ArticleMeta>
        }
      </ArticleHeader>
      {post.excerpt &&
        <ArticleContent>
          <p>{post.excerpt}</p>
        </ArticleContent>
      }
    </Article>
  )
  }
</>