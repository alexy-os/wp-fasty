# Разработка современного UI/UX Kit

- strict rule: 8 px ui/ux design.
- convert and create all colors strict new verse shadcn/ui oklch [@shadcn.css](./shadcn.css).
- darkMode - required (`dark:` on a new line in `@apply`).
- font size and style premium rules as Apple.
- icons: lucide-react.
- url images placeholder base64 muted shadcn color.
- minimal visual effects.
- gradients and animations - forbidden.

Все комментарии в коде и объяснения - строго на международном языке.

Правила создания структуры UI Kit [@llm-uikits.md](./llm-uikits.md)

Папка назначения: [@uikits](../), name: `ui8px`

Референс с сохранением стилей кроме запрещенных: [@preline-chat.tsx](./preline-chat.tsx)

Пример чата в Preline UI уже содержит подсказки: какие блоки, компоненты стоит создать, но не забывай и про другие неучтенные критически важные для UIKit элементы.

Итак. Только два файла на блок, компонент или элемент: interface + index как это отражено в правилах [@llm-uikits.md](./llm-uikits.md)

====================

Изучаю задачу по разработке UI Kit на основе предоставленных требований и материалов.

Теперь я создам структуру UI Kit на основе предоставленных требований и справочных материалов. Начнем с создания основных компонентов.

Создадим структуру папок для нашего UI Kit следуя подходу Atomic Design: [core](../core/)

Теперь давайте начнем создавать файлы интерфейсов и реализации компонентов. Начнем с основных UI-компонентов. [...](../core/)

Создадим пример использования с образцом кода, который демонстрирует, как собирать и использовать компоненты:

Search files...

Я создал пример семантического [Landing Page](../examples/landing-page-example.tsx), использующего компоненты из UI8px без утилитарных классов Tailwind. Вся верстка выполнена с использованием семантических тегов HTML5, соответствующих стандарту W3C.

Структура страницы включает:
1. Шапку сайта с логотипом и навигацией
2. Основную секцию с разделами:
   - Hero секция с основным заголовком и призывом к действию
   - Секция с ключевыми особенностями (Features)
   - Отзывы пользователей (Testimonials)
   - Тарифные планы (Pricing)
   - Призыв к действию (CTA)
3. Подвал сайта с навигацией и копирайтом

Все стили вынесены в отдельный файл [landing-page-styles.css](../examples/landing-page-styles.css), где каждый класс определен с использованием директивы @apply. Это позволяет поддерживать семантические имена классов, следуя принципам UI8px, но при этом легко перенести стили в файл импорта.

Файлы:
1. [landing-page-example.tsx](../examples/landing-page-example.tsx) - React-компонент с семантической версткой
2. [landing-page-styles.css](../examples/landing-page-styles.css) - стили с директивами @apply

Создадим также, README для UI8 Kit: [README](../README.md)

