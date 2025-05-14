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
import { Img } from "@uikits/ui8px/core/tailwind/clsx/components/media";
import { A, Time } from "@uikits/ui8px/core/tailwind/clsx/components/markup";
import { posts, page } from "@/context/data";

<>
  <Section id="hero">
    <Container>
      <SectionHeader>
        {page.title && <SectionTitle>{page.title}</SectionTitle>}
        {page.excerpt && <SectionDescription>{page.excerpt}</SectionDescription>}
      </SectionHeader>
      <SectionContent>
        <A href="#featured">Explore</A>
        <A href="#about">Learn More</A>
      </SectionContent>
    </Container>
  </Section>
  {page && (
    <Section id="about">
      <Container>
        <SectionContent>{page.content}</SectionContent>
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
              {post.excerpt && (
                <CardContent>
                  {post.excerpt}
                </CardContent>
              )}
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