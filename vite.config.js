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
                // Content-hash JS/CSS entry filenames so each build
                // that changes bytes gets a new URL — automatic cache
                // busting for browsers that already cached the prior
                // asset. Identical builds still hash to the same
                // filename (Vite's `[hash]` is a content hash, not a
                // build timestamp), so no-op rebuilds do not churn
                // URLs. Fonts / images stay stable to keep the browser
                // font cache warm across rebuilds.
                //
                // See dixlase Sep 2026 incident: pre-hash stable URLs
                // let iOS Safari cache pre-refactor CSS indefinitely,
                // and "Clear History and Website Data" did not help.
                // The middleware that injects `<link rel="stylesheet">`
                // for the banner now resolves the URL through the Vite
                // manifest at request time so the hashed filename is
                // picked up automatically — see
                // `InjectCookieConsentBanner::bannerStylesheetUrl()`.
                entryFileNames: 'js/[name]-[hash].js',
                chunkFileNames: 'js/[name]-[hash].js',
                assetFileNames: 'css/[name]-[hash][extname]',
            },
        },
    },
    css: {
        // Anchor PostCSS to this repo so Vite does not walk up and
        // pick up the Dixlase Core `postcss.config.js` (which requires
        // Tailwind — absent from the plugin ZIP and unavailable when
        // building against the release core). Empty object = no
        // PostCSS plugins for this plugin's build.
        postcss: {},
        preprocessorOptions: {
            scss: {
                api: 'modern-compiler',
            },
        },
    },
});
