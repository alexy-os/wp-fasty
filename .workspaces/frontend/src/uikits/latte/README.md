# WP Fasty Latte Components

## Создание React-компонентов на основе Latte шаблонов

Данный README описывает процесс разработки React-компонентов, совместимых с Latte-шаблонами и типизированным контекстом WordPress.

### Подготовка компонента

1. **Импорт типов**
   ```tsx
   import { WPFastyContextSchema } from './types/WPFastY-YM-ContextTypes';
   ```

2. **Определение типов для конкретного шаблона**
   ```tsx
   // Для главной страницы
   type FrontPageProps = {
     site: WPFastyContextSchema['site'];
     page?: WPFastyContextSchema['page'];
     posts?: WPFastyContextSchema['archive']['posts'][];
   };
   ```

3. **Создание компонента и типизация его props**
   ```tsx
   const Component: React.FC<FrontPageProps> = ({ site, page, posts }) => {
     // Компонент здесь
   };
   ```

### Работа с контекстными данными

1. **Тип данных сайта (общие для всех страниц)**
   ```tsx
   site.title           // Заголовок сайта
   site.description     // Описание сайта
   site.url             // URL сайта
   site.theme_url       // URL темы
   site.lang            // Язык сайта
   site.charset         // Кодировка
   ```

2. **Тип данных страницы**
   ```tsx
   page?.page?.title           // Заголовок страницы
   page?.page?.content         // Содержимое страницы (HTML)
   page?.page?.slug            // Слаг страницы
   page?.page?.url             // URL страницы
   page?.page?.featuredImage   // Изображение страницы
   ```

3. **Тип данных поста (применимо к массиву постов)**
   ```tsx
   post.title           // Заголовок поста
   post.excerpt         // Краткое описание (HTML)
   post.url             // URL поста
   post.slug            // Слаг поста
   post.content         // Полное содержимое (HTML)
   post.thumbnail       // Миниатюра
   post.categories      // Массив категорий
   post.date            // Информация о дате
   ```

### Работа с циклами

При обработке массивов данных (например, циклов) используйте строгую типизацию:

```tsx
{posts.map((post: WPFastyContextSchema['archive']['posts'], index) => (
  <article key={index}>
    <h2>{post.title}</h2>
    
    {post.categories && post.categories.length > 0 && (
      <ul>
        {post.categories.map((category, catIndex) => (
          <li key={catIndex}>
            <a href={category.url}>{category.name}</a>
          </li>
        ))}
      </ul>
    )}
  </article>
))}
```

### Особенности совместимости с Latte

1. **Обработка условных конструкций**
   ```tsx
   {/* Latte: {if isset($site->title)} <h1>{$site->title}</h1> {/if} */}
   {site.title && <h1>{site.title}</h1>}
   ```

2. **Обработка массивов (foreach в Latte)**
   ```tsx
   {/* Latte: {foreach $posts as $post} ... {/foreach} */}
   {posts.map((post, index) => ( ... ))}
   ```

3. **Вложенные циклы**
   ```tsx
   {/* Latte: 
     {foreach $posts as $post}
       {foreach $post->categories as $category}
         ...
       {/foreach}
     {/foreach} 
   */}
   {posts.map((post, index) => (
     <div key={index}>
       {post.categories && post.categories.map((category, catIndex) => (
         <span key={catIndex}>{category.name}</span>
       ))}
     </div>
   ))}
   ```

### Workflow разработки

1. **Создание компонента из Latte шаблона**
   - Используйте скрипт `generate-template.ts` для генерации HTML-шаблона из Latte
   - Преобразуйте HTML в React компонент с помощью `html-to-latte.ts`

2. **Обеспечение типизации**
   - Используйте типы из `WPFastY-YM-ContextTypes.ts`
   - Для входных props, используйте соответствующие типы контекста
   - При работе с массивами, явно типизируйте элементы

3. **Проверка типов**
   - Убедитесь, что в компоненте нет ошибок типизации
   - Проверьте правильность обращения к свойствам объектов

### Примеры использования типов

```tsx
// Пример типизации для разных типов страниц
type FrontPageProps = {
  site: WPFastyContextSchema['site'];
  page?: WPFastyContextSchema['page'];
  posts?: WPFastyContextSchema['archive']['posts'][];
};

type ArchivePageProps = {
  site: WPFastyContextSchema['site'];
  archive: WPFastyContextSchema['archive'];
};

type SinglePostProps = {
  site: WPFastyContextSchema['site'];
  post: WPFastyContextSchema['archive']['posts'];
};
```

### Демо-данные

Рекомендуется создавать демо-данные, соответствующие типам, для разработки и тестирования:

```tsx
export const DemoContent: FrontPageProps = {
  site: {
    title: "WP Fasty",
    description: "Современная тема WordPress",
    // ...прочие свойства сайта
  },
  // ...прочие свойства для демо
};

// Компонент с использованием демо-данных
const Component: React.FC<FrontPageProps> = (props) => {
  const data = {
    site: props.site || DemoContent.site,
    page: props.page || DemoContent.page,
    // ...остальные данные
  };
  
  return (
    // JSX с использованием data
  );
};
```
