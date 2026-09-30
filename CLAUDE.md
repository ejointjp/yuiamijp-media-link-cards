# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

@~/.claude/standards/wordpress.md を読むこと

## 概要

WordPressのブロックプラグイン「yuiami.jp Media Link Cards」。ブロックエディターからiTunes Search APIを検索し、App Store / Apple Books / Apple Musicなどのアイテムをカード形式で埋め込む。wordpress.org配布（slug: `yuiamijp-media-link-cards`）。

名前はwordpress.orgの審査で2回指摘を受けて変わっている。

1. 「SU Applink」（slug: `su-applink`）— 「Applink」が他者のプロジェクト名と重なると指摘され、2026年8月10日に改名
2. 「SU Blocks - Media Link Cards」（slug: `su-blocks-media-link-cards`）— 2026年8月11日、こちらも識別性が足りないと指摘された。「SU」は短い頭字語にすぎず「Blocks」は一般的な記述だという理由で、既存プラグイン「SU Blocks - Blogcard」で同じパターンを使っている点は理由として認められなかった

承認時の表示名は「yuiamijp Media Link Cards」。2026年10月1日、yuiami.jp Hidden Blocksと揃えるため表示名だけ「yuiami.jp Media Link Cards」に変えた。slugと識別子はドットを使えないので`yuiamijp`のまま。

`Applink`と`SU Blocks`はコードにもドキュメントにも戻さない。readme.txtに書いていた「SU Blocksは作者のシリーズ名である」という主張も、名指しで否決されたため削除済み。

## コマンド

```bash
pnpm install     # 依存導入（pnpm固定）
pnpm start       # src/ をwatchしてbuild/ へ出力
pnpm build       # 本番ビルド。src/ を変更したら必ず実行する
pnpm lint:all    # JS + CSS（lint:js と lint:css）
pnpm format      # 整形。JSはprettier、CSSはstylelintの--fix
phpcs            # PHP。グローバル1本運用。.phpcs.xml.dist を自動で読む
phpcbf           # PHPの自動修正
```

テストは存在しない。

`build/` は`.gitignore`済みだが`register_block_type( __DIR__ . '/build' )`が参照するため、動作確認の前に必ず`pnpm build`を通す。

**`pnpm format`でCSSにprettierをかけてはいけない。** prettierとstylelintは整形が食い違うため、交互に走らせても収束しない。食い違うのは80桁を超えるセレクタの折り返し方（prettierのインデントが`@stylistic/indentation`に違反する）と、ブロック先頭の空行（prettierは削除し、stylelintは`rule-empty-line-before`で要求する）。CSSの整形はstylelintの`--fix`に一本化してある。`format`スクリプトの対象を広げるときはこの点に注意する。

**`wp-scripts format`をファイル引数なしで直接実行しない。**`--check`は未対応で、続く引数を食って引数ゼロになり、`.`全体（`.github/*.yml`や`pnpm-lock.yaml`も）を`--write`で4スペースに整形してしまう。使うのは引数を明示した`pnpm format`だけ。整形後は`git status`で意図しないファイルが変わっていないことを確認する。

## アーキテクチャ

### 動的ブロック（SSR）

`src/save.js`は`null`を返す。投稿本文にHTMLは保存せず、`build/render.php`（実体は`src/render.php`）が毎回フロントを描画する。ブロックの状態はすべて`app`属性（オブジェクト）と`entity`属性（文字列）に入る。

カード本体のマークアップはフロント（`src/render.php`）とエディタープレビュー（`src/components/MediaLinkCard.js`）に分かれている。PHPとJSXでは共有しようがないので構造だけは二重管理で、**クラス名やDOM構造を変えるときは両方を直す**。

一方、**アイコンとストア名は`assets/icons.json`が単一の情報源**。`inc/icons.php`と`src/components/StoreIcon.js`が同じJSONを読むので、アイコンを足す・差し替えるときはJSONだけを変更すればよい。

```
icons    アイコン名 → { attr, content }  attrはsvg要素の属性、contentはその中身
preview  試聴ボタンに使うアイコン名
stores   type → { label, icon }
```

PHP側の`yuiamijp_get_icon_svg()`が返すsvgは、出力時に`wp_kses()`と`yuiamijp_get_svg_allowed_html()`（`inc/icons.php`）を必ず通す。審査でエスケープ漏れとして指摘された箇所なので、`echo`のまま戻してはいけない。**`assets/icons.json`に新しい要素や属性を足したら、許可リストにも足す。** 許可リストにない要素・属性はwp_ksesが黙って落とす。

wp_ksesは属性名を小文字化するため`viewBox`は`viewbox`として出力されるが、HTMLパーサーがSVG用の綴りへ戻すので表示には影響しない。

CSS（`src/style.css`）は共通で、`.yuiamijp-btn svg`のような子孫セレクタでアイコンに色を当てている。**svgの外側にラッパー要素を足すとフロントとエディターで見た目がズレる**ため、`StoreIcon.js`はsvg要素自体をReactで作り、`content`だけを`dangerouslySetInnerHTML`で流し込んでいる。

