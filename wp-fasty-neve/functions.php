<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

add_filter('wp_img_tag_add_auto_sizes', '__return_false');

// Retain the autoloader for proper class loading
$cached_paths = [];

spl_autoload_register(function ($class) use (&$cached_paths) {
    if (isset($cached_paths[$class])) {
        require $cached_paths[$class];
        return;
    }
    
    $prefix = 'WPFasty\\';
    $base_dir = get_stylesheet_directory() . '/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        $cached_paths[$class] = $file;
        require $file;
    }
});

// Bootstrap the application
WPFasty\Core\Application::getInstance();

// Загрузка текстового домена
add_action('init', function() {
    load_theme_textdomain('wp-fasty', get_stylesheet_directory() . '/languages');
});

// Подключение стилей темы
function wp_fasty_neve_enqueue_styles() {
    wp_enqueue_style(
        'wp-fasty-neve-theme',
        get_stylesheet_directory_uri() . '/theme-min.css',
        array('neve-style'),
        wp_get_theme()->get('Version')
    );
}
add_action('wp_enqueue_scripts', 'wp_fasty_neve_enqueue_styles', 20);

// Добавление базовых классов для тела
function wp_fasty_neve_body_classes($classes) {
    $classes[] = 'font-sans';
    $classes[] = 'bg-background';
    $classes[] = 'text-foreground';
    $classes[] = 'antialiased';
    return $classes;
}
add_filter('body_class', 'wp_fasty_neve_body_classes');

// Добавляем переключатель темного режима
function wp_fasty_neve_add_dark_mode_toggle() {
    echo '<div class="dark-mode-toggle-wrapper">
            <button id="dark-mode-toggle" class="dark-mode-toggle-btn" title="Toggle dark mode">🌓</button>
          </div>';
}
add_action('neve_after_header_wrapper_hook', 'wp_fasty_neve_add_dark_mode_toggle', 10);

// Стили для кнопки переключения тёмного режима
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

// Скрипт для переключения темного режима
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
                    darkModeToggle.innerHTML = '🌞';
                } else {
                    localStorage.theme = 'light';
                    darkModeToggle.innerHTML = '🌙';
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

/**
 * Отключение автоматического добавления тегов <p> и <br> в редакторе
 */

// Полное отключение wpautop для шаблона полной страницы
function wp_fasty_disable_wpautop_for_full_page() {
    if (is_page_template('full-page-template.php')) {
        remove_filter('the_content', 'wpautop');
        remove_filter('the_excerpt', 'wpautop');
    }
}
add_action('wp', 'wp_fasty_disable_wpautop_for_full_page');

// Отключение wpautop для всех страниц
remove_filter('the_content', 'wpautop');
remove_filter('the_excerpt', 'wpautop');

// Отключение преобразования \n в <br>
remove_filter('the_content', 'nl2br', 10);

// Отключение wptexturize, который заменяет кавычки и другие символы
remove_filter('the_content', 'wptexturize');

// Отключение автоформатирования для виджетов
remove_filter('widget_text_content', 'wpautop');

// Отключение форматирования для шорткодов
add_filter('the_content', function($content) {
    $raw_content = $content;
    // Восстановим HTML комментарии
    $raw_content = str_replace('&lt;!--', '<!--', $raw_content);
    $raw_content = str_replace('--&gt;', '-->', $raw_content);
    // Восстановим CDATA
    $raw_content = str_replace('&lt;![CDATA[', '<![CDATA[', $raw_content);
    $raw_content = str_replace(']]&gt;', ']]>', $raw_content);
    return $raw_content;
}, 0);

// Отключение автоформатирования при сохранении записи
function wp_fasty_disable_autop_on_save($content) {
    // Сохраняем HTML-код как есть
    remove_filter('content_save_pre', 'wpautop');
    return $content;
}
add_filter('content_save_pre', 'wp_fasty_disable_autop_on_save', 0);

// Добавляем поддержку для режима "Текст" в классическом редакторе
function wp_fasty_allow_full_html_in_editor() {
    // Разрешаем HTML теги в классическом редакторе
    global $allowedposttags;
    $allowedposttags['html'] = array(
        'lang' => true,
        'class' => true
    );
    $allowedposttags['head'] = array(
        'profile' => true
    );
    $allowedposttags['style'] = array(
        'type' => true
    );
    $allowedposttags['script'] = array(
        'type' => true,
        'src' => true
    );
    $allowedposttags['meta'] = array(
        'charset' => true,
        'name' => true,
        'content' => true,
        'http-equiv' => true
    );
}
add_action('init', 'wp_fasty_allow_full_html_in_editor'); 