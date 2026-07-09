import { clsx, type ClassValue } from "clsx";
import { twMerge } from "tailwind-merge";

/**
 * Merge class values with Tailwind conflict resolution (clsx + tailwind-merge).
 * Caller-supplied classes win over recipe classes. This is the canonical
 * merge used by the reference renderer and all TS runtimes.
 */
export function cn(...inputs: ClassValue[]): string {
  return twMerge(clsx(inputs));
}
