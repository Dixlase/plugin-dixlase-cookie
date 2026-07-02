# Dixlase Cookie

For Japanese, see [README.ja.md](./README.ja.md).

GDPR-ready cookie consent for Dixlase: a per-category consent banner (necessary / functional / analytics / marketing) with accept-all, reject-all and save-selection, a persistent "Cookie" trigger to re-open and withdraw consent at any time, per-visitor persistence, and a Core contract so other plugins (e.g. SEO / Google Analytics) can gate their trackers on the visitor's consent. Deliberately privacy-first: no IP, User-Agent, or session id is stored.

## Features

- **Per-category consent banner** — `necessary` (always on) / `functional` / `analytics` / `marketing`, with **Accept all**, **Reject all**, and **Save selection**.
- **Re-openable withdrawal UI** — a persistent "Cookie" trigger lets a visitor change or withdraw consent at any time (GDPR: as easy to withdraw as to give). The banner auto-opens only for visitors who have not decided yet.
- **Per-visitor persistence** — one compact row per visitor, keyed by a UUID in the `dixlase_cookie_consent_id` cookie. Categories are stored as a granted-list.
- **Consent state contract** — implements Core's `App\Contracts\Cookie\ConsentStateProviderInterface` so any plugin can read the current visitor's consent without depending on this plugin directly.
- **`ConsentChanged` event** — dispatched when a visitor's effective decision actually changes, so listeners (e.g. an audit log in another plugin) can react.
- **Re-prompt everyone** — an admin action bumps the consent version, invalidating every stored decision so the banner re-appears after a privacy-policy revision — without touching client cookies.
- **Admin settings** — enable the banner, set the persistent cookie lifetime, and add optional privacy / cookie policy links (absolute URL or site path).
- **Privacy-first / data minimization** — records only `consent_id`, the category map, the policy version, and a timestamp. No IP / User-Agent / session id.
- **Maintenance command** — `dls:cookie:prune` drops abandoned rows stamped with an out-of-date policy version (opt-in, scheduler-friendly).
- **Multilingual** — English and Japanese UI strings.

## Installation

Open the admin panel under **Dashboard → Plugins**, find this plugin, then download and enable it. The plugin's tables are created automatically on enable.

## Usage

Once enabled, **Cookie** appears in the admin sidebar with a **Cookie Management Settings** screen. Turn the banner on there, set the cookie lifetime, and paste your privacy / cookie policy links if you have them.

With the banner enabled, visitors who have not made a choice see it automatically; everyone also gets a persistent "Cookie" trigger to re-open and change their choice later. After revising your privacy policy, use **Ask everyone to re-consent** to re-prompt all visitors.

## Consent state contract

Other plugins gate behaviour on consent through Core's contract, treating this plugin as an optional soft dependency:

```php
use App\Contracts\Cookie\ConsentStateProviderInterface;
use App\Enums\ConsentCategory;

if (app()->bound(ConsentStateProviderInterface::class)) {
    $provider = app(ConsentStateProviderInterface::class);
    if (! $provider->has(ConsentCategory::Analytics->value)) {
        return; // analytics consent not granted — do not emit the tracker
    }
}
// Not installed, or consent granted → behave as usual.
```

The `App\Events\ConsentChanged` event carries the before/after category snapshots and the consent version, so an audit or analytics listener can record or react to each change.

## License

Dixlase Cookie is distributed under a **dual license**:

- **Open Source License**: [GNU General Public License v3](./LICENSE)
- **Commercial License**: A separate commercial license is planned for use cases where GPL v3 compliance is not feasible. **It is not yet available** — only a draft of the eventual terms is present in [LICENSE-COMMERCIAL](./LICENSE-COMMERCIAL). For availability timing or other questions, contact **info@dixlase.org**.

A short overview of how these files fit together is in [NOTICE](./NOTICE) ([日本語](./NOTICE.ja)).

## Contributing

The Contributor License Agreement (CLA) is still under review, so code Pull Requests are not being accepted at this time. Once the CLA is finalized, contributions will open under the [Dixlase Copyright Policy](https://github.com/Dixlase/dixlase-core/blob/main/COPYRIGHT-POLICY.md) and the Dixlase CLA (see CONTRIBUTING.md). Bug reports and proposals via Issues are welcome in the meantime.

---

(C) exc-D inc.
