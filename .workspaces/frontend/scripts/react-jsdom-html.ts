// cd .workspaces/frontend && bun scripts/react-jsdom-html.ts
import React from 'react';
import ReactDOMServer from 'react-dom/server';
import fs from 'node:fs';
import path from 'node:path';
import { JSDOM } from 'jsdom';
import {
  Article,
  ArticleHeader,
  ArticleTitle,
  ArticleMeta,
  ArticleTime,
  ArticleContent
} from "../src/uikits/ui8px/core/semantic/components/article";
import * as babel from '@babel/core';
import * as t from '@babel/types';

// Функция для получения HTML-шаблона компонента
function getComponentTemplate(Component: React.ComponentType<any>, props = {}) {
  const html = ReactDOMServer.renderToStaticMarkup(React.createElement(Component, props));
  const dom = new JSDOM(html);
  const element = dom.window.document.body.firstElementChild;

  if (!element) return null;

  return {
    tagName: element.tagName.toLowerCase(),
    attributes: Array.from(element.attributes).reduce((acc, attr) => {
      // Заменяем class на className для React
      const attrName = attr.name === 'class' ? 'className' : attr.name;
      acc[attrName] = attr.value;
      return acc;
    }, {} as Record<string, string>)
  };
}

// Получаем шаблоны компонентов
const componentTemplates = {
  Article: getComponentTemplate(Article),
  ArticleHeader: getComponentTemplate(ArticleHeader),
  ArticleTitle: getComponentTemplate(ArticleTitle),
  ArticleMeta: getComponentTemplate(ArticleMeta),
  ArticleTime: getComponentTemplate(ArticleTime, { dateTime: '' }),
  ArticleContent: getComponentTemplate(ArticleContent)
};

// Babel плагин для трансформации компонентов
const transformComponentsPlugin = () => {
  return {
    visitor: {
      JSXElement(path: any) {
        const element = path.node;
        const openingElement = element.openingElement;
        const closingElement = element.closingElement;

        // Проверяем, является ли элемент пользовательским компонентом
        if (t.isJSXIdentifier(openingElement.name)) {
          const componentName = openingElement.name.name;
          const template = componentTemplates[componentName as keyof typeof componentTemplates];

          if (template) {
            // Создаем новый JSX идентификатор для HTML-тега
            const htmlTag = t.jsxIdentifier(template.tagName);

            // Копируем все существующие атрибуты
            const attributes = [...openingElement.attributes];

            // Добавляем атрибуты из шаблона
            Object.entries(template.attributes).forEach(([name, value]) => {
              // Проверяем, есть ли уже такой атрибут
              const existingAttr = attributes.find(
                attr => t.isJSXAttribute(attr) && attr.name.name === name
              );

              if (!existingAttr) {
                attributes.push(
                  t.jsxAttribute(
                    t.jsxIdentifier(name),
                    t.stringLiteral(value)
                  )
                );
              }
            });

            // Заменяем имя открывающего элемента
            openingElement.name = htmlTag;
            openingElement.attributes = attributes;

            // Заменяем имя закрывающего элемента, если он есть
            if (closingElement) {
              closingElement.name = htmlTag;
            }
          }
        }
      },
      // Дополнительно заменяем все атрибуты class на className
      JSXAttribute(path: any) {
        if (t.isJSXIdentifier(path.node.name) && path.node.name.name === 'class') {
          path.node.name.name = 'className';
        }
      }
    }
  };
};

// Исходный JSX код
const sourceCode = `
const posts = [
  {
    id: 1,
    title: 'Post 1',
    date: { formatted: '2021-01-01', display: 'January 1, 2021' },
    excerpt: 'This is the excerpt for Post 1'
  }
];

{
  posts.map((post: any) =>
    <Article key={post.id}>
      <ArticleHeader>
        <ArticleTitle>{post.title}</ArticleTitle>
        <ArticleMeta>
          {post.date &&
            <ArticleTime dateTime={post.date.formatted}>{post.date.display}</ArticleTime>
          }
        </ArticleMeta>
      </ArticleHeader>
      <ArticleContent>
        {post.excerpt &&
          <p>{post.excerpt}</p>
        }
      </ArticleContent>
    </Article>
  )
}
`;

// Трансформируем код с помощью Babel
const result = babel.transformSync(sourceCode, {
  plugins: [transformComponentsPlugin],
  parserOpts: {
    plugins: ['jsx', 'typescript']
  }
});

// Записываем результат в файл
if (result && result.code) {
  fs.writeFileSync(
    path.join(__dirname, '../transformed-example.tsx'),
    result.code
  );
  console.log('Transformation complete!');
} else {
  console.error('Transformation failed!');
}