# Component Template Builder Prompt

## Overview
Templates for automated transformation, not runtime execution. Focus on:
- Simple syntax for easy conversion to different formats
- Direct data binding without defensive coding
- Semantic HTML5 structure

---

## Core Requirements

### 1️⃣ Use Only Components from `component-tree.json`
- Always refer to component-tree.json for available components
- Import using EXACT paths from the "importPath" field

### 2️⃣ Component Selection Priority (STRICT ORDER)
1. **Specialized child components first**
   - Example: `<SectionTitle>` inside `<Section>` (instead of `<H1>`)
   - Example: `<ArticleContent>` inside `<Article>` (instead of `<div>`)

2. **Semantic containers second**
   - Example: `<SectionHeader>` inside `<Section>` (instead of `<div>`)
   - Example: `<CardHeader>` inside `<Card>` (instead of `<div>`)

3. **Generic components last resort only**
   - Use ONLY when no specialized alternative exists

### 3️⃣ Follow Semantic Hierarchy 
- Never skip hierarchy levels:
  - `<Section>` → `<SectionHeader>` → `<SectionTitle>`
  - `<Article>` → `<ArticleHeader>` → `<ArticleTitle>`
  - `<Card>` → `<CardHeader>` → `<CardTitle>`

### 4️⃣ Component Constraints
- Components can ONLY contain children listed in their "validParents" field
- Use component slot attributes (e.g., "card-header", "article-title") with correct parent

---

## Code Examples

### ✅ Correct Component Structure
```jsx
<Section>
  <SectionHeader>
    <SectionTitle>Our Services</SectionTitle>
    <SectionDescription>What we offer</SectionDescription>
  </SectionHeader>
  <SectionContent>
    <p>Content here...</p>
  </SectionContent>
</Section>
```

### ❌ Incorrect Component Structure
```jsx
<Section>
  <div>
    <h2>Our Services</h2>
    <p>What we offer</p>
  </div>
  <div>
    <p>Content here...</p>
  </div>
</Section>
```

### ✅ Correct Data Binding
```jsx
{post.categories && (
  <ArticleTags>
    {post.categories.map(category => (
      <ArticleTag key={category.id}>{category.name}</ArticleTag>
    ))}
  </ArticleTags>
)}
```

### ❌ Incorrect Data Binding
```jsx
{post.categories && post.categories.length > 0 && (
  <div dangerouslySetInnerHTML={{ __html: formatCategories(post.categories) }} />
)}
```

---

## Creating Templates

### Starting Structure
Always begin with proper semantic containers:

```jsx
<>
  <Section>
    <Container>
      <SectionHeader>
        <SectionTitle>{data.title}</SectionTitle>
      </SectionHeader>
      <SectionContent>
        {/* Content here */}
      </SectionContent>
    </Container>
  </Section>
</>
```

### Missing Components
If needed component doesn't exist:
- Create draft in `./src/uikits/ui8px/core/tailwind/draft/`
- Follow existing naming conventions

---

## Component Selection Examples

| Generic Component | Specialized Alternative (PREFERRED) |
|-------------------|-------------------------------------|
| `<h1>`, `<h2>`    | `<SectionTitle>`, `<ArticleTitle>` |
| `<p>`             | `<SectionDescription>`             |
| `<div>`           | `<SectionContent>`, `<CardContent>` |
| `<header>`        | `<SectionHeader>`, `<CardHeader>`  |
| `<footer>`        | `<SectionFooter>`, `<CardFooter>`  |

---

## REQUIRED CHECKLIST
Before finalizing any template, verify:
- [ ] All components are from component-tree.json
- [ ] Imports use exact paths from "importPath" field
- [ ] No generic components where specialized ones exist
- [ ] Proper semantic hierarchy is maintained
- [ ] No divs used where semantic containers exist
- [ ] Data binding follows minimal syntax patterns
- [ ] All interactive elements are properly nested
- [ ] No defensive coding or runtime checks

# Minimal Template Builder Rules

## 1. Use only components from `component-tree.json`
- Import strictly by `importPath` from the map.
- Never use div, h1, p, or other HTML tags if a specialized component exists.

## 2. Follow the source structure exactly
- Do not shorten, rearrange, or change the hierarchy of sections, blocks, or elements.
- If something exists in the source, it must exist in the template.
- All conditions, loops, and nesting must match the source.

## 3. Minimal React syntax
- Use only `{data}` for output, no typeof, !!, ternary, or type checks.
- Use only `{array.map(...)}` for loops, no length or empty checks.
- Use only `{data && <Component>{data}</Component>}` for conditions.

## 4. Do not add or remove props
- Use only props listed in the component map.
- Do not add styles, classes, variant, rounded, etc., unless in the map.

## 5. No logic or defensive checks
- No typeof, null-checks, empty checks, or data formatting.
- Output data as-is.

## 6. Use Link/LinkButton for links and buttons
- Never use <a> or <button> if a specialized component exists.

## 7. If a component is missing, create a draft, do not use HTML tags

---

**Example:**
```jsx
<Article>
  <ArticleHeader>
    {post.categories && (
      <ArticleTags>
        {post.categories.map(category => (
          <ArticleTag key={category.id}>{category.name}</ArticleTag>
        ))}
      </ArticleTags>
    )}
    <ArticleTitle>{post.title}</ArticleTitle>
  </ArticleHeader>
  {post.thumbnail && (
    <LinkButton href={post.url}>
      <ArticleImage src={post.thumbnail.url} alt={post.thumbnail.alt} />
    </LinkButton>
  )}
  {post.excerpt && (
    <ArticleContent>{post.excerpt}</ArticleContent>
  )}
  <ArticleFooter>
    {post.date && (
      <ArticleMeta>
        <ArticleTime dateTime={post.date.formatted}>{post.date.display}</ArticleTime>
      </ArticleMeta>
    )}
    <LinkButton href={post.url}>
      Read More <span>→</span>
    </LinkButton>
  </ArticleFooter>
</Article>
```
