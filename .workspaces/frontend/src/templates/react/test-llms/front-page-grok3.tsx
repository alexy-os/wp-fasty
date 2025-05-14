import {
  Section,
  Container,
  SectionHeader,
  SectionTitle,
  SectionDescription,
  SectionContent,
  SectionFooter
} from "@uikits/ui8px/core/tailwind/clsx/components/section";

import {
  Article,
  ArticleHeader,
  ArticleTitle,
  ArticleMeta,
  ArticleTime,
  ArticleContent,
  ArticleFigure,
  ArticleImage,
  ArticleFooter,
  ArticleTags,
  ArticleTag
} from "@uikits/ui8px/core/tailwind/clsx/components/article";

import { A } from "@uikits/ui8px/core/tailwind/clsx/components/markup";
import {
  Button
} from "@uikits/ui8px/core/tailwind/cva/ui/button";

import { page, posts } from "@/context/data";

<>
  <Section id="hero">
    <Container>
      <SectionHeader>
        {page.title && <SectionTitle>{page.title}</SectionTitle>}
        {page.excerpt && <SectionDescription>{page.excerpt}</SectionDescription>}
      </SectionHeader>
      <SectionFooter>
        <A href="#featured">
          <Button>Explore</Button>
        </A>
        <A href="#about">
          <Button>Learn More</Button>
        </A>
      </SectionFooter>
    </Container>
  </Section>

  {page &&
    <Section id="about">
      <Container>
        <SectionContent>
          {page.content}
        </SectionContent>
      </Container>
    </Section>
  }

  {posts &&
    <Section id="featured">
      <Container>
        <SectionHeader>
          <SectionTitle>Featured</SectionTitle>
          <SectionDescription>Discover our latest articles and updates</SectionDescription>
        </SectionHeader>
        <SectionContent>
          {posts.map(post => (
            <Article key={post.id}>
              <ArticleHeader>
                {post.categories && (
                  <ArticleTags>
                    {post.categories.map(category => (
                      <ArticleTag key={category.id}><a href={category.url}>{category.name}</a></ArticleTag>
                    ))}
                  </ArticleTags>
                )}
                <ArticleTitle>
                  <A href={post.url}>{post.title}</A>
                </ArticleTitle>
              </ArticleHeader>
              {post.thumbnail && (
                <ArticleFigure>
                  <A href={post.url}>
                    <ArticleImage src={post.thumbnail.url} alt={post.thumbnail.alt} />
                  </A>
                </ArticleFigure>
              )}
              {post.excerpt && <ArticleContent>{post.excerpt}</ArticleContent>}
              <ArticleFooter>
                {post.date && <ArticleMeta><ArticleTime dateTime={post.date.formatted}>{post.date.display}</ArticleTime></ArticleMeta>}
                <A href={post.url}>
                  <Button>Read More →</Button>
                </A>
              </ArticleFooter>
            </Article>
          ))}
        </SectionContent>
        <SectionFooter>
          <A href="/blog">
            <Button>View All Posts</Button>
          </A>
        </SectionFooter>
      </Container>
    </Section>
  }
</> 