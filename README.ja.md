# Dixlase Cookie

For English, see [README.md](./README.md).

Dixlase 用の GDPR 対応クッキー同意プラグイン。

- カテゴリ別(必須 / 機能 / 分析 / マーケティング)の同意バナー
- いつでも再オープンして撤回できる常設の「Cookie」トリガー
- 訪問者ごとにコンパクトな 1 行で保存
- ポリシー改定後に全員へ再同意を求めるバージョン更新
- 管理画面でバナー有効化・Cookie 寿命・ポリシーリンクを設定
- IP / User-Agent / セッション ID は保存しないプライバシー最優先設計

## 機能

- **カテゴリ別バナー** — `necessary`(常時有効)/ `functional` / `analytics` / `marketing`。すべて許可 / すべて拒否 / 選択を保存に対応。
- **再オープン可能な撤回** — 常設の「Cookie」トリガーからいつでも変更・撤回可能。バナーは未決定の訪問者にのみ自動表示。
- **訪問者ごとの保存** — 訪問者ごとに 1 行。Cookie の UUID をキーにし、カテゴリは許可リスト形式で保存。
- **全員に再同意** — 同意バージョンを更新して、クライアント Cookie を触らずポリシー改定後にバナーを再表示。
- **管理設定** — バナーの有効化、Cookie の寿命、任意のプライバシー / クッキーポリシーリンクを設定。
- **プライバシー最優先** — `consent_id`・カテゴリマップ・ポリシーバージョン・日時のみを記録。
- **メンテナンスコマンド** — `dls:cookie:prune` で古いバージョンの放棄行を削除。

## インストール

管理画面の **ダッシュボード → プラグイン** から本プラグインを検索し、ダウンロード → 有効化します。  
有効化すると本プラグイン用のテーブルが自動で作成されます。

## 使い方

有効化すると管理画面のサイドバーに **Cookie** が追加され、**Cookie 管理設定** 画面が使えます。  
バナーの有効化・Cookie 寿命・ポリシーリンクを設定でき、ポリシー改定後は **全員に再同意を求める** で全訪問者に再提示できます。

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
