import { type ButtonProps } from "./interface";

export function Button(props: ButtonProps & {
  href?: string;
  text?: string;
  dataPath?: string;
  children?: React.ReactNode;
  className?: string;
  onClick?: () => void;
}) {
  const {
    variant = "primary",
    size = "default",
    disabled,
    href,
    text,
    dataPath,
    children,
    className = "",
    onClick
  } = props;

  const classes = `button button-${variant} ${size !== "default" ? `button-${size}` : ""} ${className}`;

  if (href) {
    return (
      <a
        className={classes}
        href={href}
        data-path={dataPath}
      >
        {text || children}
      </a>
    );
  }

  return (
    <button
      className={classes}
      type="button"
      disabled={disabled}
      data-path={dataPath}
      onClick={onClick}
    >
      {text || children}
    </button>
  );
} 