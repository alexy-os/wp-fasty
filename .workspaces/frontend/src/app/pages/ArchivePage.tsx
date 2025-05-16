import { posts, archive } from '@/context/data'
import { RootLayout } from '../layouts/RootLayout'

export function ArchivePage() {
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
                  <a href={`/post/${post.slug}`} className="hover:text-primary">
                    {post.title}
                  </a>
                </h2>
                <div className="text-sm text-muted-foreground mb-4">
                  {post.date.display}
                </div>
                <p className="text-base mb-4">{post.excerpt}</p>
                <a
                  href={`/post/${post.slug}`}
                  className="inline-block bg-primary text-white px-4 py-2 rounded hover:bg-primary/90"
                >
                  Read more
                </a>
              </div>
            </article>
          ))}
        </div>
      </div>
    </RootLayout>
  )
} 