import { type HeroProps } from "./interface";

export function Hero(props: HeroProps & {
  children?: React.ReactNode;
  className?: string;
  dataPath?: string;
  titleClassName?: string;
  descriptionClassName?: string;
  contentClassName?: string;
  buttons?: React.ReactNode;
  backgroundOverlay?: boolean;
  rightContent?: React.ReactNode;
}) {
  const {
    variant = "default",
    size = "default",
    title,
    subtitle,
    description,
    imageUrl,
    backgroundImage,
    accentColor = "primary",
    children,
    className = "",
    dataPath,
    titleClassName = "",
    descriptionClassName = "",
    contentClassName = "",
    buttons,
    backgroundOverlay = false,
    rightContent
  } = props;

  return (
    <section
      className={`hero hero-${variant} ${size !== "default" ? `hero-${size}` : ""} ${className}`}
      data-path={dataPath}
      style={backgroundImage ? { backgroundImage: `url(${backgroundImage})` } : undefined}
    >
      {variant === "gradient" && (
        <div className={`absolute inset-0 bg-gradient-to-br from-${accentColor}/10 via-transparent to-transparent`}></div>
      )}

      {backgroundImage && backgroundOverlay && (
        <div className="absolute inset-0 bg-foreground/30 backdrop-blur-sm"></div>
      )}

      <div className="hero-container container mx-auto px-4 md:px-6 relative">
        <div className={`grid gap-12 ${rightContent ? "lg:grid-cols-2" : ""} lg:gap-16 items-center`}>
          <div className={`space-y-6 ${rightContent ? "animate-fade-up" : ""} ${contentClassName}`}>
            {subtitle && (
              <div className="inline-block rounded-full bg-accent text-accent-foreground px-3 py-1 text-sm hero-subtitle">
                {subtitle}
              </div>
            )}

            {title && (
              <h1 className={`text-4xl md:text-5xl lg:text-6xl font-medium tracking-tight hero-title ${titleClassName}`}>
                {title.includes("<span") ? (
                  <div dangerouslySetInnerHTML={{ __html: title }} />
                ) : (
                  <>
                    {title}
                  </>
                )}
              </h1>
            )}

            {description && (
              <p className={`text-lg text-muted-foreground max-w-[600px] hero-description ${descriptionClassName}`}>
                {description}
              </p>
            )}

            {buttons && (
              <div className="flex flex-col sm:flex-row gap-4 pt-4 hero-buttons">
                {buttons}
              </div>
            )}

            {children && (
              <div className="hero-content">
                {children}
              </div>
            )}
          </div>

          {rightContent && (
            <div className="flex justify-center animate-fade-in">
              {rightContent}
            </div>
          )}
        </div>
      </div>
    </section>
  );
} 