import {
  Section,
  Container,
  SectionHeader,
  SectionTitle,
  SectionDescription,
  SectionContent
} from "@uikits/ui8px/core/tailwind/clsx/components/section";
import { H1, H2, P, Time } from "@uikits/ui8px/core/tailwind/clsx/components/markup";
import { Card, CardHeader, CardTitle, CardContent, CardFooter } from "@uikits/ui8px/core/tailwind/clsx/ui/card";
import { Img } from "@uikits/ui8px/core/tailwind/clsx/components/media";
import { Button } from "@uikits/ui8px/core/tailwind/cva/ui/button";
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
        <Button>
          <a href="#featured">Explore</a>
        </Button>
        <Button>
          <a href="#about">Learn More</a>
        </Button>
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
                <Button>
                  <a href={post.url}>Read More</a>
                </Button>
              </CardFooter>
            </Card>
          ))}
        </SectionContent>
        <SectionContent>
          <Button>
            <a href="/blog">View All Posts</a>
          </Button>
        </SectionContent>
      </Container>
    </Section>
  )}
</> 