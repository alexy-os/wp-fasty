# Инструкция по созданию компонентов UI8px

## Компонент Card: полное руководство

### Архитектура компонента

Компонент Card является составным и использует композиционный подход. Он состоит из нескольких частей:
- Card - основной контейнер
- CardHeader - верхняя часть карточки
- CardContent - основное содержимое
- CardFooter - нижняя часть
- CardTitle - заголовок (обычно используется внутри CardHeader)
- CardDescription - описание (обычно используется внутри CardHeader)

### Особенности именования классов

Система использует семантическое именование классов:
- Основной класс компонента = имя компонента в нижнем регистре (`card`)
- Модификаторы формируются как `[название-компонента]-[значение-свойства]` (`card-primary`)
- Части компонента используют такой же подход (`card-header`, `card-footer`)

### Процесс создания компонента

#### 1. Определение интерфейса (interface.ts)

```typescript
// Описываем типы всех допустимых вариантов компонента
export type CardProps = {
  variant?: "default" | "outline" | "primary" | "secondary" | "accent" | "user" | "ai";
  elevation?: "flat" | "default" | "raised";
  children?: React.ReactNode;
  className?: string;
};

// Описываем варианты стилей с помощью CVA (class-variance-authority)
export const cardVariants = cva(
  "rounded-lg border border-border overflow-hidden", // базовые стили
  {
    variants: {
      // варианты внешнего вида
      variant: {
        default: "bg-card text-card-foreground",
        primary: "bg-primary text-primary-foreground",
        // другие варианты...
      },
      // части компонента
      partial: {
        header: "flex flex-col space-y-1.5 p-4",
        content: "p-4 pt-0",
        footer: "flex items-center p-4 pt-0"
      },
      // уровни тени
      elevation: {
        flat: "",
        default: "shadow-sm",
        raised: "shadow-md"
      },
      // типографика
      typography: {
        description: "text-sm text-muted-foreground",
        title: "text-base font-medium"
      }
    },
    defaultVariants: {
      variant: "default",
      elevation: "default"
    }
  }
);
```

#### 2. Реализация компонента (index.tsx)

```typescript
// Создаем контекст для передачи свойств вложенным компонентам
const CardContext = createContext<{ variant: CardProps['variant']; elevation: CardProps['elevation'] }>({
  variant: 'default',
  elevation: 'default'
});

// Основной компонент-обертка
export const Card: React.FC<Omit<CardProps, 'partial'> & {
  children: React.ReactNode
}> = ({
  variant = "default",
  elevation = "default",
  children,
  className = "",
}) => {
  return (
    <CardContext.Provider value={{ variant, elevation }}>
      <div className={`card card-${variant} ${elevation !== "default" ? `card-${elevation}` : ""} ${className}`}>
        {children}
      </div>
    </CardContext.Provider>
  );
};

// Компоненты для каждой части карточки
export const CardHeader: React.FC<{
  children: React.ReactNode;
  className?: string;
}> = ({ children, className = "" }) => {
  const { variant } = useContext(CardContext);
  return (
    <div className={`card-header card-${variant} ${className}`}>
      {children}
    </div>
  );
};

// Аналогично для CardContent, CardFooter, CardTitle, CardDescription...
```

### Генерация CSS

После определения компонента в интерфейсе, система автоматически генерирует CSS-файл с помощью `variants-parser.ts`. Скрипт:

1. Ищет все файлы интерфейсов в указанных директориях
2. Извлекает варианты стилей из объекта CVA
3. Преобразует каждый вариант в класс CSS с применением директивы `@apply`
4. Создает отдельный CSS-файл для каждого компонента
5. Генерирует индексный файл CSS, который импортирует все отдельные файлы

### Пример использования компонента

```tsx
<Card variant="primary" elevation="raised">
  <CardHeader>
    <CardTitle>Заголовок карточки</CardTitle>
    <CardDescription>Подробное описание карточки</CardDescription>
  </CardHeader>
  
  <CardContent>
    <p>Основное содержимое карточки. Может содержать любые элементы.</p>
  </CardContent>
  
  <CardFooter>
    <button>Отмена</button>
    <button>Подтвердить</button>
  </CardFooter>
</Card>
```

### Важные особенности

1. **Автоматическое создание CSS-классов**: Любой ключ в объекте `variants` превращается в CSS-класс по шаблону `[компонент]-[ключ]`
2. **Контекст React**: Используется для передачи общих свойств (variant, elevation) вложенным компонентам
3. **Семантическая вложенность**: Компоненты имеют правильную семантическую структуру с заголовком, содержимым и подвалом
4. **Tailwind CSS**: Система использует Tailwind через директиву `@apply`
5. **Композиционный подход**: Компоненты разбиты на мелкие части для гибкого использования

### Преимущества подхода

- Семантически чистый HTML без лишних вложенных элементов
- Автоматическая генерация CSS-классов
- Композиционный и декларативный API
- Переиспользуемые части компонентов
- Централизованное управление вариантами стилей
