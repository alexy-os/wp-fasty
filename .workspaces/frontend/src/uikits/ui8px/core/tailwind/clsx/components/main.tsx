import * as React from "react"
import { cn } from "@ui8px/lib/utils";

function Main({ className, ...props }: React.ComponentProps<"main">) {
  return (
    <main
      data-slot="main"
      className={cn(className)}
      {...props}
    />
  )
}

Main.displayName = "Main"

export { Main }