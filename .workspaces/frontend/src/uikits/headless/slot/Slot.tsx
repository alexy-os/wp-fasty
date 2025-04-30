import * as React from "react";
import { twMerge } from "tailwind-merge";
import { composeRefs } from "./composeRefs";
import { slotStyle } from "./Slot.css";


interface SlotProps extends React.HTMLAttributes<HTMLElement> {
  children?: React.ReactNode;
  asChild?: boolean;
}


export const Slot = React.forwardRef<HTMLElement, SlotProps>(
  (props, forwardedRef) => {
    const { children, className, asChild = false, ...slotProps } = props;
    
    if (asChild && React.isValidElement(children)) {
      
      return (
        <SlotClone {...slotProps} ref={forwardedRef} className={twMerge(slotStyle, className)}>
          {children}
        </SlotClone>
      );
    }

    
    return (
      <span {...slotProps} ref={forwardedRef as React.Ref<HTMLSpanElement>} className={twMerge(slotStyle, className)}>
        {children}
      </span>
    );
  }
);

Slot.displayName = "Slot";


interface SlotCloneProps extends React.HTMLAttributes<HTMLElement> {
  children: React.ReactElement;
}


const SlotClone = React.forwardRef<any, SlotCloneProps>(
  (props, forwardedRef) => {
    const { children, ...slotProps } = props;
    const childRef = getElementRef(children);
    
    
    const mergedProps = mergeProps(slotProps, children.props);
    
    
    mergedProps.ref = forwardedRef 
      ? composeRefs(forwardedRef, childRef) 
      : childRef;

    
    return React.cloneElement(children, mergedProps);
  }
);

SlotClone.displayName = "SlotClone";


function getElementRef(element: React.ReactElement) {
  return (element.props as { ref?: React.Ref<unknown> }).ref || (element as any).ref;
}


function mergeProps(slotProps: any, childProps: any) {
  const merged = { ...childProps };

  for (const propName in slotProps) {
    const slotPropValue = slotProps[propName];
    const childPropValue = childProps[propName];

    
    if (propName.match(/^on[A-Z]/) && typeof slotPropValue === "function") {
      merged[propName] = childPropValue
        ? (...args: unknown[]) => {
            childPropValue(...args);
            slotPropValue(...args);
          }
        : slotPropValue;
    }
    
    else if (propName === "style") {
      merged[propName] = { ...slotPropValue, ...childPropValue };
    }
    
    else if (propName === "className") {
      merged[propName] = twMerge(slotPropValue, childPropValue);
    }
    
    else {
      merged[propName] = slotPropValue !== undefined ? slotPropValue : childPropValue;
    }
  }

  return merged;
} 