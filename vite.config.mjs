import { defineConfig } from 'vite';
import path from 'path';

export default defineConfig({
    build: {
        outDir: path.resolve(__dirname, 'resources/assets'),
        emptyOutDir: true,
        copyPublicDir: false,
        manifest: 'manifest.json',
        rollupOptions: {
            input: {
                app: path.resolve(__dirname, 'resources/src/js/app.js'),
                style: path.resolve(__dirname, 'resources/src/css/style.scss'),
            },
            output: {
                entryFileNames: 'js/[name].js',
                chunkFileNames: 'js/[name].js',
                assetFileNames: 'css/[name][extname]',
            },
        },
    },
    css: {
        preprocessorOptions: {
            scss: {
                api: 'modern-compiler',
            },
        },
    },
});