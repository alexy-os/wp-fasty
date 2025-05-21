// Safely import page components directly for a reliable solution
import { HomePage } from '@app/pages/HomePage'
import { ArchivePage } from '@app/pages/ArchivePage'
import { PostPage, type PostPageProps } from '@app/pages/PostPage'
import { AboutPage } from '@app/pages/AboutPage'

// Create a mapping of routes with component information
export const routes: RouteInfo[] = [
  {
    path: '/',
    component: HomePage
  },
  {
    path: '/archive',
    component: ArchivePage
  },
  {
    path: '/blog',
    component: ArchivePage
  },
  {
    path: '/about',
    component: AboutPage
  },
  {
    path: '/post/:slug',
    component: PostPage,
    // Explicitly specify how to convert URL parameters to component props
    paramMapper: (params) => ({ slug: params.slug }) as PostPageProps
  }
];

// Type for route information
type RouteInfo = {
  path: string;
  component: React.ComponentType<any>;
  // Additional function for processing parameters (if needed)
  paramMapper?: (params: Record<string, string>) => any;
};