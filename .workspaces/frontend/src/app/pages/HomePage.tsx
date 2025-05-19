import { page } from '@/context/data'
import { RootLayout } from '../layouts/RootLayout'
import { useTheme } from '@/store/theme/context'

export function getComponents() {
  const { current: currentTheme } = useTheme();

  try {
    // Dynamically import components based on the theme
    const components = require(`@${currentTheme}/components/article`);

    return {
      components,
      currentTheme: currentTheme
    };
  } catch (error) {
    console.error(`Error loading article components for theme: ${currentTheme}`, error);
    return {
      components: null,
      currentTheme: currentTheme
    };
  }
}

export type HomePageProps = {
  slug: string
}

export function HomePage() {

  const { components: ArticleComponents } = getComponents()

  if (!ArticleComponents) {
    return <div>Error loading article components</div>
  }

  // Destructure the components
  const {
    Article,
    ArticleHeader,
    ArticleContent,
  } = ArticleComponents

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