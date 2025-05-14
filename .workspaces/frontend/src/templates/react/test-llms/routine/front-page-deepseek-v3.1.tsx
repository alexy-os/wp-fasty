import {
  Article,
  ArticleHeader,
  ArticleTitle,
  ArticleMeta,
  ArticleTime,
  ArticleContent,
  ArticleFooter,
  ArticleTags,
  ArticleTag,
  ArticleActions,
} from "@uikits/ui8px/core/tailwind/clsx/components/article";

import {
  Section,
  Container,
  SectionHeader,
  SectionTitle,
  SectionDescription,
  SectionContent,
} from "@uikits/ui8px/core/tailwind/clsx/components/section";

import { A } from "@uikits/ui8px/core/tailwind/clsx/components/markup";

import { Button } from "@uikits/ui8px/core/tailwind/cva/ui/button";

import { page, posts } from "@/context/data";

export const FrontPage = () => {
  return (
    <>
      <Section id="hero">
        <Container>
          <SectionContent>
            {page.title && (
              <SectionTitle>{page.title}</SectionTitle>
            )}
            {page.excerpt && (
              <SectionDescription>
                {page.excerpt}
              </SectionDescription>
            )}
            <ArticleActions>
              <A href="#featured">
                <Button>Explore</Button>
              </A>
              <A href="#about">
                <Button>Learn More</Button>
              </A>
            </ArticleActions>
          </SectionContent>
        </Container>
      </Section>

      {page && (
        <Section id="about">
          <Container>
            <SectionContent>
              {page.content}
            </SectionContent>
          </Container>
        </Section>
      )}

      {posts && (
        <Section id="featured">
          <Container>
            <SectionHeader>
              <SectionTitle>Featured</SectionTitle>
              <SectionDescription>
                Discover our latest articles and updates
              </SectionDescription>
            </SectionHeader>
            <SectionContent>
              {posts.map((post) => (
                <Article key={post.id}>
                  <ArticleHeader>
                    {post.categories && (
                      <ArticleTags>
                        {post.categories.map((category) => (
                          <ArticleTag key={category.id}>
                            {category.name}
                          </ArticleTag>
                        ))}
                      </ArticleTags>
                    )}
                    <ArticleTitle>
                      <a href={post.url}>{post.title}</a>
                    </ArticleTitle>
                  </ArticleHeader>
                  {post.excerpt && (
                    <ArticleContent>
                      <p>{post.excerpt}</p>
                    </ArticleContent>
                  )}
                  <ArticleFooter>
                    {post.date && (
                      <ArticleMeta>
                        <ArticleTime dateTime={post.date.formatted}>
                          {post.date.display}
                        </ArticleTime>
                      </ArticleMeta>
                    )}
                    <A href={post.url}>
                      Read More
                      <span>→</span>
                    </A>
                  </ArticleFooter>
                </Article>
              ))}
            </SectionContent>
            <ArticleActions>
              <A href="/blog">
                <Button>View All Posts</Button>
              </A>
            </ArticleActions>
          </Container>
        </Section>
      )}
    </>
  );
};