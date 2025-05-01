import { convertReactToLatte } from '../src/tools/react-to-latte';

// Конвертируем все компоненты с суффиксом Latte
convertReactToLatte(
  'src/uikits/latte/**/*Latte.tsx',
  'src/templates/latte',
  'site'
);

console.log('Генерация Latte шаблонов завершена!');
