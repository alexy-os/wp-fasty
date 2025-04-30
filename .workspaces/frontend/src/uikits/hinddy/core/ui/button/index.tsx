import { type ButtonProps } from "./interface";

export function Button(props: ButtonProps & {
  href?: string;
  text?: string;
  dataPath?: string;
  children?: React.ReactNode;
  className?: string;
}) {
  const { variant = "primary", size, disabled, href, text, dataPath, children, className } = props;

  if (href) {
    return (
      <a
        className={`button button-${variant} ${size !== "default" ? `button-${size}` : ""} ${className}`}
        href={href}
        data-path={dataPath}
      >
        {text || children}
      </a>
    );
  }

  return (
    <button
      className={`button button-${variant} ${size !== "default" ? `button-${size}` : ""} ${className}`}
      type="button"
      disabled={disabled}
      data-path={dataPath}
    >
      {text || children}
    </button>
  );
}