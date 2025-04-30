import { type CardProps } from "./interface";

export function Card(props: CardProps & {
  children?: React.ReactNode;
  className?: string;
  dataPath?: string;
  titleDataPath?: string;
  descriptionDataPath?: string;
  footer?: React.ReactNode;
  image?: {
    src: string;
    alt?: string;
    className?: string;
  };
  onClick?: () => void;
}) {
  const {
    variant = "default",
    size = "default",
    elevation = "sm",
    radius = "default",
    title,
    description,
    badges = [],
    children,
    className = "",
    dataPath,
    titleDataPath,
    descriptionDataPath,
    footer,
    image,
    onClick
  } = props;

  return (
    <div
      className={`
        card card-${variant} 
        ${size !== "default" ? `card-${size}` : ""} 
        ${elevation !== "sm" ? `card-elevation-${elevation}` : ""} 
        ${radius !== "default" ? `card-radius-${radius}` : ""} 
        ${onClick ? 'cursor-pointer' : ''} 
        ${className}
      `}
      data-path={dataPath}
      onClick={onClick}
    >
      {image && (
        <div className={`card-image mb-4 -mt-6 -mx-6 overflow-hidden ${radius === "none" ? "" : radius === "sm" ? "rounded-t-sm" : radius === "lg" ? "rounded-t-2xl" : radius === "full" ? "rounded-t-3xl" : "rounded-t-xl"}`}>
          <img
            src={image.src}
            alt={image.alt || title}
            className={`w-full object-cover ${image.className || ''}`}
          />
        </div>
      )}

      {(title || badges.length > 0) && (
        <div className="card-header mb-4">
          {badges.length > 0 && (
            <div className="flex flex-wrap gap-2 mb-2">
              {badges.map((badge, index) => (
                <span key={index} className={`badge ${badge.variant ? `badge-${badge.variant}` : 'badge-default'}`}>
                  {badge.text}
                </span>
              ))}
            </div>
          )}

          {title && (
            <h3 className="text text-title" data-path={titleDataPath}>
              {title}
            </h3>
          )}

          {description && (
            <p className="text text-muted mt-2" data-path={descriptionDataPath}>
              {description}
            </p>
          )}
        </div>
      )}

      {children && (
        <div className="card-content">
          {children}
        </div>
      )}

      {footer && (
        <div className="card-footer mt-4 pt-4 border-t">
          {footer}
        </div>
      )}
    </div>
  );
} 