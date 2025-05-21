import * as React from "react"
import { cn } from "@/lib/utils";

function Header({ className, ...props }: React.ComponentProps<"header">) {
  return (
    <header
      data-slot="header"
      className={cn(className)}
      {...props}
    />
  )
}

Header.displayName = "Header"

export { Header }