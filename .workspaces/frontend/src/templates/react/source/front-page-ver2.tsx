import {
  Section,
  Container,
  Header as SectionHeader,
  Title as SectionTitle,
  Description as SectionDescription,
  Content as SectionContent
} from "@uikits/ui8px/core/tailwind/clsx/components/section";

import {
  H1,
  H2,
  H3,
  P,
  A,
  Time
} from "@uikits/ui8px/core/tailwind/clsx/components/markup";

import {
  Card,
  CardHeader,
  CardTitle,
  CardContent,
  CardFooter
} from "@uikits/ui8px/core/tailwind/clsx/ui/card";

import {
  Article,
  ArticleImage,
  ArticleFigure
} from "@uikits/ui8px/core/tailwind/clsx/components/article";

import {
  Grid
} from "@uikits/ui8px/core/tailwind/clsx/components/section";

import {
  Button
} from "@uikits/ui8px/core/tailwind/cva/ui/button";

import { page, posts } from "@/context/data";

<>
  {/* Navbar would be imported as a separate component */}

  <Section id="hero">
    <Container>
      <SectionContent>
        {page.title && (
          <H1>{page.title}</H1>
        )}
        {page.excerpt && (
          <P>{page.excerpt}</P>
        )}
        <div>
          <A href="#featured">
            <Button>Explore</Button>
          </A>
          <A href="#about">
            <Button>Learn More</Button>
          </A>
        </div>
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
          <SectionDescription>Discover our latest articles and updates</SectionDescription>
        </SectionHeader>

        <Grid>
          {posts.map(post => (
            <Article key={post.id}>
              <A href={post.url}>
                {post.thumbnail && (
                  <ArticleFigure>
                    <ArticleImage src={post.thumbnail.url} alt={post.thumbnail.alt} />
                  </ArticleFigure>
                )}
              </A>

              <CardHeader>
                {post.categories && (
                  <div>
                    {post.categories.map(category => (
                      <A key={category.id} href={category.url}>{category.name}</A>
                    ))}
                  </div>
                )}

                <CardTitle>
                  <A href={post.url}>{post.title}</A>
                </CardTitle>
              </CardHeader>

              <CardContent>
                {post.excerpt && (
                  <P>{post.excerpt}</P>
                )}
              </CardContent>

              <CardFooter>
                <div>
                  {post.date && (
                    <Time dateTime={post.date.formatted}>{post.date.display}</Time>
                  )}
                </div>
                <A href={post.url}>
                  Read More →
                </A>
              </CardFooter>
            </Article>
          ))}
        </Grid>

        <div>
          <A href="/blog">
            <Button>View All Posts</Button>
          </A>
        </div>
      </Container>
    </Section>
  )}
</> 