import * as React from "react"
import { cn } from "@/lib/utils";
import { Menu, X } from "lucide-react"

function Nav({
  className,
  children,
  ...props
}: React.ComponentProps<"nav"> & {
  children?: React.ReactNode
}) {
  return (
    <nav
      data-slot="nav"
      className={cn("flex items-center gap-4", className)}
      {...props}
    >
      {children}
    </nav>
  )
}

function NavList({
  className,
  children,
  ...props
}: React.ComponentProps<"ul"> & {
  children?: React.ReactNode
}) {
  return (
    <ul
      data-slot="nav-list"
      className={cn("flex items-center gap-4", className)}
      {...props}
    >
      {children}
    </ul>
  )
}

function NavItem({
  className,
  children,
  ...props
}: React.ComponentProps<"li"> & {
  children?: React.ReactNode
}) {
  return (
    <li
      data-slot="nav-item"
      className={cn("flex items-center gap-4", className)}
      {...props}
    >
      {children}
    </li>
  )
}

function NavLink({
  className,
  children,
  active,
  ...props
}: React.ComponentProps<"a"> & {
  children?: React.ReactNode,
  active?: boolean
}) {
  return (
    <a
      data-slot="nav-link"
      data-active={active ? "true" : undefined}
      className={cn(
        "flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium",
        "transition-colors duration-200",
        "hover:bg-accent hover:text-accent-foreground",
        "data-[active=true]:bg-accent/80 data-[active=true]:text-accent-foreground",
        className
      )}
      {...props}
    >
      {children}
    </a>
  )
}

function NavLinkIcon({
  className,
  children,
  ...props
}: React.ComponentProps<"span"> & {
  children?: React.ReactNode
}) {
  return (
    <span
      data-slot="nav-link-icon"
      className={cn("mr-2 h-4 w-4", className)}
      {...props}
    >
      {children}
    </span>
  )
}

function NavTrigger({
  className,
  ...props
}: React.ComponentProps<"button">) {
  return (
    <button
      type="button"
      data-slot="nav-trigger"
      className={cn(
        "lg:hidden inline-flex items-center justify-center p-2 rounded-md",
        "text-foreground hover:bg-accent hover:text-accent-foreground",
        "focus:outline-none focus:ring-2 focus:ring-inset focus:ring-ring/50",
        className
      )}
      aria-label="Toggle navigation menu"
      {...props}
    >
      <Menu className="h-5 w-5" />
    </button>
  )
}

function NavMobile({
  className,
  children,
  ...props
}: React.ComponentProps<"div"> & {
  children?: React.ReactNode
}) {
  return (
    <div
      data-slot="nav-mobile"
      className={cn(
        "fixed inset-0 z-50 bg-background/80 backdrop-blur-sm lg:hidden",
        "opacity-0 pointer-events-none",
        "peer-checked:opacity-100 peer-checked:pointer-events-auto",
        "transition-opacity duration-300",
        className
      )}
      aria-hidden="true"
      {...props}
    >
      <div className="fixed inset-x-0 bottom-0 z-50 mt-auto">
        <div
          className="translate-y-full peer-checked:translate-y-0 transition-transform duration-300"
          data-state="closed"
        >
          <div className="relative h-[calc(100vh-4rem)] bg-background border-t border-border rounded-t-[1.25rem] flex flex-col">
            <div className="mx-auto mt-4 h-2 w-[100px] rounded-full bg-muted"></div>

            <label
              htmlFor="nav-toggle"
              className="absolute right-4 top-4 p-2 rounded-full hover:bg-accent"
              aria-label="Close menu"
            >
              <X className="h-5 w-5" />
            </label>

            <div className="p-6 pt-10 overflow-y-auto flex-1">
              {children}
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}

function NavMobileLink({
  className,
  children,
  active,
  ...props
}: React.ComponentProps<"a"> & {
  children?: React.ReactNode,
  active?: boolean
}) {
  return (
    <a
      data-slot="nav-mobile-link"
      data-active={active ? "true" : undefined}
      className={cn(
        "flex w-full items-center gap-2 rounded-md px-3 py-2",
        "text-base font-medium transition-colors",
        "hover:bg-accent hover:text-accent-foreground",
        "data-[active=true]:bg-accent/80 data-[active=true]:text-accent-foreground",
        className
      )}
      {...props}
    >
      {children}
    </a>
  )
}

function NavBar({
  className,
  children,
  ...props
}: React.ComponentProps<"div"> & {
  children?: React.ReactNode
}) {
  return (
    <div
      data-slot="nav-bar"
      className={cn(
        "border-b border-border bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60",
        "sticky top-0 z-40 w-full",
        className
      )}
      {...props}
    >
      <div className="container flex h-14 items-center">
        {children}
      </div>

      {/* Hidden checkbox for toggling menu state without JS */}
      <input
        type="checkbox"
        id="nav-toggle"
        className="peer sr-only"
        aria-hidden="true"
      />
    </div>
  )
}

function NavGroup({
  className,
  children,
  title,
  ...props
}: React.ComponentProps<"div"> & {
  children?: React.ReactNode
  title?: string
}) {
  return (
    <div
      data-slot="nav-group"
      className={cn("py-4", className)}
      {...props}
    >
      {title && (
        <h4 className="mb-2 px-3 text-sm font-semibold tracking-tight text-foreground">
          {title}
        </h4>
      )}
      <div className="grid gap-1">
        {children}
      </div>
    </div>
  )
}

Nav.displayName = "Nav"
NavList.displayName = "NavList"
NavItem.displayName = "NavItem"
NavLink.displayName = "NavLink"
NavLinkIcon.displayName = "NavLinkIcon"
NavTrigger.displayName = "NavTrigger"
NavMobile.displayName = "NavMobile"
NavMobileLink.displayName = "NavMobileLink"
NavBar.displayName = "NavBar"
NavGroup.displayName = "NavGroup"

export {
  Nav,
  NavList,
  NavItem,
  NavLink,
  NavLinkIcon,
  NavTrigger,
  NavMobile,
  NavMobileLink,
  NavBar,
  NavGroup
}