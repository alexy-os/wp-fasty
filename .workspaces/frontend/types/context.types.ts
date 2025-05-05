
/* Auto-generated from context.schema.json - Context */

import { PageContext } from './page-context';
import { ArchiveContext } from './archive-context';

/**
 * Объединенный тип контекста
 * Представляет либо PageContext, либо ArchiveContext в зависимости от типа запрашиваемой страницы
 */
export type Context = PageContext | ArchiveContext;

/**
 * Утилита для определения типа контекста
 */
export function isPageContext(context: Context): context is PageContext {
  return 'page' in context;
}

/**
 * Утилита для определения типа контекста
 */
export function isArchiveContext(context: Context): context is ArchiveContext {
  return 'archive' in context;
}
