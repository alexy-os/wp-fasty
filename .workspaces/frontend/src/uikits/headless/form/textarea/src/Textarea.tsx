import * as React from "react";
import { Slot } from "@ui-factory/ui-headless/slot";

export interface TextareaProps extends React.TextareaHTMLAttributes<HTMLTextAreaElement> {
  asChild?: boolean;
  /**
   * Enables auto-resizing of the textarea based on content
   */
  autoResize?: boolean;
  /**
   * Maximum number of rows before scrolling
   */
  maxRows?: number;
  /**
   * Minimum number of rows
   */
  minRows?: number;
}

const Textarea = React.forwardRef<HTMLTextAreaElement, TextareaProps>(
  ({ asChild, autoResize, maxRows = 8, minRows = 3, style, ...props }, ref) => {
    const textareaRef = React.useRef<HTMLTextAreaElement>(null);
    const [height, setHeight] = React.useState<number | undefined>(undefined);
    
    const calculateHeight = React.useCallback(() => {
      const textarea = textareaRef.current;
      if (!textarea || !autoResize) return;

      // Reset height to get the correct scrollHeight
      textarea.style.height = 'auto';
      
      // Calculate line height from computed styles
      const computedStyle = window.getComputedStyle(textarea);
      const lineHeight = parseInt(computedStyle.lineHeight);
      
      // Calculate min and max heights
      const minHeight = minRows * lineHeight;
      const maxHeight = maxRows * lineHeight;
      
      // Set new height
      const newHeight = Math.min(Math.max(textarea.scrollHeight, minHeight), maxHeight);
      textarea.style.height = `${newHeight}px`;
      setHeight(newHeight);
    }, [autoResize, maxRows, minRows]);

    React.useEffect(() => {
      calculateHeight();
    }, [calculateHeight, props.value, props.defaultValue]);

    const handleInput = React.useCallback((e: React.FormEvent<HTMLTextAreaElement>) => {
      calculateHeight();
      props.onInput?.(e);
    }, [calculateHeight, props.onInput]);

    const Comp = asChild ? Slot : "textarea";

    return (
      <Comp
        ref={(node: HTMLTextAreaElement | null) => {
          textareaRef.current = node;
          if (typeof ref === 'function') ref(node);
          else if (ref) ref.current = node;
        }}
        style={{
          ...style,
          height: height ? `${height}px` : style?.height,
          resize: autoResize ? 'none' : style?.resize,
        }}
        onInput={handleInput}
        rows={minRows}
        {...props}
      />
    );
  }
);

export { Textarea }; 