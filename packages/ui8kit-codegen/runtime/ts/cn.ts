import { clsx, type ClassValue } from "clsx";
import { twMerge } from "tailwind-merge";

/**
 * Merge class values with Tailwind conflict resolution.
 * Mirrors shadcn's `cn` = clsx + tailwind-merge.
 * Caller-supplied classes win over recipe classes.
 */
export function cn(...inputs: ClassValue[]): string {
  return twMerge(clsx(inputs));
}
