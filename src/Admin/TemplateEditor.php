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
        
        $template = get_post_meta($post->ID, '_wp_fasty_template', true) ?: '';
        $templateData = get_post_meta($post->ID, '_wp_fasty_template_data', true) ?: '{}';
        
        ?>
        <div class="wp-fasty-template-editor">
            <div class="wp-fasty-tabs">
                <button class="wp-fasty-tab active" data-tab="template">Шаблон</button>
                <button class="wp-fasty-tab" data-tab="data">Данные</button>
                <button class="wp-fasty-tab" data-tab="preview">Предпросмотр</button>
                <button class="wp-fasty-tab" data-tab="fields">Поля</button>
            </div>
            
            <div class="wp-fasty-tab-content active" data-tab="template">
                <div id="template-editor" style="height: 400px; width: 100%;"></div>
                <textarea name="wp_fasty_template" id="wp_fasty_template" style="display: none;"><?php echo esc_textarea($template); ?></textarea>
                <div class="template-actions">
                    <button type="button" id="analyze-template" class="button">Анализировать шаблон</button>
                </div>
            </div>
            
            <div class="wp-fasty-tab-content" data-tab="data">
                <div id="data-editor" style="height: 400px; width: 100%;"></div>
                <textarea name="wp_fasty_template_data" id="wp_fasty_template_data" style="display: none;"><?php echo esc_textarea($templateData); ?></textarea>
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
        </div>
        <?php
    }
    
    public function ajaxGetPostData(): void {
        check_ajax_referer('wp_fasty_ajax_nonce', 'nonce');
        
        $postId = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        if (!$postId) {
            wp_send_json_error(['message' => 'ID поста не указан']);
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
     * Сохраняет данные шаблона при сохранении поста
     *
     * @param int $postId ID поста
     */
    public function saveTemplateData($postId): void {
        // Проверка nonce
        if (!isset($_POST['wp_fasty_template_nonce']) || 
            !wp_verify_nonce($_POST['wp_fasty_template_nonce'], 'wp_fasty_template_nonce')) {
            return;
        }
        
        // Проверка автосохранения
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Проверка прав доступа
        if (!current_user_can('edit_post', $postId)) {
            return;
        }
        
        // Сохранение шаблона
        if (isset($_POST['wp_fasty_template'])) {
            update_post_meta($postId, '_wp_fasty_template', $_POST['wp_fasty_template']);
        }
        
        // Сохранение данных шаблона
        if (isset($_POST['wp_fasty_template_data'])) {
            update_post_meta($postId, '_wp_fasty_template_data', $_POST['wp_fasty_template_data']);
        }
        
        // Генерация статичного HTML
        if (isset($_POST['wp_fasty_template']) && !empty($_POST['wp_fasty_template'])) {
            $this->generateStaticHtml($postId);
        }
    }
    
    /**
     * Генерирует статичный HTML на основе шаблона
     *
     * @param int $postId ID поста
     */
    private function generateStaticHtml(int $postId): void {
        $template = get_post_meta($postId, '_wp_fasty_template', true);
        if (empty($template)) {
            return;
        }
        
        // Здесь можно добавить логику для генерации HTML
        // Но пока просто сохраним пустую строку, чтобы избежать ошибок
        update_post_meta($postId, '_wp_fasty_static_html', '');
    }
} 