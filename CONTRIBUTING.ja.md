# DixlaseCookie へのコントリビューション

**DixlaseCookie** へのコントリビューションにご関心をお寄せいただきありがとうございます。本プラグインは Dixlase プロジェクトの一部であり、コントリビューションはすべて **Dixlase Core リポジトリ** に置かれているプロジェクト全体のポリシーに従います。

英語版は [CONTRIBUTING.md](./CONTRIBUTING.md) をご覧ください。

---

## プロジェクト全体のポリシー(正本)

以下の Dixlase Core リポジトリの文書が正本であり、本プラグインを含む全てのコントリビューションに適用されます:

- **[コントリビューションガイド](https://github.com/Dixlase/dixlase-core/blob/main/CONTRIBUTING.ja.md)** — 全体的なワークフロー、コーディングスタイル、テスト、PR 規約
- **[コピーライトポリシー](https://github.com/Dixlase/dixlase-core/blob/main/COPYRIGHT-POLICY.ja.md)** — 高水準のライセンス方針
- **[個人 CLA](https://github.com/Dixlase/dixlase-core/blob/main/CLA-INDIVIDUAL.ja.md)** — 個人向けコントリビューターライセンス契約
- **[法人 CLA](https://github.com/Dixlase/dixlase-core/blob/main/CLA-CORPORATE.ja.md)** — 法人向けコントリビューターライセンス契約

本プラグインは、CLA の独自コピーを保持 **しません**。Core リポジトリの正本 CLA が単一ソースです。これにより、プラグインリポジトリ間でのドリフトを防ぎます。

## なぜ CLA が必要か

**DixlaseCookie は GPL-3.0 + exc-D inc. が提供する商用ライセンスのデュアルライセンス方式** で配布されています。このモデルを維持するためには、exc-D inc. が受領したコントリビューションを両方のライセンスでサブライセンスできることが法的に必要です。CLA は、コントリビューターが所有権を保持しつつ、exc-D inc. に対しその目的に必要な権利を許諾するための仕組みです。

CLA に署名することにより、以下に同意したことになります:

- コントリビューションの **所有権を保持** します
- exc-D inc. に対し、デュアルライセンス方式を支えるに足る、永続的、全世界的、取消不能、サブライセンス可能なライセンスを **許諾** します
- 当該ライセンスの行使を妨げる態様で **著作者人格権を主張しない** ことに同意します
- ライセンス許諾の **権限を有する** ことを確認します(雇用主の許可、原始的創作、第三者素材の開示)

## CLA の提出方法

Dixlase プロジェクトが v0.1.x の期間中、CLA はメールで提出します:

1. [個人 CLA](https://github.com/Dixlase/dixlase-core/blob/main/CLA-INDIVIDUAL.ja.md) (該当する場合は [法人 CLA](https://github.com/Dixlase/dixlase-core/blob/main/CLA-CORPORATE.ja.md) も) を全文お読みください
2. コントリビューター情報欄に記入し、末尾に署名してください
3. 件名 `CLA 提出 — <氏名または組織名>` で **info@dixlase.org** に提出ファイルを添付してメール送信してください。コントリビューションを予定しているプラグインも明記してください

1 通の CLA で Core および公式プラグイン全体のコントリビューションをカバーします。リポジトリごとに別個の CLA に署名する必要はありません。

将来の v0.1.x リリースで、この手動ワークフローは [CLA Assistant](https://cla-assistant.io/) に置き換わり、PR フロー内で署名収集が自動化されます。その際、本セクションは更新されます。

## Git ワークフロー

### ブランチを切る前にローカルチェックアウトを同期する

長期に渡る開発チェックアウトでは、**機能ブランチを切る前に必ずリモートと同期してください**。ワーキングツリーが clean なことと「最新であること」は別物です — `git status` で `## main...origin/main` と出ても、それは「最後に fetch した時点の origin ポインタとローカルが一致する」という意味で、「今この瞬間の上流と一致する」という意味ではありません。

```bash
git fetch origin
git log --oneline HEAD..origin/<base-branch> | head   # 出力なし = ブランチを切って OK
git checkout -b <new-branch>
```

`git log HEAD..origin/<base>` に未取得のコミットがあれば、まずローカルベースを fast-forward / rebase してからブランチを切ること。この一手を飛ばすと、既にマージ済みの変更を二重で実装した PR を出してしまう事故の最大要因になります。

### `git log -S` はファイル内の "移動" を検出しない

「最近 X を変更した人がいるか」を調査する際、`git log -S '<term>'`（pickaxe）はその文字列の **出現回数が変わった** コミットしか検出しないことに注意してください。設定エントリが **同じファイル内で単に移動** した場合（例: SPDX 識別子が refused リストから accepted リストに移動）、`-S` には何も映りません — 出現回数は同じだからです。代わりに以下を使います:

- `git log -- <path>` — そのファイルの全変更履歴
- `git log -G '<regex>'` — 差分に正規表現に合致する行の追加/削除があるコミット（移動も拾える）
- `git log -p -- <path>` — 実差分を読む

## プルリクエストの提出方法

1. 本リポジトリを fork し、機能ブランチを作成
2. [Core コントリビューションガイド](https://github.com/Dixlase/dixlase-core/blob/main/CONTRIBUTING.ja.md) の規約に従って変更を加える
3. 変更内容に対するテストを追加
4. 全テストが通り、コードがフォーマット済みであることを確認(`vendor/bin/pint`)
5. 本プラグインの `main` ブランチに対して PR を開く
6. 関連する Issue 番号を参照
7. メンテナがレビューしフィードバックを提供します

## 不具合報告

- **DixlaseCookie に関する不具合・機能要望**: 本プラグインリポジトリで Issue を作成
- **複数プラグインまたは Core にまたがる問題**: [Dixlase Core リポジトリ](https://github.com/Dixlase/dixlase-core/issues) で Issue を作成

## 行動規範

DixlaseCookie および Dixlase プロジェクト全体へのコントリビューションは、Core リポジトリで公開されている場合 [Dixlase Code of Conduct](https://github.com/Dixlase/dixlase-core/blob/main/CODE_OF_CONDUCT.md) に従います。

---

**お問い合わせ:** info@dixlase.org
