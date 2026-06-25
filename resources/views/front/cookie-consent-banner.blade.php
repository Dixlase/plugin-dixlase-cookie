{{--
This file is part of DixlaseCookie.

Copyright (C) 2026 exc-D inc.
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

    Self-contained on purpose: this partial uses only an inline Alpine
    x-data object and never @push/@section/@stack, so it can be rendered
    standalone from the injector middleware without corrupting the host
    page's Blade section stack.

    Expects:
    - $autoOpen: bool  open the panel as a banner on load (first visit /
                       no current decision); otherwise it stays closed
                       behind the persistent "Cookie settings" trigger
    - $current:  array<string,bool>  the visitor's current category map,
                       used to pre-fill the toggles when re-opening
    - $links:    list<array{url:string,label:string}>  optional policy links

    The submit() helper posts the chosen category map to the accept
    endpoint; necessary is implicit server-side. Every action (accept-all,
    reject-all, save-selection) appends a new consent row, so changing a
    choice here IS the withdrawal mechanism. On a non-OK response the
    panel stays open so the visitor can retry rather than silently losing
    their choice.
--}}
@php($links = $links ?? [])
@php($autoOpen = $autoOpen ?? true)
@php($current = $current ?? [])

<div data-cookie-consent
     data-autoopen="{{ $autoOpen ? '1' : '0' }}"
     x-data="{
        open: {{ $autoOpen ? 'true' : 'false' }},
        {{-- Strings '1'/'0' to match the core x-form-toggle xModel contract. --}}
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
            class="fixed bottom-4 left-4 z-40 inline-flex items-center gap-2 rounded-full bg-white/80 dark:bg-gray-900/70 backdrop-blur-md border border-gray-200 dark:border-gray-700 shadow-md px-3 py-2 text-xs font-medium text-gray-700 dark:text-gray-200 hover:bg-white dark:hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.94 6.94a1.5 1.5 0 112.12 2.12 1.5 1.5 0 01-2.12-2.12zM12 13a1 1 0 11-2 0 1 1 0 012 0zM7 11a1 1 0 100-2 1 1 0 000 2zm6.5-2.5a1 1 0 11-2 0 1 1 0 012 0z" clip-rule="evenodd" />
        </svg>
        {{ __('dixlase-cookie::front/cookie-consent.reopen') }}
    </button>

    {{-- Consent panel: banner on first visit, re-openable editor after. --}}
    <div x-show="open"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="translate-y-full opacity-0"
         x-transition:enter-end="translate-y-0 opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="translate-y-0 opacity-100"
         x-transition:leave-end="translate-y-full opacity-0"
         x-cloak
         class="fixed bottom-0 inset-x-0 z-50 p-4"
         role="dialog"
         aria-modal="false"
         aria-label="{{ __('dixlase-cookie::front/cookie-consent.header') }}"
         aria-live="polite">
        <div class="relative max-w-3xl mx-auto bg-white/70 dark:bg-gray-900/60 backdrop-blur-md rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 p-4 sm:p-6 space-y-4">
            {{-- Close: only meaningful when re-opened; dismisses without
                 recording a change. --}}
            <button type="button"
                    @click="open = false"
                    class="absolute top-2 right-2 inline-flex h-8 w-8 items-center justify-center rounded-md text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    aria-label="{{ __('dixlase-cookie::front/cookie-consent.close') }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>

            <div class="space-y-2 pr-8">
                <p class="text-base font-semibold text-gray-900 dark:text-gray-100">
                    {{ __('dixlase-cookie::front/cookie-consent.header') }}
                </p>
                <p class="text-sm text-gray-700 dark:text-gray-300">
                    {{ __('dixlase-cookie::front/cookie-consent.message') }}
                </p>
                @if (! empty($links))
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-gray-600 dark:text-gray-400">
                        @foreach ($links as $i => $link)
                            @if ($i > 0)
                                <span class="text-gray-400 dark:text-gray-500">/</span>
                            @endif
                            <a href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer" class="text-blue-600 dark:text-blue-400 hover:underline">{{ $link['label'] }}</a>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Compact category toggles using the core x-form-toggle
                 component. Per-category descriptions intentionally live in the
                 linked cookie policy, not inline, to keep the banner small. --}}
            <div class="grid gap-x-8 gap-y-0 sm:grid-cols-2">
                <div class="flex items-center gap-2">
                    <x-form-toggle
                        name="cookie_necessary"
                        :label="__('dixlase-cookie::front/cookie-consent.necessary_label')"
                        :checked="true"
                        disabled
                    />
                    <span class="inline-block rounded bg-gray-200 dark:bg-gray-700 px-1.5 py-0.5 text-[10px] uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ __('dixlase-cookie::front/cookie-consent.always_on') }}</span>
                </div>

                <x-form-toggle
                    name="cookie_functional"
                    :label="__('dixlase-cookie::front/cookie-consent.functional_label')"
                    xModel="cats.functional"
                />
                <x-form-toggle
                    name="cookie_analytics"
                    :label="__('dixlase-cookie::front/cookie-consent.analytics_label')"
                    xModel="cats.analytics"
                />
                <x-form-toggle
                    name="cookie_marketing"
                    :label="__('dixlase-cookie::front/cookie-consent.marketing_label')"
                    xModel="cats.marketing"
                />
            </div>

            <div class="flex flex-col sm:flex-row sm:justify-end gap-2">
                <button type="button" @click="rejectAll()"
                        class="inline-flex justify-center items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-200 uppercase tracking-widest hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-400 transition ease-in-out duration-150">
                    {{ __('dixlase-cookie::front/cookie-consent.reject_all') }}
                </button>
                <button type="button" @click="saveSelection()"
                        class="inline-flex justify-center items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-200 uppercase tracking-widest hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-400 transition ease-in-out duration-150">
                    {{ __('dixlase-cookie::front/cookie-consent.save_selection') }}
                </button>
                <button type="button" @click="acceptAll()"
                        class="inline-flex justify-center items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 active:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                    {{ __('dixlase-cookie::front/cookie-consent.accept_all') }}
                </button>
            </div>
        </div>
    </div>
</div>
