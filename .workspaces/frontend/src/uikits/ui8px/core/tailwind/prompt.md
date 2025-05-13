# Component Template Builder Prompt

## Important: Templates for Construction, Not Runtime
These templates are designed for automated transformation, not for runtime execution. They require:
- Maximally simple syntax that can be easily converted to different template formats
- No defensive coding, null checks, or runtime protections
- Direct data binding without wrappers like dangerouslySetInnerHTML
- Semantic structure that closely mirrors HTML5 standards

## Task Description
Build clean, structural templates without styling or configuration. These templates will be automatically processed to generate different styling approaches and template formats.

## Core Principles

1. **Structure Over Style**
   - Focus only on component hierarchy and data binding
   - Omit all styling and class names
   - Use semantic component names

2. **Component Selection**
   - Use ONLY components listed in `component-tree.json` without exploring source code
   - Select components based on their semantic meaning AND EXACT file path as defined in component-tree.json
   - Preserve the semantic structure of original templates, especially top-level tags
   - Prioritize context-specific components (e.g., `<ArticleHeader>` inside `<Article>`)
   - Follow the natural HTML5 semantic hierarchy
   - CRITICAL: Always respect the parent-child relationships defined in "validParents" field
   - CRITICAL: Import components from the EXACT paths specified in "filePath", converting backslashes to forward slashes
   - NEVER mix components from different semantic contexts (e.g., don't use Card components inside Article)

3. **Component Hierarchy Best Practices**
   - Each parent component should ONLY contain children listed in its "validParents" field 
   - If a component has slot attributes (e.g., "card-header", "article-title"), only use it with the correct parent
   - When building nested components, first check if there's a specific child component available before using generic ones
   - For example, use <ArticleTitle> inside <ArticleHeader> rather than generic <H1>

4. **Minimal Data Binding Syntax**
   - Use simple conditions: `{data.property && (<Component>...</Component>)}`
   - Use simple loops: `{items.map(item => <Component>{item.value}</Component>)}`
   - Pass variables directly: `<Component>{data.content}</Component>` (not with wrappers)
   - Avoid checks for null/undefined, empty arrays, or other defensive coding

5. **Component Hierarchy Example**
   ```jsx
   <Article>
     <ArticleHeader>
       <ArticleTitle>{data.title}</ArticleTitle>
       {data.date.display && (
       <ArticleMeta>
         <ArticleTime dateTime={data.date.formatted}>{data.date.display}</ArticleTime>
       </ArticleMeta>
       )}
     </ArticleHeader>
     <ArticleContent>{data.content}</ArticleContent>
   </Article>
   ```

6. **Examples of Path Imports**
   Incorrect:
   ```jsx
   import { Card } from "@uikits/ui8px/core/tailwind/clsx/components/card";
   ```
   
   Correct:
   ```jsx
   import { Card } from "@uikits/ui8px/core/tailwind/clsx/ui/card";
   ```
   (note the path matches the "filePath" from component-tree.json with backslashes converted to forward slashes)

7. **Examples of Correct vs. Incorrect Syntax**
   
   ✅ Correct:
   ```jsx
   {post.categories && (
     <div>{post.categories.map(category => <Tag>{category.name}</Tag>)}</div>
   )}
   ```
   
   ❌ Incorrect:
   ```jsx
   {post.categories && post.categories.length > 0 && (
     <div dangerouslySetInnerHTML={{ __html: formatCategories(post.categories) }} />
   )}
   ```

8. **Working with Missing Components**
   - If a component doesn't exist, create a draft in `./src/uikits/ui8px/core/tailwind/draft/`
   - Follow existing naming conventions for new components

## Important Notes
- Treat `component-tree.json` as your ONLY component catalog
- Use the validParents field to ensure proper component nesting
- Remember that all styling will be handled automatically by processing scripts
- Focus on creating clean, semantic structures that follow HTML5 standards
