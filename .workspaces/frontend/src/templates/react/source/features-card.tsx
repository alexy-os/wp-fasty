import { Card, CardHeader, CardTitle, CardContent, CardFooter } from "@uikits/ui8px/core/tailwind/clsx/ui/card";
import { P } from "@uikits/ui8px/core/tailwind/clsx/components/markup";
import { Img } from "@uikits/ui8px/core/tailwind/clsx/components/media";
import { Button } from "@uikits/ui8px/core/tailwind/cva/ui/button";
import { Time } from "@uikits/ui8px/core/tailwind/clsx/components/markup";

import { posts } from "@/context/data";
<>
  {
    posts.map(post => (
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
          <Button asChild variant="link">
            <a href={post.url}>Read More</a>
          </Button>
        </CardFooter>
      </Card>
    ))
  }
</>