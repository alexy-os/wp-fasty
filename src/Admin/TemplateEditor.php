<?php
namespace WPFasty\Admin;

use WPFasty\Core\Container;

class TemplateEditor {
    private $container;
    
    public function __construct(Container $container) {
        $this->container = $container;
        
        add_action('admin_menu', [$this, 'addMenuPage']);
        add_action('add_meta_boxes', [$this, 'addTemplateMetaBox']);
        add_action('save_post', [$this, 'saveTemplateData']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueScripts']);
        add_action('wp_ajax_get_post_data', [$this, 'ajaxGetPostData']);
        add_action('wp_ajax_save_template_json', [$this, 'ajaxSaveTemplateJson']);
        add_action('wp_ajax_analyze_template', [$this, 'ajaxAnalyzeTemplate']);
        add_action('wp_ajax_create_missing_fields', [$this, 'ajaxCreateMissingFields']);
        add_action('wp_ajax_restore_revision', [$this, 'ajaxRestoreRevision']);
    }

    public function saveTemplateData($postId): void {
        // Проверки безопасности (nonce, автосохранение, права доступа)
        if (!isset($_POST['wp_fasty_template_nonce']) || 
            !wp_verify_nonce($_POST['wp_fasty_template_nonce'], 'wp_fasty_template_nonce')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $postId)) {
            return;
        }
        
        $post = get_post($postId);
        $post_type = $post->post_type;
        $post_slug = $post->post_name;
        
        // Если slug пустой (новый пост), используем ID
        if (empty($post_slug)) {
            $post_slug = 'id-' . $postId;
        }
        
        // Получаем данные шаблона
        $template = isset($_POST['wp_fasty_template']) ? $_POST['wp_fasty_template'] : '';
        $templateData = isset($_POST['wp_fasty_template_data']) ? $_POST['wp_fasty_template_data'] : '{}';
        
        // Валидация и санитизация данных
        $template = $this->sanitizeTemplate($template);
        $templateData = $this->sanitizeJsonData($templateData);
        
        // Создаем директории, если они не существуют
        $templates_dir = get_template_directory() . '/templates/' . $post_type . 's';
        $data_dir = get_template_directory() . '/data/' . $post_type . 's';
        
        if (!file_exists($templates_dir)) {
            wp_mkdir_p($templates_dir);
        }
        
        if (!file_exists($data_dir)) {
            wp_mkdir_p($data_dir);
        }
        
        // Сохраняем шаблон в файл
        $template_file = $templates_dir . '/' . $post_slug . '.hbs';
        file_put_contents($template_file, $template);
        
        // Сохраняем данные в файл
        $data_file = $data_dir . '/' . $post_slug . '.json';
        file_put_contents($data_file, $templateData);
        
        // Сохраняем только пути к файлам в мета-данных
        update_post_meta($postId, '_wp_fasty_template_file', $template_file);
        update_post_meta($postId, '_wp_fasty_data_file', $data_file);
        
        // Генерируем статичный HTML
        if (!empty($template)) {
            $this->generateStaticHtml($postId, $template, $templateData);
        }
        
        // Сохраняем ревизию
        $this->saveRevision($postId, $template, $templateData);
    }
    
    /**
     * Санитизация шаблона Handlebars
     */
    private function sanitizeTemplate($template) {
        // Ограничение размера шаблона (например, 100 КБ)
        if (strlen($template) > 102400) {
            $template = substr($template, 0, 102400);
        }
        
        // Базовая санитизация
        $template = wp_check_invalid_utf8($template);
        
        return $template;
    }
    
    /**
     * Санитизация JSON-данных
     */
    private function sanitizeJsonData($jsonData) {
        // Ограничение размера данных (например, 1 МБ)
        if (strlen($jsonData) > 1048576) {
            return '{"error": "Data size exceeds limit"}';
        }
        
        // Проверяем, что это валидный JSON
        $data = json_decode($jsonData, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Если JSON невалидный, возвращаем пустой объект
            return '{}';
        }
        
        // Рекурсивная санитизация данных
        $sanitizedData = $this->sanitizeArray($data);
        
        // Возвращаем санитизированный JSON
        return json_encode($sanitizedData, JSON_PRETTY_PRINT);
    }
    
