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
    outDir: '../../wp-fasty-ym/assets/css',
    emptyOutDir: false,
    rollupOptions: {
      input: path.resolve(__dirname, 'src/assets/css/import.css'),
      output: {
        assetFileNames: 'theme.min.css'
      }
    },
    minify: true
  },
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
      '@root': path.resolve(__dirname, '../../'),
      '@wp-fasty-ym': path.resolve(__dirname, '../../wp-fasty-ym')
    },
  },
});