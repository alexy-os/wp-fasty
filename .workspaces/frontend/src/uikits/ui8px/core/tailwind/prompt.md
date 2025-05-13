# Component Template Builder Prompt

## Task Description
Your task is to build clean, structural templates without any styling or configuration details. These templates will be automatically processed by scripts to extract all necessary components and convert them into different styling approaches (utility classes, semantic classes, etc.) and template formats (Latte, Twig, Handlebars, HTML).

## Template Requirements

1. **Focus on Structure Only**
   - Build templates that define only the component hierarchy and data binding
   - Do not include any styling, class names, or configuration details
   - Use component names that clearly indicate their semantic purpose

2. **Use Component-Based Architecture**
   - Always use the appropriate semantic components from our library
   - Reference components directly (e.g., `<Article>` instead of `<div className="article">`)
   - Maintain proper component nesting according to HTML5 semantic standards

3. **Component Selection Guidelines**
   - Always prioritize context-specific components over generic ones
   - For example, use `<ArticleHeader>` inside an `<Article>` rather than a generic `<Header>` component
   - Use the component-tree.json file to locate the most appropriate components

4. **Component Hierarchy Example**
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

5. **Data Handling**
   - Use appropriate data binding syntax for dynamic content
   - Include conditional rendering where needed
   - Follow the data structure provided in the context

6. **Component Discovery Process**
   1. First check component-tree.json to find appropriate components
   2. Prioritize components based on their semantic context
   3. For nested elements, prefer context-specific sub-components (e.g., ArticleHeader inside Article)
   4. If a component doesn't exist, create a draft in `./src/uikits/ui8px/core/tailwind/draft/`

7. **Slot Awareness**
   - Components use data-slot attributes for semantic targeting
   - These slots will be used to generate appropriate class names
   - Example: `data-slot="section-header"` will generate semantic class "section-header"

## Output Formats
Your templates will be processed to generate multiple output formats:

1. **Tailwind CSS Version**
   - Components will be replaced with elements using Tailwind utility classes
   - Example: `<Article>` becomes `<article className="bg-card text-card-foreground flex flex-col gap-4 rounded-xl border shadow-sm">`

2. **Semantic CSS Version**
   - Components will be replaced with elements using semantic class names
   - Example: `<Article>` becomes `<article className="article">`

3. **Template Language Versions**
   - The structure will be converted to various template languages (Latte, Twig, etc.)
   - Data binding syntax will be adjusted accordingly

## Important Notes
- Do not invent components - use only what exists in the library
- If a suitable component doesn't exist, draft it in the designated folder
- Always check component-tree.json first to locate appropriate components
- Focus on creating clean, semantic structures that follow HTML5 standards
- Remember that all styling will be handled automatically by the processing scripts
