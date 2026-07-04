import { defineConfig } from 'vite';
import path from 'path';

// Builds the plugin's front-end assets into resources/assets/ (a gitignored
// build artifact bundled into the release by CI). Currently a single entry:
// the self-contained cookie-consent banner stylesheet, authored as SCSS in
// resources/src/scss/banner.scss and built to css/banner.css, loaded by the
// injector middleware as assets/plugins/DixlaseCookie/css/banner.css.
export default defineConfig({
    build: {
        outDir: path.resolve(__dirname, 'resources/assets'),
        // Do NOT empty the output dir: hand-authored files (thumbnail.png)
        // live alongside the build output in resources/assets.
        emptyOutDir: false,
        copyPublicDir: false,
        manifest: 'manifest.json',
        rollupOptions: {
            input: {
                banner: path.resolve(__dirname, 'resources/src/scss/banner.scss'),
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
