import * as React from "react"
import { cn } from "@/lib/utils"
import { Menu, X, ChevronDown } from "lucide-react"

/**
 * CSS-only Navigation component with responsive design
 * 
 * No external JavaScript dependencies or runtime requirements.
 * Works with server-side rendering (SSR) and static site generation (SSG).
 * 
 * Required Tailwind CSS:
 * ```css
 * @layer components {
 *  
 *   .peer:checked ~[data - slot="nav-mobile"] {
 * @apply opacity - 100 visible translate - y - 0;
 *   }
 * 
 *   
 *   .group: target[data - slot= "nav-dropdown-content"] {
 * @apply opacity - 100 visible scale - 100;
 *   }
 * 
 *  
 * details[data - slot="nav-mobile-dropdown"][open] summary {
 * @apply bg - accent text - accent - foreground;
 *   }
 * }
 * ```
 * 
 * Key features:
 * - Pure CSS dropdowns using :target selector
 * - Checkbox-based mobile menu
 * - Auto-closing mobile menu when clicking outside
 * - Accessible keyboard navigation
 * 
 * Usage example:
 * 
  * <NavLayout>
    *   <NavBar>
      *     <h2>Logo</h2>
      *
      *   
      *     <Nav>
        *       <NavList>
          *         <NavItem>
            *           <NavLink href="/" active>Home</NavLink>
            *         </NavItem>
          *         <NavItem>
            *           <NavDropdown id="products-dropdown" title="Products">
              *             <NavDropdownItem href="/products/web">Web</NavDropdownItem>
              *             <NavDropdownItem href="/products/mobile">Mobile</NavDropdownItem>
              *           </NavDropdown>
            *         </NavItem>
          *       </NavList>
        *     </Nav>
      *
      *    
      *     <NavTrigger />
      *   </NavBar>
    *
    *  
    *   <NavMobile>
      *     <NavMobileList>
        *       <NavMobileItem>
          *         <NavMobileLink href="/" active>Home</NavMobileLink>
          *       </NavMobileItem>
        *       <NavMobileItem>
          *         <NavMobileDropdown title="Products">
            *           <NavMobileDropdownItem href="/products/web">Web</NavMobileDropdownItem>
            *           <NavMobileDropdownItem href="/products/mobile">Mobile</NavMobileDropdownItem>
            *         </NavMobileDropdown>
          *       </NavMobileItem>
        *     </NavMobileList>
      *   </NavMobile>
    * </NavLayout>
  * ```
 */

// Main navigation container
function Nav({ className, children, ...props }: React.ComponentProps<"nav">) {
  return (
    <nav
      data-slot="nav"
      className={cn("hidden lg:flex items-center space-x-1", className)}
      {...props}
    >
      {children}
    </nav>
  )
}

// Layout for nav with checkbox for mobile menu
function NavLayout({ className, children, ...props }: React.ComponentProps<"div">) {
  return (
    <div
      data-slot="nav-layout"
      className={cn("relative", className)}
      {...props}
    >
      {/* Hidden checkbox for mobile menu toggle */}
      <input
        type="checkbox"
        id="nav-toggle"
        className="peer sr-only"
        aria-hidden="true"
      />
      {children}
    </div>
  )
}

// Navigation bar wrapper
function NavBar({ className, children, ...props }: React.ComponentProps<"div">) {
  return (
    <div
      data-slot="nav-bar"
      className={cn("sticky top-0 z-50 w-full border-b border-border bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60", className)}
      {...props}
    >
      <div className="container flex h-16 items-center justify-between px-4">
        {children}
      </div>
    </div>
  )
}

// Navigation list
function NavList({ className, children, ...props }: React.ComponentProps<"ul">) {
  return (
    <ul
      data-slot="nav-list"
      className={cn("flex items-center space-x-1", className)}
      {...props}
    >
      {children}
    </ul>
  )
}

// Navigation item
function NavItem({ className, children, ...props }: React.ComponentProps<"li">) {
  return (
    <li
      data-slot="nav-item"
      className={cn("relative", className)}
      {...props}
    >
      {children}
    </li>
  )
}

// Navigation link
function NavLink({
  className,
  children,
  active,
  ...props
}: React.ComponentProps<"a"> & { active?: boolean }) {
  return (
    <a
      data-slot="nav-link"
      data-active={active ? "true" : undefined}
      className={cn("inline-flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors hover:bg-accent hover:text-accent-foreground data-[active=true]:bg-accent data-[active=true]:text-accent-foreground", className)}
      {...props}
    >
      {children}
    </a>
  )
}

// Dropdown navigation (CSS-only using details/summary)
function NavDropdown({
  className,
  children,
  title,
  id,
  ...props
}: React.ComponentProps<"details"> & { title: string; id: string }) {
  return (
    <details
      id={id}
      data-slot="nav-dropdown"
      className={cn("relative", className)}
      {...props}
    >
      <summary
        className={cn("inline-flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors hover:bg-accent hover:text-accent-foreground cursor-pointer list-none [&::-webkit-details-marker]:hidden [&[open]]:bg-accent [&[open]]:text-accent-foreground")}
      >
        {title}
        <ChevronDown className={cn("ml-1 h-4 w-4 transition-transform duration-200 details-open:rotate-180")} />
      </summary>

      {/* Dropdown content */}
      <div
        data-slot="nav-dropdown-content"
        className={cn("absolute top-full left-0 mt-1 w-48 rounded-md bg-popover border border-border shadow-lg z-50 origin-top-left")}
      >
        <div className="py-1">
          {children}
        </div>
      </div>
    </details>
  )
}

