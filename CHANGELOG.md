# Changelog

All notable changes to the DixlaseCookie plugin are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this plugin follows Semantic Versioning.

## [0.1.0] — 2026-10-01
Initial release. Requires Dixlase `^0.1.0` (Plugin API `^0.1`), PHP `>= 8.3`.

### Added

- GDPR-ready cookie consent with a per-category banner — necessary, functional,
  analytics, and marketing.
- Re-openable consent panel so visitors can withdraw or change consent at any time.
- Injects the consent banner into front-end responses via the
  `InjectCookieConsentBanner` middleware.
- Implements Core's `App\Contracts\Cookie\ConsentStateProviderInterface` so other
  plugins can read consent state (e.g. DixlaseSEO's analytics gating and
  DixlaseLegal's consent log).
- Dispatches Core's `ConsentChanged` event on consent updates.
- `dls:cookie:prune` Artisan command to prune abandoned / out-of-date consent rows.
