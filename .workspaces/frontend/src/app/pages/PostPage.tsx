import { posts } from '@/context/data'
import { RootLayout } from '../layouts/RootLayout'
import { getTheme } from '../../store/get-theme';

export function getArticleComponents() {
  const theme = getTheme();
  console.log(`Loading article components for theme: ${theme}`);

  try {
    // Dynamically import components based on the theme
    const components = require(`@${theme}/components/article`);

    return {
      components,
      currentTheme: theme
    };
  } catch (error) {
    console.error(`Error loading article components for theme: ${theme}`, error);
    return {
      components: null,
      currentTheme: theme
    };
  }
}

export type PostPageProps = {
  slug: string
}

export function PostPage({ slug }: PostPageProps) {
  // Get the Article components based on the current theme
  const { components: ArticleComponents } = getArticleComponents()

  // Destructure the components
  const {
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
  } = ArticleComponents

  const post = posts.find(p => p.slug === slug)

  if (!post) {
    return (
      <RootLayout title="Post Not Found">
        <Article>
          <ArticleHeader>
            <ArticleTitle>404 - Post Not Found</ArticleTitle>
          </ArticleHeader>
          <ArticleContent>
            <p>The post you're looking for doesn't exist.</p>
            <a href="/">Return to homepage</a>
          </ArticleContent>
        </Article>
      </RootLayout>
    )
  }

  return (
    <RootLayout title={post.title}>
      <Article>
        <ArticleHeader>
          <ArticleTitle>{post.title}</ArticleTitle>
          <ArticleMeta>
            <ArticleTime>Published on {post.date.display}</ArticleTime>
          </ArticleMeta>
        </ArticleHeader>

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

        <ArticleContent dangerouslySetInnerHTML={{ __html: post.content }} />

        {post.categories.length > 0 && (
          <ArticleFooter>
            <ArticleTitle>Categories</ArticleTitle>
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