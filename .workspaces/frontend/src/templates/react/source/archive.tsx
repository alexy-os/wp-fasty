import {
  Section,
  Container,
  SectionHeader,
  SectionTitle,
  SectionDescription,
  SectionContent
} from "@uikits/ui8px/core/tailwind/clsx/components/section";
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
} from "@uikits/ui8px/core/tailwind/clsx/components/article";
/*import {
  Pagination,
  PaginationContent,
  PaginationLink,
  PaginationItem
} from "@uikits/ui8px/core/tailwind/clsx/ui/pagination";*/
import { LinkButton } from "@uikits/ui8px/core/tailwind/cva/ui/link";

const ArchivePage = ({ posts, archive }: any) => (
  <Section id="archive">
    <SectionHeader>
      <Container>
        <SectionTitle>{archive.title}</SectionTitle>
        {archive.description && (
          <SectionDescription>{archive.description}</SectionDescription>
        )}
      </Container>
    </SectionHeader>
    <SectionContent>
      <Container>
        {posts && (
          <>
            {posts.map((post: any) => (
              <Article key={post.id}>
                {post.thumbnail && (
                  <LinkButton href={post.url}>
                    <ArticleImage src={post.thumbnail.url} alt={post.thumbnail.alt} />
                  </LinkButton>
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
                  <LinkButton href={post.url}>
                    Read More <span>→</span>
                  </LinkButton>
                </ArticleFooter>
              </Article>
            ))}
            {/* Pagination block: pass pagination as prop or context if available */}
            {/*
            {pagination && (
              <Pagination>
                <PaginationContent>
                  {pagination.prev_url && (
                    <PaginationItem>
                      <PaginationLink href={pagination.prev_url} size="default">← Previous</PaginationLink>
                    </PaginationItem>
                  )}
                  {pagination.pages && pagination.pages.map((page: any) => (
                    <PaginationItem key={page.number}>
                      {page.is_current ? (
                        <span>{page.number}</span>
                      ) : (
                        <PaginationLink href={page.url} size="default">{page.number}</PaginationLink>
                      )}
                    </PaginationItem>
                  ))}
                  {pagination.next_url && (
                    <PaginationItem>
                      <PaginationLink href={pagination.next_url} size="default">Next →</PaginationLink>
                    </PaginationItem>
                  )}
                </PaginationContent>
              </Pagination>
            )}
            */}
          </>
        )}
      </Container>
    </SectionContent>
  </Section>
);

export default ArchivePage; 