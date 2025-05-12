// cd .workspaces/frontend && bun run dev
// import { FrontPage } from "@uikits/latte/components/FrontPageTemplate";
// import { pageContext } from "@uikits/latte/data/context";
import {
  Article,
  ArticleHeader,
  ArticleTitle,
  ArticleMeta,
  ArticleTime,
  ArticleAuthor,
  ArticleFigure,
  ArticleImage,
  ArticleFigcaption,
  ArticleContent,
  ArticleBlockquote,
  ArticleFooter,
  ArticleTags,
  ArticleTag,
  ArticleActions
} from "@uikits/ui8px/core/semantic/components/article";
/*import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter, CardAction } from "@uikits/ui8px/core/semantic/ui/card";
import { Button } from "@uikits/ui8px/core/semantic/ui/button";
import { Badge } from "@uikits/ui8px/core/semantic/ui/badge";
import { Table, TableHeader, TableBody, TableRow, TableCell } from "@uikits/ui8px/core/semantic/ui/table";
*/
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter, CardAction } from "@uikits/ui8px/core/source/clsx/ui/card";
import { Button } from "@uikits/ui8px/core/source/cva/ui/button";
import { Badge } from "@uikits/ui8px/core/source/cva/ui/badge";
import { Table, TableHeader, TableBody, TableRow, TableCell } from "@uikits/ui8px/core/source/clsx/ui/table";


function App() {
  return (
    <>
      {/* Standard article */}
      <Article>
        <ArticleHeader>
          <ArticleTitle>Standard article</ArticleTitle>
          <ArticleMeta>
            <ArticleTime dateTime="2023-06-15">15 June 2023</ArticleTime>
            <ArticleAuthor>Author of the article</ArticleAuthor>
          </ArticleMeta>
        </ArticleHeader>
        <ArticleContent>
          <p>Article content...</p>
        </ArticleContent>
        <ArticleFooter>
          <ArticleTags>
            <ArticleTag>Tag 1</ArticleTag>
            <ArticleTag>Tag 2</ArticleTag>
          </ArticleTags>
        </ArticleFooter>
      </Article>

      {/* Article as div */}
      <Article as="div">
        <ArticleHeader>
          <ArticleTitle>Article as div</ArticleTitle>
        </ArticleHeader>
        <ArticleContent>
          <p>Article as div element...</p>
        </ArticleContent>
      </Article>

      {/* Article as list item */}
      <ul>
        <Article as="li">
          <ArticleHeader>
            <ArticleTitle>Article as list item</ArticleTitle>
          </ArticleHeader>
          <ArticleContent>
            <p>Article as list item element...</p>
          </ArticleContent>
        </Article>
      </ul>

      {/* Existing components */}
      <Button variant="destructive" size="sm" asChild>
        <a href="https://www.google.com">Button</a>
      </Button>
      <Badge>Badge</Badge>
      <Card>
        <CardHeader>
          <CardTitle>Card Title</CardTitle>
          <CardDescription>Card Description</CardDescription>
        </CardHeader>
        <CardContent>
          <CardDescription>Card Description</CardDescription>
        </CardContent>
        <CardFooter>
          <CardAction>Card Action</CardAction>
        </CardFooter>
      </Card>
      <Table>
        <TableHeader>
          <TableRow>
            <TableCell>Table Cell</TableCell>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow>
            <TableCell>Table Cell</TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </>
  )
}

export default App;