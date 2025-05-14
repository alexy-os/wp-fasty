
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
  <section className="w-full py-12 md:py-24 lg:py-32" id="hero">
    <div className="container mx-auto px-4 md:px-6 lg:px-8">
      <header className="">
        {page.title && <h2 className="">{page.title}</h2>}
        {page.excerpt && <p className="">{page.excerpt}</p>}
      </header>
      <div className="w-full">
        <A href="#featured">Explore</A>
        <A href="#about">Learn More</A>
      </div>
    </div>
  </section>
  {page && (
    <section className="w-full py-12 md:py-24 lg:py-32" id="about">
      <div className="container mx-auto px-4 md:px-6 lg:px-8">
        <div className="w-full">{page.content}</div>
      </div>
    </section>
  )}
  {posts && (
    <section className="w-full py-12 md:py-24 lg:py-32" id="featured">
      <div className="container mx-auto px-4 md:px-6 lg:px-8">
        <header className="">
          <h2 className="">Featured</h2>
          <p className="">Discover our latest articles and updates</p>
        </header>
        <div className="w-full">
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
        </div>
        <footer className="">
          <A href="/blog">View All Posts</A>
        </footer>
      </div>
    </section>
  )}
</> 