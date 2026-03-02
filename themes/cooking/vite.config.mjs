import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

const defaultOutDir = 'assets/dist';
const baseUrl = process.env.VITE_BASE || '/themes/cooking';

export default defineConfig({
    envDir: '../../',
    base: `${baseUrl}/${defaultOutDir}`,
    build: {
        outDir: defaultOutDir,
        assetsDir: '',
        manifest: true
    },
    plugins: [
        laravel({
            publicDirectory: defaultOutDir,
            input: [
                'assets/src/css/theme-cooking.css',
                'assets/src/js/theme-cooking.js',
            ],
            refresh: {
                paths: [
                    './**/*.htm',
                    './**/*.block',
                    'assets/src/**/*.css',
                    'assets/src/**/*.js',
                    'assets/src/**/*.jsx',
                ]
            },
        }),
    ],
});
