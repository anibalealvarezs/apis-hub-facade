import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css', 
                'resources/css/driver-theme.css',
                'resources/js/app.js', 
                'resources/js/theme.js', 
                'resources/js/gtm.js',
                'resources/js/filament-charts.js',
                'resources/js/formula-editor.js'
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
