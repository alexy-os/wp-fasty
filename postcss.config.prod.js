module.exports = {
  plugins: {
    'postcss-import': {},
    'postcss-scss': {},
    'tailwindcss/nesting': {},
    'tailwindcss': {},
    'autoprefixer': {},
    'postcss-apply': {},
    'cssnano': { // Минификация CSS для продакшена
      preset: ['default', {
        discardComments: {
          removeAll: true,
        },
        normalizeWhitespace: true
      }]
    }
  }
}