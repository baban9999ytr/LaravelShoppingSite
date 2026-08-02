import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.ts'],
            refresh: [
                'resources/views/**',
                'resources/js/**',
                'resources/css/**',
                'routes/**',
            ],
            // FORCE 127.0.0.1 for hot reload file generation
            hotFile: 'public/hot',
            detectTls: false,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        inertia(),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        wayfinder({
            formVariants: true,
        }),
    ],
    server: {
        host: '127.0.0.1',
        port: 5173,
        strictPort: true,
        cors: {
            origin: '*',
            methods: ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
        },
        hmr: {
            host: '127.0.0.1',
            clientPort: 5173,
        },
        watch: {
            ignored: [
                '**/storage/**',
                '**/bootstrap/cache/**',
                '**/database/**',
                '**/episode_3*',
                '**/*.sqlite*',
                '**/*.log',
            ],
        },
    },
});