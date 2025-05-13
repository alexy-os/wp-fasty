import * as React from "react"
import { cn } from "@ui8px/lib/utils";

function Section({ className, ...props }: React.ComponentProps<"section">) {
  return (
    <section
      data-slot="section"
      className={cn("w-full py-12 md:py-24 lg:py-32", className)}
      {...props}
    />
  )
}

Section.displayName = "Section"

function Container({ className, ...props }: React.ComponentProps<"div">) {
  return (
    <div
      data-slot="container"
      className={cn("container mx-auto px-4 md:px-6 lg:px-8", className)}
      {...props}
    />
  )
}

Container.displayName = "Container"

function FullWidth({ className, ...props }: React.ComponentProps<"div">) {
  return (
    <div
      data-slot="full-width"
      className={cn("w-full", className)}
      {...props}
    />
  )
}

FullWidth.displayName = "FullWidth"

function Header({ className, ...props }: React.ComponentProps<"header">) {
  return (
    <header
      data-slot="section-header"
      className={cn(className)}
      {...props}
    />
  )
}

Header.displayName = "Header"

function Title({ className, ...props }: React.ComponentProps<"h2">) {
  return (
    <h2
      data-slot="section-title"
      className={cn(className)}
      {...props}
    />
  )
}

Title.displayName = "Title"

function Description({ className, ...props }: React.ComponentProps<"p">) {
  return (
    <p
      data-slot="section-description"
      className={cn(className)}
      {...props}
    />
  )
}

Description.displayName = "Description"

function Footer({ className, ...props }: React.ComponentProps<"footer">) {
  return (
    <footer
      data-slot="section-footer"
      className={cn(className)}
      {...props}
    />
  )
}

Footer.displayName = "Footer"

function Content({ className, ...props }: React.ComponentProps<"div">) {
  return (
    <div
      data-slot="section-content"
      className={cn("w-full", className)}
      {...props}
    />
  )
}

Content.displayName = "Content"

function Row({ className, ...props }: React.ComponentProps<"div">) {
  return (
    <div
      data-slot="row"
      className={cn("flex flex-wrap -mx-4", className)}
      {...props}
    />
  )
}

Row.displayName = "Row"

function Col({ className, ...props }: React.ComponentProps<"div">) {
  return (
    <div
      data-slot="col"
      className={cn("w-full px-4", className)}
      {...props}
    />
  )
}

Col.displayName = "Col"

function Grid({ className, ...props }: React.ComponentProps<"div">) {
  return (
    <div
      data-slot="grid"
      className={cn("grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4", className)}
      {...props}
    />
  )
}

Grid.displayName = "Grid"

export { Section, Container, FullWidth, Row, Col, Content, Header, Title, Description, Footer, Grid }
