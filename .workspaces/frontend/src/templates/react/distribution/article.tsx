import { posts } from "@/context/data";
<>
  {posts.map((post: any) => <article key={post.id} className="bg-card text-card-foreground flex flex-col gap-4 rounded-xl border shadow-sm">
      <header className="flex flex-col gap-2 px-6 pt-6">
        <h2 className="text-2xl font-semibold tracking-tight">{post.title}</h2>
        {post.date && <div className="flex flex-wrap items-center gap-3 text-sm text-muted-foreground">
            <time dateTime={post.date.formatted} className="text-sm text-muted-foreground">{post.date.display}</time>
          </div>}
      </header>
      {post.excerpt && <div className="max-w-none px-6 py-4">
          <p>{post.excerpt}</p>
        </div>}
    </article>)}
</>;