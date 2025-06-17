import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
    ],
    server: {
        host: '0.0.0.0', // Listen on all interfaces
        hmr: {
            host: 'eureka.test', // Replace with your dev domain if needed
        },
    },
    optimizeDeps: {
        include: ['qr-scanner'],
    },
});
