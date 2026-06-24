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
    GDPR-ready cookie consent banner.

    Self-contained on purpose: this partial uses only an inline Alpine
    x-data object and never @push/@section/@stack, so it can be rendered
    standalone from the injector middleware without corrupting the host
    page's Blade section stack.

    Expects:
    - $links: list<array{url:string,label:string}> optional policy links

    The submit() helper posts the chosen category map to the accept
    endpoint; necessary is implicit server-side. On a non-OK response the
    banner stays visible so the visitor can retry rather than silently
    losing their choice.
--}}
@php($links = $links ?? [])

<div x-data="{
        visible: true,
        cats: { functional: false, analytics: false, marketing: false },
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
                if (r.ok) this.visible = false;
            } catch (e) {}
        },
        acceptAll() { this.submit({ functional: true, analytics: true, marketing: true }); },
        rejectAll() { this.submit({ functional: false, analytics: false, marketing: false }); },
        saveSelection() { this.submit(this.cats); }
     }"
     x-show="visible"
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
    <div class="max-w-3xl mx-auto bg-white/70 dark:bg-gray-900/60 backdrop-blur-md rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 p-4 sm:p-6 space-y-4">
        <div class="space-y-2">
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

        <div class="grid gap-3 sm:grid-cols-2">
            {{-- Necessary: implicit, shown as a disabled always-on toggle. --}}
            <label class="flex items-start gap-3 rounded-md border border-gray-200 dark:border-gray-700 p-3">
                <input type="checkbox" checked disabled
                       class="mt-0.5 h-4 w-4 rounded border-gray-300 text-blue-600 opacity-60 cursor-not-allowed">
                <span class="text-sm">
                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ __('dixlase-cookie::front/cookie-consent.necessary_label') }}</span>
                    <span class="ml-1 inline-block rounded bg-gray-200 dark:bg-gray-700 px-1.5 py-0.5 text-[10px] uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ __('dixlase-cookie::front/cookie-consent.always_on') }}</span>
                    <span class="block text-xs text-gray-500 dark:text-gray-400">{{ __('dixlase-cookie::front/cookie-consent.necessary_description') }}</span>
                </span>
            </label>

            <label class="flex items-start gap-3 rounded-md border border-gray-200 dark:border-gray-700 p-3 cursor-pointer">
                <input type="checkbox" x-model="cats.functional"
                       class="mt-0.5 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <span class="text-sm">
                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ __('dixlase-cookie::front/cookie-consent.functional_label') }}</span>
                    <span class="block text-xs text-gray-500 dark:text-gray-400">{{ __('dixlase-cookie::front/cookie-consent.functional_description') }}</span>
                </span>
            </label>

            <label class="flex items-start gap-3 rounded-md border border-gray-200 dark:border-gray-700 p-3 cursor-pointer">
                <input type="checkbox" x-model="cats.analytics"
                       class="mt-0.5 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <span class="text-sm">
                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ __('dixlase-cookie::front/cookie-consent.analytics_label') }}</span>
                    <span class="block text-xs text-gray-500 dark:text-gray-400">{{ __('dixlase-cookie::front/cookie-consent.analytics_description') }}</span>
                </span>
            </label>

            <label class="flex items-start gap-3 rounded-md border border-gray-200 dark:border-gray-700 p-3 cursor-pointer">
                <input type="checkbox" x-model="cats.marketing"
                       class="mt-0.5 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <span class="text-sm">
                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ __('dixlase-cookie::front/cookie-consent.marketing_label') }}</span>
                    <span class="block text-xs text-gray-500 dark:text-gray-400">{{ __('dixlase-cookie::front/cookie-consent.marketing_description') }}</span>
                </span>
            </label>
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
