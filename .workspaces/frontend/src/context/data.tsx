import { WPFastyContext } from './types/wpfasty';

export const site: WPFastyContext['site'] = {
  title: 'My Site',
  description: 'My Site Description',
  url: 'https://my-site.com',
  theme_url: 'https://my-site.com/theme',
  lang: 'en',
  charset: 'UTF-8',
};

export const menu: WPFastyContext['menu'] = {
  primary: {
    items: [
      {
        title: 'Home',
        url: '/',
        id: 1,
        order: 1,
        parent: null,
        classes: [],
        current: true,
      },
      {
        title: 'Blog',
        url: '/blog',
        id: 2,
        order: 2,
        parent: null,
        classes: [],
        current: false,
      },
      {
        title: 'About',
        url: '/about',
        id: 3,
        order: 3,
        parent: null,
        classes: [],
        current: false,
      },
    ]
  }
};

export const placeholderImage = "data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjxyZWN0IHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIGZpbGw9IiM2NjY2NjYiLz48L3N2Zz4=";

type PostPage = WPFastyContext['page']['page'];

export const page: PostPage =
{
  title: 'Welcome to My Site',
  content: 'My Page Content',
  slug: 'my-page',
  url: 'https://my-site.com/my-page',
  id: 1,
  excerpt: 'My Page Excerpt',
  featuredImage: {
    url: placeholderImage,
    width: 1000,
    height: 1000,
    alt: 'My Page Featured Image',
  },
  thumbnail: {
    url: placeholderImage,
    width: 1000,
    height: 1000,
    alt: 'My Page Thumbnail',
  },
  meta: {
    _edit_last: '1',
    _edit_lock: '1',
  },
  categories: [],
  date: {
    formatted: '2025-05-01',
    display: '1 May 2025',
    modified: '2025-05-02',
    modified_display: '2 May 2025',
    timestamp: 1690548000,
    year: '2025',
    month: '05',
    day: '01'
  }
};

export const archive: WPFastyContext['archive']['archive'] = {
  title: 'My Archive',
  description: 'My Archive Description',
};

export const posts: WPFastyContext['archive']['posts'][] = [
  {
    title: 'My Post 1',
    content: 'My Post 1 Content',
    slug: 'my-post-1',
    url: 'https://my-site.com/my-post-1',
    id: 1,
    excerpt: 'My Post 1 Excerpt',
    featuredImage: {
      url: placeholderImage,
      width: 1000,
      height: 1000,
      alt: 'My Post Featured Image',
    },
    thumbnail: {
      url: placeholderImage,
      width: 1000,
      height: 1000,
      alt: 'My Post Thumbnail',
    },
    meta: {
      _edit_last: '1',
      _edit_lock: '1',
    },
    categories: [
      {
        name: 'My Category',
        url: 'https://my-site.com/my-category',
        id: 1,
        slug: 'my-category',
        description: 'My Category Description',
        count: 10,
      },
    ],
    date: {
      formatted: '2025-05-01',
      display: '1 May 2025',
      modified: '2025-05-02',
      modified_display: '2 May 2025',
      timestamp: 1690548000,
      year: '2025',
      month: '05',
      day: '01'
    }
  },
  {
    title: 'My Post 2',
    content: 'My Post 2 Content',
    slug: 'my-post-2',
    url: 'https://my-site.com/my-post-2',
    id: 2,
    excerpt: 'My Post 2 Excerpt',
    featuredImage: {
      url: placeholderImage,
      width: 1000,
      height: 1000,
      alt: 'My Post 2 Featured Image',
    },
    thumbnail: {
      url: placeholderImage,
      width: 1000,
      height: 1000,
      alt: 'My Post 2 Thumbnail',
    },
    meta: {
      _edit_last: '1',
      _edit_lock: '1',
    },
    categories: [
      {
        name: 'My Category 2',
        url: 'https://my-site.com/my-category-2',
        id: 2,
        slug: 'my-category-2',
        description: 'My Category 2 Description',
        count: 20,
      },
    ],
    date: {
      formatted: '2025-05-02',
      display: '2 May 2025',
      modified: '2025-05-03',
      modified_display: '3 May 2025',
      timestamp: 1690548000,
      year: '2025',
      month: '05',
      day: '02'
    }
  },
  {
    title: 'My Post 3',
    content: 'My Post 3 Content',
    slug: 'my-post-3',
    url: 'https://my-site.com/my-post-3',
    id: 3,
    excerpt: 'My Post 3 Excerpt',
    featuredImage: {
      url: placeholderImage,
      width: 1000,
      height: 1000,
      alt: 'My Post 3 Featured Image',
    },
    thumbnail: {
      url: placeholderImage,
      width: 1000,
      height: 1000,
      alt: 'My Post 3 Thumbnail',
    },
    meta: {
      _edit_last: '1',
      _edit_lock: '1',
    },
    categories: [
      {
        name: 'My Category 3',
        url: 'https://my-site.com/my-category-3',
        id: 3,
        slug: 'my-category-3',
        description: 'My Category 3 Description',
        count: 30,
      },
    ],
    date: {
      formatted: '2025-05-03',
      display: '3 May 2025',
      modified: '2025-05-04',
      modified_display: '4 May 2025',
      timestamp: 1690548000,
      year: '2025',
      month: '05',
      day: '03'
    }
  }
];

export const pageContext = {
  site,
  page,
  posts,
  menu
};