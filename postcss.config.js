module.exports = {
  plugins: {
    'postcss-import': {},
    'postcss-scss': {}, // Добавляем поддержку SCSS синтаксиса
    'tailwindcss/nesting': {}, // Преобразует вложенность
    'tailwindcss': {}, // Применяет Tailwind
    'autoprefixer': {},
    'postcss-apply': {}, // Поддержка @apply
    'postcss-preset-env': { // Добавляем современные CSS функции
      features: {
        'nesting-rules': true
      }
    }
  }
}