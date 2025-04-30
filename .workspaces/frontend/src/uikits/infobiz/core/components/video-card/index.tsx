import { type VideoCardProps } from "./interface";

export function VideoCard(props: VideoCardProps & {
  className?: string;
  dataPath?: string;
}) {
  const {
    variant = "default",
    title,
    description,
    author,
    authorImage,
    authorUrl,
    image,
    duration = "00:00",
    platform = "YouTube",
    views = "0",
    publishedAt = "недавно",
    url,
    className = "",
    dataPath
  } = props;

  return (
    <div
      className={`video-card video-card-${variant} ${className}`}
      data-path={dataPath}
    >
      <div className="video-card-image relative aspect-video w-full overflow-hidden rounded-t-2xl group">
        <div className="absolute inset-0 bg-foreground/5 group-hover:bg-foreground/10 transition-colors z-10"></div>

        <div className="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-100 z-20">
          <button className="inline-flex items-center justify-center gap-2 whitespace-nowrap text-sm font-medium ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 w-14 h-14 rounded-full bg-background/95 text-primary hover:bg-primary hover:text-primary-foreground backdrop-blur-sm hover:scale-105 transition-all duration-100">
            <i data-lucide="play" className="h-6 w-6"></i>
            <span className="sr-only">Смотреть видео</span>
          </button>
        </div>

        <div className="absolute bottom-3 right-3 bg-background/95 text-foreground backdrop-blur-sm text-xs font-medium px-2 py-1 rounded-md z-20">
          {duration}
        </div>

        <div className="absolute top-3 left-3 z-20">
          <div className="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-full bg-primary/10 text-primary hover:bg-primary hover:text-primary-foreground backdrop-blur-sm transition-colors duration-100">
            <i data-lucide={platform.toLowerCase()} className="h-3 w-3"></i>
            <span>{platform}</span>
          </div>
        </div>

        <img
          alt={title}
          loading="lazy"
          decoding="async"
          className="absolute h-full w-full inset-0 object-cover transition-transform duration-500 group-hover:scale-105"
          src={image}
        />
      </div>

      <div className="video-card-content p-5">
        <a className="block group/title" href={url}>
          <h3 className="font-medium text-lg line-clamp-2 text-foreground group-hover/title:text-primary transition-colors">
            {title}
          </h3>
        </a>

        {description && (
          <p className="mt-2 text-sm text-muted-foreground line-clamp-2">
            {description}
          </p>
        )}

        <div className="flex items-center justify-between mt-4">
          <div className="flex items-center gap-2">
            <span className="relative flex shrink-0 overflow-hidden rounded-full h-8 w-8 ring-1 ring-border">
              <img
                className="aspect-square h-full w-full"
                alt={author}
                src={authorImage}
              />
            </span>
            <a className="text-sm font-medium text-muted-foreground hover:text-primary transition-colors" href={authorUrl}>
              {author}
            </a>
          </div>
        </div>
      </div>

      <div className="video-card-footer flex items-center px-5 py-3 border-t border-border text-xs text-muted-foreground">
        <div className="flex items-center gap-2">
          <span>{views} просмотров</span>
          <span>•</span>
          <span>{publishedAt}</span>
        </div>
      </div>
    </div>
  );
} 