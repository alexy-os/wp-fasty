# Flight CMS

Flight CMS is a modern, lightweight, and high-performance Content Management System built upon the principles of speed, security, and minimalism. It leverages the powerful and extensible [Flight PHP micro-framework](https://flightphp.com/) as its core foundation.

## Core Principles

Our development philosophy is guided by two main doctrines:

### 1. The Three Whales

Every aspect of Flight CMS adheres to:

-   **Zero Redundancy**: Eliminating superfluous code, libraries, and dependencies. We aim for a minimal core that does exactly what's needed, and nothing more.
-   **Minimal Requests**: Optimizing interactions with databases, servers, APIs, and the browser to ensure only essential communication occurs.
-   **Maximal Performance**: Prioritizing speed in both perception (fast UI) and reality (efficient backend processing).

### 2. FlightPHP First

We are committed to leveraging the capabilities of the Flight PHP framework:

-   **FlightPHP First**: All solutions must prioritize existing FlightPHP functionality and official extensions before introducing external libraries or custom solutions.
-   **No Reinvention**: We utilize Flight's built-in features like its Dependency Injection Container, Active Record implementation, Caching, Session management, and more, avoiding redundant development.
-   **Modular Extensions**: New features are developed as modular extensions that integrate seamlessly with Flight's architecture and design patterns.

## Architecture

Flight CMS employs a clean, modular architecture designed for maintainability and performance:

-   **Backend**: Powered by Flight PHP, handling routing, controllers, models (via Flight Active Record), and core logic.
-   **Frontend (Admin)**: The administrative interface is developed using Svelte and Vite. During the build process, it compiles down to highly optimized, dependency-free vanilla JavaScript, CSS, and HTML. This ensures a fast-loading and responsive admin experience without requiring Node.js or heavy JavaScript frameworks in the production environment.
-   **Content Editor**: A powerful block-based editor, built with Tiptap, is integrated. Similar to the admin panel, the editor components are pre-compiled into a standalone JavaScript bundle, ready for easy deployment and minimal runtime overhead.
-   **Database**: Flexible data storage options, defaulting to SQLite for simple setup, with support for MySQL/PostgreSQL via Flight Active Record, and even JSON files for very small sites.
-   **Deployment**: Designed for simplicity. The final CMS can be packaged into a single ZIP archive. Installation involves extracting the archive, running a web-based installer to configure the database and admin user, requiring only a standard PHP environment.

## Features (Planned)

-   Blazing fast performance
-   Secure by design
-   Modular and extensible
-   Simple installation and updates
-   Modern block-based content editor
-   Developer-friendly API and tooling (using Flight Runway)
-   Minimal server requirements (PHP 8.0+)

Flight CMS aims to be the go-to choice for developers and site owners who value speed, efficiency, and a clean codebase without unnecessary bloat.
