module.exports = {
  plugins: {
    "postcss-import": {
      path: ["./src/styles"]
    },
    "tailwindcss": {},
    "autoprefixer": {},
    "cssnano": {
      preset: ["default", {
        discardComments: {
          removeAll: true,
        },
      }]
    }
  }
}