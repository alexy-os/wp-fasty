# Решение через Symfony UX React и конвертацию Twig в Latte

Вы предложили отличный подход! Использование существующих инструментов значительно упростит задачу:

1. Использовать Symfony UX React для создания React-компонентов и их рендеринга в Twig
2. Конвертировать сгенерированные Twig шаблоны в Latte с помощью инструментов Latte

## Шаг 1: Настройка Symfony UX React

```bash
cd .workspaces/ux-react
composer require symfony/ux-react
npm install -D @babel/preset-react --force
npm run watch
```

После установки Symfony UX React создаст все необходимые файлы для интеграции React с Twig.

## Шаг 2: Создание React компонентов

```jsx
// assets/react/controllers/HeroSplit.jsx
import React from 'react';
import { Button } from "./components/ui/Button";

export default function(props) {
  const { title, description, button, buttons, images } = props;

  return (
    <section className="hero">
      <div className="container">
        <div className="hero-content">
          <h1 className="hero-title">{title}</h1>
          <p className="hero-description">{description}</p>
          
          <div className="hero-actions">
            {button && (
              <a href={button.href} className={`button button-${button.variant} ${button.className}`}>
                {button.text}
              </a>
            )}
            
            {buttons?.map((button) => (
              <Button
                key={button.id}
                variant={button.variant}
                size={button.size}
                className={button.className}
                href={button.href}
              >
                {button.text}
              </Button>
            ))}
          </div>
        </div>
        
        <div className={images.grid.className}>
          {images.grid.items.map((image) => (
            <div key={image.id} className={image.className}>
              <img src={image.src} alt="" />
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}
```

## Шаг 3: Создание Twig шаблона с использованием React компонента

```twig
{# templates/hero-split.html.twig #}
<div {{ react_component('HeroSplit', {
    title: site.title,
    description: site.description,
    button: site.button,
    buttons: site.buttons,
    images: site.images
}) }}>
    Loading...
</div>
```

## Шаг 4: Установка инструмента конвертации Twig в Latte

```bash
composer create-project latte/tools
```

## Шаг 5: Создание автоматизированного процесса конвертации

Создадим скрипт, который будет автоматически конвертировать Twig шаблоны в Latte:

```php
<?php
// scripts/convert-templates.php

// Пути к шаблонам
$twigDir = __DIR__ . '/../templates';
$latteDir = __DIR__ . '/../src/templates/latte';

// Создаем директорию для Latte шаблонов, если она не существует
if (!is_dir($latteDir)) {
    mkdir($latteDir, 0755, true);
}

// Находим все Twig файлы
$twigFiles = glob($twigDir . '/*.html.twig');

foreach ($twigFiles as $twigFile) {
    $filename = basename($twigFile, '.html.twig');
    $latteFile = $latteDir . '/' . $filename . '.latte';
    
    // Конвертируем Twig в Latte
    exec("php vendor/latte/tools/twig-to-latte.php $twigFile $latteFile");
    
    // Дополнительная обработка для адаптации к нашему контексту
    $content = file_get_contents($latteFile);
    
    // Заменяем react_component на соответствующий синтаксис в Latte
    $content = preg_replace(
        '/\{\{\s*react_component\(\'([^\']+)\',\s*(\{[^}]+\})\s*\)\s*\}\}/',
        '{* React Component $1 *}',
        $content
    );
    
    // Заменяем обращения к переменным в стиле Twig на Latte
    $content = str_replace('{{ site.', '{$site.', $content);
    $content = str_replace(' }}', '}', $content);
    
    // Сохраняем обработанный файл
    file_put_contents($latteFile, $content);
    
    echo "Converted $twigFile to $latteFile\n";
}

echo "Conversion complete!\n";
```

## Шаг 6: Настройка процесса сборки

Создадим единый скрипт для выполнения всего процесса сборки:

```bash
#!/bin/bash
# scripts/build-templates.sh

# 1. Соберем React компоненты
npm run build

# 2. Создадим Twig шаблоны с React компонентами
# (это уже настроено через Symfony UX React)

# 3. Конвертируем Twig шаблоны в Latte
php scripts/convert-templates.php

# 4. Дополнительная пост-обработка Latte шаблонов (если нужно)
echo "Template generation complete!"
```

## Преимущества этого подхода:

1. **Использование проверенных инструментов**: Symfony UX React - это стабильный инструмент с хорошей документацией и поддержкой.

2. **Стандартизация**: Следуя документации Symfony UX React, мы придерживаемся проверенных подходов к интеграции React.

3. **Меньше ручного кода**: Нам не нужно писать сложные конвертеры с нуля.

4. **Надежность**: Инструменты для конвертации Twig в Latte уже существуют и оптимизированы.

5. **Гибкость**: Мы можем добавлять пост-обработку для адаптации к нашим специфическим требованиям.

## Возможные проблемы и решения:

1. **Синтаксис React-компонентов в Twig**: Нужно будет адаптировать атрибуты `react_component` в Latte. Для этого мы можем добавить дополнительные правила в нашем конвертере.

2. **Контекстные переменные**: Мы должны убедиться, что переменные правильно передаются между слоями (React → Twig → Latte).

3. **Вложенные компоненты**: При использовании вложенных React-компонентов могут потребоваться дополнительные настройки.

## Заключение

Этот подход позволяет нам использовать лучшее из обоих миров - мощь React и удобство шаблонизатора Latte. Мы можем создавать компоненты UI в React, а затем автоматически преобразовывать их в Latte шаблоны для использования в нашем проекте.

[Документация Symfony UX React](https://symfony.com/bundles/ux-react/current/index.html) предоставляет полную информацию о возможностях и настройках интеграции React в Twig.
