/**
 * Generates a 5-character crypto value from a quark name
 */
export function generateCryptoFromQuark(quark: string): string {
  // Удаляем префикс q- если он есть
  const cleanQuark = quark.replace(/^q-/, '');
  
  // Создаем хеш из строки
  let hash = 0;
  for (let i = 0; i < cleanQuark.length; i++) {
    const char = cleanQuark.charCodeAt(i);
    hash = ((hash << 5) - hash) + char;
    hash = hash & hash; // Convert to 32-bit integer
  }

  // Конвертируем хеш в строку из 5 символов (буквы и цифры в нижнем регистре)
  const alphabet = 'abcdefghijklmnopqrstuvwxyz0123456789';
  let crypto = '';
  
  // Используем позитивное значение хеша
  const positiveHash = Math.abs(hash);
  
  for (let i = 0; i < 5; i++) {
    const index = (positiveHash >> (i * 5)) & 31; // Берем по 5 бит для каждого символа
    crypto += alphabet[index % alphabet.length];
  }

  return crypto;
} 