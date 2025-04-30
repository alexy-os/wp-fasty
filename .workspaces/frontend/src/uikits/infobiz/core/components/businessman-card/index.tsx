import { type BusinessmanCardProps } from "./interface";
import { Badge } from "../../../core/ui/badge";

export function BusinessmanCard(props: BusinessmanCardProps & {
  className?: string;
  dataPath?: string;
}) {
  const {
    variant = "default",
    rank,
    name,
    category,
    revenue,
    subscribers,
    tags,
    image,
    socialLinks = {},
    profileUrl,
    className = "",
    dataPath
  } = props;

  function getSocialIcon(platform: string) {
    const iconMap: Record<string, string> = {
      website: "external-link",
      instagram: "instagram",
      youtube: "youtube",
      tiktok: "video",
      telegram: "send",
      twitter: "twitter",
      vk: "message-circle"
    };

    return iconMap[platform] || "link";
  }

  return (
    <div
      className={`businessman-card businessman-card-${variant} ${className}`}
      data-path={dataPath}
    >
      <div className="relative h-56 w-full overflow-hidden businessman-card-image">
        {rank && (
          <div className="absolute top-4 left-4 z-10">
            <div className="bg-background/90 dark:bg-background text-foreground px-3 py-1 rounded-full text-xs font-medium backdrop-blur-sm transition-colors">
              #{rank}
            </div>
          </div>
        )}
        <img
          alt={name}
          src={image}
          className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
        />
      </div>

      <div className="businessman-card-content p-4">
        <h3 className="text-xl font-medium text-foreground">{name}</h3>
        <p className="mt-1 text-sm text-muted-foreground">{category}</p>

        <div className="mt-5 grid grid-cols-2 gap-4">
          <div>
            <p className="text-xs text-muted-foreground uppercase tracking-wide">Годовой доход</p>
            <p className="mt-1 font-medium text-foreground">{revenue}</p>
          </div>
          <div>
            <p className="text-xs text-muted-foreground uppercase tracking-wide">Аудитория</p>
            <p className="mt-1 font-medium text-foreground">{subscribers}</p>
          </div>
        </div>

        {tags.length > 0 && (
          <div className="mt-5 flex flex-wrap gap-2">
            {tags.map((tag, index) => (
              <Badge key={index} variant="tag">
                {tag}
              </Badge>
            ))}
          </div>
        )}
      </div>

      <div className="businessman-card-footer flex items-center justify-between p-4 pt-0">
        <div className="flex gap-2">
          {Object.entries(socialLinks).map(([platform, url], index) => (
            <a
              key={index}
              href={url}
              target="_blank"
              rel="noopener noreferrer"
              className="inline-flex h-9 w-9 items-center justify-center rounded-full bg-accent text-accent-foreground transition-colors hover:bg-accent/80"
            >
              <span className="sr-only">{platform}</span>
              <i data-lucide={getSocialIcon(platform)} className="h-4 w-4"></i>
            </a>
          ))}
        </div>

        <a
          href={profileUrl}
          className="inline-flex items-center justify-center rounded-full border border-input px-4 h-9 text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground"
        >
          Подробнее
        </a>
      </div>
    </div>
  );
} 