import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

// https://vite.dev/config/
export default defineConfig({
  plugins: [vue()],
  server: {
    proxy: {
      '/api': {
        //target: 'http://localhost:80/RoomMate', 
        changeOrigin: true,
        //rewrite: (path) => path.replace(/^\/api/, '/backend/pages/api')
        rewrite: (path) => path.replace(/^\/api/, '/RoomMate/backend/pages/api')
      }
    }
  }
})