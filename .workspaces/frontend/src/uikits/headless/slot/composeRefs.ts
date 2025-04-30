import * as React from "react";

type PossibleRef<T> = React.Ref<T> | undefined;

/**
 * Sets the value for a given ref
 * Supports both callback refs and RefObject
 */
function setRef<T>(ref: PossibleRef<T>, value: T | null) {
  if (typeof ref === "function") {
    ref(value);
  } else if (ref && "current" in ref) {
    (ref as React.MutableRefObject<T>).current = value as T;
  }
  return undefined;
}

/**
 * Composes multiple refs into one
 * Handles cleanup functions for React 19+ compatibility
 */
export function composeRefs<T>(...refs: PossibleRef<T>[]): React.RefCallback<T> {
  return (node: T | null) => {
    let hasCleanup = false;
    const cleanups: Array<Function | undefined> = refs.map((ref) => {
      const cleanup = setRef(ref, node);
      if (!hasCleanup && typeof cleanup === "function") {
        hasCleanup = true;
      }
      return cleanup;
    });

    if (hasCleanup) {
      return () => {
        cleanups.forEach((cleanup, index) => {
          if (typeof cleanup === "function") {
            cleanup();
          } else {
            setRef(refs[index], null);
          }
        });
      };
    }
    return undefined;
  };
}

/**
 * Hook that composes multiple refs
 * Automatically memoized using useCallback
 */
export function useComposedRefs<T>(...refs: PossibleRef<T>[]): React.RefCallback<T> {
  return React.useCallback(composeRefs(...refs), refs);
} 