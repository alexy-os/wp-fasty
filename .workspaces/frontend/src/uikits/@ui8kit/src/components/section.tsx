import * as React from "react"
import { cn } from "@/lib/utils";

function Section({ className, ...props }: React.ComponentProps<"section">) {
  // TODO: py-6 md:py-12 lg:py-24
  return (
    <section
      data-slot="section"
      className={cn("w-full py-6 md:py-12 lg:py-18", className)}
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

function SectionHeader({ className, ...props }: React.ComponentProps<"header">) {
  return (
    <header
      data-slot="section-header"
      className={cn("w-full py-6 md:py-12 lg:py-18", className)}
      {...props}
    />
  )
}

SectionHeader.displayName = "SectionHeader"

function SectionTitle({ className, ...props }: React.ComponentProps<"h2">) {
  return (
    <h2
      data-slot="section-title"
      className={cn("text-3xl font-bold mb-4", className)}
      {...props}
    />
  )
}

SectionTitle.displayName = "SectionTitle"

function SectionDescription({ className, ...props }: React.ComponentProps<"div">) {
  return (
    <div
      data-slot="section-description"
      className={cn("text-secondary-foreground mb-4", className)}
      {...props}
    />
  )
}

SectionDescription.displayName = "SectionDescription"

function SectionFooter({ className, ...props }: React.ComponentProps<"footer">) {
  return (
    <footer
      data-slot="section-footer"
      className={cn("py-8", className)}
      {...props}
    />
  )
}

SectionFooter.displayName = "SectionFooter"

function SectionContent({ className, ...props }: React.ComponentProps<"div">) {
  return (
    <div
      data-slot="section-content"
      className={cn("w-full", className)}
      {...props}
    />
  )
}

SectionContent.displayName = "SectionContent"

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
      className={cn("grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6", className)}
      {...props}
    />
  )
}

Grid.displayName = "Grid"

export { Section, Container, FullWidth, Row, Col, Grid, SectionContent, SectionHeader, SectionTitle, SectionDescription, SectionFooter }