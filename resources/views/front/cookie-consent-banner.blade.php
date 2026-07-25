{{--
This file is part of DixlaseCookie.

Copyright (C) 2026 exc-D inc. and Dixlase contributors
https://exc-d.com

DixlaseCookie is dual-licensed. You may use this file under either:

  (a) the GNU General Public License version 3 or later, as published
      by the Free Software Foundation; or

  (b) a commercial license agreement obtained from exc-D inc.

Unless you have entered into a commercial license agreement, this
file is governed by the GPL terms below.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
--}}

{{--
    GDPR-ready cookie consent UI — banner + re-openable withdrawal panel.

    Theme-independent by design: the markup uses only this plugin's own
    `dxlc-*` classes, styled by `resources/assets/banner.css` (injected by
    the middleware). It does NOT depend on the host theme's Tailwind build,
    so changing the banner never requires rebuilding the theme.

    Self-contained: uses only an inline Alpine x-data object and never
    @push/@section/@stack, so it can be rendered standalone from the
    injector middleware without corrupting the host page's Blade stack.

    Expects:
    - $autoOpen: bool  open the panel as a banner on load (first visit /
                       no current decision); otherwise it stays closed
                       behind the persistent "Cookie" trigger
    - $current:  array<string,bool>  the visitor's current category map,
                       used to pre-fill the toggles when re-opening
    - $links:    list<array{url:string,label:string}>  optional policy links

    The submit() helper posts the chosen category map to the accept
    endpoint; necessary is implicit server-side. Every action (accept-all,
    reject-all, save-selection) records the decision, so changing a choice
    here IS the withdrawal mechanism. On a non-OK response the panel stays
    open so the visitor can retry rather than silently losing their choice.
--}}
@php($links = $links ?? [])
@php($autoOpen = $autoOpen ?? true)
@php($current = $current ?? [])

