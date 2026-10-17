import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

// The PHP API is served by Apache/XAMPP at http://localhost/Adhub/api.
// Proxying through the dev server keeps the browser same-origin, so the
// PHP session cookie flows without any CORS setup.
export default defineConfig({
  plugins: [react()],
  server: {
    proxy: {
      '/api': 'http://localhost/Adhub',
    },
  },
});
