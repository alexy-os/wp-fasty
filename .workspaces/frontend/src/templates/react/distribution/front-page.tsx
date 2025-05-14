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
        <button className="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-all disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg:not([class*='size-'])]:size-4 shrink-0 [&_svg]:shrink-0 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive bg-primary text-primary-foreground shadow-xs hover:bg-primary/90 h-9 px-4 py-2 has-[>svg]:px-3">
          <a href="#featured">Explore</a>
        </button>
        <button className="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-all disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg:not([class*='size-'])]:size-4 shrink-0 [&_svg]:shrink-0 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive bg-primary text-primary-foreground shadow-xs hover:bg-primary/90 h-9 px-4 py-2 has-[>svg]:px-3">
          <a href="#about">Learn More</a>
        </button>
      </div>
    </div>
  </section>
  {/* About Section */}
  {page.content && <section id="about" className="w-full py-12 md:py-24 lg:py-32">
    <div className="container mx-auto px-4 md:px-6 lg:px-8">
      <div className="w-full">
        <p className="">{page.content}</p>
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
        {posts.map(post => <div key={post.id} className="bg-card text-card-foreground flex flex-col gap-6 rounded-xl border py-6 shadow-sm">
          <div className="@container/card-header grid-rows-[auto_auto] grid auto-rows-min items-start gap-1.5 px-6 has-[data-slot=card-action]:grid-cols-[1fr_auto] [.border-b]:pb-6">
            {post.categories && <>
              {post.categories.map(category => <p key={category.id} className="">{category.name}</p>)}
            </>}
            <div className="leading-none font-semibold">
              <a href={post.url}>{post.title}</a>
            </div>
          </div>
          {post.thumbnail && <img src={post.thumbnail.url} alt={post.thumbnail.alt} className="" />}
          <div className="px-6">
            {post.excerpt && <p className="">{post.excerpt}</p>}
          </div>
          <div className="flex items-center px-6 [.border-t]:pt-6">
            {post.date && <time dateTime={post.date.formatted} className="">{post.date.display}</time>}
            <button className="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-all disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg:not([class*='size-'])]:size-4 shrink-0 [&_svg]:shrink-0 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive bg-primary text-primary-foreground shadow-xs hover:bg-primary/90 h-9 px-4 py-2 has-[>svg]:px-3">
              <a href={post.url}>Read More</a>
            </button>
          </div>
        </div>)}
      </div>
      <div className="w-full">
        <button className="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-all disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg:not([class*='size-'])]:size-4 shrink-0 [&_svg]:shrink-0 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive bg-primary text-primary-foreground shadow-xs hover:bg-primary/90 h-9 px-4 py-2 has-[>svg]:px-3">
          <a href="/blog">View All Posts</a>
        </button>
      </div>
    </div>
  </section>}
</>;