<div data-cookie-consent
     data-autoopen="{{ $autoOpen ? '1' : '0' }}"
     class="dxlc"
     x-data="{
        open: {{ $autoOpen ? 'true' : 'false' }},
        {{-- Strings '1'/'0' so an unchecked toggle posts a definite denial. --}}
        cats: {
            functional: '{{ ($current['functional'] ?? false) ? '1' : '0' }}',
            analytics: '{{ ($current['analytics'] ?? false) ? '1' : '0' }}',
            marketing: '{{ ($current['marketing'] ?? false) ? '1' : '0' }}'
        },
        async submit(payload) {
            try {
                const r = await fetch('{{ route('dixlase-cookie::cookie-consent.accept') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });
                if (r.ok) this.open = false;
            } catch (e) {}
        },
        acceptAll() { this.cats = { functional: '1', analytics: '1', marketing: '1' }; this.submit(this.cats); },
        rejectAll() { this.cats = { functional: '0', analytics: '0', marketing: '0' }; this.submit(this.cats); },
        saveSelection() { this.submit(this.cats); }
     }">

    {{-- Persistent re-open trigger: lets a visitor change or withdraw
         their consent at any time (GDPR: as easy to withdraw as to give). --}}
    <button type="button"
            @click="open = true"
            x-show="!open"
            x-cloak
            data-cookie-consent-trigger
            class="dxlc-trigger">
        <svg class="dxlc-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.94 6.94a1.5 1.5 0 112.12 2.12 1.5 1.5 0 01-2.12-2.12zM12 13a1 1 0 11-2 0 1 1 0 012 0zM7 11a1 1 0 100-2 1 1 0 000 2zm6.5-2.5a1 1 0 11-2 0 1 1 0 012 0z" clip-rule="evenodd" />
        </svg>
        {{ __('dixlase-cookie::front/cookie-consent.reopen') }}
    </button>

    {{-- Consent panel: banner on first visit, re-openable editor after. --}}
    <div x-show="open"
         x-transition:enter="dxlc-enter"
         x-transition:enter-start="dxlc-enter-from"
         x-transition:enter-end="dxlc-enter-to"
         x-transition:leave="dxlc-leave"
         x-transition:leave-start="dxlc-leave-from"
         x-transition:leave-end="dxlc-leave-to"
         x-cloak
         class="dxlc-panel"
         role="dialog"
         aria-modal="false"
         aria-label="{{ __('dixlase-cookie::front/cookie-consent.header') }}"
         aria-live="polite">
        <div class="dxlc-card">
            {{-- Close: only meaningful when re-opened; dismisses without
                 recording a change. --}}
            <button type="button"
                    @click="open = false"
                    class="dxlc-close"
                    aria-label="{{ __('dixlase-cookie::front/cookie-consent.close') }}">
                <svg class="dxlc-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>

            <div class="dxlc-head">
                <p class="dxlc-title">{{ __('dixlase-cookie::front/cookie-consent.header') }}</p>
                <p class="dxlc-message">{{ __('dixlase-cookie::front/cookie-consent.message') }}</p>
                @if (! empty($links))
                    <div class="dxlc-links">
                        @foreach ($links as $i => $link)
                            @if ($i > 0)
                                <span class="dxlc-sep">/</span>
                            @endif
                            <a href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer" class="dxlc-link">{{ $link['label'] }}</a>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Category toggles. Per-category descriptions intentionally live
                 in the linked cookie policy, not inline, to keep it small. --}}
            <div class="dxlc-cats">
                <label class="dxlc-toggle dxlc-toggle--disabled">
                    <input type="checkbox" class="dxlc-toggle-input" checked disabled>
                    <span class="dxlc-toggle-track" aria-hidden="true"><span class="dxlc-toggle-thumb"></span></span>
                    <span class="dxlc-toggle-label">{{ __('dixlase-cookie::front/cookie-consent.necessary_label') }}</span>
                    <span class="dxlc-badge">{{ __('dixlase-cookie::front/cookie-consent.always_on') }}</span>
                </label>

                <label class="dxlc-toggle">
                    <input type="checkbox" class="dxlc-toggle-input"
                           :checked="cats.functional === '1'"
                           @change="cats.functional = $event.target.checked ? '1' : '0'">
                    <span class="dxlc-toggle-track" aria-hidden="true"><span class="dxlc-toggle-thumb"></span></span>
                    <span class="dxlc-toggle-label">{{ __('dixlase-cookie::front/cookie-consent.functional_label') }}</span>
                </label>

                <label class="dxlc-toggle">
                    <input type="checkbox" class="dxlc-toggle-input"
                           :checked="cats.analytics === '1'"
                           @change="cats.analytics = $event.target.checked ? '1' : '0'">
                    <span class="dxlc-toggle-track" aria-hidden="true"><span class="dxlc-toggle-thumb"></span></span>
                    <span class="dxlc-toggle-label">{{ __('dixlase-cookie::front/cookie-consent.analytics_label') }}</span>
                </label>

                <label class="dxlc-toggle">
                    <input type="checkbox" class="dxlc-toggle-input"
                           :checked="cats.marketing === '1'"
                           @change="cats.marketing = $event.target.checked ? '1' : '0'">
                    <span class="dxlc-toggle-track" aria-hidden="true"><span class="dxlc-toggle-thumb"></span></span>
                    <span class="dxlc-toggle-label">{{ __('dixlase-cookie::front/cookie-consent.marketing_label') }}</span>
                </label>
            </div>

            <div class="dxlc-actions">
                <button type="button" @click="rejectAll()" class="dxlc-btn dxlc-btn--secondary">
                    {{ __('dixlase-cookie::front/cookie-consent.reject_all') }}
                </button>
                <button type="button" @click="saveSelection()" class="dxlc-btn dxlc-btn--secondary">
                    {{ __('dixlase-cookie::front/cookie-consent.save_selection') }}
                </button>
                <button type="button" @click="acceptAll()" class="dxlc-btn dxlc-btn--primary">
                    {{ __('dixlase-cookie::front/cookie-consent.accept_all') }}
                </button>
            </div>
        </div>
    </div>
</div>
