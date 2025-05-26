import { posts, archive } from '@/context/data'
import { getUI, getComponents } from '@/utils/theme'
import { RootLayout } from '@app/layouts'
import { Button } from '@app/components'

export function ArchivePage() {
  const { A } = getUI()
  const {
    Grid, P,
    SectionHeader,
    SectionContent,
    SectionTitle,
    SectionDescription,
    Article,
    ArticleFigure,
    ArticleImage,
    ArticleFigcaption,
    ArticleContent,
    ArticleHeader,
    ArticleTitle,
    ArticleMeta,
    ArticleTime,
    ArticleFooter,
  } = getComponents()

  return (

    <RootLayout
      title={archive.title}
      description={archive.description}
    >
      <SectionHeader>
        <SectionTitle>{archive.title}</SectionTitle>
        <SectionDescription>{archive.description}</SectionDescription>
      </SectionHeader>

      <SectionContent>
        <Grid>
          {posts.map((post) => (
            <Article key={post.id}>

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
                <ArticleTitle>
                  <A href={`/post/${post.slug}`}>
                    {post.title}
                  </A>
                </ArticleTitle>
                <ArticleMeta>
                  <ArticleTime>
                    {post.date.display}
                  </ArticleTime>
                </ArticleMeta>
              </ArticleHeader>
              <ArticleContent className="py-0 text-sm text-secondary-foreground">
                <P>
                  {post.excerpt}
                </P>
              </ArticleContent>
              <ArticleFooter>
                <A href={`/post/${post.slug}`}>
                  <Button variant="secondary" size="sm">Read more</Button>
                </A>
              </ArticleFooter>
            </Article>
          ))}
        </Grid>
      </SectionContent>
    </RootLayout>
  )
} 