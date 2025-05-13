import * as React from "react"
import {
  Nav,
  NavBar,
  NavList,
  NavItem,
  NavLink,
  NavLinkIcon,
  NavTrigger,
  NavMobile,
  NavMobileLink,
  NavGroup
} from "./uikits/ui8px/core/semantic/components/nav"
import { CircleIcon, HomeIcon, SettingsIcon, UsersIcon } from "lucide-react"

export function NavigationExample() {
  return (
    <NavBar>
      {/* Logo */}
      <div className="mr-4 flex">
        <a href="/" className="flex items-center space-x-2">
          <CircleIcon className="h-6 w-6" />
          <span className="font-bold">Brand</span>
        </a>
      </div>

      {/* Desktop navigation */}
      <Nav className="hidden lg:flex ml-auto">
        <NavList>
          <NavItem>
            <NavLink href="/" active>
              <NavLinkIcon>
                <HomeIcon className="h-4 w-4" />
              </NavLinkIcon>
              Dashboard
            </NavLink>
          </NavItem>
          <NavItem>
            <NavLink href="/users">
              <NavLinkIcon>
                <UsersIcon className="h-4 w-4" />
              </NavLinkIcon>
              Users
            </NavLink>
          </NavItem>
          <NavItem>
            <NavLink href="/settings">
              <NavLinkIcon>
                <SettingsIcon className="h-4 w-4" />
              </NavLinkIcon>
              Settings
            </NavLink>
          </NavItem>
        </NavList>
      </Nav>

      {/* Mobile menu button */}
      <label htmlFor="nav-toggle" className="ml-auto lg:hidden">
        <NavTrigger />
      </label>

      {/* Mobile menu */}
      <NavMobile>
        <div className="grid gap-6">
          <NavGroup title="Menu">
            <NavMobileLink href="/" active>
              <HomeIcon className="h-5 w-5" />
              Dashboard
            </NavMobileLink>
            <NavMobileLink href="/users">
              <UsersIcon className="h-5 w-5" />
              Users
            </NavMobileLink>
            <NavMobileLink href="/settings">
              <SettingsIcon className="h-5 w-5" />
              Settings
            </NavMobileLink>
          </NavGroup>

          <NavGroup title="Account">
            <NavMobileLink href="/profile">
              Profile
            </NavMobileLink>
            <NavMobileLink href="/logout">
              Logout
            </NavMobileLink>
          </NavGroup>
        </div>
      </NavMobile>
    </NavBar>
  )
} 