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
