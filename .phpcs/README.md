Скрипт `phpcs-wrapper.php` используется для запуска PHP Code Sniffer без загрузки функций WordPress.

Чтобы использовать:

1. Запустите в командной строке:
```bash
php wp-fasty-ym/phpcs-wrapper.php [параметры] [пути]
```

2. Доступные параметры:
   - `--standard=название_стандарта` - выбор стандарта кодирования (WordPress, PSR12 и т.д.)
   - `--extensions=php` - проверка только PHP-файлов
   - `-p` - отображение прогресса
   - `-v` - подробный вывод

Например:
```bash
php wp-fasty-ym/phpcs-wrapper.php --standard=WordPress --extensions=php -p ./wp-content
```
