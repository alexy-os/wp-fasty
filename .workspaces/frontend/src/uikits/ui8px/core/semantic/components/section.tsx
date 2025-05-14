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

function SectionHeader({ className, ...props }: React.ComponentProps<"header">) {
  return (
    <header
      data-slot="section-header"
      className={cn("section-header", className)}
      {...props} />);


}

SectionHeader.displayName = "SectionHeader";

function SectionTitle({ className, ...props }: React.ComponentProps<"h2">) {
  return (
    <h2
      data-slot="section-title"
      className={cn("section-title", className)}
      {...props} />);


}

SectionTitle.displayName = "SectionTitle";

function SectionDescription({ className, ...props }: React.ComponentProps<"div">) {
  return (
    <div
      data-slot="section-description"
      className={cn("section-description", className)}
      {...props} />);


}

SectionDescription.displayName = "SectionDescription";

function SectionFooter({ className, ...props }: React.ComponentProps<"footer">) {
  return (
    <footer
      data-slot="section-footer"
      className={cn("section-footer", className)}
      {...props} />);


}

SectionFooter.displayName = "SectionFooter";

function SectionContent({ className, ...props }: React.ComponentProps<"div">) {
  return (
    <div
      data-slot="section-content"
      className={cn("section-content", className)}
      {...props} />);


}

SectionContent.displayName = "SectionContent";

function Row({ className, ...props }: React.ComponentProps<"div">) {
  return (
    <div
      data-slot="row"
      className={cn("row", className)}
      {...props} />);


}

Row.displayName = "Row";

function Col({ className, ...props }: React.ComponentProps<"div">) {
  return (
    <div
      data-slot="col"
      className={cn("col", className)}
      {...props} />);


}

Col.displayName = "Col";

function Grid({ className, ...props }: React.ComponentProps<"div">) {
  return (
    <div
      data-slot="grid"
      className={cn("grid", className)}
      {...props} />);


}

Grid.displayName = "Grid";

export { Section, Container, FullWidth, Row, Col, Grid, SectionContent, SectionHeader, SectionTitle, SectionDescription, SectionFooter };