## strict rules for ui 8px design

- strict rule: 8px grid-based design system;
- convert and create all colors strict new verse shadcn/ui oklch @shadcn.css;
- darkMode - required (`dark:` on a new line in `@apply`);
- icons - only lucide-react
- enforce `data-slot` attribute: every exported React component (PascalCase) must include a kebab-case `data-slot` matching its semantic purpose