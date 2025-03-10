<?php
namespace WPFasty\Hooks;

class HeaderHooks extends AbstractHooks {
    public function register(): void {
        // Remove default header
        remove_action('wp_head', 'wp_enqueue_scripts', 1);
        
        // Add our custom header actions with default priorities
        $this->addAction('wp_head', 'outputMetaTags', 1);
        $this->addAction('wp_head', 'outputFontsAndIcons', 2);
        
        // These actions will be managed by PageTemplateHooks
        // but we set them up with default state (PROD mode)
        // $this->addAction('wp_head', 'outputTailwindStyles', 4);
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
        <!--link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;500;600;700&display=swap" rel="stylesheet" / -->
        <link rel="icon" type="image/svg+xml" href="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyNCIgaGVpZ2h0PSIyNCIgdmlld0JveD0iMCAwIDI0IDI0IiBmaWxsPSJub25lIiBzdHJva2U9IiNlMTFkNDgiIHN0cm9rZS13aWR0aD0iMi43NSIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIiBzdHJva2UtbGluZWpvaW49InJvdW5kIiBjbGFzcz0ibHVjaWRlIGx1Y2lkZS1zaGFwZXMiPjxwYXRoIGQ9Ik04LjMgMTBhLjcuNyAwIDAgMS0uNjI2LTEuMDc5TDExLjQgM2EuNy43IDAgMCAxIDEuMTk4LS4wNDNMMTYuMyA4LjlhLjcuNyAwIDAgMS0uNTcyIDEuMVoiLz48cmVjdCB4PSIzIiB5PSIxNCIgd2lkdGg9IjciIGhlaWdodD0iNyIgcng9IjEiLz48Y2lyY2xlIGN4PSIxNy41IiBjeT0iMTcuNSIgcj0iMy41Ii8+PC9zdmc+" />
        <?php
    }

    public function outputTailwindConfig(): void {
        ?>
        <!--script src="https://cdn.tailwindcss.com"></script-->
        <?php
    }

    public function outputCustomStyles(): void {
        ?>
        <!--style type="text/css"></style-->
        <?php
    }

    public function outputTailwindStyles(): void {
        ?>
        <!--style type="text/tailwindcss"></style-->
        <?php
    }
} 