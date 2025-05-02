import { BookOpen, Github } from "lucide-react";
import { Button, type ButtonProps } from "../latte/components/ui/Button";

type Content = {
  button?: {
    text: string;
    variant?: ButtonProps["variant"];
    className?: string;
  };
  title: string;
  description: string;
  buttons?: {
    id: string;
    text: string;
    variant?: ButtonProps["variant"];
    size?: ButtonProps["size"];
    className?: string;
    icon?: React.ReactNode;
  }[];
  images: {
    grid: {
      className: string;
      items: {
        id: string;
        src: string;
        className: string;
      }[];
    };
  };
};

const content: Content = {
  button: {
    text: "Tailwind UI",
    variant: "outline",
    className: "text-sm font-medium"
  },
  title: "Three Ways to Style Components",
  description: "Explore different styling approaches: from utility-first classes for maximum flexibility, semantic class names for better readability, to optimized quark classes for production. Choose what works best for your project.",
  buttons: [
    {
      id: "button1",
      text: "Documentation",
      variant: "default",
      size: "lg",
      className: "items-center gap-2",
      icon: <BookOpen />
    },
    {
      id: "button2",
      text: "GitHub",
      variant: "outline",
      size: "lg",
      className: "items-center gap-2",
      icon: <Github />
    }
  ],
  images: {
    grid: {
      className: "grid grid-cols-2 gap-8",
      items: [
        {
          id: "image1",
          src: "https://placehold.co/600x400",
          className: "bg-muted rounded-md aspect-square"
        },
        {
          id: "image2",
          src: "https://placehold.co/600x400",
          className: "bg-muted rounded-md row-span-2"
        },
        {
          id: "image3",
          src: "https://placehold.co/600x400",
          className: "bg-muted rounded-md aspect-square"
        }
      ]
    }
  },
} as const;

type HeroSplitWithGalleryProps = React.ComponentPropsWithoutRef<"section"> & Partial<Content>;

export const HeroSplitLatte = (props: HeroSplitWithGalleryProps) => {
  const { button, title, description, buttons, images, className, ...rest } = {
    ...content,
    ...props,
  };

  return (
    <section className="w-full py-16 lg:py-32" {...rest}>
      <div className="container mx-auto px-4 md:px-6 lg:px-8">
        <div className="grid grid-cols-1 md:grid-cols-2 items-center gap-8">
          <div className="flex gap-4 flex-col">
            {/* <!-- {if isset($site->button)} --> */}
            {button && (
              <div className="flex justify-center">
                <Button variant={button.variant} className={button.className}>
                  {button.text}
                </Button>
              </div>
            )}
            {/* <!-- {/if} --> */}
            <div className="flex gap-4 flex-col">
              <h2 className="max-w-2xl text-3xl md:text-4xl lg:text-5xl font-bold">
                {title}
              </h2>
              <p className="text-base text-muted-foreground max-w-2xl">
                {description}
              </p>
            </div>
            <div className="flex flex-col md:flex-row gap-4 md:gap-6 lg:gap-8">
              {/* <!-- {if isset($site->buttons)} --> */}
              {/* <!-- {foreach $site->buttons as $button} --> */}
              {buttons?.map((button) => (
                <Button
                  key={button.id}
                  variant={button.variant}
                  size={button.size}
                  className={button.className}
                >
                  {button.text} {button.icon}
                </Button>
              ))}
              {/* <!-- {/foreach} --> */}
              {/* <!-- {/if} --> */}
            </div>
          </div>
          <div className={images.grid.className}>
            {/* <!-- {foreach $site->images->grid->items as $image} --> */}
            {images.grid.items?.map((image) => (
              <div key={image.id} className={image.className}></div>
            ))}
            {/* <!-- {/foreach} --> */}
          </div>
        </div>
      </div>
    </section>
  );
};
