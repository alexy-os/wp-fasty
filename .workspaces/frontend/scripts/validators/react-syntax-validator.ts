/**
 * Проверяет синтаксис React-компонента на соответствие стандартам
 */
export function validateReactSyntax(code: string): string[] {
  const errors: string[] = [];

  // Проверка корректности .map()
  const mapRegex = /\.map\(\(([^,\)]+)(?::\s*any)?(?:,\s*([^)]+)(?::\s*number)?)?\)\s*=>/g;
  let match;

  while ((match = mapRegex.exec(code)) !== null) {
    const [fullMatch, firstParam, secondParam] = match;

    if (!firstParam.includes(': any')) {
      errors.push(`Первый параметр map должен иметь тип ": any" в: ${fullMatch.slice(0, 30)}...`);
    }

    if (secondParam && !secondParam.includes(': number')) {
      errors.push(`Второй параметр map должен иметь тип ": number" в: ${fullMatch.slice(0, 30)}...`);
    }
  }

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
