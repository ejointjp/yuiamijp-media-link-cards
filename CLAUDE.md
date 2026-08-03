# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

@~/.claude/standards/wordpress.md を読むこと

## 概要

WordPressのブロックプラグイン「SU Blocks - Applink」。ブロックエディターからiTunes Search APIを検索し、App Store / Apple Books / Apple Musicなどのアイテムをカード形式で埋め込む。wordpress.org配布（slug: `su-applink`）。

## コマンド

```bash
pnpm install     # 依存導入（pnpm固定）
pnpm start       # src/ をwatchしてbuild/ へ出力
pnpm build       # 本番ビルド。src/ を変更したら必ず実行する
pnpm lint:all    # JS + CSS（lint:js と lint:css）
pnpm format      # 整形。package.jsonのスクリプト経由で対象を明示（引数なしで直接実行しない）
phpcs            # PHP。グローバル1本運用。.phpcs.xml.dist を自動で読む
phpcbf           # PHPの自動修正
```

テストは存在しない。

`build/` は`.gitignore`済みだが`register_block_type( __DIR__ . '/build' )`が参照するため、動作確認の前に必ず`pnpm build`を通す。

## アーキテクチャ

### 動的ブロック（SSR）

`src/save.js`は`null`を返す。投稿本文にHTMLは保存せず、`build/render.php`（実体は`src/render.php`）が毎回フロントを描画する。ブロックの状態はすべて`app`属性（オブジェクト）と`entity`属性（文字列）に入る。

カード本体のマークアップはフロント（`src/render.php`）とエディタープレビュー（`src/components/Applink.js`）に分かれている。PHPとJSXでは共有しようがないので構造だけは二重管理で、**クラス名やDOM構造を変えるときは両方を直す**。

一方、**アイコンとストア名は`assets/icons.json`が単一の情報源**。`inc/icons.php`と`src/components/StoreIcon.js`が同じJSONを読むので、アイコンを足す・差し替えるときはJSONだけを変更すればよい。

```
icons    アイコン名 → { attr, content }  attrはsvg要素の属性、contentはその中身
preview  試聴ボタンに使うアイコン名
stores   type → { label, icon }
```

CSS（`src/style.css`）は共通で、`.sual-btn svg`のような子孫セレクタでアイコンに色を当てている。**svgの外側にラッパー要素を足すとフロントとエディターで見た目がズレる**ため、`StoreIcon.js`はsvg要素自体をReactで作り、`content`だけを`dangerouslySetInnerHTML`で流し込んでいる。

### データの流れ

1. `src/edit.js`が`sualAjaxValues.restUrl`（= `su-applink/v1/search`）へfetch
2. `inc/api.php`が`https://itunes.apple.com/search`へ中継。パラメータのmd5をキーに12時間トランジェントでキャッシュし、`cached`フラグを付けて返す。権限は`edit_posts`
3. 検索結果の1件を選ぶと`src/app-attributes.js`が`{ id, type, title, url, artist, iconUrl, previewUrl }`へ正規化し、`app`属性に保存
4. `src/render.php`が`$attributes['app']`を読んで出力

APIリクエストの`entity`（検索条件。`src/entity-options.js`）とレスポンスの`kind` / `wrapperType`（`edit.js`の`itemAtts()`が判定）は別物。そこから決まる`type`（`app` / `mac-app` / `ebook` / `podcast` / `music-track`…）が、`render.php`・`StoreIcon.js`・CSSクラス`sual-{type}`の分岐キーになる。種別を増やすときはこの3箇所と`app-attributes.js`が対象。

### PHP → JSの受け渡し

`sual_admin_enqueue_scripts()`が`wp-block-editor`ハンドルへ`sualAjaxValues`をインラインスクリプトとして出力する。設定値・選択肢・REST URL・nonceはすべてこれ経由。`src/edit.js`はモジュール読み込み時にこのグローバルを分解代入するため、キーを増減したらedit.js側も合わせる。

### 設定

オプション名`sual-setting`（キー: `token` / `limit` / `country` / `lang`）。`inc/admin-page.php`がSettings APIで登録・サニタイズし、`uninstall.php`が削除する。有効化時のデフォルトには`limit`が入らないので、設定を一度も保存していない環境では`options.limit`がundefinedになる（`edit.js`と`inc/admin-page.php`が10でフォールバックする）。国から言語への対応表は`SUAL_COUNTRY_TO_LANG_MAP`（`inc/define.php`）で、選択肢の配列も同ファイルに集約されている。

## 命名規約

- PHP関数: `sual_` / 定数: `SUAL_` / オプション: `sual-setting`
- CSSクラス: フロント`sual-`、エディター専用`sual-editor-`
- テキストドメイン: `su-applink`。i18n対応済みなので表示文字列は必ず`__()`系に通す

## 翻訳

日本語のみ。**実際に読み込まれるのは次の3つ**。

- `languages/su-applink.pot` — 原本
- `languages/su-applink-ja.po` / `.mo` — PHP側
- `languages/su-applink-ja-dfbff627e6c248bcb3b61d7d06da9ca9.json` — JS側。ハッシュは`md5('build/index.js')`なので、**バンドルの出力パスを変えるとこのファイルは読まれなくなる**

`languages/su-applink-ja.json`とリポジトリ直下の`su-applink-ja.json`はどこからも読み込まれていない残骸。更新しない。

更新手順はwordpress.mdの通り（`wp i18n make-pot` → `msgmerge` → 訳出 → `msgfmt` / `wp i18n make-json`）。JSONを作り直したら`build/index.js`のmd5名になっているか確認する。

**`make-pot`で`build/`を除外してはいけない。** 除外すると.potの参照が`src/`側だけになり、`make-json`が`md5('src/edit.js')`のような名前でJSONを作る。実際にenqueueされるのは`build/index.js`なので、そのJSONは永久に読み込まれない。除外するのは`node_modules`だけにする。

```bash
wp i18n make-pot . languages/su-applink.pot --slug=su-applink --exclude=node_modules
wp i18n make-json languages --no-purge   # --no-purge が無いと.poからJS側の文字列が消える
```

`make-json`は`src/`配下の参照からもJSONを作るが、読み込まれるのは`build/index.js`の分だけなので残りは削除する。

## readme

- `readme.txt` — wordpress.org用の正。バージョン・Changelog・Tested up toはここを更新する
- `readme-ja.txt` — 旧名「Applink Block for WP」時代の日本語版。現在は追従していない

## 配布

**`.distignore`が配布物の中身を決める正。** `.gitattributes`は置いていない（10upのactionは`.distignore`があればそちらだけを見る）。`assets/`は`inc/icons.php`が`assets/icons.json`を実行時に読むため**除外してはいけない**。

`v*`のタグをpushすると`.github/workflows/deploy.yml`がwordpress.orgのSVNへ反映する。手順は`/wp-plugin-publish`。

**`wp-scripts plugin-zip`は使わない。** `.distignore`を一切読まず、`admin/** build/** includes/** languages/** public/**`という固定のglobで拾う。このプラグインは`inc/`と`assets/`を使っているのでどちらも欠落し、読み込むと`require_once`で致命的エラーになるZIPができる。ZIPが要るとき（初回審査への提出など）はCIと同じrsyncで作る。

```bash
rsync -rn --exclude-from=.distignore --delete-excluded ./ /tmp/distcheck/ --out-format='%n' | grep -v '/$' | sort   # 中身の確認
```
