# 変更履歴

DixlaseCookie プラグインの主要な変更はすべてこのファイルに記録します。

フォーマットは [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) に準拠し、
本プラグインはセマンティックバージョニングに従います。

## [0.1.1] — 2026-09-27

### 変更

- インストール済みサイトでプラグインの更新とロールバックを検証するためのリリース。
  バージョン番号以外に機能の変更はありません。

## [0.1.0] — 2026-10-01
初回リリース。Dixlase `^0.1.0`（Plugin API `^0.1`）、PHP `>= 8.3` が必要です。

### 追加

- カテゴリ別バナーを備えた GDPR 対応の Cookie 同意 — 必須、機能、分析、
  マーケティング。
- 訪問者がいつでも同意を撤回・変更できる、再表示可能な同意パネル。
- `InjectCookieConsentBanner` ミドルウェアを介して、フロントエンド応答に
  同意バナーを注入。
- コアの `App\Contracts\Cookie\ConsentStateProviderInterface` を実装し、他の
  プラグインが同意状態を読み取れるようにする（例: DixlaseSEO の分析ゲーティング、
  DixlaseLegal の同意ログ）。
- 同意の更新時に、コアの `ConsentChanged` イベントを発行。
- 放置された／古くなった同意行を削除する `dls:cookie:prune` Artisan コマンド。
