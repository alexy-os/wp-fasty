# Инструкция по настройке PHP_CodeSniffer и VS Code для WP-FastY темы

## 1. Установка необходимых компонентов

```bash
# Установка PHP_CodeSniffer
composer require squizlabs/php_codesniffer --dev

# Установка дополнительных стандартов
composer require wp-coding-standards/wpcs --dev
composer require slevomat/coding-standard --dev
composer require phpcompatibility/php-compatibility --dev
composer require escapestudios/symfony2-coding-standard --dev
```

## 2. Настройка Composer

Отредактируйте `composer.json`, чтобы избежать загрузки WordPress-функций вне WordPress:

```json
"autoload": {
    "psr-4": {
        "WPFasty\\": "classes/"
    }
},
"autoload-dev": {
    "files": [
        "includes/helpers.php"
    ]
}
```

## 3. Адаптация WordPress-зависимых файлов

Все файлы, использующие WordPress-функции (например, `helpers.php`), должны содержать проверку:

```php
// Run only in WordPress context
if (function_exists('add_filter')) {
    // WordPress-зависимый код
}
```

## 4. Настройка путей для стандартов PHPCS

```bash
vendor/bin/phpcs --config-set installed_paths vendor/slevomat/coding-standard,vendor/wp-coding-standards/wpcs,vendor/phpcompatibility/php-compatibility,vendor/escapestudios/symfony2-coding-standard
```

## 5. Файл конфигурации PHPCS

Создайте или отредактируйте `phpcs.xml` в корне проекта:

```xml
<?xml version="1.0"?>
<ruleset name="WPFasty">
    <!-- Основные правила -->
    <rule ref="PSR2" />
    
    <!-- Укажите правильные окончания строк -->
    <rule ref="Generic.Files.LineEndings">
        <properties>
            <property name="eolChar" value="\n"/>
        </properties>
    </rule>
    
    <!-- Другие правила -->
</ruleset>
```

## 6. Настройка VS Code

Создайте или обновите `.vscode/settings.json`:

```json
{
  "phpcs.enable": true,
  "phpcs.standard": "${workspaceFolder}/phpcs.xml",
  "phpcs.executablePath": "${workspaceFolder}/wp-fasty-ym/vendor/bin/phpcs",
  "phpcs.showSources": true,
  "phpcs.errorSeverity": 5,
  "phpcs.warningSeverity": 5,
  "phpcbf.enable": true,
  "phpcbf.executablePath": "${workspaceFolder}/wp-fasty-ym/vendor/bin/phpcbf",
  "phpcbf.standard": "${workspaceFolder}/phpcs.xml",
  "phpcbf.onsave": true,
  "files.eol": "\n",
  
  "phpSniffer.standard": "${workspaceFolder}/phpcs.xml",
  "phpSniffer.run": "onType",
  "phpSniffer.executablesFolder": "${workspaceFolder}/wp-fasty-ym/vendor/bin"
}
```

## 7. Установка расширений VS Code

Установите в VS Code следующие расширения:
- PHP Sniffer
- phpcs
- PHP CS Fixer

## 8. Скрипты для проверки и исправления кода

### Windows (`.bat`)

Создайте файл `check-code.bat`:
```batch
@echo off
echo Running PHP CodeSniffer...
vendor\bin\phpcs --standard=..\phpcs.xml classes
echo.
echo Done!
pause
```

Создайте файл `fix-code.bat`:
```batch
@echo off
echo Running PHP Code Beautifier and Fixer...
vendor\bin\phpcbf --standard=..\phpcs.xml classes
echo.
echo Done!
pause
```

### Unix/Linux/macOS (`.sh`)

Создайте файл `check-code.sh`:
```bash
#!/bin/bash
echo "Running PHP CodeSniffer..."
vendor/bin/phpcs --standard=../phpcs.xml classes
echo "Done!"
```

Создайте файл `fix-code.sh`:
```bash
#!/bin/bash
echo "Running PHP Code Beautifier and Fixer..."
vendor/bin/phpcbf --standard=../phpcs.xml classes
echo "Done!"
```

## 9. Полезные команды для тестирования

```bash
# Проверка версии PHPCS
vendor/bin/phpcs --version

# Список доступных стандартов
vendor/bin/phpcs -i

# Проверка синтаксиса PHP
php -l classes/Core/Container.php

# Проверка файла по стандарту
vendor/bin/phpcs --standard=../phpcs.xml classes/Core/Container.php

# Исправление файла по стандарту
vendor/bin/phpcbf --standard=../phpcs.xml classes/Core/Container.php

# Проверка всех PHP-файлов в директории
vendor/bin/phpcs --standard=../phpcs.xml classes

# Проверка с игнорированием некоторых правил
vendor/bin/phpcs --standard=../phpcs.xml --exclude=Generic.Files.LineLength classes/Core/Container.php

# Проверка с выводом только ошибок (не предупреждений)
vendor/bin/phpcs --standard=../phpcs.xml -n classes/Core/Container.php
```

## 10. Советы по устранению неполадок

1. **Проблема**: PHPCS не может найти стандарты  
   **Решение**: Убедитесь, что правильно настроен путь installed_paths

2. **Проблема**: Ошибки PHP при запуске PHPCS  
   **Решение**: Убедитесь, что ваши файлы с WordPress-функциями содержат условную логику

3. **Проблема**: PHPCS не находит ошибки в VS Code  
   **Решение**: Проверьте настройки VS Code и перезапустите редактор

4. **Проблема**: Неправильные окончания строк  
   **Решение**: Используйте команду `dos2unix` или настройте Git для автоматической конвертации

5. **Проблема**: PHPCS выдает ошибки для стандартов PHP 8+  
   **Решение**: Настройте версию PHP в phpcs.xml в соответствии с вашей версией

Следуя этой инструкции, вы получите полностью настроенный рабочий процесс для контроля качества кода в проекте WP-FastY с использованием PHP_CodeSniffer и VS Code.
