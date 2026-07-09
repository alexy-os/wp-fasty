import {
  Children,
  cloneElement,
  forwardRef,
  isValidElement,
  type ComponentProps,
  type ElementType,
  type MutableRefObject,
  type ReactElement,
  type ReactNode,
  type Ref,
} from "react";
import { cn } from "../../utils";

/**
 * Slot accepts arbitrary props and merges them onto its single child.
 * Props are intentionally permissive (string-keyed) because Slot is a
 * composition primitive — the parent component decides what to forward.
 */
export type SlotProps = {
  children?: ReactNode;
  className?: string;
  ref?: Ref<HTMLElement>;
  [key: string]: unknown;
};

function mergeRefs<T>(...refs: (Ref<T> | undefined)[]) {
  return (node: T) => {
    for (const r of refs) {
      if (typeof r === "function") {
        r(node);
      } else if (r && typeof r === "object") {
        (r as MutableRefObject<T>).current = node;
      }
    }
  };
}

function isEventHandler(key: string, value: unknown): value is (...args: unknown[]) => void {
  return key.startsWith("on") && typeof value === "function";
}

/**
 * Slot merges its props onto its single child element (Radix-style `asChild`).
 *
 * - `className` is merged via `cn` (clsx + tailwind-merge), so caller classes win.
 * - `ref` is merged with the child's own ref.
 * - `on*` event handlers fire child-first, then slot's.
 * - Other props from slot override child's (slot wins).
 *
 * Use this to compose components: `<Button asChild><a href="/x">x</a></Button>`.
 */
export const Slot = forwardRef<HTMLElement, SlotProps>(function Slot(
  { children, className, ...rest },
  ref
) {
  const child = Children.toArray(children as ReactNode[])[0] as ReactNode;
  if (!isValidElement(child)) {
    return null;
  }

  const childProps = (child as ReactElement<Record<string, unknown>>).props;
  const childRef = (childProps as { ref?: Ref<HTMLElement> }).ref as Ref<HTMLElement> | undefined;

  const merged: Record<string, unknown> = { ...childProps };
  for (const [key, value] of Object.entries(rest)) {
    if (isEventHandler(key, value)) {
      const slotHandler = value;
      const childHandler = childProps[key];
      if (typeof childHandler === "function") {
        merged[key] = (...args: unknown[]) => {
          (childHandler as (...a: unknown[]) => void)(...args);
          slotHandler(...args);
        };
      } else {
        merged[key] = slotHandler;
      }
    } else if (value !== undefined) {
      merged[key] = value;
    }
  }

  const childClassName =
    typeof childProps.className === "string" ? childProps.className : undefined;
  merged.className = cn(String(className ?? ""), childClassName);
  merged.ref = mergeRefs(ref, childRef);

  return cloneElement(child as ReactElement<Record<string, unknown>>, merged);
});

Slot.displayName = "Slot";

export type SlotChildProps<C extends ElementType> = ComponentProps<C> & {
  asChild?: boolean;
  children?: ReactNode;
};
