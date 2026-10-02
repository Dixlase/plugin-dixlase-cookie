# Changelog

All notable changes to the DixlaseCookie plugin are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this plugin follows Semantic Versioning.

## [0.1.2] — 2026-10-03

### Changed

- The consent banner opens compact on every screen: a short summary with Reject /
  Customize / Accept on one line, and the category toggles behind a "Customize"
  accordion (#20, fixes #18). It no longer covers about half of a phone screen, and
  desktop and tablet show the same narrow card at the bottom instead of a wide bar.
- Button labels are shorter (Reject / Customize / Accept; 「すべて拒否」「詳細設定」
  「すべて許可」), and "Accept selected" sits below the toggles.

### Known issues

- With the details collapsed, the hidden toggles can still be reached with Tab (#19).
  The main buttons stay visible and usable, so consent is never blocked.

## [0.1.1] — 2026-10-01

### Changed

- Build tooling: `vite` 5 → 8.3.1, with `esbuild` and `postcss` 8.5.28 updated
  alongside (#17). This clears the Dependabot advisories for those packages,
  all of which affect only the development server and the asset build — nothing in
  them is shipped to sites. The prebuilt assets in the release ZIP are produced by
  the same build as before; only their hashed file names change.

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
