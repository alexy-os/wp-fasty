import * as React from "react";
import { XIcon } from "lucide-react";

import { cn } from "@/lib/utils";

/**
 * CSS-only Sheet component for SSR/SSG compatibility
 * 
 * Usage example:
 * ```tsx
 * <Sheet>
 *   <SheetTrigger href="#my-sheet">Open Sheet</SheetTrigger>
 *   <SheetContent id="my-sheet">
 *     <SheetHeader>
 *       <SheetTitle>Sheet Title</SheetTitle>
 *       <SheetDescription>Description text</SheetDescription>
 *     </SheetHeader>
 *     <div className="p-6">Content here</div>
 *     <SheetFooter>
 *       <SheetClose>Close</SheetClose>
 *     </SheetFooter>
 *   </SheetContent>
 * </Sheet>
 * ```
 */

// Simple CSS-only Sheet component for SSR/SSG
interface SheetProps {
  children: React.ReactNode;
  className?: string;
}

function Sheet({ children, className }: SheetProps) {
  return (
    <div className={cn("sheet", className)}>
      {children}
    </div>);

}

// Trigger component - uses CSS :target pseudo-class
interface SheetTriggerProps {
  href: string;
  children: React.ReactNode;
  className?: string;
}

function SheetTrigger({ href, children, className }: SheetTriggerProps) {
  return (
    <a
      href={href}

      className={cn("sheet-trigger",

      className
      )}>

      {children}
    </a>);

}

// Content component - pure CSS off-canvas
interface SheetContentProps {
  id: string;
  children: React.ReactNode;
  className?: string;
}

function SheetContent({ id, children, className }: SheetContentProps) {
  return (
    <>
      {/* Sheet Content */}
      <div
        id={id}

        className={cn("sheet-content",

        className
        )}>

        {/* Close button */}
        <div className="absolute top-4 right-4 z-10">
          <a
            href="#"
            className={cn(
              "rounded-sm opacity-70 ring-offset-background transition-opacity hover:opacity-100 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 inline-flex h-6 w-6 items-center justify-center bg-background/80 backdrop-blur-sm"
            )}>

            <XIcon className="h-4 w-4" />
            <span className="sr-only">Close</span>
          </a>
        </div>

        {children}
      </div>

      {/* Overlay that shows when sheet is targeted */}
      <div
        className={cn(
          "fixed inset-0 z-40 bg-black/50 opacity-0 invisible transition-all duration-300 pointer-events-none has-[#${id}:target]:opacity-100 has-[#${id}:target]:visible has-[#${id}:target]:pointer-events-auto",
          className
        )} />

    </>);

}

// Header component
interface SheetHeaderProps {
  children: React.ReactNode;
  className?: string;
}

function SheetHeader({ children, className }: SheetHeaderProps) {
  return (
    <div

      className={cn("sheet-header", className)}>

      {children}
    </div>);

}

// Footer component
interface SheetFooterProps {
  children: React.ReactNode;
  className?: string;
}

function SheetFooter({ children, className }: SheetFooterProps) {
  return (
    <div

      className={cn("sheet-footer", className)}>

      {children}
    </div>);

}

// Title component
interface SheetTitleProps {
  children: React.ReactNode;
  className?: string;
}

function SheetTitle({ children, className }: SheetTitleProps) {
  return (
    <h2

      className={cn("sheet-title", className)}>

      {children}
    </h2>);

}

// Description component
interface SheetDescriptionProps {
  children: React.ReactNode;
  className?: string;
}

function SheetDescription({ children, className }: SheetDescriptionProps) {
  return (
    <p

      className={cn("sheet-description", className)}>

      {children}
    </p>);

}

// Close component - simple link to close
interface SheetCloseProps {
  children: React.ReactNode;
  className?: string;
}

function SheetClose({ children, className }: SheetCloseProps) {
  return (
    <a
      href="#"

      className={cn("sheet-close",

      className
      )}>

      {children}
    </a>);

}

export {
  Sheet,
  SheetTrigger,
  SheetContent,
  SheetHeader,
  SheetFooter,
  SheetTitle,
  SheetDescription,
  SheetClose };