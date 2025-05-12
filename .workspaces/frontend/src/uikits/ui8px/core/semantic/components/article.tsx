import * as React from "react";
import { cn } from "@ui8px/lib/utils";

type ElementType = "article" | "div" | "li" | "section";

// Helper function for determining styles that the parser will be able to process
function ArticleBase({
  className,
  ...props
}: React.ComponentProps<"article">) {
  return (
    <article
      data-slot="article"
      className={cn("article",

      className
      )}
      {...props} />);


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
      </ArticleBase>);

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
      className={cn("article-header",

      className
      )}
      {...props} />);


}

function ArticleTitle({
  className,
  ...props
}: React.ComponentProps<"h2">) {
  return (
    <h2
      data-slot="article-title"
      className={cn("article-title",

      className
      )}
      {...props} />);


}

function ArticleMeta({
  className,
  ...props
}: React.ComponentProps<"div">) {
  return (
    <div
      data-slot="article-meta"
      className={cn("article-meta",

      className
      )}
      {...props} />);


}

function ArticleTime({
  className,
  ...props
}: React.ComponentProps<"time">) {
  return (
    <time
      data-slot="article-time"
      className={cn("article-time",

      className
      )}
      {...props} />);


}

function ArticleAuthor({
  className,
  ...props
}: React.ComponentProps<"address">) {
  return (
    <address
      data-slot="article-author"
      className={cn("article-author",

      className
      )}
      {...props} />);


}

function ArticleFigure({
  className,
  ...props
}: React.ComponentProps<"figure">) {
  return (
    <figure
      data-slot="article-figure"
      className={cn("article-figure",

      className
      )}
      {...props} />);


}

function ArticleImage({
  className,
  alt = "",
  ...props
}: React.ComponentProps<"img">) {
  return (
    <img
      data-slot="article-image"
      className={cn("article-image",

      className
      )}
      alt={alt}
      {...props} />);


}

function ArticleFigcaption({
  className,
  ...props
}: React.ComponentProps<"figcaption">) {
  return (
    <figcaption
      data-slot="article-figcaption"
      className={cn("article-figcaption",

      className
      )}
      {...props} />);


}

function ArticleContent({
  className,
  ...props
}: React.ComponentProps<"div">) {
  return (
    <div
      data-slot="article-content"
      className={cn("article-content",

      className
      )}
      {...props} />);


}

function ArticleBlockquote({
  className,
  ...props
}: React.ComponentProps<"blockquote">) {
  return (
    <blockquote
      data-slot="article-blockquote"
      className={cn("article-blockquote",

      className
      )}
      {...props} />);


}

function ArticleFooter({
  className,
  ...props
}: React.ComponentProps<"footer">) {
  return (
    <footer
      data-slot="article-footer"
      className={cn("article-footer",

      className
      )}
      {...props} />);


}

function ArticleTags({
  className,
  ...props
}: React.ComponentProps<"ul">) {
  return (
    <ul
      data-slot="article-tags"
      className={cn("article-tags",

      className
      )}
      {...props} />);


}

function ArticleTag({
  className,
  ...props
}: React.ComponentProps<"li">) {
  return (
    <li
      data-slot="article-tag"
      className={cn("article-tag",

      className
      )}
      {...props} />);


}

function ArticleActions({
  className,
  ...props
}: React.ComponentProps<"div">) {
  return (
    <div
      data-slot="article-actions"
      className={cn("article-actions",

      className
      )}
      {...props} />);


}

Article.displayName = "Article";

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
  ArticleActions };