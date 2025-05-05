<?php
/**
 * Context Types
 *
 * DO NOT MODIFY THIS FILE DIRECTLY. This file is auto-generated.
 * Generated on: 2025-05-05 18:21:35
 */

namespace WPFasty\Types;

/**
 * This file contains type definitions for template contexts
 * Import in Latte templates with: @import \WPFasty\Types\Context
 */

/**
 * Main context interface that combines all context types
 */
interface Context
{
    // This is a marker interface only
}

/**
 * PageContext interface
 * 
 * Contains type definitions for the page context
 */
interface PageContext extends Context
{
    /**
     * Get complete page context array
     * 
     * @return array{
          site: array{
               title: string,
               url: string,
               theme_url: string,
               lang: string,
               description: string,
               charset: string,
          },
          page: array{
               title: string,
               content: string,
               slug: string,
               url: string,
               id: int,
               excerpt: string,
               featuredImage: ?mixed,
               thumbnail: ?mixed,
               meta: array{
                    _edit_last: string,
                    _edit_lock: string,
               },
               categories: array<int, array{
                    name: string,
                    url: string,
                    id: int,
                    slug: string,
                    description: string,
                    count: int,
               }>,
               date: array{
                    formatted: string,
                    display: string,
                    modified: string,
                    modified_display: string,
                    timestamp: int,
                    year: string,
                    month: string,
                    day: string,
               },
          },
          menu: array{
               primary: array{
                    items: array<int, array{
                         title: string,
                         url: string,
                         id: int,
                         order: int,
                         parent: ?mixed,
                         classes: array<int, mixed>,
                         current: bool,
                    }>,
               },
          },
     * }
     */
    public function getContext(): array;
}

/**
 * SiteContext interface
 * 
 * Contains type definitions for the site context
 */
interface SiteContext extends Context
{
    /**
     * Get complete site context array
     * 
     * @return array{
          title: string,
          url: string,
          theme_url: string,
          lang: string,
          description: string,
          charset: string,
     * }
     */
    public function getContext(): array;
}

/**
 * PageContext interface
 * 
 * Contains type definitions for the page context
 */
interface PageContext extends Context
{
    /**
     * Get complete page context array
     * 
     * @return array{
          title: string,
          content: string,
          slug: string,
          url: string,
          id: int,
          excerpt: string,
          featuredImage: ?mixed,
          thumbnail: ?mixed,
          meta: array{
               _edit_last: string,
               _edit_lock: string,
          },
          categories: array<int, array{
               name: string,
               url: string,
               id: int,
               slug: string,
               description: string,
               count: int,
          }>,
          date: array{
               formatted: string,
               display: string,
               modified: string,
               modified_display: string,
               timestamp: int,
               year: string,
               month: string,
               day: string,
          },
     * }
     */
    public function getContext(): array;
}

/**
 * MetaContext interface
 * 
 * Contains type definitions for the meta context
 */
interface MetaContext extends Context
{
    /**
     * Get complete meta context array
     * 
     * @return array{
          _edit_last: string,
          _edit_lock: string,
     * }
     */
    public function getContext(): array;
}

/**
 * CategorieItemContext interface
 * 
 * Contains type definitions for the categorie_item context
 */
interface CategorieItemContext extends Context
{
    /**
     * Get complete categorie_item context array
     * 
     * @return array{
          name: string,
          url: string,
          id: int,
          slug: string,
          description: string,
          count: int,
     * }
     */
    public function getContext(): array;
}

/**
 * DateContext interface
 * 
 * Contains type definitions for the date context
 */
interface DateContext extends Context
{
    /**
     * Get complete date context array
     * 
     * @return array{
          formatted: string,
          display: string,
          modified: string,
          modified_display: string,
          timestamp: int,
          year: string,
          month: string,
          day: string,
     * }
     */
    public function getContext(): array;
}

/**
 * MenuContext interface
 * 
 * Contains type definitions for the menu context
 */
