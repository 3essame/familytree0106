import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import path from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
        vue(),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, './resources/js'),
            '~': path.resolve(__dirname, './node_modules'),
        },
    },
    optimizeDeps: {
        include: ['vue', 'vue-router', 'pinia', 'axios', 'd3'],
    },
    build: {
        sourcemap: true,
        rollupOptions: {
            input: {
                app: '/resources/js/app.js'
            }
        }
    },
    server: {
        host: 'localhost',
        port: 5173,
        hmr: {
            host: 'localhost'
        },
        proxy: {
            '/api': {
                target: 'http://localhost:8000',
                changeOrigin: true,
                secure: false,
            }
        }
    },
});