### データの流れ

1. `src/edit.js`が`yuiamijpAjaxValues.restUrl`（= `yuiamijp-media-link-cards/v1/search`）へfetch
2. `inc/api.php`が`https://itunes.apple.com/search`へ中継。パラメータのmd5をキーに12時間トランジェントでキャッシュし、`cached`フラグを付けて返す。権限は`edit_posts`
3. 検索結果の1件を選ぶと`src/app-attributes.js`が`{ id, type, title, url, artist, iconUrl, previewUrl }`へ正規化し、`app`属性に保存
4. `src/render.php`が`$attributes['app']`を読んで出力

APIリクエストの`entity`（検索条件。`src/entity-options.js`）とレスポンスの`kind` / `wrapperType`（`edit.js`の`itemAtts()`が判定）は別物。そこから決まる`type`（`app` / `mac-app` / `ebook` / `podcast` / `music-track`…）が、`render.php`・`StoreIcon.js`・CSSクラス`yuiamijp-{type}`の分岐キーになる。種別を増やすときはこの3箇所と`app-attributes.js`が対象。

### 配信終了の判定

設定ページの「Link check」から管理者が手動で実行する一括スキャンで、カードのアイテムがストアから消えていないかを確かめる。定期実行はない。フロントから外部へ接続することもない。

- `inc/scan.php` — RESTの`/scan`のコールバックと権限チェック（`manage_options`）を実装する（ルート登録は`inc/api.php`、`phase` / `offset`を進めながら繰り返し呼ぶのは`assets/admin.js`）。`collect`は`yuiamijp_scan_post_types()`が絞る投稿タイプ（`get_post_types( array( 'exclude_from_search' => false ) )`に`wp_block`を加えたもの）と`get_post_stati( array( 'internal' => false ) )`のステータスを`parse_blocks()`で走査してカードの`app.id`を集める。`exclude_from_search`が真の投稿タイプ（`wp_template` / `wp_template_part`など）は対象外で、投稿ではなくオプション`widget_block`に保存されるブロックウィジェットも対象外だ。`check`は集めたIDを`https://itunes.apple.com/lookup`へ100件ずつ問い合わせ、返らなかったIDを配信終了と判定する
- `inc/status.php` — 結果の保存先。`yuiamijp-status`（配信終了IDだけ。フロントが読む）と`yuiamijp-scan`（全記録。管理画面が読む）。どちらもautoloadしない。`uninstall.php`が消す
- `src/render.php`は`yuiamijp_is_unavailable()`で、`src/edit.js`は`yuiamijpAjaxValues.unavailableIds`で判定し、後者は結果を`unavailable`プロップとして`src/components/MediaLinkCard.js`へ渡す。判定がtrueなら`<a>`を`<span>`にして「配信終了」ラベルを出す
- `assets/admin.js` — 設定ページの進捗表示。ビルドを通さない素のJS。`package.json`の`lint:js`と`format`の対象に入れてある。表示文字列はPHPで翻訳して`wp_localize_script()`で渡す（`.json`翻訳を同梱しないため。JS側で`__()`を使わない）

通信失敗・HTTP 200以外・JSONの破損があったバッチは判定を保留し、前回の判定を引き継ぐ（前回もなければ`unknown`）。「返らなかったIDは配信終了」はレスポンスが正常なときだけ。lookupの結果は検索APIの`limit`に切られない（生存ID 168件で実測済み）。`limit`は送らない。

投稿本文は書き換えない。判定を消したければ再スキャンするか、optionを削除する。

### PHP → JSの受け渡し

`yuiamijp_admin_enqueue_scripts()`が`wp-block-editor`ハンドルへ`yuiamijpAjaxValues`をインラインスクリプトとして出力する。設定値・選択肢・REST URL・nonceはすべてこれ経由。`src/edit.js`はモジュール読み込み時にこのグローバルを分解代入するため、キーを増減したらedit.js側も合わせる。

### 設定

オプション名`yuiamijp-setting`（キー: `token` / `limit` / `country` / `lang`）。`inc/admin-page.php`がSettings APIで登録・サニタイズし、`uninstall.php`が削除する。有効化時のデフォルトには`limit`が入らないので、設定を一度も保存していない環境では`options.limit`がundefinedになる（`edit.js`と`inc/admin-page.php`が10でフォールバックする）。国から言語への対応表は`YUIAMIJP_COUNTRY_TO_LANG_MAP`（`inc/define.php`）で、選択肢の配列も同ファイルに集約されている。

有効化時に`yuiamijp_migrate_legacy_options()`（メインファイル）が旧オプション`sual-setting`の内容を引き継ぐ。**移行元をsual世代だけに限っているのは、それより前の世代（`alfwp-setting` / `litoal-setting` / `wpalb-setting`）は既定値にアフィリエイトトークン`11l64V`が入っていたため。** 旧公開版wp-applinkのユーザーから引き継ぐと、設定した覚えのないトークンが復活する。sual世代は未公開なので作者の環境にしか存在しない。

