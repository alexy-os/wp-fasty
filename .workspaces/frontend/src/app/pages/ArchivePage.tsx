import { posts, archive } from '@/context/data'
import { getUI, getComponents } from '@/utils/theme'
import { RootLayout } from '@app/layouts'
import { Button } from '@app/components'

export function ArchivePage() {
  const { A } = getUI()
  const { Article, Grid, H1, H2, P, Img } = getComponents()

  return (
    <RootLayout title={archive.title}>
      <div className="max-w-4xl">
        <H1>{archive.title}</H1>
        <P>{archive.description}</P>

        <Grid>
          {posts.map((post) => (
            <Article key={post.id}>
              {post.thumbnail && (
                <Img
                  src={post.thumbnail.url}
                  alt={post.thumbnail.alt}
                  className="w-full h-48 object-cover"
                />
              )}
              <div className="p-6">
                <H2>
                  <A href={`/post/${post.slug}`}>
                    {post.title}
                  </A>
                </H2>
                <P>
                  {post.date.display}
                </P>
                <P>
                  {post.excerpt}
                </P>
                <A href={`/post/${post.slug}`}>
                  <Button variant="secondary" size="sm">Read more</Button>
                </A>
              </div>
            </Article>
          ))}
        </Grid>
      </div>
    </RootLayout>
  )
} 