import { SiteContext, MenuItem, PostItem } from './shared-types';

/* Auto-generated from context.schema.json - ArchiveContext */

export interface ArchiveContext {
  site: SiteContext;
  archive: {
    title: string;
    description: string;
  };
  posts: PostItem[];
}
