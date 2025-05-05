import { SiteContext, CategoryItem, MenuItem, FeaturedImage } from './shared-types';

/* Auto-generated from context.schema.json - PageContext */

export interface PageContext {
  site: SiteContext;
  page: {
    title: string;
    content: string;
    slug: string;
    url: string;
    id: number;
    excerpt: string;
    featuredImage: FeaturedImage[];
    thumbnail: null;
    meta: {
      _edit_last: string;
      _edit_lock: string;
    };
    categories: CategoryItem[];
    menu: {
      primary: {
        items: MenuItem[];
      };
    };
  };
}