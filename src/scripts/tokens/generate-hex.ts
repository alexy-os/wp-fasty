import fs from 'fs';
import path from 'path';
import * as ColorConvert from 'color-convert';

// Функция для преобразования HSL в HEX
function hslToHex(h: number, s: number, l: number): string {
  return `#${ColorConvert.hsl.hex([h, s, l])}`;
}

// Функция для преобразования строки HSL в HEX
function parseHslAndConvert(hslString: string): string {
  // Разбор строки типа "346.8 77.2% 49.8%"
  const [h, s, l] = hslString.split(' ').map(part => {
    // Удаляем % и приводим к числу
    return parseFloat(part.replace('%', ''));
  });
  
  return hslToHex(h, s, l);
}

// Загружаем дизайн-токены
const designJsonPath = path.join(process.cwd(), 'src', 'design.json');
const designData = JSON.parse(fs.readFileSync(designJsonPath, 'utf-8'));

// Создаем строки для tokens.scss
let scssContent = `// Автоматически сгенерированные токены из HSL в HEX
// Генерируются из design.json через src/scripts/tokens/generate-hex.ts

`;

// Конвертируем цвета из HSL в HEX для светлой темы
scssContent += `// Цвета фона и текста\n`;
scssContent += `$background: ${parseHslAndConvert(designData.color.background.light)}; // ${designData.color.background.light}\n`;
scssContent += `$foreground: ${parseHslAndConvert(designData.color.foreground.light)}; // ${designData.color.foreground.light}\n\n`;

// Цвета компонентов
scssContent += `// Цвета компонентов\n`;
scssContent += `$card: ${parseHslAndConvert(designData.color.card.light)}; // ${designData.color.card.light}\n`;
scssContent += `$card-foreground: ${parseHslAndConvert(designData.color["card-foreground"].light)}; // ${designData.color["card-foreground"].light}\n`;
scssContent += `$popover: ${parseHslAndConvert(designData.color.popover.light)}; // ${designData.color.popover.light}\n`;
scssContent += `$popover-foreground: ${parseHslAndConvert(designData.color["popover-foreground"].light)}; // ${designData.color["popover-foreground"].light}\n\n`;

// Основные и вторичные цвета
scssContent += `// Основные и вторичные цвета\n`;
scssContent += `$primary: ${parseHslAndConvert(designData.color.primary.light)}; // ${designData.color.primary.light}\n`;
scssContent += `$primary-foreground: ${parseHslAndConvert(designData.color["primary-foreground"].light)}; // ${designData.color["primary-foreground"].light}\n`;
scssContent += `$secondary: ${parseHslAndConvert(designData.color.secondary.light)}; // ${designData.color.secondary.light}\n`;
scssContent += `$secondary-foreground: ${parseHslAndConvert(designData.color["secondary-foreground"].light)}; // ${designData.color["secondary-foreground"].light}\n\n`;

// Дополнительные цвета
scssContent += `// Дополнительные цвета\n`;
scssContent += `$muted: ${parseHslAndConvert(designData.color.muted.light)}; // ${designData.color.muted.light}\n`;
scssContent += `$muted-foreground: ${parseHslAndConvert(designData.color["muted-foreground"].light)}; // ${designData.color["muted-foreground"].light}\n`;
scssContent += `$accent: ${parseHslAndConvert(designData.color.accent.light)}; // ${designData.color.accent.light}\n`;
scssContent += `$accent-foreground: ${parseHslAndConvert(designData.color["accent-foreground"].light)}; // ${designData.color["accent-foreground"].light}\n\n`;

// Цвета для ошибок/предупреждений
scssContent += `// Цвета для ошибок/предупреждений\n`;
scssContent += `$destructive: ${parseHslAndConvert(designData.color.destructive.light)}; // ${designData.color.destructive.light}\n`;
scssContent += `$destructive-foreground: ${parseHslAndConvert(designData.color["destructive-foreground"].light)}; // ${designData.color["destructive-foreground"].light}\n\n`;

// Служебные цвета
scssContent += `// Служебные цвета\n`;
scssContent += `$border: ${parseHslAndConvert(designData.color.border.light)}; // ${designData.color.border.light}\n`;
scssContent += `$input: ${parseHslAndConvert(designData.color.input.light)}; // ${designData.color.input.light}\n`;
scssContent += `$ring: ${parseHslAndConvert(designData.color.ring.light)}; // ${designData.color.ring.light}\n\n`;

