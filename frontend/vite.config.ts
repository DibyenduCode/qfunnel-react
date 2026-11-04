import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import path from 'path';

// https://vitejs.dev/config/
export default defineConfig(({ command }) => ({
  base: command === 'serve' ? '/' : (process.env.BUILD_TARGET === 'cpanel' ? '/' : '/qfunnel2/dist/'),
  plugins: [react()],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
    },
  },
  server: {
    port: 5173,
    proxy: {
      '/api': {
        target: 'http://localhost/qfunnel2',
        changeOrigin: true,
        secure: false,
      },
      '/qfunnel2/uploads': {
        target: 'http://localhost',
        changeOrigin: true,
        secure: false,
      },
      '/uploads': {
        target: 'http://localhost/qfunnel2',
        changeOrigin: true,
        secure: false,
      }
    },
  },
  build: {
    outDir: '../public/dist',
    emptyOutDir: true,
    chunkSizeWarningLimit: 1200,
  },
}));
