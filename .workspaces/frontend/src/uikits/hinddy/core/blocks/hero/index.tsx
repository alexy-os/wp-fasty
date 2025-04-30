import { type HeroProps } from "./interface";

export function Hero(props: HeroProps & {
  children?: React.ReactNode;
  className?: string;
  dataPath?: string;
  backgroundImage?: string;
  backgroundOverlay?: boolean;
  buttons?: React.ReactNode;
  container?: boolean;
  contentClassName?: string;
}) {
  const {
    variant = "default",
    size = "default",
    title,
    description,
    children,
    className = "",
    dataPath,
    titleDataPath,
    descriptionDataPath,
    backgroundImage,
    backgroundOverlay = false,
    buttons,
    container = true,
    contentClassName = ""
  } = props;

  const containerClass = container ? 'container mx-auto px-4' : '';
  const hasBackground = backgroundImage ? 'relative bg-cover bg-center' : '';
  const textAlignment = variant === 'centered' ? 'text-center mx-auto' : '';
  const contentMaxWidth = variant === 'centered' ? 'max-w-3xl' : '';

  return (
    <section
      className={`hero hero-${variant} ${size !== "default" ? `hero-${size}` : ""} ${hasBackground} ${className}`}
      data-path={dataPath}
      style={backgroundImage ? { backgroundImage: `url(${backgroundImage})` } : undefined}
    >
      {/* Background overlay */}
      {backgroundImage && backgroundOverlay && (
        <div className="absolute inset-0 bg-black/50"></div>
      )}

      <div className={`relative z-10 ${containerClass}`}>
        <div className={`${contentMaxWidth} ${textAlignment} ${contentClassName}`}>
          {title && (
            <h1 className="text-4xl font-bold tracking-tight mb-4 md:text-5xl lg:text-6xl" data-path={titleDataPath}>
              {title}
            </h1>
          )}

          {description && (
            <p className="text-xl text-muted-foreground mb-8 max-w-[700px]" data-path={descriptionDataPath}>
              {description}
            </p>
          )}

          {buttons && (
            <div className={`flex ${variant === 'centered' ? 'justify-center' : ''} gap-4 mt-6`}>
              {buttons}
            </div>
          )}

          {children && (
            <div className="mt-8">
              {children}
            </div>
          )}
        </div>
      </div>
    </section>
  );
} 