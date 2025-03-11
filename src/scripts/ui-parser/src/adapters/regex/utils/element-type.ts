import { PatternContextType } from '../types';

export function determineElementType(
  content: string,
  position: number,
  contextType: PatternContextType
): string {
  if (contextType === 'jsx' || contextType === 'template' || contextType === 'dynamic') {
    const beforeContent = content.substring(0, position);
    const tagMatch = beforeContent.match(/<([a-zA-Z][a-zA-Z0-9]*)(?:\s|>)[^<]*$/);
    
    if (tagMatch) {
      return tagMatch[1].toLowerCase();
    }
  }
  
  if (contextType === 'const' || contextType === 'config') {
    const contextWindow = content.substring(Math.max(0, position - 200), position);
    
    if (contextWindow.match(/Button|btn|button/i)) return 'button';
    if (contextWindow.match(/Link|anchor|a>/i)) return 'a';
    if (contextWindow.match(/heading|h[1-6]/i)) return 'h2';
    if (contextWindow.match(/paragraph|p>/i)) return 'p';
    if (contextWindow.match(/image|img/i)) return 'img';
  }
  
  return 'div';
} 