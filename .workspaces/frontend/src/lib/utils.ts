import classNames from "classnames";

// Helper function to merge class names
export function cn(...inputs: (string | undefined | null | false)[]): string {
  return classNames(...inputs);
}
