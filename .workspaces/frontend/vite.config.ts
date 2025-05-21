import { defineConfig } from 'vite'
import tailwindcss from '@tailwindcss/vite'
import path from "path"

export default defineConfig({
  plugins: [
    tailwindcss()
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
      '@app': path.resolve(__dirname, './src/app'),
      '@root': path.resolve(__dirname, '../../'),
      '@scripts': path.resolve(__dirname, './scripts'),
      '@wptheme': path.resolve(__dirname, '../../wp-fasty-ym'),
      '@uikits': path.resolve(__dirname, './src/uikits'),
      '@ui8kit': path.resolve(__dirname, './src/uikits/@ui8kit/src'),
      '@semantic': path.resolve(__dirname, './src/uikits/@semantic/src'),
      '@n4shadcn': path.resolve(__dirname, './src/uikits/n4shadcn/src'),
      '@templates': path.resolve(__dirname, './src/templates'),
      '@context': path.resolve(__dirname, './src/context'),
      '@views': path.resolve(__dirname, './src/views'),
      '@react/tailwind': path.resolve(__dirname, './src/views/tailwind/node/react'),
    },
  },
});