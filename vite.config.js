import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                // Headings and prices (docs/decisions.md, design system).
                bunny('Bricolage Grotesque', {
                    alias: 'display',
                    variable: '--font-source-display',
                    weights: [600, 700],
                    preload: [{ weight: 700 }],
                }),
                // Body text. Includes Devanagari for future Hindi labels
                // (only downloaded when Devanagari characters are on the page).
                bunny('Mukta', {
                    alias: 'body',
                    variable: '--font-source-body',
                    weights: [400, 500, 600],
                    subsets: ['latin', 'devanagari'],
                    preload: [{ weight: 400 }],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
