<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Упростим функцию по образцу работающей
function wp_fasty_neve_enqueue_styles() {
    // Отключаем стили родительской темы (опционально)
    // wp_dequeue_style('neve-style');
    
    // Подключаем наши стили
    /*wp_enqueue_style(
        'wp-fasty-neve-style',
        get_stylesheet_directory_uri() . '/style.css',
        array('neve-style'),
        wp_get_theme()->get('Version')
    );*/
    
    // Подключаем минифицированные стили
    wp_enqueue_style(
        'wp-fasty-neve-theme',
        get_stylesheet_directory_uri() . '/theme-min.css',
        array('neve-style'),
        wp_get_theme()->get('Version')
    );
    
    // Отладка
    //echo '<!-- WP Fasty Neve styles loaded: ' . get_stylesheet_directory_uri() . '/theme-min.css -->';
}
add_action('wp_enqueue_scripts', 'wp_fasty_neve_enqueue_styles', 20);

// Простой способ проверить, что функция загружается
/*add_action('wp_head', function() {
    echo '<!-- WP Fasty Neve functions.php loaded successfully -->';
}, 1);*/

// Add base classes for the body
function wp_fasty_neve_body_classes($classes) {
    $classes[] = 'font-sans';
    $classes[] = 'bg-background';
    $classes[] = 'text-foreground';
    $classes[] = 'antialiased';
    return $classes;
}
add_filter('body_class', 'wp_fasty_neve_body_classes');

// Добавляем переключатель темного режима в хедер Neve
function wp_fasty_neve_add_dark_mode_toggle() {
    echo '<div class="dark-mode-toggle-wrapper">
            <button id="dark-mode-toggle" class="dark-mode-toggle-btn" title="Toggle dark mode">🌓</button>
          </div>';
}
// Используем хук Neve для добавления в хедер (может потребоваться корректировка)
add_action('neve_after_header_wrapper_hook', 'wp_fasty_neve_add_dark_mode_toggle', 10);

// Добавляем стили для кнопки переключения тёмного режима
function wp_fasty_neve_dark_mode_toggle_styles() {
    ?>
    <style>
        .dark-mode-toggle-wrapper {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
        }
        
        .dark-mode-toggle-btn {
            background: hsl(var(--background));
            border: 1px solid hsl(var(--border));
            border-radius: 50%;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            color: hsl(var(--foreground));
            cursor: pointer;
            font-size: 1.25rem;
            height: 40px;
            width: 40px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.2s ease, background-color 0.2s ease;
        }
        
        .dark-mode-toggle-btn:hover {
            transform: scale(1.1);
            background-color: hsl(var(--secondary));
        }
        
        .dark .dark-mode-toggle-btn {
            background: hsl(var(--secondary));
            color: hsl(var(--secondary-foreground));
        }
        
        .dark .dark-mode-toggle-btn:hover {
            background: hsl(var(--secondary) / 80%);
        }
        
        /* Для мобильных устройств */
        @media (max-width: 768px) {
            .dark-mode-toggle-wrapper {
                bottom: 10px;
                right: 10px;
            }
            
            .dark-mode-toggle-btn {
                height: 36px;
                width: 36px;
                font-size: 1rem;
            }
        }
    </style>
    <?php
}
add_action('wp_head', 'wp_fasty_neve_dark_mode_toggle_styles');

// Добавляем скрипт для переключения темного режима в футер
function wp_fasty_neve_dark_mode_script() {
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const darkModeToggle = document.getElementById('dark-mode-toggle');
            if (!darkModeToggle) return;
            
            // Проверяем сохраненные настройки или используем системные предпочтения
            if (localStorage.theme === 'dark' || 
                (!('theme' in localStorage) && 
                window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
            
            // Добавляем обработчик клика для переключения
            darkModeToggle.addEventListener('click', () => {
                document.documentElement.classList.toggle('dark');
                
                // Сохраняем предпочтения
                if (document.documentElement.classList.contains('dark')) {
                    localStorage.theme = 'dark';
                    darkModeToggle.innerHTML = '🌞'; // солнце для переключения на светлый режим
                } else {
                    localStorage.theme = 'light';
                    darkModeToggle.innerHTML = '🌙'; // луна для переключения на темный режим
                }
            });
            
            // Устанавливаем правильную иконку при загрузке
            if (document.documentElement.classList.contains('dark')) {
                darkModeToggle.innerHTML = '🌞';
            } else {
                darkModeToggle.innerHTML = '🌙';
            }
        });
    </script>
    <?php
}
add_action('wp_footer', 'wp_fasty_neve_dark_mode_script'); 