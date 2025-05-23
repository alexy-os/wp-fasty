import { RootLayout } from '@app/layouts'
import { getComponents } from '@/utils/theme';
import { posts } from '@/context/data'

export type PostPageProps = {
  slug: string
}

export const NotFound = {
  title: 'Post Not Found',
  content: 'The post you\'re looking for doesn\'t exist.',
  link: '/',
  linkText: 'Return to homepage'
} as const;

export function PostPage({ slug }: PostPageProps) {

  // Destructure the components
  const {
    P,
    Article,
    ArticleHeader,
    ArticleTitle,
    ArticleMeta,
    ArticleTime,
    ArticleContent,
    ArticleFigure,
    ArticleImage,
    ArticleFigcaption,
    ArticleFooter,
    ArticleTags,
    ArticleTag,
  } = getComponents()

  const post = posts.find(p => p.slug === slug)

  if (!post) {
    return (
      <RootLayout title={NotFound.title}>
        <Article>
          <ArticleHeader>
            <ArticleTitle>{NotFound.title}</ArticleTitle>
          </ArticleHeader>
          <ArticleContent>
            <p>{NotFound.content}</p>
            <a href={NotFound.link}>{NotFound.linkText}</a>
          </ArticleContent>
        </Article>
      </RootLayout>
    )
  }

  return (
    <RootLayout title={post.title}>
      <Article>

        {post.featuredImage && (
          <ArticleFigure>
            <ArticleImage
              src={post.featuredImage.url}
              alt={post.featuredImage.alt}
            />
            {post.featuredImage.caption && (
              <ArticleFigcaption>{post.featuredImage.caption}</ArticleFigcaption>
            )}
          </ArticleFigure>
        )}

        <ArticleHeader>
          <ArticleTitle>{post.title}</ArticleTitle>
          <ArticleMeta>
            <ArticleTime>Published on {post.date.display}</ArticleTime>
          </ArticleMeta>
        </ArticleHeader>

        <ArticleContent>
          <P>{post.content}</P>
        </ArticleContent>

        {post.categories && (
          <ArticleFooter>
            <ArticleTags>
              {post.categories.map(category => (
                <ArticleTag key={category.id}>
                  <a href={category.url}>
                    {category.name}
                  </a>
                </ArticleTag>
              ))}
            </ArticleTags>
          </ArticleFooter>
        )}
      </Article>
    </RootLayout>
  )
} 