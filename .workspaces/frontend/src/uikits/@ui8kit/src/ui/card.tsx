import * as React from "react"
import { cn } from "@/lib/utils";

function Card({ className, ...props }: React.ComponentProps<"div">) {
  return (
    <div
      data-slot="card"
      className={cn(
        "bg-card text-card-foreground flex flex-col gap-6 rounded-md border shadow-sm",
        className
      )}
      {...props}
    />
  )
}

function CardHeader({ className, ...props }: React.ComponentProps<"div">) {
  return (
    <div
      data-slot="card-header"
      className={cn(
        "@container/card-header grid-rows-[auto_auto] grid auto-rows-min items-start gap-1.5 mt-4 px-6 has-[data-slot=card-action]:grid-cols-[1fr_auto] [.border-b]:pb-6",
        className
      )}
      {...props}
    />
  )
}

function CardTitle({ className, ...props }: React.ComponentProps<"h3">) {
  return (
    <h3
      data-slot="card-title"
      className={cn("leading-none font-bold text-xl mb-2", className)}
      {...props}
    />
  )
}

function CardDescription({ className, ...props }: React.ComponentProps<"div">) {
  return (
    <div
      data-slot="card-description"
      className={cn("text-muted-foreground text-sm mb-4", className)}
      {...props}
    />
  )
}

function CardAction({ className, ...props }: React.ComponentProps<"div">) {
  return (
    <div
      data-slot="card-action"
      className={cn(
        "col-start-2 row-span-2 row-start-1 self-start justify-self-end",
        className
      )}
      {...props}
    />
  )
}

function CardMeta({
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

function CardFigure({
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

function CardImage({
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

function CardFigcaption({
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

function CardContent({ className, ...props }: React.ComponentProps<"div">) {
  return (
    <div
      data-slot="card-content"
      className={cn("px-6 py-4", className)}
      {...props}
    />
  )
}

function CardFooter({ className, ...props }: React.ComponentProps<"div">) {
  return (
    <div
      data-slot="card-footer"
      className={cn("flex items-center px-6 [.border-t]:pt-6", className)}
      {...props}
    />
  )
}

export {
  Card,
  CardHeader,
  CardFooter,
  CardTitle,
  CardAction,
  CardDescription,
  CardMeta,
  CardFigure,
  CardImage,
  CardFigcaption,
  CardContent,
}
