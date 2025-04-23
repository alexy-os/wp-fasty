<?php

declare(strict_types=1);

use Latte\Runtime as LR;

/** source: D:\xampp\htdocs\alexy-os\wp-content\themes\wp-fasty\wp-fasty-ym\views\page\css\dist\critical.css */
final class Template_869fd0b139 extends Latte\Runtime\Template
{
	public const Source = 'D:\\xampp\\htdocs\\alexy-os\\wp-content\\themes\\wp-fasty\\wp-fasty-ym\\views\\page\\css\\dist\\critical.css';


	public function main(array $ʟ_args): void
	{
		echo '/* Critical CSS */
:root {
  --background: oklch(0.95 0.01 230);
  --foreground: oklch(0.25 0.05 240);
  --primary: oklch(0.62 0.15 242.99);
  --primary-foreground: oklch(0.95 0.01 0);
  --card: oklch(0.90 0.02 230);
  --card-foreground: oklch(0.25 0.05 240);
  --popover: oklch(0.95 0.01 230);
  --popover-foreground: oklch(0.25 0.05 240);
  --secondary: oklch(0.80 0.03 235);
  --secondary-foreground: oklch(0.20 0.05 240);
  --muted: oklch(0.86 0.03 248.22);
  --muted-foreground: oklch(0.3 0.01 0);
  --accent: oklch(0.27 0.06 243.1);
  --accent-foreground: oklch(0.7 0.03 235.48);
  --border: oklch(0.75 0.03 235);
  --input: oklch(0.75 0.03 235);
  --ring: oklch(0.50 0.15 240);
  --font-base: "Josefin Sans", "Josefin Sans var", ui-sans-serif, system-ui, sans-serif;
  --radius: 0.75rem;
}

.dark {
  --background: oklch(0.20 0.06 240);
  --foreground: oklch(0.90 0.02 225);
  --primary: oklch(0.50 0.15 243.11);
  --primary-foreground: oklch(0.98 0.01 0);
  --card: oklch(0.25 0.07 240);
  --card-foreground: oklch(0.90 0.02 225);
  --popover: oklch(0.25 0.07 240);
  --popover-foreground: oklch(0.90 0.02 225);
  --secondary: oklch(0.35 0.08 240);
  --secondary-foreground: oklch(0.90 0.02 225);
  --muted: oklch(0.30 0.05 240);
  --muted-foreground: oklch(0.70 0.03 230);
  --accent: oklch(0.45 0.10 240);
  --accent-foreground: oklch(0.95 0.02 225);
  --border: oklch(0.40 0.05 240);
  --input: oklch(0.40 0.05 240);
  --ring: oklch(0.50 0.18 240);
}

/* Base critical styles */
*,
::before,
::after {
  box-sizing: border-box;
  border-width: 0;
  border-style: solid;
  border-color: var(--border);
}

html {
  line-height: 1.5;
  -webkit-text-size-adjust: 100%;
  -moz-tab-size: 4;
  tab-size: 4;
  font-family: var(--font-base);
}

body {
  margin: 0;
  line-height: inherit;
  background-color: var(--background);
  color: var(--foreground);
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

/* Container */
.container {
  width: 100%;
  margin-left: auto;
  margin-right: auto;
  padding-left: 1rem;
  padding-right: 1rem;
}

@media (min-width: 640px) {
  .container {
    max-width: 640px;
    padding-left: 1.5rem;
    padding-right: 1.5rem;
  }
}

@media (min-width: 768px) {
  .container {
    max-width: 768px;
  }
}

@media (min-width: 1024px) {
  .container {
    max-width: 1024px;
    padding-left: 2rem;
    padding-right: 2rem;
  }
}

@media (min-width: 1280px) {
  .container {
    max-width: 1280px;
  }
}

/* Header critical styles */
.site-header {
  padding-top: 1rem;
  padding-bottom: 1rem;
  border-bottom-width: 1px;
  border-color: var(--border);
  background-color: var(--background);
}

.main-navigation {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.logo {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--foreground);
  text-decoration: none;
}

/* Critical utility classes */
.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border-width: 0;
}

/* Theme toggle transitions */
@media (prefers-reduced-motion: no-preference) {
  :root {
    scroll-behavior: smooth;
  }

  * {
    transition-property: color, background-color, border-color;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 100ms;
  }
}';
	}
}
