/// <reference types="vitest/config" />
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'

// The dev server proxies auth + API routes to Laravel so the session cookie
// is first-party and no CORS configuration is needed during development.
const backend = process.env.VITE_BACKEND_URL ?? 'http://localhost:8000'

export default defineConfig({
  plugins: [vue(), tailwindcss()],
  server: {
    proxy: {
      '/api': backend,
      '/login': backend,
      '/logout': backend,
      '/sanctum': backend,
    },
  },
  test: {
    environment: 'jsdom',
    globals: true,
  },
})
