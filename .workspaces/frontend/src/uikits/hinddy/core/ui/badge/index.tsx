import { type BadgeProps } from "./interface";

export function Badge(props: BadgeProps & {
  text?: string;
  dataPath?: string;
  children?: React.ReactNode;
  className?: string;
}) {
  const { variant = "default", text, dataPath, children, className = "" } = props;

  return (
    <div
      className={`badge badge-${variant} ${className}`}
      data-path={dataPath}
    >
      {text || children}
    </div>
  );
} 