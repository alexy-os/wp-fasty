<?php
/**
 * Go Frontend Component
 *
 * Компонент для интеграции Go-приложения с WordPress
 *
 * @package WPFasty
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс для интеграции с Go-приложением
 */
class WPFasty_Go_Frontend
{
    /**
     * Путь к исполняемому файлу Go
     *
     * @var string
     */
    private $go_binary;

    /**
     * Флаг кэширования
     *
     * @var bool
     */
    private $use_cache = true;

    /**
     * Время кэширования в секундах
     *
     * @var int
     */
    private $cache_time = 3600; // 1 час

    /**
     * Конструктор
     */
    public function __construct()
    {
        // Определяем путь к бинарнику Go в зависимости от ОС
        $this->go_binary = $this->get_go_binary_path();

        // Регистрируем шорткод
        add_shortcode('go_frontend', [$this, 'render_shortcode']);

        // Добавляем поддержку вывода в шаблоне
        add_action('wpfasty_go_frontend', [$this, 'render']);
    }

    /**
     * Получаем путь к бинарнику Go в зависимости от ОС
     *
     * @return string Путь к исполняемому файлу Go
     */
    public function get_go_binary_path(): string
    {
        $template_dir = get_template_directory();

        // Проверяем OS
        if (defined('PHP_OS_FAMILY')) {
            $os_family = PHP_OS_FAMILY;
        } else {
            $os_family = PHP_OS;
        }

        // Выбираем имя файла в зависимости от ОС
        if (stripos($os_family, 'WIN') !== false) {
            return $template_dir . '/goland/siteTW.exe';
        } else {
            return $template_dir . '/goland/siteTW';
        }
    }

    /**
     * Проверка существования бинарника
     */
    private function binary_exists()
    {
        return file_exists($this->go_binary) && is_executable($this->go_binary);
    }

