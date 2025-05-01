import React from 'react';

export default function HeroSplit(props) {
  const { title, description, button, buttons, images } = props;

  return (
    <section className="hero">
      <div className="container">
        <div className="hero-content">
          <h1 className="hero-title">{title}</h1>
          <p className="hero-description">{description}</p>

          <div className="hero-actions">
            {button && (
              <a
                href={button.href}
                className={`button button-${button.variant} ${button.className}`}
              >
                {button.text}
              </a>
            )}

            {buttons?.map((button) => (
              <a
                key={button.id}
                href={button.href}
                className={`button button-${button.variant} button-${button.size} ${button.className}`}
              >
                {button.text}
              </a>
            ))}
          </div>
        </div>

        <div className={images?.grid?.className}>
          {images?.grid?.items?.map((image) => (
            <div key={image.id} className={image.className}>
              <img src={image.src} alt="" />
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}
