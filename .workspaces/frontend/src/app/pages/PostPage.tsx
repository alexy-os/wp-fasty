import { posts } from '@/context/data'
import { RootLayout } from '../layouts/RootLayout'

export type PostPageProps = {
  slug: string
}

export function PostPage({ slug }: PostPageProps) {
  const post = posts.find(p => p.slug === slug)

  if (!post) {
    return (
      <RootLayout title="Post Not Found">
        <div className="max-w-4xl mx-auto text-center py-12">
          <h1 className="text-4xl font-bold mb-4">404 - Post Not Found</h1>
          <p className="text-lg mb-8">The post you're looking for doesn't exist.</p>
          <a href="/" className="text-primary hover:underline">Return to homepage</a>
        </div>
      </RootLayout>
    )
  }

  return (
    <RootLayout title={post.title}>
      <article className="prose lg:prose-xl mx-auto">
        <h1 className="text-4xl font-bold mb-6">{post.title}</h1>
        <div className="text-sm text-muted-foreground mb-8">
          Published on {post.date.display}
        </div>

        {post.featuredImage && (
          <img
            src={post.featuredImage.url}
            alt={post.featuredImage.alt}
            className="w-full h-auto mb-8 rounded-lg shadow-lg"
          />
        )}

        <div className="content" dangerouslySetInnerHTML={{ __html: post.content }} />

        {post.categories.length > 0 && (
          <div className="mt-8 pt-8 border-t">
            <h2 className="text-xl font-semibold mb-4">Categories</h2>
            <div className="flex gap-2">
              {post.categories.map(category => (
                <a
                  key={category.id}
                  href={category.url}
                  className="px-3 py-1 bg-muted rounded-full text-sm hover:bg-muted/80"
                >
                  {category.name}
                </a>
              ))}
            </div>
          </div>
        )}
      </article>
    </RootLayout>
  )
} 