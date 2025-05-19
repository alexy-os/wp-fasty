import React from 'react'
import { page } from '@/context/data'
//import { MainLayout } from '@/components/MainLayout'
import { RootLayout } from '../layouts/RootLayout'

import {
  Article,
  ArticleHeader,
  ArticleContent
} from '@/components/ThemeComponent'

export function HomePage() {
  return (
    <RootLayout
      title={page.title}
      description={page.excerpt}
    >
      <Article>
        <ArticleHeader>
          <h1>{page.title}</h1>
          {page.featuredImage && (
            <img
              src={page.featuredImage.url}
              alt={page.featuredImage.alt}
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