interface MenuContext extends Context
{
    /**
     * Get complete menu context array
     * 
     * @return array{
          primary: array{
               items: array<int, array{
                    title: string,
                    url: string,
                    id: int,
                    order: int,
                    parent: ?mixed,
                    classes: array<int, mixed>,
                    current: bool,
               }>,
          },
     * }
     */
    public function getContext(): array;
}

/**
 * PrimaryContext interface
 * 
 * Contains type definitions for the primary context
 */
interface PrimaryContext extends Context
{
    /**
     * Get complete primary context array
     * 
     * @return array{
          items: array<int, array{
               title: string,
               url: string,
               id: int,
               order: int,
               parent: ?mixed,
               classes: array<int, mixed>,
               current: bool,
          }>,
     * }
     */
    public function getContext(): array;
}

/**
 * ItemItemContext interface
 * 
 * Contains type definitions for the item_item context
 */
interface ItemItemContext extends Context
{
    /**
     * Get complete item_item context array
     * 
     * @return array{
          title: string,
          url: string,
          id: int,
          order: int,
          parent: ?mixed,
          classes: array<int, mixed>,
          current: bool,
     * }
     */
    public function getContext(): array;
}

/**
 * ArchiveContext interface
 * 
 * Contains type definitions for the archive context
 */
interface ArchiveContext extends Context
{
    /**
     * Get complete archive context array
     * 
     * @return array{
          site: array{
               title: string,
               url: string,
               theme_url: string,
               lang: string,
               description: string,
               charset: string,
          },
          archive: array{
               title: string,
               description: string,
          },
          posts: array<int, mixed>,
          pagination: ?mixed,
          menu: array{
               primary: array{
                    items: array<int, array{
                         title: string,
                         url: string,
                         id: int,
                         order: int,
                         parent: ?mixed,
                         classes: array<int, mixed>,
                         current: bool,
                    }>,
               },
          },
     * }
     */
    public function getContext(): array;
}

/**
 * SiteContext interface
 * 
 * Contains type definitions for the site context
 */
interface SiteContext extends Context
{
    /**
     * Get complete site context array
     * 
     * @return array{
          title: string,
          url: string,
          theme_url: string,
          lang: string,
          description: string,
          charset: string,
     * }
     */
    public function getContext(): array;
}

/**
 * ArchiveContext interface
 * 
 * Contains type definitions for the archive context
 */
interface ArchiveContext extends Context
{
    /**
     * Get complete archive context array
     * 
     * @return array{
          title: string,
          description: string,
     * }
     */
    public function getContext(): array;
}

/**
 * MenuContext interface
 * 
 * Contains type definitions for the menu context
 */
interface MenuContext extends Context
{
    /**
     * Get complete menu context array
     * 
     * @return array{
          primary: array{
               items: array<int, array{
                    title: string,
                    url: string,
                    id: int,
                    order: int,
                    parent: ?mixed,
                    classes: array<int, mixed>,
                    current: bool,
               }>,
          },
     * }
     */
    public function getContext(): array;
}

/**
 * PrimaryContext interface
 * 
 * Contains type definitions for the primary context
 */
interface PrimaryContext extends Context
{
    /**
     * Get complete primary context array
     * 
     * @return array{
          items: array<int, array{
               title: string,
               url: string,
               id: int,
               order: int,
               parent: ?mixed,
               classes: array<int, mixed>,
               current: bool,
          }>,
     * }
     */
    public function getContext(): array;
}

/**
 * ItemItemContext interface
 * 
 * Contains type definitions for the item_item context
 */
interface ItemItemContext extends Context
{
    /**
     * Get complete item_item context array
     * 
     * @return array{
          title: string,
          url: string,
          id: int,
          order: int,
          parent: ?mixed,
          classes: array<int, mixed>,
          current: bool,
     * }
     */
    public function getContext(): array;
}
