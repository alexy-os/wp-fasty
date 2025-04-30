import { type TextProps } from "./interface";

export function Text(props: TextProps & {
  text?: string;
  dataPath?: string;
  children?: React.ReactNode;
  className?: string;
  as?: "p" | "span" | "div" | "h1" | "h2" | "h3" | "h4" | "h5" | "h6";
}) {
  const {
    variant = "default",
    size = "default",
    weight = "normal",
    text,
    dataPath,
    children,
    className = "",
    as: Element = "p"
  } = props;

  return (
    <Element
      className={`text text-${variant} ${size !== "default" ? `text-${size}` : ""} ${weight !== "normal" ? `text-${weight}` : ""} ${className}`}
      data-path={dataPath}
    >
      {text || children}
    </Element>
  );
} 