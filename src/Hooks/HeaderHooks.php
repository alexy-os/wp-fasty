<?php
namespace WPFasty\Hooks;

class HeaderHooks extends AbstractHooks {
    public function register(): void {
        // Remove default header
        remove_action('wp_head', 'wp_enqueue_scripts', 1);
        
        // Add our custom header actions
        $this->addAction('wp_head', 'outputMetaTags', 1);
        $this->addAction('wp_head', 'outputFontsAndIcons', 2);
        $this->addAction('wp_head', 'outputTailwindConfig', 3);
        $this->addAction('wp_head', 'outputCustomStyles', 4);
    }

    public function outputMetaTags(): void {
        ?>
        <meta charset="<?php bloginfo('charset'); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="profile" href="https://gmpg.org/xfn/11">
        <script>
            (function() {
                const theme = localStorage.getItem('darkMode');
                const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches;
                if (theme === 'true' || (!theme && systemTheme)) {
                    document.documentElement.classList.add('dark');
                }
            })();
        </script>
        <meta name="description" content="<?php bloginfo('description'); ?>" />
        <?php
    }

    public function outputFontsAndIcons(): void {
        ?>
        <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;500;600;700&display=swap" rel="stylesheet" />
        <link rel="icon" type="image/svg+xml" href="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCA0NyA0MCIgZmlsbD0iIzBlYTVlOSI+DQogICAgPHBhdGggZD0iTTIzLjUgNi41QzE3LjUgNi41IDEzLjc1IDkuNSAxMi4yNSAxNS41QzE0LjUgMTIuNSAxNy4xMjUgMTEuMzc1IDIwLjEyNSAxMi4xMjVDMjEuODM2NyAxMi41NTI5IDIzLjA2MDEgMTMuNzk0NyAyNC40MTQyIDE1LjE2OTJDMjYuNjIwMiAxNy40MDg0IDI5LjE3MzQgMjAgMzQuNzUgMjBDNDAuNzUgMjAgNDQuNSAxNyA0NiAxMUM0My43NSAxNCA0MS4xMjUgMTUuMTI1IDM4LjEyNSAxNC4zNzVDMzYuNDEzMyAxMy45NDcxIDM1LjE4OTkgMTIuNzA1MyAzMy44MzU3IDExLjMzMDhDMzEuNjI5NyA5LjA5MTU4IDI5LjA3NjYgNi41IDIzLjUgNi41Wk0xMi4yNSAyMEM2LjI1IDIwIDIuNSAyMyAxIDI5QzMuMjUgMjYgNS44NzUgMjQuODc1IDguODc1IDI1LjYyNUMxMC41ODY3IDI2LjA1MjkgMTEuODEwMSAyNy4yOTQ3IDEzLjE2NDIgMjguNjY5M0MxNS4zNzAyIDMwLjkwODQgMTcuOTIzNCAzMy41IDIzLjUgMzMuNUMyOS41IDMzLjUgMzMuMjUgMzAuNSAzNC43NSAyNC41QzMyLjUgMjcuNSAyOS44NzUgMjguNjI1IDI2Ljg3NSAyNy44NzVDMjUuMTYzMyAyNy40NDcxIDIzLjkzOTkgMjYuMjA1MyAyMi41ODU4IDI0LjgzMDdDMjAuMzc5OCAyMi41OTE2IDE3LjgyNjYgMjAgMTIuMjUgMjBaIj48L3BhdGg+DQo8L3N2Zz4=" />
        <?php
    }

    public function outputTailwindConfig(): void {
        ?>
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = { "darkMode": "class", "theme": { "fontFamily": { "sans": [ "Lato", "sans-serif" ] }, "container": { "center": true, "padding": "2rem" }, "extend": { "colors": { "border": "hsl(var(--border))", "input": "hsl(var(--input))", "ring": "hsl(var(--ring))", "background": "hsl(var(--background))", "foreground": "hsl(var(--foreground))", "primary": { "DEFAULT": "hsl(var(--primary))", "foreground": "hsl(var(--primary-foreground))" }, "secondary": { "DEFAULT": "hsl(var(--secondary))", "foreground": "hsl(var(--secondary-foreground))" }, "destructive": { "DEFAULT": "hsl(var(--destructive))", "foreground": "hsl(var(--destructive-foreground))" }, "muted": { "DEFAULT": "hsl(var(--muted))", "foreground": "hsl(var(--muted-foreground))" }, "accent": { "DEFAULT": "hsl(var(--accent))", "foreground": "hsl(var(--accent-foreground))" }, "popover": { "DEFAULT": "hsl(var(--popover))", "foreground": "hsl(var(--popover-foreground))" }, "card": { "DEFAULT": "hsl(var(--card))", "foreground": "hsl(var(--card-foreground))" } }, "borderRadius": { "xl": "calc(var(--radius) + 4px)", "lg": "var(--radius)", "md": "calc(var(--radius) - 2px)", "sm": "calc(var(--radius) - 4px)" } } } }
        </script>
        <?php
    }

    public function outputCustomStyles(): void {
        ?>
        <style type="text/tailwindcss">
            @layer base { :root { --background: 0 0% 100%; --foreground: 240 10% 3.9%; --card: 0 0% 100%; --card-foreground: 240 10% 3.9%; --popover: 0 0% 100%; --popover-foreground: 240 10% 3.9%; --primary: 346.8 77.2% 49.8%; --primary-foreground: 355.7 100% 97.3%; --secondary: 240 4.8% 95.9%; --secondary-foreground: 240 5.9% 10%; --muted: 240 4.8% 95.9%; --muted-foreground: 240 3.8% 46.1%; --accent: 240 4.8% 95.9%; --accent-foreground: 240 5.9% 10%; --destructive: 0 84.2% 60.2%; --destructive-foreground: 0 0% 98%; --border: 240 5.9% 90%; --input: 240 5.9% 90%; --ring: 346.8 77.2% 49.8%; --radius: 0.5rem; --radius: 0.75em; } .dark { --background: 20 14.3% 4.1%; --foreground: 0 0% 95%; --popover: 0 0% 9%; --popover-foreground: 0 0% 95%; --card: 24 9.8% 10%; --card-foreground: 0 0% 95%; --primary: 346.8 77.2% 49.8%; --primary-foreground: 355.7 100% 97.3%; --secondary: 240 3.7% 15.9%; --secondary-foreground: 0 0% 98%; --muted: 0 0% 15%; --muted-foreground: 240 5% 64.9%; --accent: 12 6.5% 15.1%; --accent-foreground: 0 0% 98%; --destructive: 0 62.8% 30.6%; --destructive-foreground: 0 85.7% 97.3%; --border: 240 3.7% 15.9%; --input: 240 3.7% 15.9%; --ring: 346.8 77.2% 49.8%; } }
        </style>
        <style>
            :root { color-scheme: light dark; }
            html.dark { color-scheme: dark; }
        </style>
        <?php
    }
} 