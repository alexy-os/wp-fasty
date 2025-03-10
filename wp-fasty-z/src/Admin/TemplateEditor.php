<?php

namespace WPFasty\Admin;
use WPFasty\Core\Container;
class TemplateEditor
{
    private $container;
    public function __construct(Container $container)
    {
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
    public function saveTemplateData($postId): void
    {
        // Security checks (nonce, autosave, access rights)
        if (
            !isset($_POST['wp_fasty_template_nonce']) ||
            !wp_verify_nonce($_POST['wp_fasty_template_nonce'], 'wp_fasty_template_nonce')
        ) {
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
        // If the slug is empty (new post), use the ID
        if (empty($post_slug)) {
            $post_slug = 'id-' . $postId;
        }
        // Get the template data
        $template = isset($_POST['wp_fasty_template']) ? $_POST['wp_fasty_template'] : '';
        $templateData = isset($_POST['wp_fasty_template_data']) ? $_POST['wp_fasty_template_data'] : '{}';
        // Validate and sanitize the data
        $template = $this->sanitizeTemplate($template);
        $templateData = $this->sanitizeJsonData($templateData);
        // Create directories if they don't exist
        $templates_dir = get_template_directory() . '/templates/' . $post_type . 's';
        $data_dir = get_template_directory() . '/data/' . $post_type . 's';
        if (!file_exists($templates_dir)) {
            wp_mkdir_p($templates_dir);
        }
        if (!file_exists($data_dir)) {
            wp_mkdir_p($data_dir);
        }
        // Save the template to a file
        $template_file = $templates_dir . '/' . $post_slug . '.hbs';
        file_put_contents($template_file, $template);
        // Save the data to a file
        $data_file = $data_dir . '/' . $post_slug . '.json';
        file_put_contents($data_file, $templateData);
        // Save only the file paths in the metadata
        update_post_meta($postId, '_wp_fasty_template_file', $template_file);
        update_post_meta($postId, '_wp_fasty_data_file', $data_file);
        // Generate static HTML
        if (!empty($template)) {
            $this->generateStaticHtml($postId, $template, $templateData);
        }
        // Save the revision
        $this->saveRevision($postId, $template, $templateData);
    }
    /**
     * Sanitize the Handlebars template
     */
    private function sanitizeTemplate($template)
    {
        // Limit the template size (e.g., 100 KB)
        if (strlen($template) > 102400) {
            $template = substr($template, 0, 102400);
        }
        // Basic sanitization
        $template = wp_check_invalid_utf8($template);
        return $template;
    }
    /**
     * Sanitize JSON data
     */
    private function sanitizeJsonData($jsonData)
    {
        // Limit the data size (e.g., 1 MB)
        if (strlen($jsonData) > 1048576) {
            return '{"error": "Data size exceeds limit"}';
        }
        // Check if this is a valid JSON
        $data = json_decode($jsonData, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            // If the JSON is invalid, return an empty object
            return '{}';
        }
        // Recursive sanitization of data
        $sanitizedData = $this->sanitizeArray($data);
        // Return the sanitized JSON
        return json_encode($sanitizedData, JSON_PRETTY_PRINT);
    }
    /**
     * Recursive sanitization of the data array
     */
    private function sanitizeArray($array)
    {
        $result = [];
        foreach ($array as $key => $value) {
            // Sanitize the key
            $key = sanitize_text_field($key);
            if (is_array($value)) {
                // Recursive sanitization of nested arrays
                $result[$key] = $this->sanitizeArray($value);
            } else if (is_string($value)) {
                // Sanitize string values
                $result[$key] = sanitize_text_field($value);
            } else {
                // Other data types (numbers, boolean values, etc.)
                $result[$key] = $value;
            }
        }
        return $result;
    }
    private function generateStaticHtml($postId, $template, $templateData): void
    {
        $post = get_post($postId);
        $post_type = $post->post_type;
        $post_slug = $post->post_name;
        // If the slug is empty (new post), use the ID
        if (empty($post_slug)) {
            $post_slug = 'id-' . $postId;
        }
        // Get the post data for the template
        $postData = $this->getPostData($postId);
        // Merge with user data
        $customData = json_decode($templateData, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $customData = [];
        }
        $data = array_merge($postData, $customData);
        try {
            // Create a relative path to the file
            $relative_path = '/~app/static/' . $post_type . 's/' . $post_slug;
            $static_dir = get_template_directory() . $relative_path;
            if (!file_exists($static_dir)) {
                wp_mkdir_p($static_dir);
            }
            // Save the data for later rendering
            $template_data = [
                'template' => $template,
                'data' => $data
            ];
            // Save the data to a JSON file
            file_put_contents($static_dir . '/template-data.json', json_encode($template_data, JSON_PRETTY_PRINT));
            // Create a simple HTML file with Handlebars
            $html = $this->createStaticHtmlWrapper($template, $data);
            // Save the HTML to a file
            file_put_contents($static_dir . '/index.php', $html);
            // Save the relative path in the metadata
            update_post_meta($postId, '_wp_fasty_static_file', $relative_path . '/index.php');
        } catch (\Exception $e) {
            // Log the error
            error_log('Error generating HTML: ' . $e->getMessage());
        }
    }
    /**
     * Creates an HTML wrapper for a static file
     */
    private function createStaticHtmlWrapper($template, $data): string
    {
        // Render the template on the admin side
        $json_data = json_encode($data, JSON_HEX_APOS | JSON_HEX_QUOT);
        $escaped_template = htmlspecialchars($template, ENT_QUOTES, 'UTF-8');
        
        // Return only the rendering result
        return <<<HTML
<div id="wp-fasty-content">
    {$this->renderHandlebarsTemplate($escaped_template, $json_data)}
</div>
HTML;
    }
    private function renderHandlebarsTemplate($template, $data): string
    {
        // Use PHP for template rendering
        ob_start();
        ?>
        <script>
            var template = Handlebars.compile(<?php echo json_encode($template); ?>);
            var data = <?php echo $data; ?>;
            document.write(template(data));
        </script>
        <?php
        return ob_get_clean();
    }
    private function getPostData($postId): array
    {
        $post = get_post($postId);
        // Basic post data
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
        // Add custom fields
        $meta = get_post_meta($postId);
        foreach ($meta as $key => $values) {
            if (substr($key, 0, 1) !== '_') {
                $data['page'][$key] = maybe_unserialize($values[0]);
            }
        }
        return $data;
    }
    /**
     * Adds a menu item to the admin panel
     */
    public function addMenuPage(): void
    {
        add_menu_page(
            'Manage templates',
            'Templates',
            'manage_options',
            'wp-fasty-templates',
            [$this, 'renderTemplatesPage'],
            'dashicons-layout',
            30
        );
    }
    /**
     * Displays the templates management page
     */
    public function renderTemplatesPage(): void
    {
?>
        <div class="wrap">
            <h1>Manage templates</h1>
            <p>Here you can manage templates for your site.</p>
            <div class="card">
                <h2>Instructions</h2>
                <p>To create and edit templates:</p>
                <ol>
                    <li>Open the page or post for editing</li>
                    <li>Find the "Template Editor" meta box</li>
                    <li>Create a template using the Handlebars syntax</li>
                    <li>Click "Update preview" to view the result</li>
                    <li>Save the page to apply the template</li>
                </ol>
            </div>
        </div>
    <?php
    }
    public function enqueueScripts($hook): void
    {
        $screens = ['post.php', 'post-new.php'];
        if (!in_array($hook, $screens)) {
            return;
        }
        // Register scripts
        wp_enqueue_script(
            'handlebars',
            'https://cdn.jsdelivr.net/npm/handlebars@latest/dist/handlebars.min.js',
            [],
            '4.7.8'
        );
        // Change the way Monaco Editor is loaded
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
        // Styles
        wp_enqueue_style(
            'wp-fasty-template-editor',
            get_template_directory_uri() . '/assets/css/template-editor.css',
            [],
            '1.0.0'
        );
    }
    public function addTemplateMetaBox(): void
    {
        add_meta_box(
            'wp-fasty-template',
            'Template editor',
            [$this, 'renderTemplateMetaBox'],
            ['page', 'post'],
            'normal',
            'high'
        );
    }
    public function renderTemplateMetaBox($post): void
    {
        wp_nonce_field('wp_fasty_template_nonce', 'wp_fasty_template_nonce');
        // Get the file paths
        $template_file = get_post_meta($post->ID, '_wp_fasty_template_file', true);
        $data_file = get_post_meta($post->ID, '_wp_fasty_data_file', true);
        // Define the slug of the page
        $post_slug = $post->post_name;
        if (empty($post_slug)) {
            $post_slug = 'id-' . $post->ID;
        }
        // Check if the files exist by slug, if the paths are not saved
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
        // Load the template from the file if it exists
        if ($template_file && file_exists($template_file)) {
            $template = file_get_contents($template_file);
        } else {
            // Otherwise, try to load from the meta field or use an empty string
            $template = get_post_meta($post->ID, '_wp_fasty_template', true) ?: '';
        }
        // Load the data from the file if it exists
        if ($data_file && file_exists($data_file)) {
            $templateData = file_get_contents($data_file);
        } else {
            // Otherwise, try to load from the meta field or use an empty object
            $templateData = get_post_meta($post->ID, '_wp_fasty_template_data', true) ?: '{}';
        }
        // Render the editor interface
    ?>
        <div class="wp-fasty-template-editor">
            <input type="hidden" name="wp_fasty_template" id="wp_fasty_template" value="<?php echo esc_attr($template); ?>">
            <input type="hidden" name="wp_fasty_template_data" id="wp_fasty_template_data" value="<?php echo esc_attr($templateData); ?>">
            <div class="wp-fasty-tabs">
                <button type="button" class="wp-fasty-tab active" data-tab="template">Template</button>
                <button type="button" class="wp-fasty-tab" data-tab="data">Data</button>
                <button type="button" class="wp-fasty-tab" data-tab="preview">Preview</button>
                <button type="button" class="wp-fasty-tab" data-tab="fields">Fields</button>
                <button type="button" class="wp-fasty-tab" data-tab="revisions">Revisions</button>
            </div>
            <div class="wp-fasty-tab-content active" data-tab="template">
                <div id="template-editor" style="height: 400px; width: 100%;"></div>
                <div class="template-actions">
                    <button type="button" id="analyze-template" class="button">Analyze template</button>
                    <button type="button" id="save-draft" class="button">Save draft</button>
                    <button type="button" id="load-draft" class="button">Load draft</button>
                </div>
            </div>
            <div class="wp-fasty-tab-content" data-tab="data">
                <div id="data-editor" style="height: 400px; width: 100%;"></div>
            </div>
            <div class="wp-fasty-tab-content" data-tab="preview">
                <div id="wp_fasty_preview" class="preview-container">
                    <div class="preview-placeholder">Click "Update preview"</div>
                </div>
                <div class="preview-actions">
                    <button type="button" id="wp_fasty_preview_button" class="button button-primary">Update preview</button>
                    <button type="button" id="wp_fasty_save_html" class="button">Save HTML</button>
                </div>
            </div>
            <div class="wp-fasty-tab-content" data-tab="fields">
                <div id="template-fields" class="template-fields">
                    <div class="fields-loading">Loading fields...</div>
                </div>
                <div class="fields-actions">
                    <button type="button" id="create-missing-fields" class="button button-primary">Create missing fields</button>
                </div>
            </div>
            <div class="wp-fasty-tab-content" data-tab="revisions">
                <div id="template-revisions" class="template-revisions">
                    <?php $this->renderRevisions($post->ID); ?>
                </div>
            </div>
            <div class="template-info">
                <?php if ($template_file && file_exists($template_file)): ?>
                    <p><strong>Template file:</strong> <?php echo esc_html($this->getRelativePath($template_file)); ?></p>
                <?php endif; ?>
                <?php if ($data_file && file_exists($data_file)): ?>
                    <p><strong>Data file:</strong> <?php echo esc_html($this->getRelativePath($data_file)); ?></p>
                <?php endif; ?>
            </div>
        </div>
<?php
    }
    /**
     * Displays the list of template revisions
     */
    private function renderRevisions($postId): void
    {
        $revisions = get_post_meta($postId, '_wp_fasty_revisions', true) ?: [];
        if (empty($revisions)) {
            echo '<p>Revisions not found.</p>';
            return;
        }
        // Sort revisions by time (newest on top)
        usort($revisions, function ($a, $b) {
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
            echo '<button type="button" class="button restore-revision" data-template="' . esc_attr($template_file) . '" data-data="' . esc_attr($data_file) . '">Restore</button>';
            echo '</div>';
            echo '</li>';
        }
        echo '</ul>';
    }
    public function ajaxGetPostData(): void
    {
        check_ajax_referer('wp_fasty_ajax_nonce', 'nonce');
        $postId = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        if (!$postId) {
            wp_send_json_error(['message' => 'ID поста не указан']);
        }
        // Check access rights
        if (!current_user_can('edit_post', $postId)) {
            wp_send_json_error(['message' => 'Not enough rights to edit']);
            return;
        }
        $post = get_post($postId);
        if (!$post) {
            wp_send_json_error(['message' => 'Post not found']);
        }
        // Basic post data
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
        // Add custom fields
        $meta = get_post_meta($postId);
        foreach ($meta as $key => $values) {
            if (substr($key, 0, 1) !== '_') {
                $data['page'][$key] = maybe_unserialize($values[0]);
            }
        }
        // Add taxonomies
        $taxonomies = get_object_taxonomies($post->post_type);
        $data['taxonomies'] = [];
        foreach ($taxonomies as $taxonomy) {
            $terms = get_the_terms($postId, $taxonomy);
            if ($terms && !is_wp_error($terms)) {
                $data['taxonomies'][$taxonomy] = array_map(function ($term) {
                    return [
                        'id' => $term->term_id,
                        'name' => $term->name,
                        'slug' => $term->slug
                    ];
                }, $terms);
            }
        }
        // Add images
        if (has_post_thumbnail($postId)) {
            $data['page']['featured_image'] = [
                'thumbnail' => get_the_post_thumbnail_url($postId, 'thumbnail'),
                'medium' => get_the_post_thumbnail_url($postId, 'medium'),
                'large' => get_the_post_thumbnail_url($postId, 'large'),
                'full' => get_the_post_thumbnail_url($postId, 'full')
            ];
        }
        // Add menus
        $locations = get_nav_menu_locations();
        $data['menus'] = [];
        foreach ($locations as $location => $menu_id) {
            if ($menu_id) {
                $menu_items = wp_get_nav_menu_items($menu_id);
                if ($menu_items) {
                    $data['menus'][$location] = array_map(function ($item) {
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
    public function ajaxSaveTemplateJson(): void
    {
        check_ajax_referer('wp_fasty_ajax_nonce', 'nonce');
        $postId = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $html = isset($_POST['html']) ? $_POST['html'] : '';
        $templateData = isset($_POST['template_data']) ? $_POST['template_data'] : '{}';
        if (!$postId) {
            wp_send_json_error(['message' => 'ID поста не указан']);
        }
        // Save HTML
        update_post_meta($postId, '_wp_fasty_static_html', $html);
        // Save template data
        update_post_meta($postId, '_wp_fasty_template_data', $templateData);
        // Save to file
        $this->saveStaticFile($postId, $html);
        wp_send_json_success(['message' => 'Data saved successfully']);
    }
    public function ajaxAnalyzeTemplate(): void
    {
        check_ajax_referer('wp_fasty_ajax_nonce', 'nonce');
        $postId = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $template = isset($_POST['template']) ? $_POST['template'] : '';
        if (!$postId || empty($template)) {
            wp_send_json_error(['message' => 'Not enough data']);
        }
        // Analyze template for variables
        $variables = $this->extractTemplateVariables($template);
        // Get existing fields
        $existingFields = $this->getExistingFields($postId);
        // Compare and determine missing fields
        $missingFields = $this->findMissingFields($variables, $existingFields);
        wp_send_json_success([
            'variables' => $variables,
            'existingFields' => $existingFields,
            'missingFields' => $missingFields
        ]);
    }
    public function ajaxCreateMissingFields(): void
    {
        check_ajax_referer('wp_fasty_ajax_nonce', 'nonce');
        $postId = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $fields = isset($_POST['fields']) ? json_decode(stripslashes($_POST['fields']), true) : [];
        if (!$postId || empty($fields)) {
            wp_send_json_error(['message' => 'Not enough data']);
        }
        $created = [];
        foreach ($fields as $field) {
            // Create field if it doesn't exist
            if (!metadata_exists('post', $postId, $field)) {
                update_post_meta($postId, $field, '');
                $created[] = $field;
            }
        }
        wp_send_json_success([
            'message' => 'Created fields: ' . count($created),
            'created' => $created
        ]);
    }
    private function extractTemplateVariables(string $template): array
    {
        $variables = [];
        // Regular expression to find Handlebars variables
        // Search for {{page.something}}, {{site.something}} etc.
        preg_match_all('/\{\{([^}]+)\}\}/', $template, $matches);
        if (!empty($matches[1])) {
            foreach ($matches[1] as $match) {
                // Clean from spaces and helpers
                $match = trim($match);
                // Skip helpers and blocks
                if (strpos($match, '#') === 0 || strpos($match, '/') === 0) {
                    continue;
                }
                // Skip expressions with spaces (probably helpers)
                if (strpos($match, ' ') !== false) {
                    continue;
                }
                // Add to the list of variables
                $variables[] = $match;
            }
        }
        // Filter only page.* variables
        $pageVariables = [];
        foreach ($variables as $variable) {
            if (strpos($variable, 'page.') === 0) {
                $field = substr($variable, 5); // Remove 'page.'
                $pageVariables[$field] = $variable;
            }
        }
        return $pageVariables;
    }
    private function getExistingFields(int $postId): array
    {
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
    private function findMissingFields(array $variables, array $existingFields): array
    {
        $missing = [];
        foreach ($variables as $field => $variable) {
            if (!isset($existingFields[$field])) {
                $missing[] = $field;
            }
        }
        return $missing;
    }
    private function saveStaticFile(int $postId, string $html): void
    {
        $post = get_post($postId);
        $relative_path = '/~app/static/' . $post->post_type . '/' . $post->post_name;
        $static_dir = get_template_directory() . $relative_path;
        if (!file_exists($static_dir)) {
            wp_mkdir_p($static_dir);
        }
        file_put_contents($static_dir . '/index.php', $html);
        update_post_meta($postId, '_wp_fasty_static_file', $relative_path . '/index.php');
    }
    /**
     * Saves the template revision and data
     */
    private function saveRevision($postId, $template, $templateData): void
    {
        // Create directories for revisions if they don't exist
        $templates_revisions_dir = get_template_directory() . '/~app/templates/revisions';
        $data_revisions_dir = get_template_directory() . '/~app/data/revisions';
        if (!file_exists($templates_revisions_dir)) {
            wp_mkdir_p($templates_revisions_dir);
        }
        if (!file_exists($data_revisions_dir)) {
            wp_mkdir_p($data_revisions_dir);
        }
        // Form the revision file name
        $revision_name = $postId . '-' . time();
        // Save the template revision
        $template_revision_file = $templates_revisions_dir . '/' . $revision_name . '.hbs';
        file_put_contents($template_revision_file, $template);
        // Save the data revision
        $data_revision_file = $data_revisions_dir . '/' . $revision_name . '.json';
        file_put_contents($data_revision_file, $templateData);
        // Save the revision information (only file paths)
        $revisions = get_post_meta($postId, '_wp_fasty_revisions', true) ?: [];
        $revisions[] = [
            'timestamp' => time(),
            'template_file' => $template_revision_file,
            'data_file' => $data_revision_file
        ];
        // Limit the number of revisions (e.g., 10 last)
        if (count($revisions) > 10) {
            $revisions = array_slice($revisions, -10);
        }
        update_post_meta($postId, '_wp_fasty_revisions', $revisions);
    }
    /**
     * AJAX handler for restoring a revision
     */
    public function ajaxRestoreRevision(): void
    {
        check_ajax_referer('wp_fasty_ajax_nonce', 'nonce');
        $postId = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $template_file = isset($_POST['template_file']) ? $_POST['template_file'] : '';
        $data_file = isset($_POST['data_file']) ? $_POST['data_file'] : '';
        if (!$postId || empty($template_file) || empty($data_file)) {
            wp_send_json_error(['message' => 'Not enough data']);
        }
        // Check the existence of files
        if (!file_exists($template_file) || !file_exists($data_file)) {
            wp_send_json_error(['message' => 'Revision files not found']);
        }
        // Load the contents of the files
        $template = file_get_contents($template_file);
        $data = file_get_contents($data_file);
        // Send the data
        wp_send_json_success([
            'template' => $template,
            'data' => $data
        ]);
    }
    /**
     * Checks that the file path is within the theme directory
     */
    private function isPathSafe($path): bool
    {
        $theme_dir = wp_normalize_path(get_template_directory());
        $path = wp_normalize_path($path);
        return strpos($path, $theme_dir) === 0;
    }
    private function getStaticPath()
    {
        return get_template_directory() . '/~app/static/index.php';
    }
    private function detectXssInTemplate($template): array
    {
        $warnings = [];
        // Check for potential XSS vulnerabilities
        if (preg_match('/<script\b[^>]*>(.*?)<\/script>/is', $template)) {
            $warnings[] = 'The template contains <script> tags, which may pose a security risk.';
        }
        if (preg_match('/on\w+\s*=/i', $template)) {
            $warnings[] = 'The template contains event handlers (onclick, onload, etc.), which may pose a security risk.';
        }
        if (preg_match('/javascript:/i', $template)) {
            $warnings[] = 'The template contains javascript: URLs, which may pose a security risk.';
        }
        return $warnings;
    }
    private function getRelativePath($path): string
    {
        $theme_dir = get_template_directory();
        return str_replace($theme_dir, '[THEME]', $path);
    }
}
