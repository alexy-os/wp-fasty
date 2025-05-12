import * as React from "react";

import { cn } from "@ui8px/lib/utils";

function Textarea({ className, ...props }: React.ComponentProps<"textarea">) {
  return (
    <textarea
      data-slot="textarea"
      className={cn("textarea",

      className
      )}
      {...props} />);


}

export { Textarea };