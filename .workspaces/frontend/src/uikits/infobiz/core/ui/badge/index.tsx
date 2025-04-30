import { type BadgeProps } from "./interface";

export function Badge(props: BadgeProps & {
  text?: string;
  dataPath?: string;
  children?: React.ReactNode;
  className?: string;
  onClick?: () => void;
}) {
  const {
    variant = "default",
    size = "default",
    text,
    dataPath,
    children,
    className = "",
    onClick
  } = props;

  return (
    <span
      className={`badge badge-${variant} ${size !== "default" ? `badge-${size}` : ""} ${className}`}
      data-path={dataPath}
      onClick={onClick}
    >
      {text || children}
    </span>
  );
} 