slugが変わるとディレクトリ名も変わり、WordPressからは別プラグインとして扱われる。旧プラグインを削除すると`uninstall.php`が旧設定を消すので、**設定を引き継ぐには先に新しい側を有効化する**。

## 命名規約

- PHP関数: `yuiamijp_` / 定数: `YUIAMIJP_` / オプション: `yuiamijp-setting` `yuiamijp-status` `yuiamijp-scan` / トランジェント: `yuiamijp_search_*` `yuiamijp_scan_progress`
- CSSクラス: フロント`yuiamijp-`（ベースクラスは`.yuiamijp`）、エディター専用`yuiamijp-editor-`
- テキストドメイン: `yuiamijp-media-link-cards`。i18n対応済みなので表示文字列は必ず`__()`系に通す
- ブロック名: `yuiamijp/media-link-cards`（フロントのラッパークラスは`wp-block-yuiamijp-media-link-cards`）
- ブロックカテゴリー: slugは`yuiamijp`、タイトルは`yuiami.jp`
- JSへ渡すグローバル: ブロックエディター向けの`yuiamijpAjaxValues`、設定ページ向けの`yuiamijpScan`
- 接頭辞はすべて`yuiamijp`で統一した。旧名由来の`sual_`は残していない

## 翻訳

日本語のみ。**翻訳ファイルは配布物に同梱しない。** wordpress.orgでホストされるプラグインの翻訳はtranslate.wordpress.orgが言語パックとして配信するため、`.po` / `.mo` / `.json` を同梱すると審査で指摘される。`.distignore`で除外済み。

リポジトリに置くのは次の2つだけ。

- `languages/yuiamijp-media-link-cards.pot` — 原本。配布物にも含める
- `languages/yuiamijp-media-link-cards-ja.po` — 日本語訳。translate.wordpress.orgへImportするための原資であって、実行時には読まれない

`.mo`と`.json`は作らない。`wp_set_script_translations()`にもパスを渡していないので、WordPressは`WP_LANG_DIR/plugins`の言語パックだけを見る。

公開後の流れ。

1. translate.wordpress.orgのプロジェクトページから`languages/yuiamijp-media-link-cards-ja.po`をImportする
2. 承認には日本語ロケールのPTE権限が要る。作者でも自動では付かないので申請する
3. 初回の言語パックはStableの90%以上が承認された時点で生成される。以降は閾値に関係なく更新される

表示文字列を追加・変更したら`.pot`と`.po`を更新する。**`make-pot`で`build/`を除外してはいけない**（.potの参照が`src/`側だけになる）。除外するのは`node_modules`だけ。

```bash
wp i18n make-pot . languages/yuiamijp-media-link-cards.pot --slug=yuiamijp-media-link-cards --exclude=node_modules
msgmerge --update --backup=none languages/yuiamijp-media-link-cards-ja.po languages/yuiamijp-media-link-cards.pot
msgattrib --no-obsolete --output-file=languages/yuiamijp-media-link-cards-ja.po languages/yuiamijp-media-link-cards-ja.po
msgfmt --statistics -o /dev/null languages/yuiamijp-media-link-cards-ja.po   # 未訳とfuzzyが0であることを確認
```

改名前のエントリが`#~`のobsoleteとして残ると、translate.wordpress.orgへのImportに不要なエントリが混ざる。`msgattrib`で落とす。ただし、一時的にコードから消しただけの文字列の既訳も同時に落ちるため、戻すときは訳し直しになる。

## readme

- `readme.txt` — wordpress.org用の正。バージョン・Changelog・Tested up toはここを更新する
- `readme-ja.txt` — 旧名時代の日本語版。追従しておらず配布物にも入らない残骸

## 配布

**`.distignore`が配布物の中身を決める正。** `.gitattributes`は置いていない（10upのactionは`.distignore`があればそちらだけを見る）。`assets/`は`inc/icons.php`が`assets/icons.json`を実行時に読むため**除外してはいけない**。

`v*`のタグをpushすると`.github/workflows/deploy.yml`がwordpress.orgのSVNへ反映する。手順は`/wp-plugin-publish`。

workflowは`SLUG: yuiamijp-media-link-cards`を明示している。リポジトリ名に依存させていないので、リポジトリ名を変えてもデプロイ先はズレない。

**`wp-scripts plugin-zip`は使わない。** `.distignore`を一切読まず、`admin/** build/** includes/** languages/** public/**`という固定のglobで拾う。このプラグインは`inc/`と`assets/`を使っているのでどちらも欠落し、読み込むと`require_once`で致命的エラーになるZIPができる。ZIPが要るとき（初回審査への提出など）はCIと同じrsyncで作る。

```bash
rsync -rn --exclude-from=.distignore --delete-excluded ./ /tmp/distcheck/ --out-format='%n' | grep -v '/$' | sort   # 中身の確認
```
