<script>
  import { tv } from "tailwind-variants";

  export let variant = "primary";
  export let size = "default";
  export let disabled = false;
  export let type = "button";

  const button = tv({
    base: "inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:opacity-50 disabled:pointer-events-none",
    variants: {
      variant: {
        primary: "bg-primary text-primary-foreground hover:bg-primary/90",
        destructive:
          "bg-destructive text-destructive-foreground hover:bg-destructive/90",
        outline:
          "border border-input hover:bg-accent hover:text-accent-foreground",
        secondary:
          "bg-secondary text-secondary-foreground hover:bg-secondary/80",
        ghost: "hover:bg-accent hover:text-accent-foreground",
        link: "underline-offset-4 hover:underline text-primary",
      },
      size: {
        default: "h-10 py-2 px-4",
        sm: "h-9 px-3 rounded-md",
        lg: "h-11 px-8 rounded-md",
      },
    },
    defaultVariants: {
      variant: "primary",
      size: "default",
    },
  });

  $: classes = `button button-${variant} ${size !== "default" ? `button-${size}` : ""}`;
</script>

<button class={classes} {disabled} {type}>
  <slot></slot>
</button>

<style>
  .button {
    @apply inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-colors;
    @apply focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2;
    @apply disabled:opacity-50 disabled:pointer-events-none;
  }

  .button-primary {
    @apply bg-primary text-primary-foreground hover:bg-primary/90;
    @apply dark:bg-primary dark:text-primary-foreground dark:hover:bg-primary/80;
  }

  .button-secondary {
    @apply bg-secondary text-secondary-foreground hover:bg-secondary/80;
  }

  .button-destructive {
    @apply bg-destructive text-destructive-foreground hover:bg-destructive/90;
  }

  .button-outline {
    @apply border border-input hover:bg-accent hover:text-accent-foreground;
  }

  .button-ghost {
    @apply hover:bg-accent hover:text-accent-foreground;
  }

  .button-link {
    @apply underline-offset-4 hover:underline text-primary;
  }

  .button-sm {
    @apply h-9 px-3 rounded-md;
  }

  .button-lg {
    @apply h-11 px-8 rounded-md;
  }
</style>
