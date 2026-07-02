# Dixlase Cookie

For English, see [README.md](./README.md).

Dixlase 用の GDPR 対応クッキー同意プラグイン。カテゴリ別（必須 / 機能 / 分析 / マーケティング）の同意バナーを、すべて許可・すべて拒否・選択を保存に対応して表示し、常設の「Cookie」トリガーからいつでも再オープンして同意を撤回できます。訪問者ごとに状態を保存し、コアの契約を実装するため、他プラグイン（SEO / Google アナリティクス等）が訪問者の同意に応じてトラッカーを制御できます。プライバシー最優先の設計で、IP・User-Agent・セッション ID は保存しません。

## 機能

- **カテゴリ別同意バナー** — `necessary`（常時有効）/ `functional` / `analytics` / `marketing`。**すべて許可**・**すべて拒否**・**選択を保存**に対応。
- **再オープン可能な撤回 UI** — 常設の「Cookie」トリガーから、いつでも同意の変更・撤回が可能（GDPR「撤回は同意と同じくらい簡単に」）。バナーは未決定の訪問者にのみ自動表示。
- **訪問者ごとの保存** — 訪問者ごとにコンパクトな 1 行で保存。`dixlase_cookie_consent_id` Cookie の UUID をキーにし、カテゴリは許可リスト形式で保存。
- **同意状態の契約** — コアの `App\Contracts\Cookie\ConsentStateProviderInterface` を実装。他プラグインは本プラグインへ直接依存せず現在の同意状態を参照できます。
- **`ConsentChanged` イベント** — 訪問者の実効的な決定が実際に変化したときに発火。リスナー（例: 別プラグインの監査ログ）が反応できます。
- **全員に再同意を求める** — 管理操作で同意バージョンを更新し、保存済みの決定をすべて無効化。ポリシー改定後に、クライアント Cookie を触らずにバナーを再表示できます。
- **管理設定** — バナーの有効化、永続 Cookie の寿命、任意のプライバシー / クッキーポリシーのリンク（絶対 URL またはサイトパス）を設定。
- **プライバシー最優先 / データ最小化** — `consent_id`・カテゴリマップ・ポリシーバージョン・日時のみを記録。IP / User-Agent / セッション ID は保存しません。
- **メンテナンスコマンド** — `dls:cookie:prune` で、古いポリシーバージョンの放棄行を削除（任意実行・スケジューラ向け）。
- **多言語** — 英語・日本語の UI 文言。

## インストール

管理画面の **ダッシュボード → プラグイン** から本プラグインを検索し、ダウンロード → 有効化します。有効化すると本プラグイン用のテーブルが自動で作成されます。

## 使い方

有効化すると管理画面のサイドバーに **Cookie** が追加され、**Cookie 管理設定** 画面が使えます。ここでバナーを有効化し、Cookie の寿命や、あればプライバシー / クッキーポリシーのリンクを設定します。

バナーを有効化すると、未決定の訪問者には自動でバナーが表示され、加えて全員に常設の「Cookie」トリガーが表示されて後から選択を変更できます。プライバシーポリシー改定後は **全員に再同意を求める** で全訪問者に再提示できます。

## 同意状態の契約

他プラグインは、本プラグインを任意のソフト依存として扱いながら、コアの契約経由で同意に応じた制御を行えます。

```php
use App\Contracts\Cookie\ConsentStateProviderInterface;
use App\Enums\ConsentCategory;

if (app()->bound(ConsentStateProviderInterface::class)) {
    $provider = app(ConsentStateProviderInterface::class);
    if (! $provider->has(ConsentCategory::Analytics->value)) {
        return; // analytics 同意なし — トラッカーを出力しない
    }
}
// 未導入、または同意済み → 従来どおり動作。
```

`App\Events\ConsentChanged` イベントは前後のカテゴリスナップショットと同意バージョンを保持するため、監査・分析リスナーが各変更を記録・処理できます。

## ライセンス

Dixlase Cookie は **デュアルライセンス** で配布されています。

- **オープンソースライセンス**: [GNU General Public License v3](./LICENSE)
- **商用ライセンス**: GPL v3 の遵守が現実的でないユースケース向けに、別途商用ライセンスの提供を予定しています。

**現時点では商用ライセンスはまだ提供しておりません。**  
(雛形のみ [LICENSE-COMMERCIAL](./LICENSE-COMMERCIAL) に Draft として置いています)。  
提供開始時期や条件に関するお問い合わせは **info@dixlase.org** までご連絡ください。

各ファイルの関係概要は [NOTICE.ja](./NOTICE.ja)([English](./NOTICE))にあります。

## コントリビューションについて

CLA (Contributor License Agreement) のレビュー中のため、現在 Pull Request を受け付けていません。  
CLA 確定後に受付を開始し、その時点から [Dixlase Copyright Policy](https://github.com/Dixlase/dixlase-core/blob/main/COPYRIGHT-POLICY.md) と Dixlase CLA(詳細は CONTRIBUTING.md)の対象となります。  
それまでも Issue での不具合報告・機能提案は歓迎しています。

---

(C) exc-D inc.
