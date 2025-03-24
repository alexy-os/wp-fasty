module.exports = {
  plugins: {
    'postcss-import': {},
    // 'postcss-scss': {}, // Удаляем или комментируем этот плагин
    'tailwindcss/nesting': {}, 
    'tailwindcss': {},
    'postcss-nested': {}, // Используем postcss-nested вместо tailwindcss/nesting + postcss-scss
    'postcss-apply': {},
    'autoprefixer': {},
    'cssnano': process.env.NODE_ENV === 'production' ? {
      preset: ['default', {
        discardComments: {
          removeAll: true,
        },
        normalizeWhitespace: true
      }]
    } : false
  }
}