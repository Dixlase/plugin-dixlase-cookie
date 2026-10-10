# Changelog

All notable changes to the DixlaseCookie plugin are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this plugin follows Semantic Versioning.

## [0.1.5] — 2026-10-10

### Development

- Bump `source-map-js` from 1.2.1 to 1.2.2, a build-time dependency that is not
  shipped to sites (#28).
- The test workflow checks out core with its default token instead of
  `CORE_REPO_TOKEN`. Core is public, and Dependabot pull requests cannot read Actions
  secrets, so their tests stopped before running (#30, fixes #29).

## [0.1.4] — 2026-10-10

### Development

- The four banner-injection feature tests pass again: the test now resolves the HTTP
  kernel before pushing the injector middleware, so the push is not lost when the kernel
  is built later (#27, fixes #21). No runtime code changed.
- The test workflow no longer sets an unused `COMPOSER_AUTH` value (#26, fixes #25).

## [0.1.3] — 2026-10-03

### Changed

- On desktop and tablet the banner sits in the bottom-left corner instead of the bottom
  centre, so it no longer covers the middle of the page (#23, fixes #22).

### Development

- The built front-end assets (`resources/assets/`) are no longer tracked in git (#23,
  fixes #24). A committed `manifest.json` could point at an old build, so a local checkout
  showed the previous banner after pulling. Run `npm run build` in a local checkout;
  deployments and the release ZIP build the assets as before. The assets were never part of
  the signature, so signing is unaffected.

### Upgrade notes

No action needed for sites that install from a release or receive deployments. In a git
checkout of the plugin, run `npm ci && npm run build` once after pulling.

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
