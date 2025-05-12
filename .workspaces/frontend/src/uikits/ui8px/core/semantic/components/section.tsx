import * as React from "react";
import { cn } from "@ui8px/lib/utils";

function Section({ className, ...props }: React.ComponentProps<"section">) {
  return (
    <section
      data-slot="section"
      className={cn("section", className)}
      {...props} />);


}

Section.displayName = "Section";

function Container({ className, ...props }: React.ComponentProps<"div">) {
  return (
    <div
      data-slot="container"
      className={cn("container", className)}
      {...props} />);


}

Container.displayName = "Container";

function FullWidth({ className, ...props }: React.ComponentProps<"div">) {
  return (
    <div
      data-slot="full-width"
      className={cn("full-width", className)}
      {...props} />);


}

FullWidth.displayName = "FullWidth";

export { Section, Container, FullWidth };