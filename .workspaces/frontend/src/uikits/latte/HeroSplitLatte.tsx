import React from 'react';
import { Button } from "./components/ui/Button";

/**
 * Адаптированная версия HeroSplit для генерации Latte шаблонов
 * Использует те же пропсы, что и оригинальный компонент,
 * но добавляет специальные маркеры для конвертации в Latte
 */
export const HeroSplitLatte: React.FC = () => {
  // Определяем переменные в контексте site
  const site = {
    title: "Заголовок",
    description: "Описание",
    button: {
      text: "Текст кнопки",
      variant: "primary",
      className: "",
      href: "#"
    },
    buttons: [
      {
        id: "button1",
        text: "Кнопка 1",
        variant: "primary",
        size: "default",
        className: "",
        href: "#"
      }
    ],
    images: {
      grid: {
        className: "grid-class",
        items: [
          {
            id: "image1",
            src: "/image.jpg",
            className: "image-class"
          }
        ]
      }
    }
  };

  // Возвращаем JSX в стиле React, но с учетом будущей конвертации в Latte
  return (
    <section className="hero">
      <div className="container">
        <div className="hero-content">
          <h1 className="hero-title">{site.title}</h1>
          <p className="hero-description">{site.description}</p>

          <div className="hero-actions">
            {site.button && (
              <a href={site.button.href} className={`button button-${site.button.variant} ${site.button.className}`}>
                {site.button.text}
              </a>
            )}

            {site.buttons.map((button) => (
              <Button
                key={button.id}
                variant={button.variant}
                size={button.size}
                className={button.className}
                href={button.href}
              >
                {button.text}
              </Button>
            ))}
          </div>
        </div>

        <div className={site.images.grid.className}>
          {site.images.grid.items.map((image) => (
            <div key={image.id} className={image.className}>
              <img src={image.src} alt="" />
            </div>
          ))}
        </div>
      </div>
    </section>
  );
};
