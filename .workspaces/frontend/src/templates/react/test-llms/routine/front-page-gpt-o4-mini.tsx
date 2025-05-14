import React from 'react';
import {
  Section,
  Container,
  SectionHeader,
  SectionTitle,
  SectionDescription,
  SectionContent,
  SectionFooter
} from '@uikits/ui8px/core/tailwind/clsx/components/section';
import { P, A, Time } from '@uikits/ui8px/core/tailwind/clsx/components/markup';
import { Img } from '@uikits/ui8px/core/tailwind/clsx/components/media';
import {
  Card,
  CardHeader,
  CardTitle,
  CardContent,
  CardDescription,
  CardFooter,
  CardAction
} from '@uikits/ui8px/core/tailwind/clsx/ui/card';
import { Button } from '@uikits/ui8px/core/tailwind/cva/ui/button';
import { page, posts } from '@/context/data';

const FrontPage: React.FC = () => (
  <>
    {/* Hero Section */}
    <Section id="hero">
      <Container>
        <SectionHeader>
          {page.title && <SectionTitle>{page.title}</SectionTitle>}
          {page.excerpt && <SectionDescription>{page.excerpt}</SectionDescription>}
        </SectionHeader>
        <SectionContent>
          <A href="#featured">
            <Button>Explore</Button>
          </A>
          <A href="#about">
            <Button>Learn More</Button>
          </A>
        </SectionContent>
      </Container>
    </Section>

    {/* About Section */}
    {page && (
      <Section id="about">
        <Container>
          <SectionContent>
            <P>{page.content}</P>
          </SectionContent>
        </Container>
      </Section>
    )}

    {/* Featured Posts */}
    {posts && (
      <Section id="featured">
        <Container>
          <SectionHeader>
            <SectionTitle>Featured</SectionTitle>
            <SectionDescription>Discover our latest articles and updates</SectionDescription>
          </SectionHeader>
          <SectionContent>
            {posts.map(post => (
              <Card key={post.id}>
                <CardAction>
                  {post.thumbnail && (
                    <A href={post.url}>
                      <Img src={post.thumbnail.url} alt={post.thumbnail.alt} />
                    </A>
                  )}
                </CardAction>
                <CardHeader>
                  {post.categories.map(category => (
                    <A key={category.id} href={category.url}>{category.name}</A>
                  ))}
                  <CardTitle>
                    <A href={post.url}>{post.title}</A>
                  </CardTitle>
                </CardHeader>
                <CardContent>
                  {post.excerpt && <CardDescription>{post.excerpt}</CardDescription>}
                </CardContent>
                <CardFooter>
                  {post.date && <Time dateTime={post.date.formatted}>{post.date.display}</Time>}
                  <A href={post.url}>Read More →</A>
                </CardFooter>
              </Card>
            ))}
          </SectionContent>
          <SectionFooter>
            <A href="/blog">View All Posts</A>
          </SectionFooter>
        </Container>
      </Section>
    )}
  </>
);

export default FrontPage; 