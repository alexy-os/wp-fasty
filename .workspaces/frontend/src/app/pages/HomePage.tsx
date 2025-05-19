import { page } from '@/context/data'
import { RootLayout } from '../layouts/RootLayout'

export function HomePage() {
  return (
    <RootLayout title={page.title}>
      <div className="prose lg:prose-xl mx-auto">
        <h1 className="text-4xl font-bold mb-6">{page.title}</h1>
        {page.featuredImage && (
          <img
            src={page.featuredImage.url}
            alt={page.featuredImage.alt}
            className="w-full h-auto mb-8 rounded-lg shadow-lg"
          />
        )}
        <div className="content" dangerouslySetInnerHTML={{ __html: page.content }} />
      </div>
    </RootLayout>
  )
} 