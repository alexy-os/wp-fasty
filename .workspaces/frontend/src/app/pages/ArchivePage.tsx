import { posts, archive } from '@/context/data'
import { getUI } from '@/utils/theme'
import { RootLayout } from '@app/layouts'

export function ArchivePage() {
  const { A, Button } = getUI()

  return (
    <RootLayout title={archive.title}>
      <div className="max-w-4xl mx-auto">
        <h1 className="text-4xl font-bold mb-8">{archive.title}</h1>
        <p className="text-lg mb-12">{archive.description}</p>

        <div className="grid gap-8 md:grid-cols-2">
          {posts.map((post) => (
            <article key={post.id} className="bg-card rounded-lg shadow-lg overflow-hidden">
              {post.thumbnail && (
                <img
                  src={post.thumbnail.url}
                  alt={post.thumbnail.alt}
                  className="w-full h-48 object-cover"
                />
              )}
              <div className="p-6">
                <h2 className="text-2xl font-bold mb-2">
                  <A href={`/post/${post.slug}`}>
                    {post.title}
                  </A>
                </h2>
                <div className="text-sm text-muted-foreground mb-4">
                  {post.date.display}
                </div>
                <p className="text-base mb-4">{post.excerpt}</p>
                <Button href={`/post/${post.slug}`}>Read more</Button>
              </div>
            </article>
          ))}
        </div>
      </div>
    </RootLayout>
  )
} 