// Добавляем CSS переменные
scssContent += `// Также определим CSS переменные для использования в HTML\n`;
scssContent += `:root {\n`;
scssContent += `  --background: \#{$background};\n`;
scssContent += `  --foreground: \#{$foreground};\n`;
scssContent += `  --card: \#{$card};\n`;
scssContent += `  --card-foreground: \#{$card-foreground};\n`;
scssContent += `  --popover: \#{$popover};\n`;
scssContent += `  --popover-foreground: \#{$popover-foreground};\n`;
scssContent += `  --primary: \#{$primary};\n`;
scssContent += `  --primary-foreground: \#{$primary-foreground};\n`;
scssContent += `  --secondary: \#{$secondary};\n`;
scssContent += `  --secondary-foreground: \#{$secondary-foreground};\n`;
scssContent += `  --muted: \#{$muted};\n`;
scssContent += `  --muted-foreground: \#{$muted-foreground};\n`;
scssContent += `  --accent: \#{$accent};\n`;
scssContent += `  --accent-foreground: \#{$accent-foreground};\n`;
scssContent += `  --destructive: \#{$destructive};\n`;
scssContent += `  --destructive-foreground: \#{$destructive-foreground};\n`;
scssContent += `  --border: \#{$border};\n`;
scssContent += `  --input: \#{$input};\n`;
scssContent += `  --ring: \#{$ring};\n`;
scssContent += `  --radius: ${designData.radius.default.value};\n`;
scssContent += `}\n\n`;

// Добавляем темную тему
scssContent += `// Темная тема\n`;
scssContent += `.dark {\n`;
scssContent += `  --background: ${parseHslAndConvert(designData.color.background.dark)}; // ${designData.color.background.dark}\n`;
scssContent += `  --foreground: ${parseHslAndConvert(designData.color.foreground.dark)}; // ${designData.color.foreground.dark}\n`;
scssContent += `  --card: ${parseHslAndConvert(designData.color.card.dark)}; // ${designData.color.card.dark}\n`;
scssContent += `  --card-foreground: ${parseHslAndConvert(designData.color["card-foreground"].dark)}; // ${designData.color["card-foreground"].dark}\n`;
scssContent += `  --popover: ${parseHslAndConvert(designData.color.popover.dark)}; // ${designData.color.popover.dark}\n`;
scssContent += `  --popover-foreground: ${parseHslAndConvert(designData.color["popover-foreground"].dark)}; // ${designData.color["popover-foreground"].dark}\n`;
scssContent += `  --primary: ${parseHslAndConvert(designData.color.primary.dark)}; // ${designData.color.primary.dark}\n`;
scssContent += `  --primary-foreground: ${parseHslAndConvert(designData.color["primary-foreground"].dark)}; // ${designData.color["primary-foreground"].dark}\n`;
scssContent += `  --secondary: ${parseHslAndConvert(designData.color.secondary.dark)}; // ${designData.color.secondary.dark}\n`;
scssContent += `  --secondary-foreground: ${parseHslAndConvert(designData.color["secondary-foreground"].dark)}; // ${designData.color["secondary-foreground"].dark}\n`;
scssContent += `  --muted: ${parseHslAndConvert(designData.color.muted.dark)}; // ${designData.color.muted.dark}\n`;
scssContent += `  --muted-foreground: ${parseHslAndConvert(designData.color["muted-foreground"].dark)}; // ${designData.color["muted-foreground"].dark}\n`;
scssContent += `  --accent: ${parseHslAndConvert(designData.color.accent.dark)}; // ${designData.color.accent.dark}\n`;
scssContent += `  --accent-foreground: ${parseHslAndConvert(designData.color["accent-foreground"].dark)}; // ${designData.color["accent-foreground"].dark}\n`;
scssContent += `  --destructive: ${parseHslAndConvert(designData.color.destructive.dark)}; // ${designData.color.destructive.dark}\n`;
scssContent += `  --destructive-foreground: ${parseHslAndConvert(designData.color["destructive-foreground"].dark)}; // ${designData.color["destructive-foreground"].dark}\n`;
scssContent += `  --border: ${parseHslAndConvert(designData.color.border.dark)}; // ${designData.color.border.dark}\n`;
scssContent += `  --input: ${parseHslAndConvert(designData.color.input.dark)}; // ${designData.color.input.dark}\n`;
scssContent += `  --ring: ${parseHslAndConvert(designData.color.ring.dark)}; // ${designData.color.ring.dark}\n`;
scssContent += `}\n`;

// Записываем результат в файл
const outputPath = path.join(process.cwd(), 'wp-fasty-storefront', 'assets', 'scss', 'tokens.scss');
fs.writeFileSync(outputPath, scssContent, 'utf-8');

console.log(`Токены успешно сгенерированы в ${outputPath}`); 