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

export { Section, Container, FullWidth }