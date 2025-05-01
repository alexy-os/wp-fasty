# Как использовать Symfony UX React для генерации Twig шаблонов

Давайте разберемся с процессом создания React компонентов для Symfony UX React и генерации из них Twig шаблонов. Я вижу, что вам не нужен рантайм, а только сам процесс сборки шаблонов.

## Шаг 1: Понимание архитектуры Symfony UX React

Согласно [документации Symfony UX React](https://symfony.com/bundles/ux-react/current/index.html), библиотека использует следующий подход:

1. React компоненты размещаются в директории `assets/react/controllers/`
2. Компоненты регистрируются через `registerReactControllerComponents`
3. В Twig шаблонах компоненты рендерятся через `react_component()`

## Шаг 2: Создание структуры проекта

Основываясь на вашем проекте `.workspaces/ux-react`, давайте создадим необходимую структуру:

```
.workspaces/ux-react/
├── src/
│   ├── assets/
│   │   ├── react/
│   │   │   └── controllers/  # React компоненты
│   │   └── app.js            # Точка входа для регистрации компонентов
│   └── templates/            # Twig шаблоны
│       └── twig/             # Шаблоны с React компонентами
├── scripts/
│   └── build-twig.js         # Скрипт для сборки шаблонов
└── dist/
    └── templates/            # Собранные шаблоны
```

## Шаг 3: Создание React компонента

Создадим компонент HeroSplit:

```jsx
// src/assets/react/controllers/HeroSplit.jsx
import React from 'react';

export default function HeroSplit(props) {
  const { title, description, button, buttons, images } = props;

  return (
    <section className="hero">
      <div className="container">
        <div className="hero-content">
          <h1 className="hero-title">{title}</h1>
          <p className="hero-description">{description}</p>
          
          <div className="hero-actions">
            {button && (
              <a 
                href={button.href} 
                className={`button button-${button.variant} ${button.className}`}
              >
                {button.text}
              </a>
            )}
            
            {buttons?.map((button) => (
              <a
                key={button.id}
                href={button.href}
                className={`button button-${button.variant} button-${button.size} ${button.className}`}
              >
                {button.text}
              </a>
            ))}
          </div>
        </div>
        
        <div className={images?.grid?.className}>
          {images?.grid?.items?.map((image) => (
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

## Шаг 4: Создание регистрации компонентов

```js
// src/assets/app.js
import { registerReactControllerComponents } from '@symfony/ux-react';

// Регистрируем все React компоненты из директории controllers
registerReactControllerComponents(require.context('./react/controllers', true, /\.(j|t)sx?$/));
```

## Шаг 5: Создание Twig шаблона

Создадим Twig шаблон, использующий React компонент:

```twig
{# src/templates/twig/hero-split.html.twig #}
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

## Шаг 6: Создание скрипта для генерации статического шаблона

Поскольку вам не нужен рантайм, а только шаблоны, мы можем создать скрипт, который будет генерировать статические Twig шаблоны на основе React компонентов:

```js
// scripts/build-twig.js
import fs from 'fs';
import path from 'path';
import React from 'react';
import { renderToString } from 'react-dom/server';

// Функция для получения всех компонентов
function getComponents() {
  const componentsDir = path.resolve('src/assets/react/controllers');
  const files = fs.readdirSync(componentsDir);
  
  const components = {};
  
  for (const file of files) {
    if (file.endsWith('.jsx') || file.endsWith('.tsx')) {
      const name = path.basename(file, path.extname(file));
      const componentPath = path.join(componentsDir, file);
      
      // Импортируем компонент
      const Component = require(componentPath).default;
      components[name] = Component;
    }
  }
  
  return components;
}

// Функция для создания Twig шаблона
function createTwigTemplate(componentName, Component) {
  // Создаем пример пропсов для компонента
  const mockProps = {
    title: '{{ site.title }}',
    description: '{{ site.description }}',
    button: {
      text: '{{ site.button.text }}',
      href: '{{ site.button.href }}',
      variant: '{{ site.button.variant }}',
      className: '{{ site.button.className }}'
    },
    buttons: [
      // В Twig это будет цикл
      '{% for button in site.buttons %}',
      {
        id: '{{ button.id }}',
        text: '{{ button.text }}',
        href: '{{ button.href }}',
        variant: '{{ button.variant }}',
        size: '{{ button.size }}',
        className: '{{ button.className }}'
      },
      '{% endfor %}'
    ],
    images: {
      grid: {
        className: '{{ site.images.grid.className }}',
        items: [
          // В Twig это будет цикл
          '{% for image in site.images.grid.items %}',
          {
            id: '{{ image.id }}',
            src: '{{ image.src }}',
            className: '{{ image.className }}'
          },
          '{% endfor %}'
        ]
      }
    }
  };
  
  // Рендерим компонент в строку
  const html = renderToString(<Component {...mockProps} />);
  
  // Создаем Twig шаблон
  const twigTemplate = `
{# Generated from React component ${componentName} #}
<div {{ react_component('${componentName}', {
    title: site.title,
    description: site.description,
    button: site.button,
    buttons: site.buttons,
    images: site.images
}) }}>
    ${html}
</div>
  `.trim();
  
  return twigTemplate;
}

// Основная функция сборки
function build() {
  const components = getComponents();
  const outputDir = path.resolve('dist/templates/twig');
  
  // Создаем выходную директорию
  if (!fs.existsSync(outputDir)) {
    fs.mkdirSync(outputDir, { recursive: true });
  }
  
  // Генерируем шаблоны для каждого компонента
  for (const [name, Component] of Object.entries(components)) {
    const template = createTwigTemplate(name, Component);
    const outputPath = path.join(outputDir, `${name.toLowerCase()}.html.twig`);
    
    fs.writeFileSync(outputPath, template);
    console.log(`Generated template: ${outputPath}`);
  }
  
  console.log('Build complete!');
}

// Запускаем сборку
build();
```

## Шаг 7: Создание скрипта сборки

Добавим скрипт в `package.json`:

```json
{
  "scripts": {
    "build:twig": "node scripts/build-twig.js"
  }
}
```

## Шаг 8: Сборка и генерация шаблонов

```bash
npm run build:twig
```

## Процесс работы Symfony UX React

В реальной среде Symfony UX React работает следующим образом:

1. При вызове `{{ react_component() }}` в Twig шаблоне, Symfony генерирует HTML с атрибутами данных:
   ```html
   <div data-react-component="HeroSplit" data-react-props="{&quot;title&quot;:&quot;Hello&quot;}">
       Loading...
   </div>
   ```

2. На клиенте JavaScript код `registerReactControllerComponents` находит эти элементы и инициализирует React компоненты с указанными пропсами.

3. React заменяет содержимое элемента на отрендеренный компонент.

## Специфика работы с Twig шаблонами

Тонкости работы с Twig шаблонами и React компонентами:

1. **Условные блоки**: В React используются выражения типа `{condition && <Element />}`, а в Twig это `{% if condition %}...{% endif %}`

2. **Циклы**: В React используются методы массивов типа `{items.map(item => <Element />)}`, а в Twig это `{% for item in items %}...{% endfor %}`

3. **Интерполяция строк**: В React используется `className={`button ${variant}`}`, а в Twig это `class="button {{ variant }}"`

4. **Доступ к свойствам**: В React используется точечная нотация `user.name`, а в Twig также работает точечная нотация `{{ user.name }}`

## Пример готового Twig шаблона

Вот как будет выглядеть готовый Twig шаблон для нашего HeroSplit компонента:

```twig
{# Generated from React component HeroSplit #}
<div {{ react_component('HeroSplit', {
    title: site.title,
    description: site.description,
    button: site.button,
    buttons: site.buttons,
    images: site.images
}) }}>
    <section class="hero">
      <div class="container">
        <div class="hero-content">
          <h1 class="hero-title">{{ site.title }}</h1>
          <p class="hero-description">{{ site.description }}</p>
          
          <div class="hero-actions">
            {% if site.button %}
              <a href="{{ site.button.href }}" class="button button-{{ site.button.variant }} {{ site.button.className }}">
                {{ site.button.text }}
              </a>
            {% endif %}
            
            {% for button in site.buttons %}
              <a
                href="{{ button.href }}"
                class="button button-{{ button.variant }} button-{{ button.size }} {{ button.className }}"
              >
                {{ button.text }}
              </a>
            {% endfor %}
          </div>
        </div>
        
        <div class="{{ site.images.grid.className }}">
          {% for image in site.images.grid.items %}
            <div class="{{ image.className }}">
              <img src="{{ image.src }}" alt="">
            </div>
          {% endfor %}
        </div>
      </div>
    </section>
</div>
```

Это шаблон, который можно использовать в Symfony приложении с UX React, и он будет работать как с JavaScript (клиентский рендеринг через React), так и без него (серверный рендеринг через Twig).

## Заключение

Symfony UX React предоставляет удобный способ интеграции React компонентов в Twig шаблоны. Для вашей задачи создания статических шаблонов можно использовать описанный выше подход с генерацией Twig шаблонов на основе React компонентов.

Основная идея заключается в создании React компонентов с сохранением синтаксиса и структуры Twig, а затем преобразовании их в шаблоны с помощью серверного рендеринга и последующей адаптации к синтаксису Twig.
