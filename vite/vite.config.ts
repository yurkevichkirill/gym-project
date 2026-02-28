import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import tailwindcss from '@tailwindcss/vite'

export default defineConfig({
    base: "/",
    plugins: [
        react(),
        tailwindcss(),
    ],
    server: {
        port: 5173,
        strictPort: true,
        host: true,
        proxy: {
            '/api': 'http://nginx:80'
        },
    },
});
