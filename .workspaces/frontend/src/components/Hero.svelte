<script>
  //import Button from "./ui/Button.svelte";

  // Props for the Hero component
  export let title = "Welcome to our Website";
  export let description = "Discover amazing content and features";
  export let primaryButton = {
    text: "Explore",
    href: "#featured",
    variant: "primary",
  };
  export let secondaryButton = {
    text: "Learn More",
    href: "#about",
    variant: "secondary",
  };
</script>

<section class="hero">
  <div class="container">
    <div class="hero-content">
      <h1 class="hero-title">{title}</h1>
      <p class="hero-description">{description}</p>

      <div class="hero-actions">
        <a
          href={primaryButton.href}
          class="button button-{primaryButton.variant} rounded"
        >
          {primaryButton.text}
        </a>
        <a
          href={secondaryButton.href}
          class="button button-{secondaryButton.variant} rounded"
        >
          {secondaryButton.text}
        </a>
      </div>
    </div>
  </div>
</section>

<style>
  .hero {
    @apply py-20 bg-background;
  }

  .hero-content {
    @apply flex flex-col items-center justify-center text-center max-w-3xl mx-auto;
  }

  .hero-title {
    @apply text-4xl font-bold tracking-tight mb-4;
  }

  .hero-description {
    @apply text-xl text-foreground/80 mb-8;
  }

  .hero-actions {
    @apply flex flex-wrap gap-4 justify-center;
  }

  .rounded {
    @apply rounded-full;
  }
</style>
