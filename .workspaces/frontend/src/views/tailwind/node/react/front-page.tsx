const FrontPage = ({
  posts,
  page
}: any) => {
  return <>
    {/* Hero Section */}
    <section id="hero" className="w-full py-12 md:py-24 lg:py-32">
      <div className="container mx-auto px-4 md:px-6 lg:px-8">
        <header className="py-8">
          <h2 className="text-2xl font-bold mb-4">{page.title}</h2>
          {page.excerpt && <div className="text-secondary-foreground mb-4">{page.excerpt}</div>}
        </header>
        <div className="w-full">
          <a href="#featured" className="button inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-all disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg:not([class*='size-'])]:size-4 shrink-0 [&_svg]:shrink-0 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive bg-primary text-primary-foreground shadow-xs hover:bg-primary/90 h-9 px-4 py-2 has-[>svg]:px-3" type="button">Explore</a>
          <a href="#about" className="link-button inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-all disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg:not([class*='size-'])]:size-4 shrink-0 [&_svg]:shrink-0 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive text-primary underline-offset-4 hover:underline h-9 px-4 py-2 has-[>svg]:px-3" type="button">Learn More</a>
        </div>
      </div>
    </section>
    {/* About Section */}
    <section id="about">
      <div className="container mx-auto px-4 md:px-6 lg:px-8">
        <div className="w-full">
          {page.content}
        </div>
      </div>
    </section>
    {/* Featured Posts Section */}
    {posts && <section id="featured" className="w-full py-12 md:py-24 lg:py-32">
      <div className="container mx-auto px-4 md:px-6 lg:px-8">
        <header className="py-8">
          <h2 className="text-2xl font-bold mb-4">Featured</h2>
          <div className="text-secondary-foreground mb-4">Discover our latest articles and updates</div>
        </header>
        <div className="w-full">
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {posts.map((post: any) => <article key={post.id} className="bg-card text-card-foreground flex flex-col gap-4 rounded-md border shadow-sm">
              {post.thumbnail && <a href={post.url}>
                <img src={post.thumbnail.url} alt={post.thumbnail.alt} className="aspect-video w-full object-cover rounded-t-xl" />
              </a>}
              <header className="flex flex-col gap-2 px-6 pt-6">
                {post.categories && <ul className="flex flex-wrap gap-2">
                  {post.categories.map((category: any) => <li key={category.id} className="inline-flex items-center rounded-full border bg-muted px-2.5 py-0.5 text-xs font-semibold">{category.name}</li>)}
                </ul>}
                <h2 className="text-2xl font-semibold tracking-tight">{post.title}</h2>
              </header>
              {post.excerpt && <div className="max-w-none px-6 py-4">{post.excerpt}</div>}
              <footer className="flex items-center justify-between border-t px-6 py-4">
                {post.date && <div className="flex flex-wrap items-center gap-3 text-sm text-muted-foreground">
                  <time dateTime={post.date.formatted} className="text-sm text-muted-foreground">{post.date.display}</time>
                </div>}
                <a href={post.url} className="link-button inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-all disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg:not([class*='size-'])]:size-4 shrink-0 [&_svg]:shrink-0 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive text-primary underline-offset-4 hover:underline h-9 px-4 py-2 has-[>svg]:px-3" type="button">
                  Read More <span>→</span>
                </a>
              </footer>
            </article>)}
          </div>
        </div>
        <footer className="py-8">
          <a href="/blog" className="button inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-all disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg:not([class*='size-'])]:size-4 shrink-0 [&_svg]:shrink-0 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive bg-primary text-primary-foreground shadow-xs hover:bg-primary/90 h-9 px-4 py-2 has-[>svg]:px-3" type="button">View All Posts</a>
        </footer>
      </div>
    </section>}
  </>;
};
export default FrontPage;