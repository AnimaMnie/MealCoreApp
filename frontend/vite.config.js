import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

// W trybie deweloperskim (npm run dev) requesty /api są przekierowywane do backendu Laravel.
export default defineConfig({
  plugins: [vue()],
  server: {
    port: 5173,
    proxy: {
      '/api': process.env.VITE_API_PROXY || 'http://localhost:8080',
    },
  },
})
