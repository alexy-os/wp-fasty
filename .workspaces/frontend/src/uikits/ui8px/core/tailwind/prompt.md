# Component Template Builder Prompt

## Task Description
Build clean, structural templates without styling or configuration. These templates will be automatically processed to generate different styling approaches and template formats.

## Core Principles

1. **Structure Over Style**
   - Focus only on component hierarchy and data binding
   - Omit all styling and class names
   - Use semantic component names

2. **Component Selection**
   - Always use component-tree.json as your primary reference
   - Select components based on their semantic meaning
   - Prioritize context-specific components (e.g., `<ArticleHeader>` inside `<Article>`)
   - Follow the natural HTML5 semantic hierarchy

3. **Component Hierarchy Example**
   ```jsx
   <Article>
     <ArticleHeader>
       <ArticleTitle>{data.title}</ArticleTitle>
       <ArticleMeta>
         <ArticleTime dateTime={data.date.formatted}>{data.date.display}</ArticleTime>
       </ArticleMeta>
     </ArticleHeader>
     <ArticleContent>{data.content}</ArticleContent>
   </Article>
   ```

4. **Working with Missing Components**
   - If a component doesn't exist, create a draft in `./src/uikits/ui8px/core/tailwind/draft/`
   - Follow existing naming conventions for new components

## Important Notes
- Treat component-tree.json as your component catalog
- Use the validParents field to ensure proper component nesting
- Remember that all styling will be handled automatically by processing scripts
- Focus on creating clean, semantic structures that follow HTML5 standards
