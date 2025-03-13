/**
 * Generates a 5-character crypto value from a quark name
 */
export function generateCryptoFromQuark(quark: string): string {
  // Remove the q- prefix if it exists
  const cleanQuark = quark.replace(/^q-/, '');
  
  // Create hash from string
  let hash = 0;
  for (let i = 0; i < cleanQuark.length; i++) {
    const char = cleanQuark.charCodeAt(i);
    hash = ((hash << 5) - hash) + char;
    hash = hash & hash; // Convert to 32-bit integer
  }

  // Use only letters for the first character
  const alphabet = 'abcdefghijklmnopqrstuvwxyz';
  // Letters and numbers for the rest of the characters
  const fullAlphabet = 'abcdefghijklmnopqrstuvwxyz0123456789';
  
  // The first character is always a letter
  const firstChar = alphabet[Math.abs(hash) % alphabet.length];
  
  let crypto = firstChar;
  const positiveHash = Math.abs(hash);
  
  // Generate the remaining 4 characters
  for (let i = 1; i < 5; i++) {
    const index = (positiveHash >> (i * 5)) & 31;
    crypto += fullAlphabet[index % fullAlphabet.length];
  }

  return crypto;
} 