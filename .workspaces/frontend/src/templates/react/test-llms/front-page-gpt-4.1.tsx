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
import { Button } from "@uikits/ui8px/core/tailwind/cva/ui/button";
import { Img } from "@uikits/ui8px/core/tailwind/clsx/components/media";
import { P } from "@uikits/ui8px/core/tailwind/clsx/components/markup";
import { A, Time } from "@uikits/ui8px/core/tailwind/clsx/components/markup";
import { posts, page } from "@/context/data";

<>
  <Section id="hero">
    <Container>
      <SectionHeader>
        {page.title && <SectionTitle>{page.title}</SectionTitle>}
        {page.excerpt && <SectionDescription><P>{page.excerpt}</P></SectionDescription>}
      </SectionHeader>
      <SectionContent>
        <A href="#featured"><Button variant="outline">Explore</Button></A>
        <A href="#about"><Button>Learn More</Button></A>
      </SectionContent>
    </Container>
  </Section>
  {page && (
    <Section id="about">
      <Container>
        <SectionContent><P>{page.content}</P></SectionContent>
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
        <SectionContent>
          {posts.map(post => (
            <Card key={post.id}>
              <A href={post.url}>
                {post.thumbnail && (
                  <Img src={post.thumbnail.url} alt={post.thumbnail.alt} />
                )}
              </A>
              <CardHeader>
                {post.categories.map(category => (
                  <A key={category.id} href={category.url}>{category.name}</A>
                ))}
                <CardTitle>
                  <A href={post.url}>{post.title}</A>
                </CardTitle>
              </CardHeader>
              <CardContent>
                {post.excerpt && <P>{post.excerpt}</P>}
              </CardContent>
              <CardFooter>
                {post.date && (
                  <Time dateTime={post.date.formatted}>{post.date.display}</Time>
                )}
                <A href={post.url}>Read More</A>
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