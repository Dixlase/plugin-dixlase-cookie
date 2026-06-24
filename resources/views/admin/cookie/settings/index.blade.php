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

@extends('layouts.admin')

@section('content')
    <form id="cookie-settings-form" action="{{ route('dixlase-cookie::admin.cookie.settings.update') }}" method="POST">
        @csrf
        @method('PATCH')

        <div class="space-y-6">
            {{-- Banner enable + persistent cookie lifetime --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    {{ __('dixlase-cookie::admin/cookie/settings/index.banner_heading') }}
                </h3>
                <x-form-toggle
                    name="cookie_consent_enabled"
                    :label="__('dixlase-cookie::admin/cookie/settings/index.enabled_label')"
                    :checked="old('cookie_consent_enabled', $cookieConsentEnabled)"
                    value="1"
                />
                <x-form-help-text :text="__('dixlase-cookie::admin/cookie/settings/index.enabled_help')" />

                <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">
                        {{ __('dixlase-cookie::admin/cookie/settings/index.lifetime_heading') }}
                    </h4>
                    <x-form-help-text :text="__('dixlase-cookie::admin/cookie/settings/index.lifetime_help', ['max' => $cookieConsentLifetimeMaxDays])" />
                    <div class="mt-3">
                        <x-form-text
                            id="cookie_consent_lifetime_days"
                            name="cookie_consent_lifetime_days"
                            type="number"
                            :min="1"
                            :max="$cookieConsentLifetimeMaxDays"
                            :value="old('cookie_consent_lifetime_days', $cookieConsentLifetimeDays)"
                            :label="__('dixlase-cookie::admin/cookie/settings/index.lifetime_label')"
                        />
                        <x-form-error name="cookie_consent_lifetime_days" />
                    </div>
                </div>
            </div>

            {{-- Optional policy links (URL input pattern, no Legal dependency) --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    {{ __('dixlase-cookie::admin/cookie/settings/index.links_heading') }}
                </h3>
                <x-form-help-text :text="__('dixlase-cookie::admin/cookie/settings/index.links_help')" />

                <div class="mt-3 space-y-4">
                    <div>
                        <x-form-text
                            id="cookie_consent_privacy_url"
                            name="cookie_consent_privacy_url"
                            type="text"
                            :value="old('cookie_consent_privacy_url', $privacyUrl)"
                            :label="__('dixlase-cookie::admin/cookie/settings/index.privacy_url_label')"
                            placeholder="/privacy"
                        />
                        <x-form-error name="cookie_consent_privacy_url" />
                    </div>
                    <div>
                        <x-form-text
                            id="cookie_consent_cookie_url"
                            name="cookie_consent_cookie_url"
                            type="text"
                            :value="old('cookie_consent_cookie_url', $cookieUrl)"
                            :label="__('dixlase-cookie::admin/cookie/settings/index.cookie_url_label')"
                            placeholder="/cookies"
                        />
                        <x-form-error name="cookie_consent_cookie_url" />
                    </div>
                </div>
            </div>
        </div>
    </form>

    {{-- Consent version: re-prompt every visitor. Separate form so it is
         not submitted with the settings PATCH and not nested in it. --}}
    <div class="mt-6 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
            {{ __('dixlase-cookie::admin/cookie/settings/index.version_heading') }}
        </h3>
        <x-form-help-text :text="__('dixlase-cookie::admin/cookie/settings/index.version_help', ['version' => $currentVersion])" />

        <div class="mt-4">
            <form id="cookie-bump-version-form" action="{{ route('dixlase-cookie::admin.cookie.settings.bump-version') }}" method="POST" class="contents">
                @csrf
                <x-form-button
                    type="button"
                    variant="warning"
                    :label="__('dixlase-cookie::admin/cookie/settings/index.bump_button')"
                    icon="fas fa-rotate-right"
                    @click="openModal('confirmCookieBumpModal')"
                />
            </form>
        </div>

        <x-ui-modal
            id="confirmCookieBumpModal"
            :title="__('dixlase-cookie::admin/cookie/settings/index.bump_confirm_title')"
            :message="__('dixlase-cookie::admin/cookie/settings/index.bump_confirm_message')"
            :confirm_label="__('dixlase-cookie::admin/cookie/settings/index.bump_button')"
            :cancel_label="__('common.cancel')"
            icon_type="warning"
            confirm_color="yellow"
            form="cookie-bump-version-form"
        />
    </div>
@endsection

@section('save')
    <x-admin.save-button
        id_confirmation="confirmCookieSettingsModal"
        :label="__('common.save')"
        :title="__('dixlase-cookie::admin/cookie/settings/index.confirm_title')"
        :message="__('dixlase-cookie::admin/cookie/settings/index.confirm_message')"
        :confirm_label="__('common.save')"
        :cancel_label="__('common.cancel')"
        form="cookie-settings-form"
    />
@endsection
