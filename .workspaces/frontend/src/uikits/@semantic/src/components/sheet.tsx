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

// Layout for sheet with checkbox
interface SheetLayoutProps {
  children: React.ReactNode;
  className?: string;
  trigger?: React.ReactNode;
}

function SheetLayout({ trigger, children, className }: SheetLayoutProps) {
  return (
    <div className={cn("relative", className)}>
      <input type="checkbox" id="sheet-toggle" className="peer sr-only" aria-hidden="true" />
      {trigger}
      {children}
    </div>);

}

// Props interfaces
interface SheetTriggerProps {
  children: React.ReactNode;
  className?: string;
}

interface SheetContentProps {
  children: React.ReactNode;
  className?: string;
}

// Trigger component - uses peer checkbox
function SheetTrigger({ children, className, ...props }: SheetTriggerProps) {
  return (
    <label htmlFor="sheet-toggle" className={cn("sheet-trigger", className)} {...props}>
      {children}
    </label>);

}

// Content component - pure CSS off-canvas
// To change the side from which the panel slides in:
// For LEFT: use 'inset-y-0 left-0 border-r transform -translate-x-full peer-checked:translate-x-0'
// For RIGHT: use 'inset-y-0 right-0 border-l transform translate-x-full peer-checked:translate-x-0'
function SheetContent({ children, className, ...props }: SheetContentProps) {
  // Example usage (choose one line):
  // LEFT PANEL:  className={cn("fixed inset-y-0 left-0 z-50 w-full max-w-sm bg-background shadow-lg border-r transform -translate-x-full transition-transform duration-300 ease-out peer-checked:translate-x-0 flex flex-col overflow-hidden", className)}
  // RIGHT PANEL: className={cn("fixed inset-y-0 right-0 z-50 w-full max-w-sm bg-background shadow-lg border-l transform translate-x-full transition-transform duration-300 ease-out peer-checked:translate-x-0 flex flex-col overflow-hidden", className)}
  return (
    <div role="dialog" aria-modal="true" className={cn("sheet-content", className)} {...props}>
      {/* Close button */}
      <div className="absolute top-4 right-4 z-10">
        <label htmlFor="sheet-toggle" className={cn("rounded-sm opacity-70 ring-offset-background transition-opacity hover:opacity-100 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 inline-flex h-6 w-6 items-center justify-center bg-background/80 backdrop-blur-sm cursor-pointer")}>
          <XIcon className="h-4 w-4" />
          <span className="sr-only">Close</span>
        </label>
      </div>
      {children}
    </div>);

}

// Overlay component
function SheetOverlay({ className, ...props }: {className?: string;}) {
  return (
    <label
      htmlFor="sheet-toggle"

      className={cn("sheet-overlay",

      className
      )}
      {...props} />);


}

// Header component
interface SheetHeaderProps {
  children: React.ReactNode;
  className?: string;
}

function SheetHeader({ children, className, ...props }: SheetHeaderProps) {
  return (
    <div className={cn("sheet-header", className)} {...props}>
      {children}
    </div>);

}

// Body component for main content
interface SheetBodyProps {
  children: React.ReactNode;
  className?: string;
}

function SheetBody({ children, className, ...props }: SheetBodyProps) {
  return (
    <div className={cn("sheet-body", className)} {...props}>
      {children}
    </div>);

}

// Footer component
interface SheetFooterProps {
  children: React.ReactNode;
  className?: string;
}

function SheetFooter({ children, className, ...props }: SheetFooterProps) {
  return (
    <div className={cn("sheet-footer", className)} {...props}>
      {children}
    </div>);

}

// Title component
interface SheetTitleProps {
  children: React.ReactNode;
  className?: string;
}

function SheetTitle({ children, className, ...props }: SheetTitleProps) {
  return (
    <h2 className={cn("sheet-title", className)} {...props}>
      {children}
    </h2>);

}

// Description component
interface SheetDescriptionProps {
  children: React.ReactNode;
  className?: string;
}

function SheetDescription({ children, className, ...props }: SheetDescriptionProps) {
  return (
    <p className={cn("sheet-description", className)} {...props}>
      {children}
    </p>);

}

// Close component - simple link to close
interface SheetCloseProps {
  children: React.ReactNode;
  className?: string;
}

function SheetClose({ children, className, ...props }: SheetCloseProps) {
  return (
    <label htmlFor="sheet-toggle" className={cn("sheet-close", className)} {...props}>
      {children}
    </label>);

}

export {
  SheetLayout,
  SheetTrigger,
  SheetContent,
  SheetHeader,
  SheetFooter,
  SheetTitle,
  SheetDescription,
  SheetClose,
  SheetBody,
  SheetOverlay };