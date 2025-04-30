import { type CardProps } from "./interface";

export function Card(props: CardProps & {
  children?: React.ReactNode;
  className?: string;
  dataPath?: string;
  header?: React.ReactNode;
  footer?: React.ReactNode;
  image?: {
    src: string;
    alt?: string;
    className?: string;
    overlay?: React.ReactNode;
  };
  onClick?: () => void;
}) {
  const {
    variant = "default",
    elevation = "sm",
    radius = "lg",
    hover = false,
    children,
    className = "",
    dataPath,
    header,
    footer,
    image,
    onClick
  } = props;

  const variantClass = hover ? "card-hover" : `card-${variant}`;

  return (
    <div
      className={`card ${variantClass} ${elevation !== "sm" ? `card-elevation-${elevation}` : ""} ${radius !== "lg" ? `card-radius-${radius}` : ""} ${className}`}
      data-path={dataPath}
      onClick={onClick}
    >
      {image && (
        <div className={`card-image relative w-full overflow-hidden ${radius === "none" ? "" : radius === "sm" ? "rounded-t-md" : radius === "default" ? "rounded-t-lg" : radius === "xl" ? "rounded-t-2xl" : "rounded-t-xl"}`}>
          <img
            src={image.src}
            alt={image.alt || ""}
            className={`w-full object-cover ${image.className || ""}`}
          />
          {image.overlay && (
            <div className="absolute inset-0 flex items-center justify-center">
              {image.overlay}
            </div>
          )}
        </div>
      )}

      {header && (
        <div className="card-header">
          {header}
        </div>
      )}

      {children && (
        <div className="card-content">
          {children}
        </div>
      )}

      {footer && (
        <div className="card-footer">
          {footer}
        </div>
      )}
    </div>
  );
} 