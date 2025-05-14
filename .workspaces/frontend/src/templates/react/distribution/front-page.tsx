import { Section, Container, SectionHeader, SectionTitle, SectionDescription, SectionContent } from "@uikits/ui8px/core/tailwind/clsx/components/section";
import { H1, H2, P, Time } from "@uikits/ui8px/core/tailwind/clsx/components/markup";
import { Card, CardHeader, CardTitle, CardContent, CardFooter } from "@uikits/ui8px/core/tailwind/clsx/ui/card";
import { Img } from "@uikits/ui8px/core/tailwind/clsx/components/media";
import { Button } from "@uikits/ui8px/core/tailwind/cva/ui/button";
import { posts, page } from "@/context/data";
<>
  {/* Hero Section */}
  <section className="w-full py-12 md:py-24 lg:py-32">
    <div className="container mx-auto px-4 md:px-6 lg:px-8">
      <header className="">
        {page.title && <h2 className="">{page.title}</h2>}
        {page.excerpt && <p className="">{page.excerpt}</p>}
      </header>
      <div className="w-full">
        <Button asChild variant="default">
          <a href="#featured">Explore</a>
        </Button>
        <Button asChild variant="secondary">
          <a href="#about">Learn More</a>
        </Button>
      </div>
    </div>
  </section>
  {/* About Section */}
  {page.content && <section id="about" className="w-full py-12 md:py-24 lg:py-32">
      <div className="container mx-auto px-4 md:px-6 lg:px-8">
        <div className="w-full">
          <P>{page.content}</P>
        </div>
      </div>
    </section>}
  {/* Featured Posts Section */}
  {posts && <section id="featured" className="w-full py-12 md:py-24 lg:py-32">
      <div className="container mx-auto px-4 md:px-6 lg:px-8">
        <header className="">
          <h2 className="">Featured</h2>
          <p className="">Discover our latest articles and updates</p>
        </header>
        <div className="w-full">
          {posts.map(post => <Card key={post.id}>
              <CardHeader>
                {post.categories && <>
                    {post.categories.map(category => <P key={category.id}>{category.name}</P>)}
                  </>}
                <CardTitle>
                  <a href={post.url}>{post.title}</a>
                </CardTitle>
              </CardHeader>
              {post.thumbnail && <Img src={post.thumbnail.url} alt={post.thumbnail.alt} />}
              <CardContent>
                {post.excerpt && <P>{post.excerpt}</P>}
              </CardContent>
              <CardFooter>
                {post.date && <Time dateTime={post.date.formatted}>{post.date.display}</Time>}
                <Button asChild variant="link">
                  <a href={post.url}>Read More</a>
                </Button>
              </CardFooter>
            </Card>)}
        </div>
        <div className="w-full">
          <Button asChild variant="default">
            <a href="/blog">View All Posts</a>
          </Button>
        </div>
      </div>
    </section>}
</>;