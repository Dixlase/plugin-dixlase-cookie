<?php

/**
 * This file is part of DixlaseCookie.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * DixlaseCookie is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU General Public License version 3 or later, as published
 *       by the Free Software Foundation; or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the GPL terms below.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace Plugins\DixlaseCookie\App\Providers;

use App\Contracts\CspPolicyProvider;
use Illuminate\Support\ServiceProvider;

/**
 * プラグインのServiceProvider
 * 
 * CspPolicyProviderを実装することで、プラグインが必要とする
 * 外部リソースのCSPディレクティブを宣言できます。
 * 外部リソースが不要な場合は、implements CspPolicyProvider と
 * getCspDirectives() メソッドを削除してください。
 */
class DixlaseCookieServiceProvider extends ServiceProvider implements CspPolicyProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // 設定ファイルをマージ
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/dixlase_cookie.php',
            'dixlase_cookie'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // CSPポリシーの登録（外部リソースが必要な場合）
        $this->registerCspPolicy();

        // ビューの登録
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'dixlase-cookie');

        // 翻訳ファイルの登録
        $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'dixlase-cookie');

        // マイグレーションの登録
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // 注: ルート（routes/web.php, routes/admin.php, routes/api.php）はPluginServiceProviderが自動読み込み

        // 公開可能なアセット
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/dixlase_cookie.php' => config_path('dixlase_cookie.php'),
            ], 'dixlase-cookie-config');

            $this->publishes([
                __DIR__ . '/../../resources/views' => resource_path('views/vendor/dixlase-cookie'),
            ], 'dixlase-cookie-views');
        }
    }

    /**
     * CSPポリシーを登録
     * 
     * 外部リソースが不要な場合は、このメソッドと
     * getCspDirectives() メソッドを削除してください。
     */
    protected function registerCspPolicy(): void
    {
        if (app()->bound(\App\Services\Csp\CspPolicyRegistry::class)) {
            app(\App\Services\Csp\CspPolicyRegistry::class)
                ->registerProvider('dixlase-cookie', $this);
        }
    }

    /**
     * CSPディレクティブを取得
     * 
     * プラグインが必要とする外部リソースのドメインを指定します。
     * 不要な場合は空の配列を返すか、このメソッドを削除してください。
     * 
     * @return array<string, array<string>>
     */
    public function getCspDirectives(): array
    {
        return [
            // 例: 外部スクリプトが必要な場合
            // 'script-src' => ['https://cdn.example.com'],
            // 
            // 例: 外部スタイルシートが必要な場合
            // 'style-src' => ['https://fonts.googleapis.com'],
            // 
            // 例: APIへの接続が必要な場合
            // 'connect-src' => ['https://api.example.com'],
            // 
            // 例: 外部画像が必要な場合
            // 'img-src' => ['https://images.example.com'],
            // 
            // 例: iframeの埋め込みが必要な場合
            // 'frame-src' => ['https://youtube.com', 'https://vimeo.com'],
        ];
    }

}