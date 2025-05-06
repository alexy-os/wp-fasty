/**
 * Проверяет синтаксис React-компонента на соответствие стандартам
 */
export function validateReactSyntax(code: string): string[] {
  const errors: string[] = [];

  // Проверка корректности .map()
  const mapRegex = /\.map\(\(([^,\)]+)(?::\s*([^,\)]+))?(?:,\s*([^)]+))?\)\s*=>/g;
  let match;

  while ((match = mapRegex.exec(code)) !== null) {
    const [fullMatch, paramType, paramName, indexParam] = match;

    // Проверка формата параметра: должен быть один из допустимых с типом any

    if (!paramType.includes(': any')) {
      //if (!paramType || paramType !== 'any') {
      errors.push(`Параметр map должен иметь тип ": any" в: ${fullMatch.slice(0, 30)}...`);
    }

    // Допустимые имена параметров (post, category, item и т.д.)
    /*const validParamNames = ['post', 'category', 'item', 'menu', 'product', 'page'];
    if (!validParamNames.includes(paramName)) {
      errors.push(`Имя параметра map должно быть одним из (${validParamNames.join(', ')}) в: ${fullMatch.slice(0, 30)}...`);
    }*/

    // Предупреждение о наличии индекса
    if (indexParam) {
      errors.push(`Не рекомендуется использовать индекс в map, используйте key={${paramName}.id}: ${fullMatch.slice(0, 30)}...`);
    }
  }

  // Проверка наличия key={*.id} в map
  /*const mapWithoutKeyRegex = /\.map\(\(([^,\)]+)[^\)]*\)\s*=>\s*<[^>]*(?!key=\{\1\.id\})[^>]*>/g;
  while ((match = mapWithoutKeyRegex.exec(code)) !== null) {
    const [fullMatch, paramName] = match;
    errors.push(`Отсутствует key={${paramName}.id} в map: ${fullMatch.slice(0, 30)}...`);
  }*/

  // Проверка на избыточные проверки
  const redundantChecksRegex = /(\w+)\s+&&\s+\1\./g;
  while ((match = redundantChecksRegex.exec(code)) !== null) {
    errors.push(`Избыточная проверка: используйте просто проверку на существование переменной: ${match[0]}`);
  }

  // Проверка простой структуры props
  if (!code.match(/const \w+\s*=\s*\(\{\s*[^{}]*\}\)\s*=>/)) {
    errors.push('Компонент должен использовать деструктуризацию props: const Component = ({ prop1, prop2 }) => {...}');
  }

  return errors;
}
