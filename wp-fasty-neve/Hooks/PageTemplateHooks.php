<?php
namespace WPFasty\Hooks;

class PageTemplateHooks extends AbstractHooks {
    public function register(): void {
        // Простой режим работы - просто регистрируем шаблон
        $this->addAction('template_redirect', 'setupTemplate', 5);
    }

    public function setupTemplate(): void {
        // Проверяем, что мы на странице с нашим шаблоном
        if (is_page() && is_page_template('full-page-template.php')) {
            // Добавляем классы для использования стилей темы
            add_filter('body_class', function($classes) {
                $classes[] = 'wp-fasty-full-page';
                return $classes;
            });
        }
    }
} 