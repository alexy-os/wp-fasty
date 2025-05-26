import * as React from "react"
import { cn } from "@/lib/utils"

type ElementType = "article" | "div" | "li" | "section";

// Helper function for determining styles that the parser will be able to process
function ArticleBase({
  className,
  ...props
}: React.ComponentProps<"article">) {
  return (
    <article
      data-slot="article"
      className={cn(
        "bg-card text-card-foreground flex flex-col gap-6 rounded-md border shadow-sm",
        className
      )}
      {...props}
    />
  );
}

// Main component with polymorphism support
type BaseProps<T extends ElementType = "article"> = {
  as?: T;
  className?: string;
  children: React.ReactNode;
} & React.ComponentPropsWithoutRef<T>;

function Article<T extends ElementType = "article">({
  as,
  className,
  children,
  ...props
}: BaseProps<T>) {
  const Component = as || "article";

  if (Component === "article") {
    return (
      <ArticleBase className={className} {...props}>
        {children}
      </ArticleBase>
    );
  }

  return React.createElement(
    Component,
    {
      "data-slot": "article",
      className: cn("article", className),
      ...props
    },
    children
  );
}

function ArticleHeader({
  className,
  ...props
}: React.ComponentProps<"header">) {
  return (
    <header
      data-slot="article-header"
      className={cn(
        "flex flex-col gap-2 px-6 mt-4",
        className
      )}
      {...props}
    />
  );
}

function ArticleTitle({
  className,
  ...props
}: React.ComponentProps<"h3">) {
  return (
    <h3
      data-slot="article-title"
      className={cn(
        "text-2xl font-bold mb-4",
        className
      )}
      {...props}
    />
  );
}

function ArticleMeta({
  className,
  ...props
}: React.ComponentProps<"div">) {
  return (
    <div
      data-slot="article-meta"
      className={cn(
        "flex flex-wrap items-center gap-3 text-sm text-muted-foreground",
        className
      )}
      {...props}
    />
  );
}

function ArticleTime({
  className,
  ...props
}: React.ComponentProps<"time">) {
  return (
    <time
      data-slot="article-time"
      className={cn(
        "text-sm text-muted-foreground",
        className
      )}
      {...props}
    />
  );
}

function ArticleAuthor({
  className,
  ...props
}: React.ComponentProps<"address">) {
  return (
    <address
      data-slot="article-author"
      className={cn(
        "text-sm not-italic",
        className
      )}
      {...props}
    />
  );
}

function ArticleFigure({
  className,
  ...props
}: React.ComponentProps<"figure">) {
  return (
    <figure
      data-slot="article-figure"
      className={cn(
        "overflow-hidden",
        className
      )}
      {...props}
    />
  );
}

function ArticleImage({
  className,
  ...props
}: React.ComponentProps<"img">) {
  return (
    <img
      data-slot="article-image"
      className={cn(
        "aspect-video w-full object-cover rounded-t-md",
        className
      )}
      {...props}
    />
  );
}

function ArticleFigcaption({
  className,
  ...props
}: React.ComponentProps<"figcaption">) {
  return (
    <figcaption
      data-slot="article-figcaption"
      className={cn(
        "mt-2 text-center text-sm text-muted-foreground",
        className
      )}
      {...props}
    />
  );
}

function ArticleContent({
  className,
  ...props
}: React.ComponentProps<"div">) {
  return (
    <div
      data-slot="article-content"
      className={cn(
        "max-w-none px-6 py-4",
        className
      )}
      {...props}
    />
  );
}

function ArticleBlockquote({
  className,
  ...props
}: React.ComponentProps<"blockquote">) {
  return (
    <blockquote
      data-slot="article-blockquote"
      className={cn(
        "border-l-4 border-muted pl-4 italic",
        className
      )}
      {...props}
    />
  );
}

function ArticleFooter({
  className,
  ...props
}: React.ComponentProps<"footer">) {
  return (
    <footer
      data-slot="article-footer"
      className={cn(
        "flex items-center justify-between px-6 py-4",
        className
      )}
      {...props}
    />
  );
}

function ArticleTags({
  className,
  ...props
}: React.ComponentProps<"ul">) {
  return (
    <ul
      data-slot="article-tags"
      className={cn(
        "flex flex-wrap gap-2",
        className
      )}
      {...props}
    />
  );
}

function ArticleTag({
  className,
  ...props
}: React.ComponentProps<"li">) {
  return (
    <li
      data-slot="article-tag"
      className={cn(
        "inline-flex items-center rounded-full border bg-muted px-2.5 py-0.5 text-xs font-semibold",
        className
      )}
      {...props}
    />
  );
}

function ArticleActions({
  className,
  ...props
}: React.ComponentProps<"div">) {
  return (
    <div
      data-slot="article-actions"
      className={cn(
        "flex items-center gap-2",
        className
      )}
      {...props}
    />
  );
}

Article.displayName = "Article"

export {
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
};
