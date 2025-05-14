import {
  Section,
  Container,
  SectionHeader,
  SectionTitle,
  SectionDescription,
  SectionContent
} from "@uikits/ui8px/core/tailwind/clsx/components/section";
import { P, Time } from "@uikits/ui8px/core/tailwind/clsx/components/markup";
import { Card, CardHeader, CardTitle, CardContent, CardFooter } from "@uikits/ui8px/core/tailwind/clsx/ui/card";
import { Img } from "@uikits/ui8px/core/tailwind/clsx/components/media";
import { Link, A, LinkButton } from "@uikits/ui8px/core/tailwind/cva/ui/link";
import { posts, page } from "@/context/data";

<>
  {/* Hero Section */}
  <Section>
    <Container>
      <SectionHeader>
        {page.title && <SectionTitle>{page.title}</SectionTitle>}
        {page.excerpt && <SectionDescription>{page.excerpt}</SectionDescription>}
      </SectionHeader>
      <SectionContent>
        <LinkButton href="#featured">Explore</LinkButton>
        <Link href="#about">Learn More</Link>
      </SectionContent>
    </Container>
  </Section>

  {/* About Section */}
  {page.content && (
    <Section id="about">
      <Container>
        <SectionContent>
          <P>{page.content}</P>
        </SectionContent>
      </Container>
    </Section>
  )}

  {/* Featured Posts Section */}
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
              <CardHeader>
                {post.categories && (
                  <>
                    {post.categories.map(category => (
                      <P key={category.id}>{category.name}</P>
                    ))}
                  </>
                )}
                <CardTitle>
                  <a href={post.url}>{post.title}</a>
                </CardTitle>
              </CardHeader>
              {post.thumbnail && (
                <Img src={post.thumbnail.url} alt={post.thumbnail.alt} />
              )}
              <CardContent>
                {post.excerpt && <P>{post.excerpt}</P>}
              </CardContent>
              <CardFooter>
                {post.date && (
                  <Time dateTime={post.date.formatted}>{post.date.display}</Time>
                )}
                <A href={post.url} target="_blank" rel="noopener noreferrer">Read More</A>
              </CardFooter>
            </Card>
          ))}
        </SectionContent>
        <SectionContent>
          <Link href="/blog">View All Posts</Link>
        </SectionContent>
      </Container>
    </Section>
  )}
</> 