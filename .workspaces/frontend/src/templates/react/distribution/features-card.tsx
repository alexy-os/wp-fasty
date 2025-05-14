import { Card, CardHeader, CardTitle, CardContent, CardFooter } from "@uikits/ui8px/core/tailwind/clsx/ui/card";
import { P } from "@uikits/ui8px/core/tailwind/clsx/components/markup";
import { Img } from "@uikits/ui8px/core/tailwind/clsx/components/media";
import { Button } from "@uikits/ui8px/core/tailwind/cva/ui/button";
import { Time } from "@uikits/ui8px/core/tailwind/clsx/components/markup";
import { posts } from "@/context/data";
<>
  {posts.map(post => <div key={post.id} className="bg-card text-card-foreground flex flex-col gap-6 rounded-xl border py-6 shadow-sm">
        <div className="@container/card-header grid-rows-[auto_auto] grid auto-rows-min items-start gap-1.5 px-6 has-[data-slot=card-action]:grid-cols-[1fr_auto] [.border-b]:pb-6">
          {post.categories && <>
              {post.categories.map(category => <P key={category.id}>{category.name}</P>)}
            </>}
          <div className="leading-none font-semibold">
            <a href={post.url}>{post.title}</a>
          </div>
        </div>
        {post.thumbnail && <Img src={post.thumbnail.url} alt={post.thumbnail.alt} />}
        <div className="px-6">
          {post.excerpt && <P>{post.excerpt}</P>}
        </div>
        <div className="flex items-center px-6 [.border-t]:pt-6">
          {post.date && <Time dateTime={post.date.formatted}>{post.date.display}</Time>}
          <Button asChild variant="link">
            <a href={post.url}>Read More</a>
          </Button>
        </div>
      </div>)}
</>;