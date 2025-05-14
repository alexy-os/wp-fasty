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
  Card,
  CardHeader,
  CardTitle,
  CardContent,
  CardFooter
} from "@uikits/ui8px/core/tailwind/clsx/ui/card";

import {
  H1,
  P,
  A,
  Time,
  Figure,
} from "@uikits/ui8px/core/tailwind/clsx/components/markup";

import {
  Img
} from "@uikits/ui8px/core/tailwind/clsx/components/media";

import {
  Button
} from "@uikits/ui8px/core/tailwind/cva/ui/button";

import { pageContext } from "@/context/data";

const { page, posts } = pageContext;

const FrontPage = () => (
  <>
    <Section>
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
            <SectionDescription>
              Discover our latest articles and updates
            </SectionDescription>
          </SectionHeader>

          <SectionContent>
            {posts.map(post => (
              <Card key={post.id}>
                <A href={post.url}>
                  {post.thumbnail && (
                    <Figure>
                      <Img src={post.thumbnail.url} alt={post.thumbnail.alt} />
                    </Figure>
                  )}
                </A>
                <CardHeader>
                  {post.categories && (
                    <div>
                      {post.categories.map(category => (
                        <A key={category.id} href={category.url}>
                          {category.name}
                        </A>
                      ))}
                    </div>
                  )}
                  <CardTitle>
                    <A href={post.url}>
                      {post.title}
                    </A>
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
                      <Time dateTime={post.date.formatted}>
                        {post.date.display}
                      </Time>
                    )}
                  </div>
                  <A href={post.url}>
                    Read More →
                  </A>
                </CardFooter>
              </Card>
            ))}
          </SectionContent>

          <SectionFooter>
            <A href="/blog">
              <Button>View All Posts</Button>
            </A>
          </SectionFooter>
        </Container>
      </Section>
    )}
  </>
);

export default FrontPage; 