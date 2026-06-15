import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/qr-builder.js', 'resources/js/bio-builder.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