    /**
     * Рекурсивная санитизация массива данных
     */
    private function sanitizeArray($array) {
        $result = [];
        
        foreach ($array as $key => $value) {
            // Санитизация ключа
            $key = sanitize_text_field($key);
            
            if (is_array($value)) {
                // Рекурсивная санитизация вложенных массивов
                $result[$key] = $this->sanitizeArray($value);
            } else if (is_string($value)) {
                // Санитизация строковых значений
                $result[$key] = sanitize_text_field($value);
            } else {
                // Другие типы данных (числа, булевы значения и т.д.)
                $result[$key] = $value;
            }
        }
        
        return $result;
    }
    
    private function generateStaticHtml($postId, $template, $templateData): void {
        $post = get_post($postId);
        $post_type = $post->post_type;
        $post_slug = $post->post_name;
        
        // Если slug пустой (новый пост), используем ID
        if (empty($post_slug)) {
            $post_slug = 'id-' . $postId;
        }
        
        // Получаем данные поста для шаблона
        $postData = $this->getPostData($postId);
        
        // Объединяем с пользовательскими данными
        $customData = json_decode($templateData, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $customData = [];
        }
        
        $data = array_merge($postData, $customData);
        
        // Рендерим шаблон
        try {
            // Создаем директорию для статичного HTML
            $static_dir = WP_CONTENT_DIR . '/static/' . $post_type . 's/' . $post_slug;
            if (!file_exists($static_dir)) {
                wp_mkdir_p($static_dir);
            }
            
            // Сохраняем данные для последующего рендеринга
            $template_data = [
                'template' => $template,
                'data' => $data
            ];
            
            // Сохраняем данные в JSON-файл
            file_put_contents($static_dir . '/template-data.json', json_encode($template_data, JSON_PRETTY_PRINT));
            
            // Создаем простой HTML-файл с подключением Handlebars
            $html = $this->createStaticHtmlWrapper($template, $data);
            
            // Сохраняем HTML в файл
            file_put_contents($static_dir . '/index.html', $html);
            
            // Сохраняем только путь к статичному файлу в мета-данных
            update_post_meta($postId, '_wp_fasty_static_file', $static_dir . '/index.html');
        } catch (\Exception $e) {
            // Логируем ошибку
            error_log('Ошибка генерации HTML: ' . $e->getMessage());
        }
    }
    
    /**
     * Создает HTML-обертку для статичного файла
     */
    private function createStaticHtmlWrapper($template, $data): string {
        $json_data = json_encode($data, JSON_HEX_APOS | JSON_HEX_QUOT);
        $escaped_template = htmlspecialchars($template, ENT_QUOTES, 'UTF-8');
        
        return <<<HTML
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$data['page']['title']} - {$data['site']['title']}</title>
    <script src="https://cdn.jsdelivr.net/npm/handlebars@latest/dist/handlebars.min.js"></script>
</head>
<body>
    <div id="content"></div>
    
    <script id="template" type="text/x-handlebars-template">
{$escaped_template}
    </script>
    
    <script>
        // Данные для шаблона
        var data = {$json_data};
        
        // Компиляция шаблона
        var source = document.getElementById('template').innerHTML;
        var template = Handlebars.compile(source);
        
