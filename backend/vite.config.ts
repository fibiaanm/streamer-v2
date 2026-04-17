import { defineConfig } from 'vite'
import tailwindcss from '@tailwindcss/vite'
import vue from '@vitejs/plugin-vue'
import laravel from 'laravel-vite-plugin'

export default defineConfig({
  plugins: [
    tailwindcss(),
    laravel({
      input: ['resources/css/app.css', 'resources/js/app.ts'],
      refresh: true,
    }),
    vue({
      template: {
        transformAssetUrls: {
          base: null,
          includeAbsolute: false,
        },
      },
    }),
  ],
  server: {
    host: '0.0.0.0',
    port: 5173,
    origin: 'http://localhost:8000',
    hmr: {
      host: 'localhost',
      port: 5173,
    },
  },
  resolve: {
    alias: {
      '@': '/resources/js',
    },
  },
})
