import { defineConfig } from 'vitest/config'
import vue from '@vitejs/plugin-vue'
import { resolve } from 'path'

export default defineConfig({
  plugins: [vue()],
  test: {
    environment: 'happy-dom',
    globals: true,
    include: ['resources/js/**/*.test.ts'],
    coverage: {
      provider: 'v8',
      include: ['resources/js/composables/imageStudio/**', 'resources/js/workers/**'],
    },
  },
  resolve: {
    alias: {
      '@': resolve(__dirname, 'resources/js'),
    },
  },
})
