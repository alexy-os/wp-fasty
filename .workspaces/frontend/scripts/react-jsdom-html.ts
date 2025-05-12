// cd .workspaces/frontend && bun scripts/react-jsdom-html.ts
import React from 'react';
import ReactDOMServer from 'react-dom/server';
import { JSDOM } from 'jsdom';
import fs from 'node:fs';
import path from 'node:path';
import {
  Article,
  ArticleHeader,
  ArticleTitle,
  ArticleMeta,
  ArticleTime,
  ArticleContent
} from "../src/uikits/ui8px/core/semantic/components/article";

// Функция для получения шаблона компонента
function getComponentTemplate(
  Component: React.ComponentType<any>,
  props: Record<string, any> = {}
): string {
  // Рендерим компонент в HTML
  const html = ReactDOMServer.renderToStaticMarkup(React.createElement(Component, props));

  // Создаем DOM из HTML
  const dom = new JSDOM(html);
  const element = dom.window.document.body.firstChild;

  if (!element) {
    return '';
  }

  // Получаем открывающий тег с атрибутами
  const tagName = element.nodeName.toLowerCase();
  const attributes: string[] = [];

  // Собираем атрибуты
  for (let i = 0; i < element.attributes.length; i++) {
    const attr = element.attributes[i];
    attributes.push(`${attr.name}="${attr.value}"`);
  }

  // Формируем открывающий тег
  return `<${tagName} ${attributes.join(' ')}>`;
}

// Получаем шаблоны компонентов
console.log('Generating component templates...');

const templates: Record<string, string> = {
  Article: getComponentTemplate(Article),
  ArticleHeader: getComponentTemplate(ArticleHeader),
  ArticleTitle: getComponentTemplate(ArticleTitle),
  ArticleMeta: getComponentTemplate(ArticleMeta),
  ArticleTime: getComponentTemplate(ArticleTime, { dateTime: '' }),
  ArticleContent: getComponentTemplate(ArticleContent)
};

console.log('Templates generated:');
console.log(templates);

// Пример трансформированного JSX
const transformedJSX = `
{posts.map((post: any) =>
  ${templates.Article.replace('>', '')} key={post.id}>
    ${templates.ArticleHeader}
      ${templates.ArticleTitle}{post.title}</h2>
      ${templates.ArticleMeta}
        {post.date &&
          ${templates.ArticleTime.replace('datetime=""', 'dateTime={post.date.formatted}')}>
            {post.date.display}
          </time>
        }
      </div>
    </header>
    ${templates.ArticleContent}
      {post.excerpt &&
        <p>{post.excerpt}</p>
      }
    </div>
  </article>
)}`;

console.log('\nTransformed JSX:');
console.log(transformedJSX);

// Записываем результат в файл
const outputPath = path.join(process.cwd(), 'transformed-example.jsx');
fs.writeFileSync(outputPath, transformedJSX);
console.log(`\nResult saved to ${outputPath}`);