    /**
     * Собираем контекст для передачи Go-приложению
     */
    private function get_context()
    {
        global $post;

        // Базовый контекст сайта
        $context = [
            'site' => [
                'title' => get_bloginfo('name'),
                'url' => home_url(),
                'description' => get_bloginfo('description'),
                'language' => get_bloginfo('language'),
                'charset' => get_bloginfo('charset')
            ],
            'query' => [
                'is_single' => is_single(),
                'is_page' => is_page(),
                'is_archive' => is_archive(),
                'is_home' => is_home(),
                'is_front_page' => is_front_page()
            ]
        ];

        // Данные страницы, если это пост или страница
        if (is_singular() && $post) {
            $context['page'] = [
                'id' => $post->ID,
                'title' => get_the_title($post),
                'content' => get_the_content(null, false, $post),
                'excerpt' => has_excerpt($post) ? get_the_excerpt($post) : '',
                'permalink' => get_permalink($post),
                'modified' => get_the_modified_date('c', $post)
            ];

            // Миниатюра, если есть
            if (has_post_thumbnail($post)) {
                $context['page']['thumbnail'] = [
                    'url' => get_the_post_thumbnail_url($post, 'full'),
                    'alt' => get_post_meta(get_post_thumbnail_id($post), '_wp_attachment_image_alt', true)
                ];
            }
        }

        // Данные меню
        $menus = get_nav_menu_locations();
        if (!empty($menus)) {
            $context['menu'] = [];

            foreach ($menus as $location => $menu_id) {
                if (!$menu_id) {
                    continue;
                }

                $menu_items = wp_get_nav_menu_items($menu_id);
                if (!$menu_items) {
                    continue;
                }

                $menu_data = [];
                foreach ($menu_items as $item) {
                    $menu_data[] = [
                        'id' => $item->ID,
                        'title' => $item->title,
                        'url' => $item->url,
                        'target' => $item->target,
                        'current' => $item->object_id == get_queried_object_id()
                    ];
                }

                $context['menu'][$location] = $menu_data;
            }
        }

        // Возвращаем JSON-закодированный контекст
        return json_encode($context, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Получаем уникальный ключ кэша для текущего запроса
     */
    private function get_cache_key()
    {
        global $wp_query;
        return md5('go_frontend_' . json_encode($wp_query->query_vars) . '_' . get_locale());
    }

    /**
     * Получаем HTML из кэша или генерируем новый
     */
    private function get_html()
    {
        // Проверяем кэш, если включен
        if ($this->use_cache) {
            $cache_key = $this->get_cache_key();
            $cached_html = get_transient($cache_key);

            if ($cached_html !== false) {
                return $cached_html;
            }
        }

        // Если бинарник не существует, возвращаем сообщение об ошибке
        if (!$this->binary_exists()) {
            return '<div class="error">Go Frontend: бинарный файл не найден по пути: ' . esc_html($this->go_binary) . '</div>';
        }

        // Получаем контекст
        $context = $this->get_context();

        // Подготавливаем команду для выполнения
        $descriptorspec = array(
            0 => array("pipe", "r"),  // stdin
            1 => array("pipe", "w"),  // stdout
            2 => array("pipe", "w")   // stderr
        );

        // Запускаем процесс
        $process = proc_open($this->go_binary, $descriptorspec, $pipes);

        if (is_resource($process)) {
            // Отправляем контекст в stdin
            fwrite($pipes[0], $context);
            fclose($pipes[0]);

            // Читаем HTML из stdout
            $html = stream_get_contents($pipes[1]);
            fclose($pipes[1]);

            // Читаем ошибки из stderr
            $errors = stream_get_contents($pipes[2]);
            fclose($pipes[2]);

            // Закрываем процесс
            $return_value = proc_close($process);

            // Обрабатываем результат
            if ($return_value !== 0 || !empty($errors)) {
                // В случае ошибки возвращаем сообщение
                if (WP_DEBUG) {
                    return '<div class="error">Ошибка выполнения Go: ' . esc_html($errors) . '</div>';
                } else {
                    return '<div class="error">Ошибка рендеринга фронтенда. Включите WP_DEBUG для подробностей.</div>';
                }
            }

            // Кэшируем результат, если включено кэширование
            if ($this->use_cache) {
                set_transient($this->get_cache_key(), $html, $this->cache_time);
            }

            return $html;
        } else {
            return '<div class="error">Не удалось запустить Go-приложение.</div>';
        }
    }

    /**
     * Рендеринг фронтенда через Go-приложение
     *
     * @param array $args Опции рендеринга
     */
    public function render(array $args = []): void
    {
        // Отключаем кэширование, если передан соответствующий параметр
        if (isset($args['cache']) && $args['cache'] === false) {
            $this->use_cache = false;
        }

        // Устанавливаем время кэширования, если передано
        if (isset($args['cache_time']) && is_numeric($args['cache_time'])) {
            $this->cache_time = (int)$args['cache_time'];
        }

        // Получаем HTML
        $html = $this->get_html();

        // Выводим HTML
        echo $this->render_safe_html($html);
    }

    /**
     * Безопасный вывод HTML с PHP-кодом
     *
     * @param string $html HTML-код для вывода
     * @return string Обработанный HTML
     */
    private function render_safe_html(string $html): string
    {
        // Сохраняем PHP-код перед обработкой через kses
        $php_parts = [];
        $html = preg_replace_callback(
            '/(<\?php.*?\?>)/s',
            function ($matches) use (&$php_parts) {
                $key = '##PHP_CODE_' . count($php_parts) . '##';
                $php_parts[$key] = $matches[0];
                return $key;
            },
            $html
        );

        // Обрабатываем HTML через kses с разрешенными тегами
        $html = wp_kses_post($html);

        // Возвращаем PHP-код на место
        foreach ($php_parts as $key => $code) {
            $html = str_replace($key, $code, $html);
        }

        return $html;
    }

    /**
     * Обработчик шорткода
     */
    public function render_shortcode($atts = [])
    {
        $args = shortcode_atts([
            'cache' => true,
            'cache_time' => 3600
        ], $atts);

        // Преобразуем строковые значения в нужные типы
        $args['cache'] = filter_var($args['cache'], FILTER_VALIDATE_BOOLEAN);
        $args['cache_time'] = (int)$args['cache_time'];

        // Буферизируем вывод, чтобы вернуть его как строку
        ob_start();
        $this->render($args);
        return ob_get_clean();
    }
}

// Инициализируем компонент
new WPFasty_Go_Frontend();
