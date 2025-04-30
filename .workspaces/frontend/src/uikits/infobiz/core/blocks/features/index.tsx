import { type FeaturesProps } from "./interface";

export function Features(props: FeaturesProps & {
  children?: React.ReactNode;
  className?: string;
  dataPath?: string;
  leftContent?: React.ReactNode;
  rightContent?: React.ReactNode;
  headerClassName?: string;
  titleClassName?: string;
  descriptionClassName?: string;
  contentClassName?: string;
}) {
  const {
    variant = "default",
    title,
    description,
    features = [],
    children,
    className = "",
    dataPath,
    leftContent,
    rightContent,
    headerClassName = "",
    titleClassName = "",
    descriptionClassName = "",
    contentClassName = ""
  } = props;

  const renderFeatureIcon = (icon?: string, iconColor = "accent") => {
    if (!icon) return null;

    return (
      <div className={`rounded-full bg-${iconColor} text-${iconColor}-foreground p-1`}>
        <i data-lucide={icon} className="h-4 w-4"></i>
      </div>
    );
  };

  const renderFeatureItem = (feature: {
    title: string;
    description: string;
    icon?: string;
    iconColor?: string;
  }, index: number) => (
    <li key={index} className="flex items-center gap-3">
      {renderFeatureIcon(feature.icon, feature.iconColor)}
      {feature.description}
    </li>
  );

  return (
    <section
      className={`features features-${variant} ${className}`}
      data-path={dataPath}
    >
      <div className="container mx-auto px-4 md:px-6">
        {(title || description) && (
          <div className={`flex flex-col items-center justify-center space-y-4 text-center mb-16 ${headerClassName}`}>
            <div className="space-y-3 max-w-3xl">
              {title && (
                <h2 className={`text-3xl md:text-4xl font-medium tracking-tight ${titleClassName}`}>{title}</h2>
              )}
              {description && (
                <p className={`text-lg text-muted-foreground ${descriptionClassName}`}>{description}</p>
              )}
            </div>
          </div>
        )}

        {variant === "split" && (
          <div className="mx-auto grid max-w-5xl items-center gap-12 py-8 lg:grid-cols-2 lg:gap-16">
            {leftContent}
            <div className="space-y-5">
              {features.length > 0 && (
                <ul className="grid gap-3 text-muted-foreground text-lg">
                  {features.map(renderFeatureItem)}
                </ul>
              )}
              {children}
            </div>
          </div>
        )}

        {variant === "grid" && (
          <div className={`grid grid-cols-1 md:grid-cols-2 gap-8 ${contentClassName}`}>
            {features.map((feature, index) => (
              <div key={index} className="space-y-3">
                <div className="inline-block rounded-full bg-accent text-accent-foreground px-3 py-1 text-sm">
                  {feature.title}
                </div>
                <p className="text-muted-foreground">{feature.description}</p>
              </div>
            ))}
            {children}
          </div>
        )}

        {variant !== "split" && variant !== "grid" && children && (
          <div className={contentClassName}>{children}</div>
        )}
      </div>
    </section>
  );
} 