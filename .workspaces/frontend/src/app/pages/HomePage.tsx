import { RootLayout } from '@app/layouts'
import { getComponents } from '@/utils/theme'
import { page } from '@/context/data'

export type HomePageProps = {
  slug: string
}

export function HomePage() {

  // Destructure the components
  const {
    H1, P,
    Section,
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
      <Section>
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
      </Section>
    </RootLayout>
  )
} 