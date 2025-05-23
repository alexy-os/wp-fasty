import * as React from "react";
import { cn } from "@/lib/utils";
import { Menu, X } from "lucide-react";

function Nav({
  className,
  children,
  ...props


}: React.ComponentProps<"nav"> & {children?: React.ReactNode;}) {
  return (
    <nav

      className={cn("nav", className)}
      {...props}>

      {children}
    </nav>);

}

function Navbar({ className, ...props }: React.ComponentProps<"div">) {
  return (
    <div

      className={cn("navbar", className)}
      {...props} />);


}

Navbar.displayName = "Navbar";

function NavList({
  className,
  children,
  ...props


}: React.ComponentProps<"ul"> & {children?: React.ReactNode;}) {
  return (
    <ul

      className={cn("nav-list", className)}
      {...props}>

      {children}
    </ul>);

}

function NavItem({
  className,
  children,
  ...props


}: React.ComponentProps<"li"> & {children?: React.ReactNode;}) {
  return (
    <li

      className={cn("", className)}
      {...props}>

      {children}
    </li>);

}

function NavLink({
  className,
  children,
  active,
  ...props



}: React.ComponentProps<"a"> & {children?: React.ReactNode;active?: boolean;}) {
  return (
    <a

      data-active={active ? "true" : undefined}
      className={cn("nav-link", className)}
      {...props}>

      {children}
    </a>);

}

function NavLinkIcon({
  className,
  children,
  ...props


}: React.ComponentProps<"span"> & {children?: React.ReactNode;}) {
  return (
    <span

      className={cn("nav-link-icon", className)}
      {...props}>

      {children}
    </span>);

}

function NavTrigger({
  className,
  ...props
}: React.ComponentProps<"button">) {
  return (
    <button
      type="button"

      className={cn("nav-trigger", className)}
      aria-label="Toggle navigation menu"
      {...props}>

      <Menu className="h-5 w-5" />
    </button>);

}

function NavMobile({
  className,
  children,
  ...props


}: React.ComponentProps<"div"> & {children?: React.ReactNode;}) {
  return (
    <div

      className={cn("nav-mobile", className)}
      aria-hidden="true"
      {...props}>

      <div className="fixed inset-x-0 bottom-0 z-50 mt-auto">
        <div
          className="translate-y-full peer-checked:translate-y-0 transition-transform duration-300"
          data-state="closed">

          <div className="relative h-[calc(100vh-4rem)] bg-background border-t border-border rounded-t-[1.25rem] flex flex-col">
            <div className="mx-auto mt-4 h-2 w-[100px] rounded-full bg-muted"></div>

            <label
              htmlFor="nav-toggle"
              className="absolute right-4 top-4 p-2 rounded-full hover:bg-accent"
              aria-label="Close menu">

              <X className="h-5 w-5" />
            </label>

            <div className="p-6 pt-10 overflow-y-auto flex-1">
              {children}
            </div>
          </div>
        </div>
      </div>
    </div>);

}

function NavMobileLink({
  className,
  children,
  active,
  ...props



}: React.ComponentProps<"a"> & {children?: React.ReactNode;active?: boolean;}) {
  return (
    <a

      data-active={active ? "true" : undefined}
      className={cn("nav-mobile-link", className)}
      {...props}>

      {children}
    </a>);

}

function NavBar({
  className,
  children,
  ...props


}: React.ComponentProps<"div"> & {children?: React.ReactNode;}) {
  return (
    <div

      className={cn("nav-bar", className)}
      {...props}>

      <div className="container flex h-14 items-center">
        {children}
      </div>

      {/* Hidden checkbox for toggling menu state without JS */}
      <input
        type="checkbox"
        id="nav-toggle"
        className="peer sr-only"
        aria-hidden="true" />

    </div>);

}

function NavGroupButtons({
  className,
  children,
  ...props


}: React.ComponentProps<"div"> & {children?: React.ReactNode;}) {
  return (
    <div

      className={cn("nav-group-buttons", className)}
      {...props}>

      {children}
    </div>);

}

function NavGroup({
  className,
  children,
  title,
  ...props



}: React.ComponentProps<"div"> & {children?: React.ReactNode;title?: string;}) {
  return (
    <div

      className={cn("nav-group", className)}
      {...props}>

      {title &&
      <h4 className="mb-2 px-3 text-sm font-semibold tracking-tight text-foreground">
          {title}
        </h4>
      }
      <div className="grid gap-1">
        {children}
      </div>
    </div>);

}

Nav.displayName = "Nav";
NavList.displayName = "NavList";
NavItem.displayName = "NavItem";
NavLink.displayName = "NavLink";
NavLinkIcon.displayName = "NavLinkIcon";
NavTrigger.displayName = "NavTrigger";
NavMobile.displayName = "NavMobile";
NavMobileLink.displayName = "NavMobileLink";
NavBar.displayName = "NavBar";
NavGroup.displayName = "NavGroup";
NavGroupButtons.displayName = "NavGroupButtons";
export {
  Nav,
  Navbar,
  NavList,
  NavItem,
  NavLink,
  NavLinkIcon,
  NavTrigger,
  NavMobile,
  NavMobileLink,
  NavBar,
  NavGroup,
  NavGroupButtons };