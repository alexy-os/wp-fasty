<script>
  import { Button } from "./src/components/ui/button";
  import { Hero } from "./src/components/ui/hero";
</script>

<main class="container">
  <Hero />

  <div class="buttons-showcase">
    <h1>Button Variants</h1>

    <section>
      <h2>Main Variants</h2>
      <div class="button-row">
        <Button variant="primary">Primary</Button>
        <Button variant="secondary">Secondary</Button>
        <Button variant="destructive">Destructive</Button>
      </div>
    </section>

    <section>
      <h2>Special Variants</h2>
      <div class="button-row">
        <Button variant="outline">Outline</Button>
        <Button variant="ghost">Ghost</Button>
        <Button variant="link">Link</Button>
      </div>
    </section>

    <section>
      <h2>Size Variants</h2>
      <div class="button-row">
        <Button variant="primary" size="sm">Small</Button>
        <Button variant="primary">Default</Button>
        <Button variant="primary" size="lg">Large</Button>
      </div>
    </section>

    <section>
      <h2>State</h2>
      <div class="button-row">
        <Button variant="primary" disabled>Disabled</Button>
      </div>
    </section>
  </div>
</main>

<style>
  .container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
  }

  .buttons-showcase {
    display: flex;
    flex-direction: column;
    gap: 2rem;
  }

  section {
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }

  .button-row {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    align-items: center;
  }

  h1 {
    font-size: 2rem;
    font-weight: bold;
    margin-bottom: 1rem;
  }

  h2 {
    font-size: 1.5rem;
    font-weight: bold;
    margin-bottom: 0.5rem;
  }
</style>
