import {
  Section,
  Container,
  SectionHeader,
  SectionTitle,
  SectionDescription,
  SectionContent,
  SectionFooter
} from "@semantic/components/section";
import {
  Article,
  ArticleHeader,
  ArticleTitle,
  ArticleContent,
  ArticleFooter,
  ArticleMeta,
  ArticleTime,
  ArticleImage,
  ArticleTags,
  ArticleTag
} from "@semantic/components/article";
import { LinkButton, A, Link } from "@semantic/ui/link";
import { Grid } from "@semantic/components/section";

const FrontPage = ({ posts, page }: any) => {
  return (
    <>
      {/* Hero Section */}
      <Section id="hero">
        <Container>
          <SectionHeader>
            <SectionTitle>{page.title}</SectionTitle>
            {page.excerpt && (
              <SectionDescription>{page.excerpt}</SectionDescription>
            )}
          </SectionHeader>
          <SectionContent>
            <LinkButton href="#featured">Explore</LinkButton>
            <Link href="#about">Learn More</Link>
          </SectionContent>
        </Container>
      </Section>
      {/* About Section */}
      <section id="about">
        <Container>
          <SectionContent>
            {page.content}
          </SectionContent>
        </Container>
      </section>
      {/* Featured Posts Section */}
      {posts && (
        <Section id="featured">
          <Container>
            <SectionHeader>
              <SectionTitle>Featured</SectionTitle>
              <SectionDescription>Discover our latest articles and updates</SectionDescription>
            </SectionHeader>
            <SectionContent>
              <Grid>
                {posts.map((post: any) => (
                  <Article key={post.id}>
                    {post.thumbnail && (
                      <A href={post.url}>
                        <ArticleImage src={post.thumbnail.url} alt={post.thumbnail.alt} />
                      </A>
                    )}
                    <ArticleHeader>
                      {post.categories && (
                        <ArticleTags>
                          {post.categories.map((category: any) => (
                            <ArticleTag key={category.id}>{category.name}</ArticleTag>
                          ))}
                        </ArticleTags>
                      )}
                      <ArticleTitle>{post.title}</ArticleTitle>
                    </ArticleHeader>
                    {post.excerpt && (
                      <ArticleContent>{post.excerpt}</ArticleContent>
                    )}
                    <ArticleFooter>
                      {post.date && (
                        <ArticleMeta>
                          <ArticleTime dateTime={post.date.formatted}>{post.date.display}</ArticleTime>
                        </ArticleMeta>
                      )}
                      <Link href={post.url}>
                        Read More <span>→</span>
                      </Link>
                    </ArticleFooter>
                  </Article>
                ))}
              </Grid>
            </SectionContent>
            <SectionFooter>
              <LinkButton href="/blog">View All Posts</LinkButton>
            </SectionFooter>
          </Container>
        </Section>
      )}
    </>
  );
};

export default FrontPage; 