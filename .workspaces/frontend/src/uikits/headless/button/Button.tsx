import * as React from "react";
import { twMerge } from "tailwind-merge";
import { Slot } from "../slot";
import { buttonBaseStyle } from "./Button.css";


export interface ButtonProps extends React.ButtonHTMLAttributes<HTMLButtonElement> {
  asChild?: boolean;
  
  ignoreBaseStyle?: boolean;
}


export const Button = React.forwardRef<HTMLButtonElement, ButtonProps>(
  (props, ref) => {
    const { 
      asChild = false, 
      className, 
      children, 
      ignoreBaseStyle = false,
      ...buttonProps 
    } = props;
    
    
    const buttonClassName = ignoreBaseStyle 
      ? className 
      : twMerge(buttonBaseStyle, className);
    
    if (asChild) {
      return (
        <Slot 
          {...buttonProps}
          className={buttonClassName}
          ref={ref as any}
          asChild={true}
        >
          {children}
        </Slot>
      );
    }
    
    return (
      <button 
        {...buttonProps}
        className={buttonClassName}
        ref={ref}
      >
        {children}
      </button>
    );
  }
);

Button.displayName = "Button"; 