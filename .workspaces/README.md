# Development Workspaces

## Core Concept

This directory, `.workspaces`, serves as the central hub for all development activities related to this project, distinct from the core production-ready PHP application (`/app`, `/lib`, `/public`). It houses the necessary tools, configurations, sub-projects, documentation, and development environments required to build, test, and extend the system's capabilities, irrespective of the underlying technology stack.

Our objective extends beyond building a typical CMS. We are engineering a highly flexible, secure, and streamlined framework suitable for constructing a diverse range of web applications, including Content Management Systems, Customer Relationship Management platforms, analytical dashboards, and more.

This workspace is designed to accommodate various development workflows and technologies side-by-side. You might find sub-directories dedicated to:

*   Frontend development (e.g., using Bun.js + Vite with Tailwind 4)
*   Backend logic extensions (e.g., using Go or Rust compiled to Wasm)
*   PHP development tooling and Composer configurations specific to development tasks
*   Python scripts for automation or data processing
*   Specific rules (`.rules/`), guidelines, and setup instructions for contributors.
*   Any other development tools or experimental projects.

While the core runtime leverages FlightPHP for the backend foundation and aims for a clean Tailwind 4 frontend presentation, the logic developed within these workspaces is not constrained to any single programming language, fostering flexibility and the use of the best tool for each specific task.

## Three WhalY - *Core Principles*

The development carried out within these workspaces adheres strictly to our foundational principles:

### 1. Zero Redundant Code
*Every line serves a purpose. No bloat, no legacy code, no unnecessary abstractions. Pure, efficient implementation focused on the specific needs of the component or feature.*

### 2. Zero Database Queries (Where Applicable)
*Development should favour intelligent caching, state management, and efficient data structures to minimise or eliminate redundant database operations, contributing to lightning-fast response times and reduced server load in the final integration.*

### 3. Perfect PageSpeed Score Goal
*All components and integrations developed here should ultimately contribute to achieving perfect PageSpeed scores in the final product:*
*   100/100 *Performance*
*   100/100 *Accessibility*
*   100/100 *Best Practices*
*   100/100 *SEO*

This ensures that flexibility and diverse tooling do not compromise the end-user experience or operational efficiency.

## Further Reading

To fully understand the ethos and standards guiding development within this workspace and the project as a whole, please consult:

*   [**Manifesto**](.rules/Manifesto.md) - Our guiding philosophy and overarching vision, serving as a compass for all contributors.
*   [**Principles**](.rules/Principles.md) - The foundational tenets and standards that inform our approach, emphasizing core values like speed, security, and minimalism in all endeavours.