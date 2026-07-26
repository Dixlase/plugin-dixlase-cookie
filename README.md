# Dixlase Cookie

For Japanese, see [README.ja.md](./README.ja.md).

GDPR-ready cookie consent for Dixlase: a per-category consent banner with a re-openable withdrawal panel, per-visitor persistence, and a Core contract so other plugins can gate their trackers on consent. Privacy-first — no IP, User-Agent, or session id is stored.

## Features

- **Per-category banner** — `necessary` (always on) / `functional` / `analytics` / `marketing`, with Accept all / Reject all / Save selection.
- **Re-openable withdrawal** — a persistent "Cookie" trigger to change or withdraw consent anytime; the banner auto-opens only for undecided visitors.
- **Per-visitor persistence** — one row per visitor, keyed by a UUID cookie; categories stored as a compact granted-list.
- **Re-prompt everyone** — bump the consent version to re-show the banner after a privacy-policy revision, without touching client cookies.
- **Admin settings** — enable the banner, set the cookie lifetime, add optional privacy / cookie policy links.
- **Privacy-first** — records only `consent_id`, the category map, the policy version, and a timestamp.
- **Maintenance command** — `dls:cookie:prune` drops abandoned rows stamped with an out-of-date policy version.

## Installation

Open the admin panel under **Dashboard → Plugins**, find this plugin, then download and enable it.  
The plugin's tables are created automatically on enable.

## Usage

Once enabled, **Cookie** appears in the admin sidebar with a **Cookie Management Settings** screen.  
Turn the banner on, set the cookie lifetime, add policy links, or use **Ask everyone to re-consent** to re-prompt all visitors after a policy change.

## License

Dixlase Cookie is distributed under a **dual license**:

- **Open Source License**: [GNU General Public License v3](./LICENSE)
- **Commercial License**: A separate commercial license is planned for use cases where GPL v3 compliance is not feasible. **It is not yet available** — only a draft of the eventual terms is present in [LICENSE-COMMERCIAL](./LICENSE-COMMERCIAL). For availability timing or other questions, contact **info@dixlase.org**.

A short overview of how these files fit together is in [NOTICE](./NOTICE) ([日本語](./NOTICE.ja)).

## Contributing

We do not yet accept external code Pull Requests.  
They will open once we have assessed core API stability and how the project operates after the initial release, and prepared a Contributor License Agreement (CLA) that has passed legal review.  
Once the CLA is finalized, contributions will fall under the [Dixlase Copyright Policy](https://github.com/Dixlase/dixlase-core/blob/main/COPYRIGHT-POLICY.md) and the Dixlase CLA (see CONTRIBUTING.md).  
Bug reports and proposals via Issues are welcome.  
For feature proposals, please take a look at [the Dixlase philosophy](https://dixlase.org/en/philosophy) — and consider whether the feature belongs in the core or could work as a plugin. It helps us align on direction.

---

© 2026 exc-D inc. and Dixlase contributors
