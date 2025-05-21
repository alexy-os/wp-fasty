import { RootLayout } from '@app/layouts'
import { getComponents } from '@/utils/theme'
import { page } from '@/context/data'

export type HomePageProps = {
  slug: string
}

export function HomePage() {

  // Destructure the components
  const {
    Article,
    ArticleHeader,
    ArticleContent,
  } = getComponents()

  return (
    <RootLayout
      title={page.title}
      description={page.excerpt}
    >
      <Article>
        <ArticleHeader>
          <h1 className="text-3xl font-bold mb-4">{page.title}</h1>
          {page.featuredImage && (
            <img
              src={page.featuredImage.url}
              alt={page.featuredImage.alt}
              className="w-full h-auto rounded-lg mb-6"
            />
          )}
        </ArticleHeader>

        <ArticleContent>
          <div dangerouslySetInnerHTML={{ __html: page.content }} />
        </ArticleContent>
      </Article>
    </RootLayout>
  )
} 