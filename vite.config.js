import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/css/filament/admin/theme.css'],
            refresh: true,
            fonts: [
                // Rose Atelier theme (ADR-017). Headings and prices.
                bunny('DM Serif Display', {
                    alias: 'display',
                    variable: '--font-source-display',
                    weights: [400],
                    preload: [{ weight: 400 }],
                }),
                // Body text.
                bunny('Figtree', {
                    alias: 'body',
                    variable: '--font-source-body',
                    weights: [400, 500, 600, 700],
                    preload: [{ weight: 400 }],
                }),
                // Devanagari fallback for Hindi text (Figtree has no Devanagari).
                // Only downloaded when Devanagari characters are on the page.
                bunny('Mukta', {
                    alias: 'devanagari',
                    variable: '--font-source-devanagari',
                    weights: [400, 600],
                    subsets: ['devanagari'],
                    preload: false,
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
