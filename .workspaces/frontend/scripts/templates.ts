#!/usr/bin/env ts-node
import { generateTemplate } from './generate-template';

// Получаем имя компонента из аргументов командной строки
const componentName = process.argv[2];

if (!componentName) {
  console.error('Пожалуйста, укажите имя компонента');
  process.exit(1);
}

// Генерируем шаблон
generateTemplate(componentName);