import { defineConfig } from 'vite'
import tailwindcss from '@tailwindcss/vite'
import path from "path"

export default defineConfig({
  plugins: [
    tailwindcss(),
  ],
  optimizeDeps: {
    exclude: ['@tailwindcss/vite']
  },
  build: {
    outDir: 'src/assets/css',
    emptyOutDir: false,
    rollupOptions: {
      input: path.resolve(__dirname, 'src/assets/css/import.css'),
      output: {
        assetFileNames: 'styles.css'
      }
    },
    minify: true,
  },
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
      '@root': path.resolve(__dirname, '../../'),
      '@scripts': path.resolve(__dirname, './scripts'),
      '@wptheme': path.resolve(__dirname, '../../wp-fasty-ym'),
      '@uikits': path.resolve(__dirname, './src/uikits'),
      '@hinddy': path.resolve(__dirname, './src/uikits/hinddy/core'),
      '@infobiz': path.resolve(__dirname, './src/uikits/infobiz/core'),
      '@ui8px': path.resolve(__dirname, './src/uikits/ui8px/core')
    },
  },
});