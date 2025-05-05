/* Auto-generated shared types */

export interface CategoryItem {
  name: string;
  url: string;
  id: number;
  slug: string;
  description: string;
  count: number;
}

export interface MenuItem {
  title: string;
  url: string;
  id: number;
  order: number;
  parent: null | number;
  classes: string[];
  current: boolean;
}

export interface FeaturedImage {
  url: string;
  alt: string;
  width: number;
  height: number;
}

export interface PostItem {
  title: string;
  url: string;
  id: number;
  excerpt: string;
  thumbnail: FeaturedImage | null;
  categories: CategoryItem[];
  date: {
    formatted: string;
    display: string;
  };
}

export interface SiteContext {
  title: string;
  url: string;
  theme_url: string;
  lang: string;
  description: string;
  charset: string;
}