        // Рендеринг
        document.getElementById('content').innerHTML = template(data);
    </script>
</body>
</html>
HTML;
    }
    
    private function getPostData($postId): array {
        $post = get_post($postId);
        
        // Базовые данные поста
        $data = [
            'page' => [
                'id' => $post->ID,
                'title' => $post->post_title,
                'content' => $post->post_content,
                'excerpt' => get_the_excerpt($post),
                'date' => $post->post_date,
                'modified' => $post->post_modified,
                'slug' => $post->post_name,
                'type' => $post->post_type,
                'status' => $post->post_status
            ],
            'site' => [
                'title' => get_bloginfo('name'),
                'description' => get_bloginfo('description'),
                'url' => get_site_url()
            ]
        ];
        
        // Добавляем произвольные поля
        $meta = get_post_meta($postId);
        foreach ($meta as $key => $values) {
            if (substr($key, 0, 1) !== '_') {
                $data['page'][$key] = maybe_unserialize($values[0]);
            }
        }
        
        return $data;
    }
    
    /**
     * Добавляет пункт меню в админ-панель
     */
    public function addMenuPage(): void {
        add_menu_page(
            'Управление шаблонами',
            'Шаблоны',
            'manage_options',
            'wp-fasty-templates',
            [$this, 'renderTemplatesPage'],
            'dashicons-layout',
            30
        );
    }
    
    /**
     * Отображает страницу управления шаблонами
     */
    public function renderTemplatesPage(): void {
        ?>
        <div class="wrap">
            <h1>Управление шаблонами</h1>
            <p>Здесь вы можете управлять шаблонами для вашего сайта.</p>
            
            <div class="card">
                <h2>Инструкции</h2>
                <p>Для создания и редактирования шаблонов:</p>
                <ol>
                    <li>Откройте страницу или запись для редактирования</li>
                    <li>Найдите метабокс "Редактор шаблона"</li>
                    <li>Создайте шаблон с использованием синтаксиса Handlebars</li>
                    <li>Нажмите "Обновить предпросмотр" для просмотра результата</li>
                    <li>Сохраните страницу для применения шаблона</li>
                </ol>
            </div>
        </div>
        <?php
    }
    
    public function enqueueScripts($hook): void {
        $screens = ['post.php', 'post-new.php'];
        if (!in_array($hook, $screens)) {
            return;
        }
        
        // Регистрация скриптов
        wp_enqueue_script(
            'handlebars',
            'https://cdn.jsdelivr.net/npm/handlebars@latest/dist/handlebars.min.js',
            [],
            '4.7.8'
        );
        
        // Изменяем способ загрузки Monaco Editor
        wp_enqueue_script(
            'wp-fasty-monaco-loader',
            get_template_directory_uri() . '/assets/js/monaco-loader.js',
            ['jquery'],
            '1.0.0',
            true
        );
        
        wp_enqueue_script(
            'wp-fasty-template-editor',
            get_template_directory_uri() . '/assets/js/template-editor.js',
            ['jquery', 'handlebars', 'wp-fasty-monaco-loader'],
            '1.0.0',
            true
        );
        
        wp_localize_script('wp-fasty-template-editor', 'wpFasty', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_fasty_ajax_nonce'),
            'postId' => get_the_ID(),
            'monacoPath' => 'https://cdn.jsdelivr.net/npm/monaco-editor@latest/min/'
        ]);
        
        // Стили
        wp_enqueue_style(
            'wp-fasty-template-editor',
            get_template_directory_uri() . '/assets/css/template-editor.css',
            [],
            '1.0.0'
        );
    }
    
    public function addTemplateMetaBox(): void {
        add_meta_box(
            'wp-fasty-template',
            'Редактор шаблона',
            [$this, 'renderTemplateMetaBox'],
            ['page', 'post'],
            'normal',
            'high'
        );
    }
    
    public function renderTemplateMetaBox($post): void {
        wp_nonce_field('wp_fasty_template_nonce', 'wp_fasty_template_nonce');
        
        // Получаем пути к файлам
        $template_file = get_post_meta($post->ID, '_wp_fasty_template_file', true);
        $data_file = get_post_meta($post->ID, '_wp_fasty_data_file', true);
        
        // Определяем slug страницы
        $post_slug = $post->post_name;
        if (empty($post_slug)) {
            $post_slug = 'id-' . $post->ID;
        }
        
        // Проверяем существование файлов по slug, если пути не сохранены
        if (empty($template_file)) {
            $potential_template_file = get_template_directory() . '/templates/' . $post->post_type . 's/' . $post_slug . '.hbs';
            if (file_exists($potential_template_file)) {
                $template_file = $potential_template_file;
                update_post_meta($post->ID, '_wp_fasty_template_file', $template_file);
            }
        }
        
        if (empty($data_file)) {
            $potential_data_file = get_template_directory() . '/data/' . $post->post_type . 's/' . $post_slug . '.json';
            if (file_exists($potential_data_file)) {
                $data_file = $potential_data_file;
                update_post_meta($post->ID, '_wp_fasty_data_file', $data_file);
            }
        }
        
        // Загружаем шаблон из файла, если он существует
        if ($template_file && file_exists($template_file)) {
            $template = file_get_contents($template_file);
        } else {
            // Иначе пытаемся загрузить из мета-поля или используем пустую строку
            $template = get_post_meta($post->ID, '_wp_fasty_template', true) ?: '';
        }
        
        // Загружаем данные из файла, если он существует
        if ($data_file && file_exists($data_file)) {
            $templateData = file_get_contents($data_file);
        } else {
            // Иначе пытаемся загрузить из мета-поля или используем пустой объект
            $templateData = get_post_meta($post->ID, '_wp_fasty_template_data', true) ?: '{}';
        }
        
        // Рендерим интерфейс редактора
        ?>
        <div class="wp-fasty-template-editor">
            <input type="hidden" name="wp_fasty_template" id="wp_fasty_template" value="<?php echo esc_attr($template); ?>">
            <input type="hidden" name="wp_fasty_template_data" id="wp_fasty_template_data" value="<?php echo esc_attr($templateData); ?>">
            
            <div class="wp-fasty-tabs">
                <button type="button" class="wp-fasty-tab active" data-tab="template">Шаблон</button>
                <button type="button" class="wp-fasty-tab" data-tab="data">Данные</button>
                <button type="button" class="wp-fasty-tab" data-tab="preview">Предпросмотр</button>
                <button type="button" class="wp-fasty-tab" data-tab="fields">Поля</button>
                <button type="button" class="wp-fasty-tab" data-tab="revisions">Ревизии</button>
            </div>
            
            <div class="wp-fasty-tab-content active" data-tab="template">
                <div id="template-editor" style="height: 400px; width: 100%;"></div>
                <div class="template-actions">
                    <button type="button" id="analyze-template" class="button">Анализировать шаблон</button>
                    <button type="button" id="save-draft" class="button">Сохранить черновик</button>
                    <button type="button" id="load-draft" class="button">Загрузить черновик</button>
                </div>
            </div>
            
            <div class="wp-fasty-tab-content" data-tab="data">
                <div id="data-editor" style="height: 400px; width: 100%;"></div>
            </div>
            
            <div class="wp-fasty-tab-content" data-tab="preview">
                <div id="wp_fasty_preview" class="preview-container">
                    <div class="preview-placeholder">Нажмите "Обновить предпросмотр"</div>
                </div>
                <div class="preview-actions">
                    <button type="button" id="wp_fasty_preview_button" class="button button-primary">Обновить предпросмотр</button>
                    <button type="button" id="wp_fasty_save_html" class="button">Сохранить HTML</button>
                </div>
            </div>
            
            <div class="wp-fasty-tab-content" data-tab="fields">
                <div id="template-fields" class="template-fields">
                    <div class="fields-loading">Загрузка полей...</div>
                </div>
                <div class="fields-actions">
                    <button type="button" id="create-missing-fields" class="button button-primary">Создать недостающие поля</button>
                </div>
            </div>
            
            <div class="wp-fasty-tab-content" data-tab="revisions">
                <div id="template-revisions" class="template-revisions">
                    <?php $this->renderRevisions($post->ID); ?>
                </div>
            </div>
            
            <div class="template-info">
                <?php if ($template_file && file_exists($template_file)): ?>
                    <p><strong>Файл шаблона:</strong> <?php echo esc_html($this->getRelativePath($template_file)); ?></p>
                <?php endif; ?>
                
                <?php if ($data_file && file_exists($data_file)): ?>
                    <p><strong>Файл данных:</strong> <?php echo esc_html($this->getRelativePath($data_file)); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Отображает список ревизий шаблона
     */
    private function renderRevisions($postId): void {
        $revisions = get_post_meta($postId, '_wp_fasty_revisions', true) ?: [];
        
        if (empty($revisions)) {
            echo '<p>Ревизии не найдены.</p>';
            return;
        }
        
        // Сортируем ревизии по времени (новые сверху)
        usort($revisions, function($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });
        
        echo '<ul class="revisions-list">';
        
        foreach ($revisions as $index => $revision) {
            $date = date('d.m.Y H:i:s', $revision['timestamp']);
            $template_file = $revision['template_file'];
            $data_file = $revision['data_file'];
            
            echo '<li class="revision-item">';
            echo '<div class="revision-info">';
            echo '<span class="revision-date">' . esc_html($date) . '</span>';
            echo '<button type="button" class="button restore-revision" data-template="' . esc_attr($template_file) . '" data-data="' . esc_attr($data_file) . '">Восстановить</button>';
            echo '</div>';
            echo '</li>';
        }
        
        echo '</ul>';
    }
    
    public function ajaxGetPostData(): void {
        check_ajax_referer('wp_fasty_ajax_nonce', 'nonce');
        
        $postId = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        if (!$postId) {
            wp_send_json_error(['message' => 'ID поста не указан']);
        }
        
        // Проверка прав доступа
        if (!current_user_can('edit_post', $postId)) {
            wp_send_json_error(['message' => 'Недостаточно прав для редактирования']);
            return;
        }
        
        $post = get_post($postId);
        if (!$post) {
            wp_send_json_error(['message' => 'Пост не найден']);
        }
        
        // Базовые данные поста
        $data = [
            'page' => [
                'id' => $post->ID,
                'title' => $post->post_title,
                'content' => $post->post_content,
                'excerpt' => get_the_excerpt($post),
                'date' => $post->post_date,
                'modified' => $post->post_modified,
                'slug' => $post->post_name,
                'type' => $post->post_type,
                'status' => $post->post_status
            ],
            'site' => [
                'title' => get_bloginfo('name'),
                'description' => get_bloginfo('description'),
                'url' => get_site_url()
            ]
        ];
        
        // Добавляем произвольные поля
        $meta = get_post_meta($postId);
        foreach ($meta as $key => $values) {
            if (substr($key, 0, 1) !== '_') {
                $data['page'][$key] = maybe_unserialize($values[0]);
            }
        }
        
        // Добавляем таксономии
        $taxonomies = get_object_taxonomies($post->post_type);
        $data['taxonomies'] = [];
        
        foreach ($taxonomies as $taxonomy) {
            $terms = get_the_terms($postId, $taxonomy);
            if ($terms && !is_wp_error($terms)) {
                $data['taxonomies'][$taxonomy] = array_map(function($term) {
                    return [
                        'id' => $term->term_id,
                        'name' => $term->name,
                        'slug' => $term->slug
                    ];
                }, $terms);
            }
        }
        
        // Добавляем изображения
        if (has_post_thumbnail($postId)) {
            $data['page']['featured_image'] = [
                'thumbnail' => get_the_post_thumbnail_url($postId, 'thumbnail'),
                'medium' => get_the_post_thumbnail_url($postId, 'medium'),
                'large' => get_the_post_thumbnail_url($postId, 'large'),
                'full' => get_the_post_thumbnail_url($postId, 'full')
            ];
        }
        
        // Добавляем меню
        $locations = get_nav_menu_locations();
        $data['menus'] = [];
        
        foreach ($locations as $location => $menu_id) {
            if ($menu_id) {
                $menu_items = wp_get_nav_menu_items($menu_id);
                if ($menu_items) {
                    $data['menus'][$location] = array_map(function($item) {
                        return [
                            'id' => $item->ID,
                            'title' => $item->title,
                            'url' => $item->url,
                            'target' => $item->target,
                            'parent' => $item->menu_item_parent
                        ];
                    }, $menu_items);
                }
            }
        }
        
        wp_send_json_success($data);
    }
    
    public function ajaxSaveTemplateJson(): void {
        check_ajax_referer('wp_fasty_ajax_nonce', 'nonce');
        
        $postId = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $html = isset($_POST['html']) ? $_POST['html'] : '';
        $templateData = isset($_POST['template_data']) ? $_POST['template_data'] : '{}';
        
        if (!$postId) {
            wp_send_json_error(['message' => 'ID поста не указан']);
        }
        
        // Сохраняем HTML
        update_post_meta($postId, '_wp_fasty_static_html', $html);
        
        // Сохраняем данные шаблона
        update_post_meta($postId, '_wp_fasty_template_data', $templateData);
        
        // Сохраняем в файл
        $this->saveStaticFile($postId, $html);
        
        wp_send_json_success(['message' => 'Данные успешно сохранены']);
    }
    
    public function ajaxAnalyzeTemplate(): void {
        check_ajax_referer('wp_fasty_ajax_nonce', 'nonce');
        
        $postId = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $template = isset($_POST['template']) ? $_POST['template'] : '';
        
        if (!$postId || empty($template)) {
            wp_send_json_error(['message' => 'Недостаточно данных']);
        }
        
        // Анализ шаблона для поиска переменных
        $variables = $this->extractTemplateVariables($template);
        
        // Получение существующих полей
        $existingFields = $this->getExistingFields($postId);
        
        // Сравнение и определение недостающих полей
        $missingFields = $this->findMissingFields($variables, $existingFields);
        
        wp_send_json_success([
            'variables' => $variables,
            'existingFields' => $existingFields,
            'missingFields' => $missingFields
        ]);
    }
    
    public function ajaxCreateMissingFields(): void {
        check_ajax_referer('wp_fasty_ajax_nonce', 'nonce');
        
        $postId = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $fields = isset($_POST['fields']) ? json_decode(stripslashes($_POST['fields']), true) : [];
        
        if (!$postId || empty($fields)) {
            wp_send_json_error(['message' => 'Недостаточно данных']);
        }
        
        $created = [];
        
        foreach ($fields as $field) {
            // Создаем поле, если оно еще не существует
            if (!metadata_exists('post', $postId, $field)) {
                update_post_meta($postId, $field, '');
                $created[] = $field;
            }
        }
        
        wp_send_json_success([
            'message' => 'Создано полей: ' . count($created),
            'created' => $created
        ]);
    }
    
    private function extractTemplateVariables(string $template): array {
        $variables = [];
        
        // Регулярное выражение для поиска переменных Handlebars
        // Ищем {{page.something}}, {{site.something}} и т.д.
        preg_match_all('/\{\{([^}]+)\}\}/', $template, $matches);
        
        if (!empty($matches[1])) {
            foreach ($matches[1] as $match) {
                // Очищаем от пробелов и хелперов
                $match = trim($match);
                
                // Пропускаем хелперы и блоки
                if (strpos($match, '#') === 0 || strpos($match, '/') === 0) {
                    continue;
                }
                
                // Пропускаем выражения с пробелами (вероятно, хелперы)
                if (strpos($match, ' ') !== false) {
                    continue;
                }
                
                // Добавляем в список переменных
                $variables[] = $match;
            }
        }
        
        // Фильтруем только переменные page.*
        $pageVariables = [];
        foreach ($variables as $variable) {
            if (strpos($variable, 'page.') === 0) {
                $field = substr($variable, 5); // Убираем 'page.'
                $pageVariables[$field] = $variable;
            }
        }
        
        return $pageVariables;
    }
    
    private function getExistingFields(int $postId): array {
        $meta = get_post_meta($postId);
        $fields = [];
        
        foreach ($meta as $key => $values) {
            if (substr($key, 0, 1) !== '_') {
                $fields[$key] = [
                    'name' => $key,
                    'value' => maybe_unserialize($values[0])
                ];
            }
        }
        
        return $fields;
    }
    
    private function findMissingFields(array $variables, array $existingFields): array {
        $missing = [];
        
        foreach ($variables as $field => $variable) {
            if (!isset($existingFields[$field])) {
                $missing[] = $field;
            }
        }
        
        return $missing;
    }
    
    private function saveStaticFile(int $postId, string $html): void {
        $post = get_post($postId);
        $staticDir = WP_CONTENT_DIR . '/static';
        
        if (!file_exists($staticDir)) {
            mkdir($staticDir, 0755, true);
        }
        
        $slug = $post->post_name;
        $type = $post->post_type;
        
        $dir = $staticDir . '/' . $type . '/' . $slug;
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
        
        file_put_contents($dir . '/index.html', $html);
    }
    
    /**
     * Сохраняет ревизию шаблона и данных
     */
    private function saveRevision($postId, $template, $templateData): void {
        // Создаем директории для ревизий, если они не существуют
        $templates_revisions_dir = get_template_directory() . '/templates/revisions';
        $data_revisions_dir = get_template_directory() . '/data/revisions';
        
        if (!file_exists($templates_revisions_dir)) {
            wp_mkdir_p($templates_revisions_dir);
        }
        
        if (!file_exists($data_revisions_dir)) {
            wp_mkdir_p($data_revisions_dir);
        }
        
        // Формируем имя файла ревизии
        $revision_name = $postId . '-' . time();
        
        // Сохраняем ревизию шаблона
        $template_revision_file = $templates_revisions_dir . '/' . $revision_name . '.hbs';
        file_put_contents($template_revision_file, $template);
        
        // Сохраняем ревизию данных
        $data_revision_file = $data_revisions_dir . '/' . $revision_name . '.json';
        file_put_contents($data_revision_file, $templateData);
        
        // Сохраняем информацию о ревизии (только пути к файлам)
        $revisions = get_post_meta($postId, '_wp_fasty_revisions', true) ?: [];
        $revisions[] = [
            'timestamp' => time(),
            'template_file' => $template_revision_file,
            'data_file' => $data_revision_file
        ];
        
        // Ограничиваем количество ревизий (например, 10 последних)
        if (count($revisions) > 10) {
            $revisions = array_slice($revisions, -10);
        }
        
        update_post_meta($postId, '_wp_fasty_revisions', $revisions);
    }

    /**
     * Обработчик AJAX для восстановления ревизии
     */
    public function ajaxRestoreRevision(): void {
        check_ajax_referer('wp_fasty_ajax_nonce', 'nonce');
        
        $postId = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $template_file = isset($_POST['template_file']) ? $_POST['template_file'] : '';
        $data_file = isset($_POST['data_file']) ? $_POST['data_file'] : '';
        
        if (!$postId || empty($template_file) || empty($data_file)) {
            wp_send_json_error(['message' => 'Недостаточно данных']);
        }
        
        // Проверяем существование файлов
        if (!file_exists($template_file) || !file_exists($data_file)) {
            wp_send_json_error(['message' => 'Файлы ревизии не найдены']);
        }
        
        // Загружаем содержимое файлов
        $template = file_get_contents($template_file);
        $data = file_get_contents($data_file);
        
        // Отправляем данные
        wp_send_json_success([
            'template' => $template,
            'data' => $data
        ]);
    }

    /**
     * Проверяет, что путь к файлу находится в пределах директории темы
     */
    private function isPathSafe($path): bool {
        $theme_dir = wp_normalize_path(get_template_directory());
        $path = wp_normalize_path($path);
        
        return strpos($path, $theme_dir) === 0;
    }

    private function detectXssInTemplate($template): array {
        $warnings = [];
        
        // Проверка на потенциальные XSS-уязвимости
        if (preg_match('/<script\b[^>]*>(.*?)<\/script>/is', $template)) {
            $warnings[] = 'Шаблон содержит теги <script>, что может представлять угрозу безопасности.';
        }
        
        if (preg_match('/on\w+\s*=/i', $template)) {
            $warnings[] = 'Шаблон содержит обработчики событий (onclick, onload и т.д.), что может представлять угрозу безопасности.';
        }
        
        if (preg_match('/javascript:/i', $template)) {
            $warnings[] = 'Шаблон содержит javascript: URL, что может представлять угрозу безопасности.';
        }
        
        return $warnings;
    }

    private function getRelativePath($path): string {
        $theme_dir = get_template_directory();
        return str_replace($theme_dir, '[THEME]', $path);
    }
} 