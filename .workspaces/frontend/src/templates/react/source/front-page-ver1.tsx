import React from "react";
import {
  Section,
  Container,
  Grid,
  Header as SectionHeader,
  Title as SectionTitle,
  Description as SectionDescription,
  Content as SectionContent
} from "@uikits/ui8px/core/tailwind/clsx/components/section";

import {
  Card,
  CardHeader,
  CardContent,
  CardFooter,
  CardTitle
} from "@uikits/ui8px/core/tailwind/clsx/ui/card";

import {
  H1,
  P,
  A,
  Figure,
  Time
} from "@uikits/ui8px/core/tailwind/clsx/components/markup";

import { Img } from "@uikits/ui8px/core/tailwind/clsx/components/media";

import { Button } from "@uikits/ui8px/core/tailwind/cva/ui/button";

import { page, posts } from "@/context/data";

const FrontPage: React.FC = () => {
  return (
    <>
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
                <Button variant="secondary">Learn More</Button>
              </A>
            </div>
          </SectionContent>
        </Container>
      </Section>

      <Section id="about">
        <Container>
          <SectionContent>
            <div dangerouslySetInnerHTML={{ __html: page.content }} />
          </SectionContent>
        </Container>
      </Section>

      <Section id="featured">
        <Container>
          <SectionHeader>
            <SectionTitle>Featured</SectionTitle>
            <SectionDescription>Discover our latest articles and updates</SectionDescription>
          </SectionHeader>
          <Grid>
            {posts.map((post) => (
              <Card key={post.id}>
                <A href={post.url}>
                  {post.thumbnail && (
                    <Figure>
                      <Img src={post.thumbnail.url} alt={post.thumbnail.alt} />
                    </Figure>
                  )}
                </A>
                <CardHeader>
                  {post.categories && post.categories.length > 0 && (
                    <div>
                      {post.categories.map((category) => (
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
                    Read More
                    <span>→</span>
                  </A>
                </CardFooter>
              </Card>
            ))}
          </Grid>
          <div>
            <A href="/blog">
              <Button>View All Posts</Button>
            </A>
          </div>
        </Container>
      </Section>
    </>
  );
};

export default FrontPage; 