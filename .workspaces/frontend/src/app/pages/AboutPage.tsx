import { RootLayout } from '@app/layouts'
import { getComponents } from '@/utils/theme'
// import { page } from '@/context/data'

export const urlImage = 'https://images.unsplash.com/photo-1542125387-c71274d94f0a?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=560&q=80';

const page = {
  title: 'About Our Approach',
  excerpt: 'Our approach to building web applications',
  featuredImage: {
    url: urlImage,
    alt: 'About Our Approach',
    caption: 'About Our Approach',
  },
  content: 'Our approach to building web applications',
}

export function AboutPage() {
  // Destructure the components
  const {
    H1, P,
    Article,
    ArticleFigure,
    ArticleImage,
    ArticleFigcaption,
    ArticleContent,
  } = getComponents()

  return (
    <RootLayout
      title={page.title}
      description={page.excerpt}
    >
      <Article>

        {page.featuredImage && (
          <ArticleFigure>
            <ArticleImage
              src={page.featuredImage.url}
              alt={page.featuredImage.alt}
            />
            {page.featuredImage.caption && (
              <ArticleFigcaption>{page.featuredImage.caption}</ArticleFigcaption>
            )}
          </ArticleFigure>
        )}

        <ArticleContent>
          <H1>{page.title}</H1>
          <P>{page.content}</P>
        </ArticleContent>
      </Article>
    </RootLayout>
  )
} 