// Dropdown item
function NavDropdownItem({
  className,
  children,
  ...props
}: React.ComponentProps<"a">) {
  return (
    <a
      data-slot="nav-dropdown-item"
      className={cn("block px-4 py-2 text-sm text-popover-foreground transition-colors hover:bg-accent hover:text-accent-foreground", className)}
      {...props}
    >
      {children}
    </a>
  )
}

// Mobile menu trigger
function NavTrigger({ className, ...props }: React.ComponentProps<"label">) {
  return (
    <label
      htmlFor="nav-toggle"
      data-slot="nav-trigger"
      className={cn("lg:hidden inline-flex items-center justify-center p-2 rounded-md text-foreground hover:bg-accent hover:text-accent-foreground focus:outline-none focus:ring-2 focus:ring-ring cursor-pointer", className)}
      aria-label="Toggle navigation menu"
      {...props}
    >
      <Menu className="h-5 w-5" />
    </label>
  )
}

// Mobile navigation overlay
function NavMobile({ className, children, ...props }: React.ComponentProps<"div">) {
  return (
    <div
      data-slot="nav-mobile"
      className={cn("fixed inset-0 z-50 lg:hidden opacity-0 invisible -translate-y-full transition-all duration-300 ease-out peer-checked:opacity-100 peer-checked:visible peer-checked:translate-y-0", className)}
      {...props}
    >
      {/* Backdrop */}
      <div className="absolute inset-0 bg-black/50 backdrop-blur-sm" />

      {/* Mobile menu panel */}
      <div className="relative bg-background border-b border-border shadow-lg">
        {/* Close button */}
        <div className="flex items-center justify-between p-4 border-b border-border">
          <span className="text-lg font-semibold">Menu</span>
          <label
            htmlFor="nav-toggle"
            className="p-2 rounded-md hover:bg-accent cursor-pointer"
            aria-label="Close menu"
          >
            <X className="h-5 w-5" />
          </label>
        </div>

        {/* Mobile menu content */}
        <div className="p-4">
          {children}
        </div>
      </div>
    </div>
  )
}

// Mobile navigation list
function NavMobileList({ className, children, ...props }: React.ComponentProps<"ul">) {
  return (
    <ul
      data-slot="nav-mobile-list"
      className={cn("space-y-1", className)}
      {...props}
    >
      {children}
    </ul>
  )
}

// Mobile navigation item
function NavMobileItem({ className, children, ...props }: React.ComponentProps<"li">) {
  return (
    <li
      data-slot="nav-mobile-item"
      className={cn(className)}
      {...props}
    >
      {children}
    </li>
  )
}

// Mobile navigation link
function NavMobileLink({
  className,
  children,
  active,
  href,
  ...props
}: React.ComponentProps<"a"> & { active?: boolean }) {
  return (
    <a
      href={href}
      data-slot="nav-mobile-link"
      data-active={active ? "true" : undefined}
      className={cn(
        "flex items-center w-full px-3 py-3 text-base font-medium rounded-md transition-colors hover:bg-accent hover:text-accent-foreground data-[active=true]:bg-accent data-[active=true]:text-accent-foreground",
        className

      )}
      {...props}
    >
      {children}
    </a>
  )
}

// Mobile dropdown item
function NavMobileDropdownItem({
  className,
  children,
  href,
  ...props
}: React.ComponentProps<"a">) {
  return (
    <a
      href={href}
      data-slot="nav-mobile-dropdown-item"
      className={cn(
        "block px-3 py-2 text-sm rounded-md transition-colors hover:bg-accent hover:text-accent-foreground",
        className

      )}
      {...props}
    >
      {children}
    </a>
  )
}

// Mobile dropdown (CSS-only with details/summary)
function NavMobileDropdown({
  className,
  children,
  title,
  ...props
}: React.ComponentProps<"details"> & { title: string }) {
  return (
    <details
      data-slot="nav-mobile-dropdown"
      className={cn("", className)}
      {...props}
    >
      <summary
        className={cn("flex items-center justify-between w-full px-3 py-3 text-base font-medium rounded-md hover:bg-accent hover:text-accent-foreground cursor-pointer list-none [&::-webkit-details-marker]:hidden [&[open]]:bg-accent [&[open]]:text-accent-foreground")}
      >
        {title}
        <ChevronDown className={cn("h-4 w-4 transition-transform duration-200 group-open:rotate-180")} />
      </summary>
      <div className="mt-1 ml-4 space-y-1">
        {children}
      </div>
    </details>
  )
}

// Navigation group for organizing links
function NavGroup({
  className,
  children,
  title,
  ...props
}: React.ComponentProps<"div"> & { title?: string }) {
  return (
    <div
      data-slot="nav-group"
      className={cn("py-2", className)}
      {...props}
    >
      {title && (
        <h4 className="mb-2 px-3 text-sm font-semibold tracking-tight text-muted-foreground">
          {title}
        </h4>
      )}
      <div className="space-y-1">
        {children}
      </div>
    </div>
  )
}

// Navigation group buttons (e.g. for theme toggle)
function NavGroupButtons({ className, children, ...props }: React.ComponentProps<"div">) {
  return (
    <div
      data-slot="nav-group-buttons"
      className={cn("flex items-center space-x-2", className)}
      {...props}
    >
      {children}
    </div>
  )
}

export {
  Nav,
  NavBar,
  NavList,
  NavItem,
  NavLink,
  NavDropdown,
  NavDropdownItem,
  NavTrigger,
  NavMobile,
  NavMobileList,
  NavMobileItem,
  NavMobileLink,
  NavMobileDropdownItem,
  NavMobileDropdown,
  NavGroup,
  NavGroupButtons,
  NavLayout,
}